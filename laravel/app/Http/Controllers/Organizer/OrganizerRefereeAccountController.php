<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Services\Organizer\OrganizerRefereeAccountService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class OrganizerRefereeAccountController extends Controller
{
    public function __construct(private readonly OrganizerRefereeAccountService $service)
    {
    }

    public function page(): Response
    {
        if (!$this->canApproveRefereeAccounts()) {
            return response()->view('errors.403', [
                'role' => LegacySessionUser::role(),
                'requiredRoles' => ['BAN_TO_CHUC'],
            ], 403);
        }

        return response()->view('organizer.referee-accounts', [
            'pageTitle' => 'VTMS - Duyet tai khoan trong tai',
            'moduleTitle' => 'Duyet tai khoan trong tai',
            'styles' => ['css/bantochuc-taikhoanhlv.css'],
            'scripts' => ['js/bantochuc-taikhoantrongtai.js'],
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respond($this->service->all(LegacySessionUser::id(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'trangthai' => $request->query('status', $request->query('trangthai', '')),
        ]));
    }

    public function show(Request $request): JsonResponse
    {
        $accountId = $this->routePositiveInt($request, 'accountId');

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->find($accountId, LegacySessionUser::id()));
    }

    public function approve(Request $request): JsonResponse
    {
        $accountId = $this->routePositiveInt($request, 'accountId');

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->approve($accountId, LegacySessionUser::id(), $request));
    }

    public function reject(Request $request): JsonResponse
    {
        $accountId = $this->routePositiveInt($request, 'accountId');

        if ($accountId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->reject($accountId, LegacySessionUser::id(), $request));
    }

    private function respond(array $result): JsonResponse
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

        return response()->json($payload, (int) $result['status']);
    }

    private function routePositiveInt(Request $request, string $key): ?int
    {
        $raw = (string) $request->route($key, '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $value = (int) $raw;

        return $value > 0 ? $value : null;
    }

    private function canApproveRefereeAccounts(): bool
    {
        $user = LegacySessionUser::user();

        return (string) ($user['role'] ?? '') === 'BAN_TO_CHUC'
            && (bool) ($user['organizer']['can_approve_referee_accounts'] ?? false);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Khong tim thay tai khoan trong tai.',
        ], 404);
    }
}
