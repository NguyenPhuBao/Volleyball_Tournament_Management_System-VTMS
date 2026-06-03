<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\RefereeRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class RefereeRegistrationController extends Controller
{
    public function __construct(private readonly RefereeRegistrationService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('public.referee-register', [
            'pageTitle' => 'VTMS - Dang ky tai khoan trong tai',
            'moduleTitle' => 'Dang ky tai khoan Trong tai',
            'styles' => ['css/huanluyenvien-trang.css'],
            'scripts' => ['js/huanluyenvien-dungchung.js', 'js/trongtai-dangky.js'],
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

        if (array_key_exists('referee', $result)) {
            $payload['data'] = $result['referee'];
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
