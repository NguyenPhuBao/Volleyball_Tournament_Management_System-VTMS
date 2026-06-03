<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Referee;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Referee\RefereeLeaveRequestService;

final class RefereeLeaveRequestController extends Controller
{
    private RefereeLeaveRequestService $service;

    public function __construct()
    {
        $this->service = new RefereeLeaveRequestService();
    }

    public function page(Request $request): Response
    {
        return $this->view('trongtai.leaves', [
            'pageTitle' => 'VTMS - Xin nghi phep',
            'styles' => ['css/referee-leaves.css'],
            'scripts' => ['js/referee-leaves.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
                'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
            ], $request)
        );
    }

    public function show(Request $request): Response
    {
        $leaveId = $this->routePositiveInt($request, 'leaveId');

        if ($leaveId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->show($leaveId, $this->accountId(), $request)
        );
    }

    public function store(Request $request): Response
    {
        return $this->respond(
            $this->service->create($request->all(), $this->accountId(), $request)
        );
    }

    public function cancel(Request $request): Response
    {
        $leaveId = $this->routePositiveInt($request, 'leaveId');

        if ($leaveId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->cancel($leaveId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routePositiveInt(Request $request, string $key): ?int
    {
        $raw = (string) $request->route($key, $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('leave_requests', $result)) {
            $payload['data'] = $result['leave_requests'];
        }

        if (array_key_exists('leave_request', $result)) {
            $payload['data'] = $result['leave_request'];
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
            'message' => 'Khong tim thay don nghi phep.',
        ], 404);
    }
}

