<?php

declare(strict_types=1);

namespace App\Backend\Core\Route;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Database;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Core\View;
use Throwable;

final class Router
{
    private array $routes = [];
    private array $middlewareAliases = [];

    public function get(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array|string $handler, array $middleware = []): void
    {
        [$pattern, $params] = $this->compilePath($path);

        $this->routes[strtoupper($method)][] = [
            'path' => $path,
            'pattern' => $pattern,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function aliasMiddleware(string $alias, string $class): void
    {
        $this->middlewareAliases[$alias] = $class;
    }

    public function dispatch(?Request $request = null): void
    {
        $request ??= Request::capture();
        $route = null;
        $actorBefore = Auth::user();

        try {
            $route = $this->match($request);

            if ($route === null) {
                $this->recordAudit($request, null, 404, $actorBefore, 'Khong tim thay route.');
                View::render('errors.404', ['path' => $request->path()], 'main', 404)->send();
                return;
            }

            $request = $request->withRouteParams($route['routeParams']);
            $response = $this->runPipeline($request, $route);
            $this->recordAudit($request, $route, $this->responseStatus($response), $actorBefore);
            $this->send($response);
        } catch (Throwable $exception) {
            $this->recordAudit($request, $route, 500, $actorBefore, $exception->getMessage());

            if ((bool) config('app.debug', false)) {
                throw $exception;
            }

            View::render('errors.500', [], 'main', 500)->send();
        }
    }

    private function match(Request $request): ?array
    {
        foreach ($this->routes[$request->method()] ?? [] as $route) {
            if (!preg_match($route['pattern'], $request->path(), $matches)) {
                continue;
            }

            $params = [];

            foreach ($route['params'] as $name) {
                $params[$name] = $matches[$name] ?? null;
            }

            $route['routeParams'] = $params;

            return $route;
        }

        return null;
    }

    private function runPipeline(Request $request, array $route): mixed
    {
        $next = fn (Request $request): mixed => $this->runHandler($route['handler'], $request);

        foreach (array_reverse($route['middleware']) as $middleware) {
            $next = function (Request $request) use ($middleware, $next): mixed {
                [$class, $params] = $this->resolveMiddleware($middleware);
                $instance = new $class();

                return $instance->handle($request, $next, ...$params);
            };
        }

        return $next($request);
    }

    private function runHandler(callable|array|string $handler, Request $request): mixed
    {
        if (is_array($handler) && is_string($handler[0])) {
            $handler[0] = new $handler[0]();
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $handler = [new $class(), $method];
        }

        return $handler($request);
    }

    private function send(mixed $response): void
    {
        if ($response instanceof Response) {
            $response->send();
            return;
        }

        echo (string) $response;
    }

    private function resolveMiddleware(string $middleware): array
    {
        [$name, $params] = array_pad(explode(':', $middleware, 2), 2, '');
        $class = $this->middlewareAliases[$name] ?? $name;
        $params = $params === '' ? [] : array_map('trim', explode(',', $params));

        return [$class, $params];
    }

    private function compilePath(string $path): array
    {
        $params = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)}/', static function (array $matches) use (&$params): string {
            $params[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, '/' . trim($path, '/'));

        if ($pattern === '') {
            $pattern = '/';
        }

        return ['#^' . $pattern . '$#', $params];
    }

    private function responseStatus(mixed $response): int
    {
        return $response instanceof Response ? $response->status() : 200;
    }

    private function recordAudit(Request $request, ?array $route, int $status, ?array $actorBefore, ?string $error = null): void
    {
        try {
            $actor = Auth::user() ?? $actorBefore;
            $accountId = isset($actor['id']) ? (int) $actor['id'] : null;

            [$action, $targetTable] = $this->auditActionAndTarget($request, $route);
            $targetId = $this->auditTargetId($route);
            $note = $this->auditNote($request, $route, $status, $actor, $error);

            $statement = Database::connection()->prepare(
                "INSERT INTO Nhatkyhethong (idtaikhoan, hanhdong, bangtacdong, iddoituong, ipaddress, ghichu)
                 VALUES (:account_id, :action, :target_table, :target_id, :ip_address, :note)"
            );
            $statement->execute([
                'account_id' => $accountId,
                'action' => $action,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'ip_address' => $request->ip(),
                'note' => $note,
            ]);
        } catch (Throwable) {
            // Audit logging must not break the user-facing request.
        }
    }

