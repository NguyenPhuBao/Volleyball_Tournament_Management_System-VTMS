<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Referee;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Referee\RefereeMatchSupervisionService;

final class RefereeMatchSupervisionController extends Controller
{
    private RefereeMatchSupervisionService $service;

    public function __construct()
    {
        $this->service = new RefereeMatchSupervisionService();
    }

    public function page(Request $request): Response
    {
        return $this->view('trongtai.supervise', [
            'pageTitle' => 'VTMS - Giam sat tran dau',
            'styles' => ['css/referee-supervise.css'],
            'scripts' => ['js/referee-supervise.js'],
        ]);
    }

    public function show(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->show($matchId, $this->accountId(), $request)
        );
    }

    public function confirmParticipants(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->confirmParticipants($matchId, $request->all(), $this->accountId(), $request)
        );
    }

    public function start(Request $request): Response
    {
        return $this->statusAction($request, 'start');
    }

    public function pause(Request $request): Response
    {
        return $this->statusAction($request, 'pause');
    }

    public function resume(Request $request): Response
    {
        return $this->statusAction($request, 'resume');
    }

    public function recordResult(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->recordResult($matchId, $request->all(), $this->accountId(), $request)
        );
    }

    public function finish(Request $request): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->finish($matchId, $request->all(), $this->accountId(), $request)
        );
    }

    private function statusAction(Request $request, string $action): Response
    {
        $matchId = $this->routePositiveInt($request, 'matchId');

        if ($matchId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->statusAction($matchId, $action, $this->accountId(), $request)
        );
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
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('supervision', $result)) {
            $payload['data'] = $result['supervision'];
        }

        if (array_key_exists('match', $result)) {
            $payload['data'] = $result['match'];
        }

        if (array_key_exists('result', $result)) {
            $payload['result'] = $result['result'];
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
            'message' => 'Khong tim thay tran dau duoc phan cong.',
        ], 404);
    }
}

