<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Referee;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Referee\RefereeIncidentReportService;

final class RefereeIncidentReportController extends Controller
{
    private RefereeIncidentReportService $service;

    public function __construct()
    {
        $this->service = new RefereeIncidentReportService();
    }

    public function page(Request $request): Response
    {
        return $this->view('trongtai.incidents', [
            'pageTitle' => 'VTMS - Bao cao su co',
            'styles' => ['css/referee-incidents.css'],
            'scripts' => ['js/referee-incidents.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', null)),
                'match_id' => $request->query('match_id', $request->query('idtrandau', null)),
                'from' => $request->query('from', $request->query('from_date', '')),
                'to' => $request->query('to', $request->query('to_date', '')),
            ], $request)
        );
    }

    public function matches(Request $request): Response
    {
        return $this->respond(
            $this->service->reportableMatches($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', null)),
                'match_status' => $request->query('match_status', $request->query('trangthai_trandau', '')),
            ], $request)
        );
    }

    public function show(Request $request): Response
    {
        $reportId = $this->routePositiveInt($request, 'reportId');

        if ($reportId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->show($reportId, $this->accountId(), $request)
        );
    }

    public function store(Request $request): Response
    {
        $routeMatchId = $this->routePositiveInt($request, 'matchId');

        return $this->respond(
            $this->service->create($request->all(), $this->accountId(), $request, $routeMatchId)
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

        if (array_key_exists('reports', $result)) {
            $payload['data'] = $result['reports'];
        }

        if (array_key_exists('report', $result)) {
            $payload['data'] = $result['report'];
        }

        if (array_key_exists('matches', $result)) {
            $payload['data'] = $result['matches'];
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
            'message' => 'Khong tim thay bao cao su co.',
        ], 404);
    }
}

