<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Coach;

use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Coach\CoachRegistrationService;

final class CoachRegistrationController extends Controller
{
    private CoachRegistrationService $service;

    public function __construct()
    {
        $this->service = new CoachRegistrationService();
    }

    public function store(Request $request): Response
    {
        return $this->respond(
            $this->service->register($request->all(), $request)
        );
    }

    public function options(Request $request): Response
    {
        return $this->respond(
            $this->service->options()
        );
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('coach', $result)) {
            $payload['data'] = $result['coach'];
        }

        if (array_key_exists('registration', $result)) {
            $payload['registration'] = $result['registration'];
        }

        if (array_key_exists('options', $result)) {
            $payload['data'] = $result['options'];
        }

        if (!empty($result['errors'])) {
            $payload['errors'] = $result['errors'];
        }

        return Response::json($payload, (int) $result['status']);
    }
}

