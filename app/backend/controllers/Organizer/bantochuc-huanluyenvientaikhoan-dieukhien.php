<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerCoachAccountService;

final class OrganizerCoachAccountController extends Controller
{
    private OrganizerCoachAccountService $service;

    public function __construct()
    {
        $this->service = new OrganizerCoachAccountService();
    }

    public function page(Request $request): Response
    {
        $authorization = $this->service->authorize($this->accountId());

        if (($authorization['ok'] ?? false) !== true) {
            return $this->view('errors.403', [
                'pageTitle' => 'VTMS - Khong co quyen duyet tai khoan HLV',
            ], 'main', 403);
        }

        return $this->view('bantochuc.coach-accounts', [
            'pageTitle' => 'VTMS - Duyệt tài khoản HLV',
            'styles' => ['css/organizer-coach-accounts.css'],
            'scripts' => ['js/organizer-coach-accounts.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'trangthai' => $request->query('status', $request->query('trangthai', '')),
            ])
        );
    }

    public function show(Request $request): Response
    {
        $accountId = $this->routeAccountId($request);

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($accountId, $this->accountId())
        );
    }

    public function approve(Request $request): Response
    {
        $accountId = $this->routeAccountId($request);

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->approve($accountId, $this->accountId(), $request)
        );
    }

    public function reject(Request $request): Response
    {
        $accountId = $this->routeAccountId($request);

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->reject($accountId, $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeAccountId(Request $request): ?int
    {
        $raw = (string) $request->route('accountId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $accountId = (int) $raw;

        return $accountId > 0 ? $accountId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('accounts', $result)) {
            $payload['data'] = $result['accounts'];
        }

        if (array_key_exists('account', $result)) {
            $payload['data'] = $result['account'];
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
            'message' => 'Không tìm thấy tài khoản huấn luyện viên.',
        ], 404);
    }
}

