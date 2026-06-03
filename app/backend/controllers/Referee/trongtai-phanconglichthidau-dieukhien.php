<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Referee;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Referee\RefereeAssignmentScheduleService;

final class RefereeAssignmentScheduleController extends Controller
{
    private RefereeAssignmentScheduleService $service;

    public function __construct()
    {
        $this->service = new RefereeAssignmentScheduleService();
    }

    public function page(Request $request): Response
    {
        return $this->view('trongtai.my-assignments', [
            'pageTitle' => 'VTMS - Lich phan cong trong tai',
            'styles' => ['css/referee-my-assignments.css'],
            'scripts' => ['js/referee-my-assignments.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'assignment_status' => $request->query('assignment_status', $request->query('trangthai_phancong', $request->query('status', ''))),
                'match_status' => $request->query('match_status', $request->query('trangthai_trandau', '')),
                'role' => $request->query('role', $request->query('vaitro', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', null)),
                'venue_id' => $request->query('venue_id', $request->query('idsandau', null)),
                'from' => $request->query('from', $request->query('from_date', '')),
                'to' => $request->query('to', $request->query('to_date', '')),
            ], $request)
        );
    }

    public function tournaments(Request $request): Response
    {
        return $this->respond(
            $this->service->tournamentsOfMe($this->accountId(), $request)
        );
    }

    public function venues(Request $request): Response
    {
        return $this->respond(
            $this->service->venuesOfMe($this->accountId(), $request)
        );
    }

    public function show(Request $request): Response
    {
        $assignmentId = $this->routePositiveInt($request, 'assignmentId');

        if ($assignmentId === null) {
            return $this->notFound('Khong tim thay phan cong tran dau.');
        }

        return $this->respond(
            $this->service->findByAssignment($assignmentId, $this->accountId(), $request)
        );
    }

    public function showMatch(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound('Khong tim thay tran dau duoc phan cong.');
        }

        return $this->respond(
            $this->service->findByMatch($matchId, $this->accountId(), $request)
        );
    }

    public function matchDetail(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound('Khong tim thay tran dau duoc phan cong.');
        }

        return $this->respond(
            $this->service->matchDetail($matchId, $this->accountId(), $request)
        );
    }

    public function confirm(Request $request): Response
    {
        $assignmentId = $this->routePositiveInt($request, 'assignmentId');

        if ($assignmentId === null) {
            return $this->notFound('Khong tim thay phan cong tran dau.');
        }

        return $this->respond(
            $this->service->confirm($assignmentId, $this->accountId(), $request)
        );
    }

    public function decline(Request $request): Response
    {
        $assignmentId = $this->routePositiveInt($request, 'assignmentId');

        if ($assignmentId === null) {
            return $this->notFound('Khong tim thay phan cong tran dau.');
        }

        return $this->respond(
            $this->service->decline($assignmentId, $this->accountId(), $request)
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

        if (array_key_exists('assignments', $result)) {
            $payload['data'] = $result['assignments'];
        }

        if (array_key_exists('tournaments', $result)) {
            $payload['data'] = $result['tournaments'];
        }

        if (array_key_exists('venues', $result)) {
            $payload['data'] = $result['venues'];
        }

        if (array_key_exists('assignment', $result)) {
            $payload['data'] = $result['assignment'];
        }

        if (array_key_exists('match', $result)) {
            $payload['data'] = $result['match'];
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

