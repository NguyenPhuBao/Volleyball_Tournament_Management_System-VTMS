<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerRankingService;

final class OrganizerRankingController extends Controller
{
    private OrganizerRankingService $service;

    public function __construct()
    {
        $this->service = new OrganizerRankingService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.standings', [
            'pageTitle' => 'VTMS - Quan ly xep hang',
            'styles' => ['css/organizer-standings.css'],
            'scripts' => ['js/organizer-standings.js'],
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

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', null)),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $rankingId = $this->routePositiveInt($request, 'rankingId');

        if ($rankingId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($rankingId, $this->accountId())
        );
    }

    public function generate(Request $request): Response
    {
        $tournamentId = $this->routePositiveInt($request, 'id')
            ?? $this->positiveIntFromPayload($request->input('idgiaidau', $request->input('tournament_id')));

        if ($tournamentId === null) {
            return Response::json([
                'success' => false,
                'message' => 'Khong tim thay giai dau.',
            ], 404);
        }

        return $this->respond(
            $this->service->generate($tournamentId, $request->all(), $this->accountId(), $request)
        );
    }

    public function publish(Request $request): Response
    {
        $rankingId = $this->routePositiveInt($request, 'rankingId');

        if ($rankingId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->publish($rankingId, $this->accountId(), $request)
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

    private function positiveIntFromPayload(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        foreach (['tournaments', 'rankings', 'ranking'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
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

    private function notFound(): Response
    {
        return Response::json([
            'success' => false,
            'message' => 'Khong tim thay bang xep hang.',
        ], 404);
    }
}

