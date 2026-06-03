<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerScheduleViewService;

final class OrganizerScheduleViewController extends Controller
{
    private OrganizerScheduleViewService $service;

    public function __construct()
    {
        $this->service = new OrganizerScheduleViewService();
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->all($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'result_status' => $request->query('result_status', $request->query('ketqua_trangthai', '')),
            'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', null)),
            'group_id' => $request->query('group_id', $request->query('idbangdau', null)),
            'team_id' => $request->query('team_id', $request->query('iddoibong', null)),
            'venue_id' => $request->query('venue_id', $request->query('idsandau', null)),
            'from' => $request->query('from', $request->query('from_date', '')),
            'to' => $request->query('to', $request->query('to_date', '')),
        ], $request));
    }

    public function show(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound('Khong tim thay tran dau.');
        }

        return $this->respond($this->service->show($matchId, $this->accountId(), $request));
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

        foreach (['matches', 'match'] as $key) {
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

