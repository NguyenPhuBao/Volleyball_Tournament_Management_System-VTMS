<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Coach;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Coach\CoachAthleteLeaveService;

final class CoachAthleteLeaveController extends Controller
{
    private CoachAthleteLeaveService $service;

    public function __construct()
    {
        $this->service = new CoachAthleteLeaveService();
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->all($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'athlete_id' => $request->query('athlete_id', $request->query('idvandongvien', '')),
            'match_id' => $request->query('match_id', $request->query('idtrandau', '')),
            'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
            'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
        ], $request));
    }

    public function show(Request $request): Response
    {
        $leaveId = $this->routePositiveInt($request, 'leaveId');

        if ($leaveId === null) {
            return $this->notFound('Khong tim thay don xin nghi phep VDV.');
        }

        return $this->respond($this->service->show($leaveId, $this->accountId(), $request));
    }

    public function approve(Request $request): Response
    {
        $leaveId = $this->routePositiveInt($request, 'leaveId');

        if ($leaveId === null) {
            return $this->notFound('Khong tim thay don xin nghi phep VDV.');
        }

        return $this->respond($this->service->approve($leaveId, $request->all(), $this->accountId(), $request));
    }

    public function reject(Request $request): Response
    {
        $leaveId = $this->routePositiveInt($request, 'leaveId');

        if ($leaveId === null) {
            return $this->notFound('Khong tim thay don xin nghi phep VDV.');
        }

        return $this->respond($this->service->reject($leaveId, $request->all(), $this->accountId(), $request));
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

        foreach (['leave_requests', 'leave_request'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
                break;
            }
        }

        if (array_key_exists('meta', $result)) {
            $payload['meta'] = $result['meta'];
        }

        if (!empty($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        return Response::json($payload, (int) $result['status']);
    }

    private function notFound(string $message): Response
    {
        return Response::json([
            'success' => false,
            'message' => $message,
        ], 404);
    }
}
