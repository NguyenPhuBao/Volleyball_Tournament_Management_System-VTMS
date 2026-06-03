<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerVenueService;

final class OrganizerVenueController extends Controller
{
    private OrganizerVenueService $service;

    public function __construct()
    {
        $this->service = new OrganizerVenueService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.venues', [
            'pageTitle' => 'VTMS - Quan ly san dau',
            'styles' => ['css/organizer-venues.css'],
            'scripts' => ['js/organizer-venues.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', '')),
            ])
        );
    }

    public function store(Request $request): Response
    {
        return $this->respond(
            $this->service->create($request->all(), $this->accountId(), $request)
        );
    }

    public function show(Request $request): Response
    {
        $venueId = $this->routeVenueId($request);

        if ($venueId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($venueId, $this->accountId())
        );
    }

    public function update(Request $request): Response
    {
        $venueId = $this->routeVenueId($request);

        if ($venueId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->update($venueId, $request->all(), $this->accountId(), $request)
        );
    }

    public function deactivate(Request $request): Response
    {
        $venueId = $this->routeVenueId($request);

        if ($venueId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->deactivate($venueId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function routeVenueId(Request $request): ?int
    {
        $raw = (string) $request->route('venueId', $request->route('id', ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $venueId = (int) $raw;

        return $venueId > 0 ? $venueId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('venues', $result)) {
            $payload['data'] = $result['venues'];
        }

        if (array_key_exists('venue', $result)) {
            $payload['data'] = $result['venue'];
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
            'message' => 'Khong tim thay san dau.',
        ], 404);
    }
}