    private function auditActionAndTarget(Request $request, ?array $route): array
    {
        $method = $request->method();
        $path = $request->path();
        $routePath = (string) ($route['path'] ?? $path);
        $lowerPath = strtolower($path);
        $lowerRoute = strtolower($routePath);
        $hasRouteId = $this->auditTargetId($route) !== null;

        $action = match (true) {
            str_contains($lowerPath, 'logout') => 'Đăng xuất',
            str_contains($lowerPath, 'login') && $method === 'POST' => 'Đăng nhập',
            str_contains($lowerPath, 'login') => 'Mở trang đăng nhập',
            str_contains($lowerPath, 'password') || str_contains($lowerPath, 'doi-mat-khau') => $method === 'GET' ? 'Mở trang đổi mật khẩu' : 'Đổi mật khẩu',
            str_contains($lowerPath, 'register') || str_contains($lowerPath, 'dang-ky') => $method === 'GET' ? 'Mở trang đăng ký' : 'Đăng ký tài khoản',
            $method === 'GET' && str_starts_with($lowerPath, '/api/') && $_GET !== [] => 'Tìm kiếm / lọc dữ liệu',
            $method === 'GET' && str_starts_with($lowerPath, '/api/') && $hasRouteId => 'Xem chi tiết dữ liệu',
            $method === 'GET' && str_starts_with($lowerPath, '/api/') => 'Xem danh sách dữ liệu',
            $method === 'GET' => 'Xem trang',
            $method === 'DELETE' || str_contains($lowerRoute, 'delete') || str_contains($lowerRoute, 'remove') => 'Xóa dữ liệu',
            in_array($method, ['PUT', 'PATCH'], true) || str_contains($lowerRoute, 'update') => 'Sửa dữ liệu',
            str_contains($lowerRoute, 'approve') || str_contains($lowerRoute, 'publish') || str_contains($lowerRoute, 'confirm') => 'Duyệt / xác nhận dữ liệu',
            str_contains($lowerRoute, 'reject') || str_contains($lowerRoute, 'cancel') || str_contains($lowerRoute, 'deactivate') => 'Từ chối / hủy dữ liệu',
            $method === 'POST' => 'Tạo mới / gửi thao tác',
            default => 'Thao tác hệ thống',
        };

        return [$action, $this->auditTargetTable($lowerPath)];
    }

    private function auditTargetTable(string $lowerPath): string
    {
        return match (true) {
            str_contains($lowerPath, 'log') || str_contains($lowerPath, 'nhat-ky') => 'Nhatkyhethong',
            str_contains($lowerPath, 'login') || str_contains($lowerPath, 'logout') || str_contains($lowerPath, 'password') || str_contains($lowerPath, 'tai-khoan') || str_contains($lowerPath, 'account') => 'Taikhoan',
            str_contains($lowerPath, 'profile') || str_contains($lowerPath, 'nguoi-dung') || str_contains($lowerPath, 'users') => 'Nguoidung',
            str_contains($lowerPath, 'tournament') || str_contains($lowerPath, 'giai-dau') => 'Giaidau',
            str_contains($lowerPath, 'coach-account') => 'Taikhoan',
            str_contains($lowerPath, 'coach') || str_contains($lowerPath, 'huan-luyen-vien') || str_contains($lowerPath, 'huanluyenvien') => 'Huanluyenvien',
            str_contains($lowerPath, 'referee-account') => 'Taikhoan',
            str_contains($lowerPath, 'referee') || str_contains($lowerPath, 'trong-tai') || str_contains($lowerPath, 'trongtai') => 'Trongtai',
            str_contains($lowerPath, 'athlete') || str_contains($lowerPath, 'player') || str_contains($lowerPath, 'van-dong-vien') || str_contains($lowerPath, 'vandongvien') => 'Vandongvien',
            str_contains($lowerPath, 'team') || str_contains($lowerPath, 'doi-bong') => 'Doibong',
            str_contains($lowerPath, 'venue') || str_contains($lowerPath, 'san-dau') => 'Sandau',
            str_contains($lowerPath, 'schedule') || str_contains($lowerPath, 'lich') || str_contains($lowerPath, 'match') || str_contains($lowerPath, 'tran-dau') => 'Lichthidau',
            str_contains($lowerPath, 'result') || str_contains($lowerPath, 'ket-qua') => 'Ketquatrandau',
            str_contains($lowerPath, 'complaint') || str_contains($lowerPath, 'khieu-nai') => 'Khieunai',
            str_contains($lowerPath, 'ranking') || str_contains($lowerPath, 'standing') || str_contains($lowerPath, 'xep-hang') => 'Bangxephang',
            str_contains($lowerPath, 'eligibility') || str_contains($lowerPath, 'tu-cach') => 'Tucachthamgia',
            str_contains($lowerPath, 'dashboard') => 'Dashboard',
            default => 'Route',
        };
    }

    private function auditTargetId(?array $route): ?int
    {
        foreach (($route['routeParams'] ?? []) as $value) {
            if ($value !== null && ctype_digit((string) $value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function auditNote(Request $request, ?array $route, int $status, ?array $actor, ?string $error): string
    {
        $routePath = (string) ($route['path'] ?? 'NO_ROUTE');
        $actorText = isset($actor['id'])
            ? sprintf('Tai khoan #%d %s', (int) $actor['id'], (string) ($actor['role'] ?? ''))
            : 'Khach chua dang nhap';
        $query = $_GET === [] ? '' : (' Query: ' . $this->safeJson($_GET));
        $routeParams = empty($route['routeParams'] ?? []) ? '' : (' Params: ' . $this->safeJson($route['routeParams']));
        $errorText = $error === null ? '' : (' Loi: ' . $error);

        return $this->limitNote(sprintf(
            '%s thuc hien %s %s (route %s), HTTP %d.%s%s%s',
            $actorText,
            $request->method(),
            $request->path(),
            $routePath,
            $status,
            $query,
            $routeParams,
            $errorText
        ));
    }

    private function safeJson(array $value): string
    {
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/password|matkhau|token|secret|csrf/i', $key)) {
                $value[$key] = '[REDACTED]';
            } elseif (is_array($item)) {
                $value[$key] = $this->safeJsonArray($item);
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function safeJsonArray(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/password|matkhau|token|secret|csrf/i', $key)) {
                $value[$key] = '[REDACTED]';
            } elseif (is_array($item)) {
                $value[$key] = $this->safeJsonArray($item);
            }
        }

        return $value;
    }

    private function limitNote(string $note): string
    {
        return strlen($note) <= 1000 ? $note : substr($note, 0, 997) . '...';
    }
}
