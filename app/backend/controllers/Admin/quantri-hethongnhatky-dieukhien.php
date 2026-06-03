<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Admin;

use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Admin\AdminSystemLogService;

final class AdminSystemLogController extends Controller
{
    private AdminSystemLogService $service;

    public function __construct()
    {
        $this->service = new AdminSystemLogService();
    }

    public function page(Request $request): Response
    {
        return $this->view('admin.system-logs', [
            'pageTitle' => 'VTMS - Nhat ky he thong',
            'styles' => ['css/admin-logs.css'],
            'scripts' => ['js/admin-logs.js'],
        ]);
    }

    public function index(Request $request): Response
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

        return Response::json([
            'success' => true,
            'data' => $result['logs'],
            'meta' => [
                'filters' => $result['filters'],
                'pagination' => $result['pagination'],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $logId = $this->routeLogId($request);

        if ($logId === null) {
            return $this->notFound();
        }

        $log = $this->service->find($logId);

        if ($log === null) {
            return $this->notFound();
        }

        return Response::json([
            'success' => true,
            'data' => $log,
        ]);
    }

    public function options(Request $request): Response
    {
        return Response::json([
            'success' => true,
            'data' => $this->service->options(),
        ]);
    }

    private function routeLogId(Request $request): ?int
    {
        $raw = (string) $request->route('id', '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $logId = (int) $raw;

        return $logId > 0 ? $logId : null;
    }

    private function notFound(): Response
    {
        return Response::json([
            'success' => false,
            'message' => 'Khong tim thay nhat ky he thong.',
        ], 404);
    }
}

