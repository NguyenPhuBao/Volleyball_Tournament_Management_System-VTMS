<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerTeamProfileService;

final class OrganizerTeamProfileController extends Controller
{
    private OrganizerTeamProfileService $service;

    public function __construct()
    {
        $this->service = new OrganizerTeamProfileService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.teams', [
            'pageTitle' => 'VTMS - Ho so doi bong tham gia',
            'styles' => ['css/organizer-teams.css'],
            'scripts' => ['js/organizer-teams.js'],
        ]);
    }

    public function all(Request $request): Response
    {
        return $this->respond(
            $this->service->listAll($this->accountId(), [
                'q' => $request->query('q', ''),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
                'registration_status' => $request->query('registration_status', $request->query('status', $request->query('trangthaidangky', ''))),
                'team_status' => $request->query('team_status', $request->query('trangthaidoibong', '')),
            ])
        );
    }

    public function index(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');

        if ($tournamentId === null) {
            return $this->notFound('Khong tim thay giai dau.');
        }

        return $this->respond(
            $this->service->list($tournamentId, $this->accountId(), [
                'q' => $request->query('q', ''),
                'registration_status' => $request->query('registration_status', $request->query('status', $request->query('trangthaidangky', ''))),
                'team_status' => $request->query('team_status', $request->query('trangthaidoibong', '')),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($tournamentId === null || $teamId === null) {
            return $this->notFound('Khong tim thay ho so doi bong.');
        }

        return $this->respond(
            $this->service->show($tournamentId, $teamId, $this->accountId())
        );
    }

    public function update(Request $request): Response
    {
        return Response::json([
            'success' => false,
            'message' => 'Ban to chuc chi co quyen xem ho so doi bong.',
        ], 403);
    }

    public function cancelParticipation(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id');
        $teamId = $this->routePositiveInt($request, 'teamId');

        if ($tournamentId === null || $teamId === null) {
            return $this->notFound('Khong tim thay ho so doi bong.');
        }

        return $this->respond(
            $this->service->cancelParticipation($tournamentId, $teamId, $request->all(), $this->accountId(), $request)
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

        if (array_key_exists('teams', $result)) {
            $payload['data'] = $result['teams'];
        }

        if (array_key_exists('profile', $result)) {
            $payload['data'] = $result['profile'];
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

