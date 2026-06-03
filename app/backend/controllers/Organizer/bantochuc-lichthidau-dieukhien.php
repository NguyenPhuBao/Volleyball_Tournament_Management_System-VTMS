<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerScheduleService;

final class OrganizerScheduleController extends Controller
{
    private OrganizerScheduleService $service;

    public function __construct()
    {
        $this->service = new OrganizerScheduleService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.schedule', [
            'pageTitle' => 'VTMS - Quan ly lich thi dau',
            'styles' => ['css/organizer-schedule.css'],
            'scripts' => ['js/organizer-schedule.js'],
        ]);
    }

    public function tournaments(Request $request): Response
    {
        return $this->respond(
            $this->service->tournaments($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
            ])
        );
    }

    public function summary(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->summary($tournamentId, $this->accountId())
        );
    }

    public function groups(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->groups($tournamentId, $this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'round_id' => $request->query('round_id', $request->query('idvongdau', null)),
            ])
        );
    }

    public function group(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $groupId = $this->routePositiveInt($request, 'groupId');

        if ($tournamentId === null || $groupId === null) {
            return $this->notFound('Khong tim thay bang dau.');
        }

        return $this->respond(
            $this->service->group($tournamentId, $groupId, $this->accountId())
        );
    }

    public function storeGroup(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->createGroup($tournamentId, $request->all(), $this->accountId(), $request)
        );
    }

    public function updateGroup(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $groupId = $this->routePositiveInt($request, 'groupId');

        if ($tournamentId === null || $groupId === null) {
            return $this->notFound('Khong tim thay bang dau.');
        }

        return $this->respond(
            $this->service->updateGroup($tournamentId, $groupId, $request->all(), $this->accountId(), $request)
        );
    }

    public function deleteGroup(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $groupId = $this->routePositiveInt($request, 'groupId');

        if ($tournamentId === null || $groupId === null) {
            return $this->notFound('Khong tim thay bang dau.');
        }

        return $this->respond(
            $this->service->deleteGroup($tournamentId, $groupId, $this->accountId(), $request)
        );
    }

    public function matches(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->matches($tournamentId, $this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'group_id' => $request->query('group_id', $request->query('idbangdau', null)),
                'round_id' => $request->query('round_id', $request->query('idvongdau', null)),
            ])
        );
    }

    public function match(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($tournamentId === null || $matchId === null) {
            return $this->notFound('Khong tim thay tran dau.');
        }

        return $this->respond(
            $this->service->match($tournamentId, $matchId, $this->accountId())
        );
    }

    public function storeMatch(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->createMatch($tournamentId, $request->all(), $this->accountId(), $request)
        );
    }

    public function generateStandardSchedule(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->generateStandardPreliminarySchedule($tournamentId, $request->all(), $this->accountId(), $request)
        );
    }

    public function updateMatch(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($tournamentId === null || $matchId === null) {
            return $this->notFound('Khong tim thay tran dau.');
        }

        return $this->respond(
            $this->service->updateMatch($tournamentId, $matchId, $request->all(), $this->accountId(), $request)
        );
    }

    public function deleteMatch(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($tournamentId === null || $matchId === null) {
            return $this->notFound('Khong tim thay tran dau.');
        }

        return $this->respond(
            $this->service->deleteMatch($tournamentId, $matchId, $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routePositiveInt(Request $request, string $key): ?int
    {
        $raw = (string) $request->route($key, '');

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

        foreach (['tournaments', 'schedule', 'groups', 'group', 'matches', 'match'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
            }
        }

        if (array_key_exists('meta', $result)) {
            $payload['meta'] = $result['meta'];
        }

        if (array_key_exists('deleted_id', $result)) {
            $payload['deleted_id'] = $result['deleted_id'];
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

