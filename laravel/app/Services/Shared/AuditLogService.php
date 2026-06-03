<?php

namespace App\Services\Shared;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AuditLogService
{
    public function describe(Request $request, string $routePath, array $routeParams = [], ?array $actor = null, ?string $error = null, int $status = 200): array
    {
        [$action, $targetTable] = $this->actionAndTarget($request, $routePath, $routeParams);
        $targetId = $this->targetId($routeParams);

        return [
            'account_id' => isset($actor['id']) ? (int) $actor['id'] : null,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'ip_address' => $request->ip(),
            'note' => $this->note($request, $routePath, $routeParams, $status, $actor, $error),
        ];
    }

    public function record(Request $request, string $routePath, array $routeParams = [], ?array $actor = null, ?string $error = null, int $status = 200): void
    {
        $entry = $this->describe($request, $routePath, $routeParams, $actor, $error, $status);

        DB::insert(
            "INSERT INTO Nhatkyhethong (idtaikhoan, hanhdong, bangtacdong, iddoituong, ipaddress, ghichu)
             VALUES (:account_id, :action, :target_table, :target_id, :ip_address, :note)",
            $entry
        );
    }

    private function actionAndTarget(Request $request, string $routePath, array $routeParams): array
    {
        $method = strtoupper($request->method());
        $path = '/'.trim($request->path(), '/');
        $route = strtolower($routePath);
        $lowerPath = strtolower($path);
        $hasRouteId = $this->targetId($routeParams) !== null;

        $action = match (true) {
            str_contains($lowerPath, 'logout') => 'Dang xuat',
            str_contains($lowerPath, 'login') && $method === 'POST' => 'Dang nhap',
            str_contains($lowerPath, 'login') => 'Mo trang dang nhap',
            str_contains($lowerPath, 'password') || str_contains($lowerPath, 'doi-mat-khau') => $method === 'GET' ? 'Mo trang doi mat khau' : 'Doi mat khau',
            str_contains($lowerPath, 'register') || str_contains($lowerPath, 'dang-ky') => $method === 'GET' ? 'Mo trang dang ky' : 'Dang ky tai khoan',
            $method === 'GET' && str_starts_with($lowerPath, '/api/') && $request->query() !== [] => 'Tim kiem / loc du lieu',
            $method === 'GET' && str_starts_with($lowerPath, '/api/') && $hasRouteId => 'Xem chi tiet du lieu',
            $method === 'GET' && str_starts_with($lowerPath, '/api/') => 'Xem danh sach du lieu',
            $method === 'GET' => 'Xem trang',
            $method === 'DELETE' || str_contains($route, 'delete') || str_contains($route, 'remove') => 'Xoa du lieu',
            in_array($method, ['PUT', 'PATCH'], true) || str_contains($route, 'update') => 'Sua du lieu',
            str_contains($route, 'approve') || str_contains($route, 'publish') || str_contains($route, 'confirm') => 'Duyet / xac nhan du lieu',
            str_contains($route, 'reject') || str_contains($route, 'cancel') || str_contains($route, 'deactivate') => 'Tu choi / huy du lieu',
            $method === 'POST' => 'Tao moi / gui thao tac',
            default => 'Thao tac he thong',
        };

        return [$action, $this->targetTable($lowerPath)];
    }

    private function targetTable(string $lowerPath): string
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

    private function targetId(array $routeParams): ?int
    {
        foreach ($routeParams as $value) {
            if ($value !== null && ctype_digit((string) $value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function note(Request $request, string $routePath, array $routeParams, int $status, ?array $actor, ?string $error): string
    {
        $path = '/'.trim($request->path(), '/');
        $actorText = isset($actor['id'])
            ? sprintf('Tai khoan #%d %s', (int) $actor['id'], (string) ($actor['role'] ?? ''))
            : 'Khach chua dang nhap';
        $query = $request->query() === [] ? '' : (' Query: '.$this->safeJson($request->query()));
        $body = $request->request->all() === [] ? '' : (' Body: '.$this->safeJson($request->request->all()));
        $params = $routeParams === [] ? '' : (' Params: '.$this->safeJson($routeParams));
        $errorText = $error === null ? '' : (' Loi: '.$error);

        return $this->limit(sprintf(
            '%s thuc hien %s %s (route %s), HTTP %d.%s%s%s%s',
            $actorText,
            strtoupper($request->method()),
            $path,
            $routePath,
            $status,
            $query,
            $body,
            $params,
            $errorText
        ));
    }

    private function safeJson(array $value): string
    {
        return json_encode($this->redact($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function redact(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/password|matkhau|token|secret|csrf/i', $key)) {
                $value[$key] = '[REDACTED]';
            } elseif (is_array($item)) {
                $value[$key] = $this->redact($item);
            }
        }

        return $value;
    }

    private function limit(string $note): string
    {
        return strlen($note) <= 1000 ? $note : substr($note, 0, 997).'...';
    }
}
