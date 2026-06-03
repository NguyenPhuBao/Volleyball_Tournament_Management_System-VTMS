<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerRefereeService;

final class OrganizerRefereeController extends Controller
{
    private OrganizerRefereeService $service;

    public function __construct()
    {
        $this->service = new OrganizerRefereeService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.referees', [
            'pageTitle' => 'VTMS - Quan ly trong tai',
            'styles' => ['css/organizer-referees.css'],
            'scripts' => ['js/organizer-referees.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'account_status' => $request->query('account_status', $request->query('trangthai_taikhoan', '')),
            ])
        );
    }

    public function store(Request $request): Response
    {
        return $this->respond(
            $this->service->create($request->all(), $this->accountId(), $request)
        );
    }

    public function show(Request $request): Response
    {
        $refereeId = $this->routeRefereeId($request);

        if ($refereeId === null) {
            return $this->notFound('Khong tim thay trong tai.');
        }

        return $this->respond(
            $this->service->find($refereeId, $this->accountId())
        );
    }

    public function matches(Request $request): Response
    {
        return $this->respond(
            $this->service->matches($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'tournament_id' => $request->query('tournament_id', $request->query('idgiaidau', null)),
            ])
        );
    }

    public function assign(Request $request): Response
    {
        $refereeId = $this->routeRefereeId($request);

        if ($refereeId === null) {
            $refereeId = $this->positiveInt($request->input('idtrongtai', $request->input('referee_id', null)));
        }

        if ($refereeId === null) {
            return $this->notFound('Khong tim thay trong tai.');
        }

        return $this->respond(
            $this->service->assign($refereeId, $request->all(), $this->accountId(), $request)
        );
    }

    public function leave(Request $request): Response
    {
        $refereeId = $this->routeRefereeId($request);

        if ($refereeId === null) {
            return $this->notFound('Khong tim thay trong tai.');
        }

        return $this->respond(
            $this->service->leave($refereeId, $request->all(), $this->accountId(), $request)
        );
    }

    public function leaves(Request $request): Response
    {
        return $this->respond(
            $this->service->leaves($this->accountId(), [
                'referee_id' => $request->query('referee_id', $request->query('idtrongtai', null)),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'from' => $request->query('from', $request->query('tungay', '')),
                'to' => $request->query('to', $request->query('denngay', '')),
            ])
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeRefereeId(Request $request): ?int
    {
        $raw = (string) $request->route('refereeId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $refereeId = (int) $raw;

        return $refereeId > 0 ? $refereeId : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        foreach (['referee', 'referees', 'matches', 'assignment', 'leave_request', 'leave_requests'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
            }
        }

        if (array_key_exists('leave_request', $result) && array_key_exists('referee', $result)) {
            $payload['data'] = [
                'leave_request' => $result['leave_request'],
                'referee' => $result['referee'],
            ];
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

