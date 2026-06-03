<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Referee;

use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Referee\RefereeRegistrationService;

final class RefereeRegistrationController extends Controller
{
    private RefereeRegistrationService $service;

    public function __construct()
    {
        $this->service = new RefereeRegistrationService();
    }

    public function page(Request $request): Response
    {
        return $this->view('trongtai.register', [
            'pageTitle' => 'VTMS - Dang ky tai khoan trong tai',
            'styles' => ['css/coach-pages.css'],
            'scripts' => ['js/coach-common.js', 'js/referee-register.js'],
        ]);
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

        return Response::json($payload, (int) $result['status']);
    }
}
