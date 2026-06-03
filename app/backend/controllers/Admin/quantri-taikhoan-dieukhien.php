<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Admin;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Admin\AdminAccountService;

final class AdminAccountController extends Controller
{
    private AdminAccountService $service;

    public function __construct()
    {
        $this->service = new AdminAccountService();
    }

    public function page(Request $request): Response
    {
        return $this->view('admin.users', [
            'pageTitle' => 'VTMS - Quan ly tai khoan',
            'styles' => ['css/admin-users.css'],
            'scripts' => ['js/admin-users.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        $result = $this->service->list([
            'q' => $request->query('q', ''),
            'role' => $request->query('role', ''),
            'trangthai' => $request->query('trangthai', $request->query('status', '')),
        ]);

        return Response::json([
            'success' => true,
            'data' => $result['accounts'],
            'meta' => [
                'filters' => $result['filters'],
                'statuses' => $result['statuses'],
                'total' => count($result['accounts']),
            ],
        ]);
    }

    public function roles(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'data' => $this->service->roles(),
        ]);
    }

    public function show(Request $request): Response
    {
        $accountId = $this->routeAccountId($request);

        if ($accountId === null) {
            return $this->notFound();
        }

        $account = $this->service->find($accountId);

        if ($account === null) {
            return $this->notFound();
        }

        return Response::json([
            'success' => true,
            'data' => $account,
        ]);
    }

    public function store(Request $request): Response
    {
        return $this->respond(
            $this->service->create($request->all(), $this->adminId(), $request)
        );
    }

    public function update(Request $request): Response
    {
        $accountId = $this->routeAccountId($request);

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->update($accountId, $request->all(), $this->adminId(), $request)
        );
    }

    public function destroy(Request $request): Response
    {
        $accountId = $this->routeAccountId($request);

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->delete($accountId, $this->adminId(), $request)
        );
    }

    private function adminId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeAccountId(Request $request): ?int
    {
        $raw = (string) $request->route('id', '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $accountId = (int) $raw;

        return $accountId > 0 ? $accountId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (isset($result['account'])) {
            $payload['data'] = $result['account'];
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
            'message' => 'Khong tim thay tai khoan.',
        ], 404);
    }
}

