<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerComplaintService;

final class OrganizerComplaintController extends Controller
{
    private OrganizerComplaintService $service;

    public function __construct()
    {
        $this->service = new OrganizerComplaintService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.complaints', [
            'pageTitle' => 'VTMS - Quan ly khieu nai',
            'styles' => ['css/organizer-complaints.css'],
            'scripts' => ['js/organizer-complaints.js'],
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
        $complaintId = $this->routeComplaintId($request);

        if ($complaintId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($complaintId, $this->accountId())
        );
    }

    public function receive(Request $request): Response
    {
        $complaintId = $this->routeComplaintId($request);

        if ($complaintId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->receive($complaintId, $request->all(), $this->accountId(), $request)
        );
    }

    public function reject(Request $request): Response
    {
        $complaintId = $this->routeComplaintId($request);

        if ($complaintId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->reject($complaintId, $request->all(), $this->accountId(), $request)
        );
    }

    public function resolve(Request $request): Response
    {
        $complaintId = $this->routeComplaintId($request);

        if ($complaintId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->resolve($complaintId, $request->all(), $this->accountId(), $request)
        );
    }

    public function noProcess(Request $request): Response
    {
        $complaintId = $this->routeComplaintId($request);

        if ($complaintId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->noProcess($complaintId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeComplaintId(Request $request): ?int
    {
        $raw = (string) $request->route('complaintId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $complaintId = (int) $raw;

        return $complaintId > 0 ? $complaintId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('complaints', $result)) {
            $payload['data'] = $result['complaints'];
        }

        if (array_key_exists('complaint', $result)) {
            $payload['data'] = $result['complaint'];
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
            'message' => 'Khong tim thay khieu nai.',
        ], 404);
    }
}

