<?php

namespace App\Http\Middleware;

use App\Support\LegacySessionUser;
use Closure;
use Illuminate\Http\Request;

final class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $roles): mixed
    {
        $requiredRoles = array_map('trim', explode(',', $roles));

        if (!LegacySessionUser::check()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Vui long dang nhap.'], 401)
                : redirect('/login');
        }

        if (!LegacySessionUser::hasRole($requiredRoles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tai khoan khong co quyen thuc hien thao tac nay.',
                    'required_roles' => $requiredRoles,
                    'current_role' => LegacySessionUser::role(),
                ], 403);
            }

            return response()->view('errors.403', [
                'role' => LegacySessionUser::role(),
                'requiredRoles' => $requiredRoles,
            ], 403);
        }

        return $next($request);
    }
}
