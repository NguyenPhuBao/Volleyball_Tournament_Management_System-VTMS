<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Athlete;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Athlete\AthleteLineupViewService;

final class AthleteLineupViewController extends Controller
{
    private AthleteLineupViewService $service;

    public function __construct()
    {
        $this->service = new AthleteLineupViewService();
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->all($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
        ], $request));
    }

    public function show(Request $request): Response
    {
        $id = $this->routePositiveInt($request, 'lineupId');

        if ($id === null) {
            return $this->notFound('Khong tim thay doi hinh.');
        }

        return $this->respond($this->service->show($id, $this->accountId(), $request));
    }

    public function current(Request $request): Response
    {
        $accountId = $this->accountId();
        $result = $this->service->all($accountId, [
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
        ], $request);

        if (($result['ok'] ?? false) !== true) {
            return $this->respond($result);
        }

        $lineups = $result['lineups'] ?? [];
        $lineup = null;

        foreach ($lineups as $item) {
            if ((int) ($item['current_athlete_in_lineup'] ?? 0) > 0) {
                $lineup = $item;
                break;
            }
        }

        $lineup ??= $lineups[0] ?? null;

        if ($lineup === null) {
            return $this->respond([
                'ok' => true,
                'status' => 200,
                'message' => 'Chua co doi hinh.',
                'lineup' => null,
                'details' => [],
                'meta' => $result['meta'] ?? [],
            ]);
        }

        return $this->respond($this->service->show((int) $lineup['iddoihinh'], $accountId, $request));
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
        $payload = ['success' => $result['ok'], 'message' => $result['message']];

        foreach (['lineups', 'lineup'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
                break;
            }
        }

        if (array_key_exists('details', $result)) {
            $payload['details'] = $result['details'];
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
        return Response::json(['success' => false, 'message' => $message], 404);
    }
}

