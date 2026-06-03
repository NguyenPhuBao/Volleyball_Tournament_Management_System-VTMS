<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerMatchResultService;

final class OrganizerMatchResultController extends Controller
{
    private OrganizerMatchResultService $service;

    public function __construct()
    {
        $this->service = new OrganizerMatchResultService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.results', [
            'pageTitle' => 'VTMS - Quan ly ket qua tran dau',
            'styles' => ['css/organizer-results.css'],
            'scripts' => ['js/organizer-results.js'],
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
            ])
        );
    }

    public function show(Request $request): Response
    {
        $resultId = $this->routeResultId($request);

        if ($resultId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($resultId, $this->accountId())
        );
    }

    public function adjust(Request $request): Response
    {
        $resultId = $this->routeResultId($request);

        if ($resultId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->adjust($resultId, $request->all(), $this->accountId(), $request)
        );
    }

    public function publish(Request $request): Response
    {
        $resultId = $this->routeResultId($request);

        if ($resultId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->publish($resultId, $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeResultId(Request $request): ?int
    {
        $raw = (string) $request->route('resultId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $resultId = (int) $raw;

        return $resultId > 0 ? $resultId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('results', $result)) {
            $payload['data'] = $result['results'];
        }

        if (array_key_exists('result', $result)) {
            $payload['data'] = $result['result'];
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
            'message' => 'Khong tim thay ket qua tran dau.',
        ], 404);
    }
}

