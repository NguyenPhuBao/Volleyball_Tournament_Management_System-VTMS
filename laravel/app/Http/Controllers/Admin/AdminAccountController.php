<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAccountService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AdminAccountController extends Controller
{
    public function __construct(private readonly AdminAccountService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('admin.accounts', [
            'pageTitle' => 'VTMS - Quan ly tai khoan',
            'moduleTitle' => 'Quan tri tai khoan',
            'styles' => ['css/quantri-taikhoan.css'],
            'scripts' => ['js/quantri-taikhoan.js'],
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list([
            'q' => $request->query('q', ''),
            'role' => $request->query('role', ''),
            'trangthai' => $request->query('trangthai', $request->query('status', '')),
        ]);

        return response()->json([
            'success' => true,
            'data' => $result['accounts'],
            'meta' => [
                'filters' => $result['filters'],
                'statuses' => $result['statuses'],
                'total' => count($result['accounts']),
            ],
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->roles(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $accountId = $this->routePositiveInt($request, 'id');

        if ($accountId === null) {
            return $this->notFound();
        }

        $account = $this->service->find($accountId);

        if ($account === null) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->respond($this->service->create($request->all(), LegacySessionUser::id(), $request));
    }

    public function update(Request $request): JsonResponse
    {
        $accountId = $this->routePositiveInt($request, 'id');

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->update($accountId, $request->all(), LegacySessionUser::id(), $request));
    }

    public function destroy(Request $request): JsonResponse
    {
        $accountId = $this->routePositiveInt($request, 'id');

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->delete($accountId, LegacySessionUser::id(), $request));
    }

    private function respond(array $result): JsonResponse
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

        return response()->json($payload, (int) $result['status']);
    }

    private function routePositiveInt(Request $request, string $key): ?int
    {
        $raw = (string) $request->route($key, '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $value = (int) $raw;

        return $value > 0 ? $value : null;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Khong tim thay tai khoan.',
        ], 404);
    }
}
