<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Admin;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Admin\AdminUserService;

final class AdminUserController extends Controller
{
    private AdminUserService $service;

    public function __construct()
    {
        $this->service = new AdminUserService();
    }

    public function page(Request $request): Response
    {
        return $this->view('admin.user-profiles', [
            'pageTitle' => 'VTMS - Quan ly nguoi dung',
            'styles' => ['css/admin-qluser.css'],
            'scripts' => ['js/admin-qluser.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        $result = $this->service->list([
            'q' => $request->query('q', ''),
            'role' => $request->query('role', ''),
            'gioitinh' => $request->query('gioitinh', $request->query('gender', '')),
            'trangthai_taikhoan' => $request->query('trangthai_taikhoan', $request->query('status', '')),
        ]);

        return Response::json([
            'success' => true,
            'data' => $result['users'],
            'meta' => [
                'filters' => $result['filters'],
                'genders' => $result['genders'],
                'account_statuses' => $result['account_statuses'],
                'total' => count($result['users']),
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $userId = $this->routeUserId($request);

        if ($userId === null) {
            return $this->notFound();
        }

        $user = $this->service->find($userId);

        if ($user === null) {
            return $this->notFound();
        }

        return Response::json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request): Response
    {
        $userId = $this->routeUserId($request);

        if ($userId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->update($userId, $request->all(), $this->adminId(), $request)
        );
    }

    private function adminId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeUserId(Request $request): ?int
    {
        $raw = (string) $request->route('id', '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $userId = (int) $raw;

        return $userId > 0 ? $userId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (isset($result['user'])) {
            $payload['data'] = $result['user'];
        }

        if (!empty($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        return Response::json($payload, (int) $result['status']);
    }

    private function notFound(): Response
    {
        return Response::json([
            'success' => false,
            'message' => 'Khong tim thay nguoi dung.',
        ], 404);
    }
}

