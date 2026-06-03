<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Shared;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Shared\AuthService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/dashboard');
        }

        return $this->view('public.login', [
            'error' => $_SESSION['login_error'] ?? null,
        ], 'auth');
    }

    public function login(Request $request): Response
    {
        if (!csrf_verify($request->input('_csrf_token'))) {
            $_SESSION['login_error'] = 'Phien lam viec khong hop le. Vui long thu lai.';
            return $this->redirect('/login');
        }

        unset($_SESSION['login_error']);

        $username = trim((string) ($request->input('username') ?? $request->input('identifier', '')));
        $password = (string) $request->input('password', '');
        $result = (new AuthService())->attempt($username, $password, $request);

        if ($result['ok']) {
            return $this->redirect('/dashboard');
        }

        $_SESSION['login_error'] = $result['message'];

        return $this->redirect('/login');
    }

    public function logout(Request $request): Response
    {
        if (csrf_verify($request->input('_csrf_token'))) {
            (new AuthService())->logout();
        }

        return $this->redirect('/');
    }

    public function apiLogin(Request $request): Response
    {
        $result = (new AuthService())->attempt(
            trim((string) ($request->input('username') ?? $request->input('identifier', ''))),
            (string) $request->input('password', ''),
            $request
        );

        return Response::json([
            'success' => $result['ok'],
            'message' => $result['message'],
            'user' => $result['user'],
        ], $result['status']);
    }

    public function apiLogout(Request $request): Response
    {
        (new AuthService())->logout();

        return Response::json([
            'success' => true,
            'message' => 'Dang xuat thanh cong.',
        ]);
    }

    public function apiMe(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'user' => Auth::user(),
        ]);
    }
}

