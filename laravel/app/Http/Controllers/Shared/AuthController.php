<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\Shared\AuthService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function showLogin(): Response|RedirectResponse
    {
        if (LegacySessionUser::check()) {
            return redirect('/dashboard');
        }

        return response()->view('public.login', [
            'error' => session('login_error'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $result = $this->auth->attempt(
            trim((string) ($request->input('username') ?? $request->input('identifier', ''))),
            (string) $request->input('password', ''),
            $request
        );

        if ($result['ok']) {
            $request->session()->forget('login_error');

            return redirect('/dashboard');
        }

        return redirect('/login')->with('login_error', $result['message']);
    }

    public function logout(): RedirectResponse
    {
        $this->auth->logout();

        return redirect('/');
    }

    public function apiLogin(Request $request): JsonResponse
    {
        $result = $this->auth->attempt(
            trim((string) ($request->input('username') ?? $request->input('identifier', ''))),
            (string) $request->input('password', ''),
            $request
        );

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
            'user' => $result['user'],
        ], (int) $result['status']);
    }

    public function apiLogout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json([
            'success' => true,
            'message' => 'Dang xuat thanh cong.',
        ]);
    }

    public function apiMe(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => LegacySessionUser::user(),
        ]);
    }
}
