<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerPersonalInfoChangeRequestService;

final class OrganizerPersonalInfoChangeRequestController extends Controller
{
    private OrganizerPersonalInfoChangeRequestService $service;

    public function __construct()
    {
        $this->service = new OrganizerPersonalInfoChangeRequestService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.approvals', [
            'pageTitle' => 'VTMS - Xac nhan thong tin ca nhan',
            'styles' => ['css/organizer-profile-approvals.css'],
            'scripts' => ['js/organizer-profile-approvals.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'trangthai' => $request->query('trangthai', $request->query('status', '')),
                'role' => $request->query('role', $request->query('vai_tro', '')),
                'banglienquan' => $request->query('banglienquan', $request->query('target_table', '')),
                'truongcapnhat' => $request->query('truongcapnhat', $request->query('field', '')),
                'idnguoidung' => $request->query('idnguoidung', $request->query('user_id', '')),
                'from' => $request->query('from', $request->query('from_date', '')),
                'to' => $request->query('to', $request->query('to_date', '')),
                'page' => $request->query('page', 1),
                'per_page' => $request->query('per_page', $request->query('limit', null)),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $requestId = $this->routeRequestId($request);

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($requestId, $this->accountId())
        );
    }

    public function approve(Request $request): Response
    {
        $requestId = $this->routeRequestId($request);

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->approve($requestId, $request->all(), $this->accountId(), $request)
        );
    }

    public function reject(Request $request): Response
    {
        $requestId = $this->routeRequestId($request);

        if ($requestId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->reject($requestId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeRequestId(Request $request): ?int
    {
        $raw = (string) $request->route('requestId', $request->route('id', ''));

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

        if (array_key_exists('requests', $result)) {
            $payload['data'] = $result['requests'];
        }

        if (array_key_exists('request', $result)) {
            $payload['data'] = $result['request'];
        }

        if (array_key_exists('meta', $result)) {
            $payload['meta'] = $result['meta'];
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
            'message' => 'Khong tim thay yeu cau xac nhan thong tin ca nhan.',
        ], 404);
    }
}

