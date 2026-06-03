<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Shared;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Shared\AccountSecurityService;

final class AccountSecurityController extends Controller
{
    private AccountSecurityService $service;

    public function __construct()
    {
        $this->service = new AccountSecurityService();
    }

    public function page(Request $request): Response
    {
        return $this->view('account.change-password', [
            'pageTitle' => 'VTMS - Đổi mật khẩu',
            'moduleTitle' => 'Đổi mật khẩu',
            'moduleDescription' => 'Cập nhật mật khẩu đăng nhập cho tài khoản hiện tại.',
            'styles' => ['css/account-password.css'],
            'scripts' => ['js/account-password.js'],
        ]);
    }

    public function changePassword(Request $request): Response
    {
        $accountId = (int) (Auth::user()['id'] ?? 0);
        $result = $this->service->changePassword($accountId, $request->all(), $request);

        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (!empty($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        if (isset($result['account'])) {
            $payload['data'] = $result['account'];
        }

        return Response::json($payload, (int) $result['status']);
    }
}
