<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerHigherEligibilityService;

final class OrganizerHigherEligibilityController extends Controller
{
    private OrganizerHigherEligibilityService $service;

    public function __construct()
    {
        $this->service = new OrganizerHigherEligibilityService();
    }

    public function page(Request $request): Response
    {
        $authorization = $this->service->authorize($this->accountId());

        if (($authorization['ok'] ?? false) !== true) {
            return $this->view('errors.403', [
                'pageTitle' => 'VTMS - Khong co quyen xet tu cach cap tren',
            ], 'main', 403);
        }

        return $this->view('bantochuc.higher-eligibility', [
            'pageTitle' => 'VTMS - Tu cach tham gia cap tren',
            'styles' => ['css/organizer-teams.css'],
            'scripts' => ['js/organizer-higher-eligibility.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->overview($this->accountId(), [
            'q' => $request->query('q', ''),
            'source_tournament_id' => $request->query('source_tournament_id', ''),
            'achievement' => $request->query('achievement', ''),
        ]));
    }

    public function mark(Request $request): Response
    {
        return $this->respond($this->service->markEligible($request->all(), $this->accountId()));
    }

    public function review(Request $request): Response
    {
        return $this->respond($this->service->reviewProfile([
            'idthanhtich' => $request->query('idthanhtich', $request->query('achievement_id', '')),
            'idgiaidau_dich' => $request->query('idgiaidau_dich', $request->query('target_tournament_id', '')),
        ], $this->accountId()));
    }

    public function nominate(Request $request): Response
    {
        $proposalId = $this->routeProposalId($request);

        if ($proposalId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->nominate($proposalId, $request->all(), $this->accountId()));
    }

    public function approve(Request $request): Response
    {
        $proposalId = $this->routeProposalId($request);

        if ($proposalId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->approve($proposalId, $request->all(), $this->accountId()));
    }

    public function reject(Request $request): Response
    {
        $proposalId = $this->routeProposalId($request);

        if ($proposalId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->reject($proposalId, $request->all(), $this->accountId()));
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeProposalId(Request $request): ?int
    {
        $raw = (string) $request->route('proposalId', '');

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

        if (array_key_exists('data', $result)) {
            $payload['data'] = $result['data'];
        }

        if (array_key_exists('proposal_id', $result)) {
            $payload['proposal_id'] = $result['proposal_id'];
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
            'message' => 'Khong tim thay de cu tu cach tham gia.',
        ], 404);
    }
}
