<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminUserService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('admin.user-profiles', [
            'pageTitle' => 'VTMS - Quan ly nguoi dung',
            'moduleTitle' => 'Ho so nguoi dung',
            'styles' => ['css/quantri-quanlynguoidung.css'],
            'scripts' => ['js/quantri-quanlynguoidung.js'],
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list([
            'q' => $request->query('q', ''),
            'role' => $request->query('role', ''),
            'gioitinh' => $request->query('gioitinh', $request->query('gender', '')),
            'trangthai_taikhoan' => $request->query('trangthai_taikhoan', $request->query('status', '')),
        ]);

        return response()->json([
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

    public function show(Request $request): JsonResponse
    {
        $userId = $this->routePositiveInt($request, 'id');

        if ($userId === null) {
            return $this->notFound();
        }

        $user = $this->service->find($userId);

        if ($user === null) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $userId = $this->routePositiveInt($request, 'id');

        if ($userId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->update($userId, $request->all(), LegacySessionUser::id(), $request));
    }

    private function respond(array $result): JsonResponse
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
            'message' => 'Khong tim thay nguoi dung.',
        ], 404);
    }
}
