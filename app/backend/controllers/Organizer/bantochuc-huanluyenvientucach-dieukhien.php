<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerCoachQualificationService;

final class OrganizerCoachQualificationController extends Controller
{
    private OrganizerCoachQualificationService $service;

    public function __construct()
    {
        $this->service = new OrganizerCoachQualificationService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.coaches', [
            'pageTitle' => 'VTMS - Quan ly tu cach huan luyen vien',
            'styles' => ['css/organizer-coaches.css'],
            'scripts' => ['js/organizer-coaches.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'account_status' => $request->query('account_status', $request->query('trangthai_taikhoan', '')),
                'request_status' => $request->query('request_status', $request->query('trangthai_yeucau', '')),
                'request_presence' => $request->query('request_presence', $request->query('request_filter', '')),
                'from' => $request->query('from', $request->query('fromDate', $request->query('tungay', ''))),
                'to' => $request->query('to', $request->query('toDate', $request->query('denngay', ''))),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $coachId = $this->routeCoachId($request);

        if ($coachId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($coachId, $this->accountId())
        );
    }

    public function approve(Request $request): Response
    {
        $coachId = $this->routeCoachId($request);

        if ($coachId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->approve($coachId, $this->accountId(), $request)
        );
    }

    public function cancel(Request $request): Response
    {
        $coachId = $this->routeCoachId($request);

        if ($coachId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->cancel($coachId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeCoachId(Request $request): ?int
    {
        $raw = (string) $request->route('coachId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $coachId = (int) $raw;

        return $coachId > 0 ? $coachId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('coaches', $result)) {
            $payload['data'] = $result['coaches'];
        }

        if (array_key_exists('coach', $result)) {
            $payload['data'] = $result['coach'];
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
            'message' => 'Khong tim thay huan luyen vien.',
        ], 404);
    }
}

