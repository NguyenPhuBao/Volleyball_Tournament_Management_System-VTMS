<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Athlete;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Athlete\AthleteTeamInfoService;

final class AthleteTeamInfoController extends Controller
{
    private AthleteTeamInfoService $service;

    public function __construct()
    {
        $this->service = new AthleteTeamInfoService();
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->all($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_status' => $request->query('team_status', $request->query('doibong_trangthai', '')),
        ], $request));
    }

    public function show(Request $request): Response
    {
        $id = $this->routePositiveInt($request, 'teamId');

        if ($id === null) {
            return $this->notFound('Khong tim thay doi bong.');
        }

        return $this->respond($this->service->show($id, $this->accountId(), $request));
    }

    public function current(Request $request): Response
    {
        $accountId = $this->accountId();
        $result = $this->service->all($accountId, [
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_status' => $request->query('team_status', $request->query('doibong_trangthai', '')),
        ], $request);

        if (($result['ok'] ?? false) !== true) {
            return $this->respond($result);
        }

        $teams = $result['teams'] ?? [];
        $team = null;

        foreach ($teams as $item) {
            if ((string) ($item['trangthaithanhvien'] ?? '') === 'DANG_THAM_GIA') {
                $team = $item;
                break;
            }
        }

        $team ??= $teams[0] ?? null;

        if ($team === null) {
            return $this->respond([
                'ok' => true,
                'status' => 200,
                'message' => 'VDV chua co doi bong.',
                'team' => null,
                'members' => [],
                'tournaments' => [],
                'meta' => $result['meta'] ?? [],
            ]);
        }

        return $this->respond($this->service->show((int) $team['iddoibong'], $accountId, $request));
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

        foreach (['teams', 'team'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
                break;
            }
        }

        foreach (['members', 'tournaments'] as $key) {
            if (array_key_exists($key, $result)) {
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
        return Response::json(['success' => false, 'message' => $message], 404);
    }
}

