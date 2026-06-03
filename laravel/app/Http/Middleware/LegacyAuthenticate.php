<?php

namespace App\Http\Middleware;

use App\Support\LegacySessionUser;
use Closure;
use Illuminate\Http\Request;

final class LegacyAuthenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (LegacySessionUser::check()) {
            return $next($request);
        }

        if ($request->expectsJson() || str_starts_with('/'.ltrim($request->path(), '/'), '/api/')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui long dang nhap.',
            ], 401);
        }

        return redirect('/login');
    }
}
