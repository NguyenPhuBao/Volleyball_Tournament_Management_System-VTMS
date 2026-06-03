<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminSystemLogService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AdminSystemLogController extends Controller
{
    public function __construct(private readonly AdminSystemLogService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('admin.system-logs', [
            'pageTitle' => 'VTMS - Nhat ky he thong',
            'moduleTitle' => 'Nhat ky he thong',
            'styles' => ['css/quantri-nhatkyhethong.css'],
            'scripts' => ['js/quantri-nhatkyhethong.js'],
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->list([
            'q' => $request->query('q', ''),
            'idtaikhoan' => $request->query('idtaikhoan', $request->query('actor_id', $request->query('account_id', ''))),
            'bangtacdong' => $request->query('bangtacdong', $request->query('target_table', '')),
            'hanhdong' => $request->query('hanhdong', $request->query('action', '')),
            'from' => $request->query('from', $request->query('from_date', '')),
            'to' => $request->query('to', $request->query('to_date', '')),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', $request->query('limit', null)),
        ]);

        return response()->json([
            'success' => true,
            'data' => $result['logs'],
            'meta' => [
                'filters' => $result['filters'],
                'pagination' => $result['pagination'],
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $logId = $this->routePositiveInt($request, 'id');

        if ($logId === null) {
            return $this->notFound();
        }

        $log = $this->service->find($logId);

        if ($log === null) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->options(),
        ]);
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
            'message' => 'Khong tim thay nhat ky he thong.',
        ], 404);
    }
}
