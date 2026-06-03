<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Admin;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Admin\AdminOrganizerChangeRequestService;

final class AdminOrganizerChangeRequestController extends Controller
{
    private AdminOrganizerChangeRequestService $service;

    public function __construct()
    {
        $this->service = new AdminOrganizerChangeRequestService();
    }

    public function page(Request $request): Response
    {
        return $this->view('admin.confirm-info', [
            'pageTitle' => 'VTMS - Xac nhan thay doi thong tin BTC',
            'styles' => ['css/admin-confirm-info.css'],
            'scripts' => ['js/admin-confirm-info.js'],
        ]);
    }

    public function index(Request $request): Response
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

        return Response::json([
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

    public function show(Request $request): Response
    {
        $requestId = $this->routeRequestId($request);

        if ($requestId === null) {
            return $this->notFound();
        }

        $changeRequest = $this->service->find($requestId);

        if ($changeRequest === null) {
            return $this->notFound();
        }

        return Response::json([
            'success' => true,
            'data' => $changeRequest,
        ]);
    }

    public function approve(Request $request): Response
    {
        $requestId = $this->routeRequestId($request);

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->approve($requestId, $request->all(), $this->adminId(), $request)
        );
    }

    public function reject(Request $request): Response
    {
        $requestId = $this->routeRequestId($request);

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->reject($requestId, $request->all(), $this->adminId(), $request)
        );
    }

    private function adminId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeRequestId(Request $request): ?int
    {
        $raw = (string) $request->route('id', '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $requestId = (int) $raw;

        return $requestId > 0 ? $requestId : null;
    }

    private function respond(array $result): Response
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

        return Response::json($payload, (int) $result['status']);
    }

    private function notFound(): Response
    {
        return Response::json([
            'success' => false,
            'message' => 'Khong tim thay yeu cau thay doi thong tin ban to chuc.',
        ], 404);
    }
}

