<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerAthleteQualificationService;

final class OrganizerAthleteQualificationController extends Controller
{
    private OrganizerAthleteQualificationService $service;

    public function __construct()
    {
        $this->service = new OrganizerAthleteQualificationService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.athletes', [
            'pageTitle' => 'VTMS - Quan ly tu cach thi dau van dong vien',
            'styles' => ['css/organizer-athletes.css'],
            'scripts' => ['js/organizer-athletes.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthaidaugiai', '')),
                'account_status' => $request->query('account_status', $request->query('trangthai_taikhoan', '')),
                'request_status' => $request->query('request_status', $request->query('trangthai_yeucau', '')),
                'request_presence' => $request->query('request_presence', $request->query('request_filter', '')),
                'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', '')),
                'from' => $request->query('from', $request->query('fromDate', $request->query('tungay', ''))),
                'to' => $request->query('to', $request->query('toDate', $request->query('denngay', ''))),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $athleteId = $this->routeAthleteId($request);

        if ($athleteId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($athleteId, $this->accountId())
        );
    }

    public function approve(Request $request): Response
    {
        $athleteId = $this->routeAthleteId($request);

        if ($athleteId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->approve($athleteId, $this->accountId(), $request)
        );
    }

    public function cancel(Request $request): Response
    {
        $athleteId = $this->routeAthleteId($request);

        if ($athleteId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->cancel($athleteId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeAthleteId(Request $request): ?int
    {
        $raw = (string) $request->route('athleteId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $athleteId = (int) $raw;

        return $athleteId > 0 ? $athleteId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('athletes', $result)) {
            $payload['data'] = $result['athletes'];
        }

        if (array_key_exists('athlete', $result)) {
            $payload['data'] = $result['athlete'];
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
            'message' => 'Khong tim thay van dong vien.',
        ], 404);
    }
}

