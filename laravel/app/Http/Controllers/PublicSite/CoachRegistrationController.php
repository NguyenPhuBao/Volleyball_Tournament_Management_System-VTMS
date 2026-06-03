<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\CoachRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CoachRegistrationController extends Controller
{
    public function __construct(private readonly CoachRegistrationService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('public.coach-register', [
            'pageTitle' => 'VTMS - Dang ky tai khoan huan luyen vien',
            'moduleTitle' => 'Dang ky tai khoan Huan luyen vien',
            'styles' => ['css/huanluyenvien-trang.css'],
            'scripts' => ['js/huanluyenvien-dungchung.js', 'js/huanluyenvien-dangky.js'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->respond($this->service->register($request->all(), $request));
    }

    public function options(): JsonResponse
    {
        return $this->respond($this->service->options());
    }

    private function respond(array $result): JsonResponse
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

        return response()->json($payload, (int) $result['status']);
    }
}
