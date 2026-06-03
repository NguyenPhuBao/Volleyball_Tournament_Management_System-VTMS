<?php

declare(strict_types=1);

namespace App\Backend\Core\Middleware;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;

final class AuthMiddleware
{
    public function handle(Request $request, callable $next): mixed
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

        return $next($request);
    }
}
