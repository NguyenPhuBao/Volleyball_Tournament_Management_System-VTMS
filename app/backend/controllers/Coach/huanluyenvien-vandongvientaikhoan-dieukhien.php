<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Coach;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Coach\CoachAthleteAccountService;

final class CoachAthleteAccountController extends Controller
{
    private CoachAthleteAccountService $service;

    public function __construct()
    {
        $this->service = new CoachAthleteAccountService();
    }

    public function store(Request $request): Response
    {
        return $this->respond(
            $this->service->create($request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('athlete', $result)) {
            $payload['data'] = $result['athlete'];
        }

        if (array_key_exists('created', $result)) {
            $payload['created'] = $result['created'];
        }

        if (!empty($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        return Response::json($payload, (int) $result['status']);
    }
}

