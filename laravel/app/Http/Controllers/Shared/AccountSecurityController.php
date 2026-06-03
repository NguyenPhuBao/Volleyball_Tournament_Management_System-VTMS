<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\Shared\AccountSecurityService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AccountSecurityController extends Controller
{
    public function __construct(private readonly AccountSecurityService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('account.change-password', [
            'pageTitle' => 'VTMS - Doi mat khau',
            'moduleTitle' => 'Doi mat khau',
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $result = $this->service->changePassword(LegacySessionUser::id(), $request->all(), $request);

        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (!empty($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        if (isset($result['account'])) {
            $payload['data'] = $result['account'];
        }

        return response()->json($payload, (int) $result['status']);
    }
}
