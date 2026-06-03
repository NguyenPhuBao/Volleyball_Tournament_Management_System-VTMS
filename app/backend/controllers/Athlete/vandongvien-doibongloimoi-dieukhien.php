<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Athlete;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Athlete\AthleteTeamInvitationService;

final class AthleteTeamInvitationController extends Controller
{
    private AthleteTeamInvitationService $service;

    public function __construct()
    {
        $this->service = new AthleteTeamInvitationService();
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->all($this->accountId(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
            'team_id' => $request->query('team_id', $request->query('iddoibong', '')),
            'from' => $request->query('from', $request->query('from_date', $request->query('tungay', ''))),
            'to' => $request->query('to', $request->query('to_date', $request->query('denngay', ''))),
        ], $request));
    }

    public function show(Request $request): Response
    {
        $id = $this->routePositiveInt($request, 'invitationId');

        if ($id === null) {
            return $this->notFound('Khong tim thay loi moi doi bong.');
        }

        return $this->respond($this->service->show($id, $this->accountId(), $request));
    }

    public function accept(Request $request): Response
    {
        $id = $this->routePositiveInt($request, 'invitationId');

        if ($id === null) {
            return $this->notFound('Khong tim thay loi moi doi bong.');
        }

        return $this->respond($this->service->accept($id, $this->accountId(), $request));
    }

    public function reject(Request $request): Response
    {
        $id = $this->routePositiveInt($request, 'invitationId');

        if ($id === null) {
            return $this->notFound('Khong tim thay loi moi doi bong.');
        }

        return $this->respond($this->service->reject($id, $this->accountId(), $request));
    }

    public function confirmMembership(Request $request): Response
    {
        $id = $this->routePositiveInt($request, 'memberId');

        if ($id === null) {
            return $this->notFound('Khong tim thay thanh vien doi bong.');
        }

        return $this->respond($this->service->confirmMembership($id, $this->accountId(), $request));
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

        foreach (['invitations', 'invitation', 'member'] as $key) {
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
        return Response::json(['success' => false, 'message' => $message], 404);
    }
}

