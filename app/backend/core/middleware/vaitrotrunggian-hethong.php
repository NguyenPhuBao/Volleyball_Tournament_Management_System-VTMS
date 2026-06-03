<?php

declare(strict_types=1);

namespace App\Backend\Core\Middleware;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Core\View;

final class RoleMiddleware
{
    public function handle(Request $request, callable $next, string ...$roles): mixed
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return Response::json([
                    'success' => false,
                    'message' => 'Vui long dang nhap.',
                ], 401);
            }

            return Response::redirect('/login');
        }

        if (!Auth::hasRole($roles)) {
            if ($request->expectsJson()) {
                return Response::json([
                    'success' => false,
                    'message' => 'Tai khoan khong co quyen thuc hien thao tac nay.',
                    'required_roles' => $roles,
                    'current_role' => Auth::role(),
                ], 403);
            }

            return View::render('errors.403', [
                'role' => Auth::role(),
                'requiredRoles' => $roles,
            ], 'main', 403);
        }

        return $next($request);
    }
}
