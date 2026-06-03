<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Coach;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Coach\CoachTeamManagementService;

final class CoachTeamManagementController extends Controller
{
    private CoachTeamManagementService $service;

    public function __construct()
    {
        $this->service = new CoachTeamManagementService();
    }

    public function teams(Request $request): Response
    {
        return $this->respond($this->service->teams($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
        ]));
    }

    public function team(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->team($teamId, $this->accountId()));
    }

    public function storeTeam(Request $request): Response
    {
        return $this->respond($this->service->createTeam($request->all(), $this->accountId(), $request));
    }

    public function updateTeam(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->updateTeam($teamId, $request->all(), $this->accountId(), $request));
    }

    public function members(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->members($teamId, $this->accountId()));
    }

    public function addMember(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->addMember($teamId, $request->all(), $this->accountId(), $request));
    }

    public function removeMember(Request $request): Response
    {
        $memberId = $this->routePositiveInt($request, 'memberId');

        if ($memberId === null) {
            return $this->notFound('Khong tim thay thanh vien.');
        }

        return $this->respond($this->service->removeMember($memberId, $request->all(), $this->accountId(), $request));
    }

    public function transferMember(Request $request): Response
    {
        $memberId = $this->routePositiveInt($request, 'memberId');

        if ($memberId === null) {
            return $this->notFound('Khong tim thay thanh vien.');
        }

        return $this->respond($this->service->transferMember($memberId, $request->all(), $this->accountId(), $request));
    }

    public function lineups(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->lineups($teamId, $this->accountId(), [
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
        ]));
    }

    public function lineupList(Request $request): Response
    {
        return $this->respond($this->service->lineupList($this->accountId(), [
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
        ]));
    }

    public function lineup(Request $request): Response
    {
        $lineupId = $this->routePositiveInt($request, 'lineupId');

        if ($lineupId === null) {
            return $this->notFound('Khong tim thay doi hinh.');
        }

        return $this->respond($this->service->lineup($lineupId, $this->accountId()));
    }

    public function storeLineup(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->createLineup($teamId, $request->all(), $this->accountId(), $request));
    }

    public function updateLineup(Request $request): Response
    {
        $lineupId = $this->routePositiveInt($request, 'lineupId');

        if ($lineupId === null) {
            return $this->notFound('Khong tim thay doi hinh.');
        }

        return $this->respond($this->service->updateLineup($lineupId, $request->all(), $this->accountId(), $request));
    }

    public function schedule(Request $request): Response
    {
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($teamId === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->schedule($teamId, $this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
            'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
            'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
        ], $request));
    }

    public function results(Request $request): Response
    {
        return $this->respond($this->service->results($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
            'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
            'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
        ], $request));
    }

    public function complainResult(Request $request): Response
    {
        $resultId = $this->routePositiveInt($request, 'resultId');

        if ($resultId === null) {
            return $this->notFound('Khong tim thay ket qua thi dau.');
        }

        return $this->respond($this->service->complainResult($resultId, $request->all(), $this->accountId(), $request));
    }

    public function athleteChangeRequests(Request $request): Response
    {
        return $this->respond($this->service->athleteChangeRequests($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'target_table' => $request->query('target_table', $request->query('banglienquan', '')),
            'field' => $request->query('field', $request->query('truongcapnhat', '')),
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'athlete_id' => $request->query('athlete_id', $request->query('idvandongvien', '')),
            'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
            'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
            'limit' => $request->query('limit', '50'),
            'page' => $request->query('page', '1'),
            'offset' => $request->query('offset', ''),
        ]));
    }

    public function athleteChangeRequest(Request $request): Response
    {
        $requestId = $this->routePositiveInt($request, 'requestId');

        if ($requestId === null) {
            return $this->notFound('Khong tim thay yeu cau.');
        }

        return $this->respond($this->service->athleteChangeRequest($requestId, $this->accountId()));
    }

    public function approveAthleteChangeRequest(Request $request): Response
    {
        $requestId = $this->routePositiveInt($request, 'requestId');

        if ($requestId === null) {
            return $this->notFound('Khong tim thay yeu cau.');
        }

        return $this->respond($this->service->approveAthleteChangeRequest($requestId, $request->all(), $this->accountId(), $request));
    }

    public function rejectAthleteChangeRequest(Request $request): Response
    {
        $requestId = $this->routePositiveInt($request, 'requestId');

        if ($requestId === null) {
            return $this->notFound('Khong tim thay yeu cau.');
        }

        return $this->respond($this->service->rejectAthleteChangeRequest($requestId, $request->all(), $this->accountId(), $request));
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

        $dataKey = null;

        foreach (['teams', 'members', 'member', 'lineups', 'lineup', 'matches', 'results', 'complaint', 'requests', 'request', 'team'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
                $dataKey = $key;
                break;
            }
        }

        foreach (['details', 'created', 'team'] as $key) {
            if (array_key_exists($key, $result) && $dataKey !== $key && !array_key_exists($key, $payload)) {
                $payload[$key] = $result[$key];
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

