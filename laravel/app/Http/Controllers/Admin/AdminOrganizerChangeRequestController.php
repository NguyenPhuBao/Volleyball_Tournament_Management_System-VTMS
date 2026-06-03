<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminOrganizerChangeRequestService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AdminOrganizerChangeRequestController extends Controller
{
    public function __construct(private readonly AdminOrganizerChangeRequestService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('admin.organizer-change-requests', [
            'pageTitle' => 'VTMS - Xac nhan thay doi thong tin BTC',
            'moduleTitle' => 'Xac nhan thong tin ban to chuc',
            'styles' => ['css/quantri-xacnhanthongtin.css'],
            'scripts' => ['js/quantri-xacnhanthongtin.js'],
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list([
            'q' => $request->query('q', ''),
            'trangthai' => $request->query('trangthai', $request->query('status', '')),
            'truongcapnhat' => $request->query('truongcapnhat', $request->query('field', '')),
            'idnguoidung' => $request->query('idnguoidung', $request->query('user_id', '')),
            'from' => $request->query('from', $request->query('from_date', '')),
            'to' => $request->query('to', $request->query('to_date', '')),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', $request->query('limit', null)),
        ]);

        return response()->json([
            'success' => true,
            'data' => $result['requests'],
            'meta' => [
                'filters' => $result['filters'],
                'statuses' => $result['statuses'],
                'fields' => $result['fields'],
                'status_counts' => $result['status_counts'],
                'pagination' => $result['pagination'],
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $requestId = $this->routePositiveInt($request, 'id');

        if ($requestId === null) {
            return $this->notFound();
        }

        $changeRequest = $this->service->find($requestId);

        if ($changeRequest === null) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $changeRequest,
        ]);
    }

    public function approve(Request $request): JsonResponse
    {
        $requestId = $this->routePositiveInt($request, 'id');

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->approve($requestId, $request->all(), LegacySessionUser::id(), $request));
    }

    public function reject(Request $request): JsonResponse
    {
        $requestId = $this->routePositiveInt($request, 'id');

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->reject($requestId, $request->all(), LegacySessionUser::id(), $request));
    }

    private function respond(array $result): JsonResponse
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (isset($result['request'])) {
            $payload['data'] = $result['request'];
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
            'message' => 'Khong tim thay yeu cau thay doi thong tin ban to chuc.',
        ], 404);
    }
}
