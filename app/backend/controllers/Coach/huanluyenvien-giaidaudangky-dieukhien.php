<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Coach;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Coach\CoachTournamentRegistrationService;

final class CoachTournamentRegistrationController extends Controller
{
    private CoachTournamentRegistrationService $service;

    public function __construct()
    {
        $this->service = new CoachTournamentRegistrationService();
    }

    public function tournaments(Request $request): Response
    {
        return $this->respond(
            $this->service->tournaments($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
                'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
            ])
        );
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->registrations($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
                'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
                'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
                'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $registrationId = $this->routePositiveInt($request, 'registrationId');

        if ($registrationId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($registrationId, $this->accountId())
        );
    }

    public function store(Request $request): Response
    {
        $payload = $request->all();
        $tournamentId = $this->routePositiveInt($request, 'tournamentId');

        if ($tournamentId !== null && !array_key_exists('idgiaidau', $payload) && !array_key_exists('tournament_id', $payload)) {
            $payload['idgiaidau'] = $tournamentId;
        }

        return $this->respond(
            $this->service->register($payload, $this->accountId(), $request)
        );
    }

    public function cancel(Request $request): Response
    {
        $registrationId = $this->routePositiveInt($request, 'registrationId');

        if ($registrationId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->cancel($registrationId, $request->all(), $this->accountId(), $request)
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

        foreach (['tournaments', 'registrations', 'registration'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
                break;
            }
        }

        if (array_key_exists('created', $result)) {
            $payload['created'] = $result['created'];
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
            'message' => 'Khong tim thay dang ky giai dau.',
        ], 404);
    }
}

