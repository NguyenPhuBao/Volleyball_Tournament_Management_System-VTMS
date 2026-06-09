<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Services\Organizer\OrganizerVenueService;
use App\Support\LegacySessionUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class OrganizerVenueController extends Controller
{
    public function __construct(private readonly OrganizerVenueService $service)
    {
    }

    public function page(): Response
    {
        return response()->view('organizer.venues', [
            'pageTitle' => 'VTMS - Quan ly san dau',
            'moduleTitle' => 'Quan ly san dau',
            'styles' => ['css/bantochuc-sandau.css'],
            'scripts' => ['js/bantochuc-sandau.js'],
            'user' => LegacySessionUser::user(),
        ]);
    }

    public function locations(Request $request): JsonResponse
    {
        return $this->respond($this->service->locations(LegacySessionUser::id(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', 'HOAT_DONG')),
        ]));
    }

    public function index(Request $request): JsonResponse
    {
        return $this->respond($this->service->all(LegacySessionUser::id(), [
            'q' => $request->query('q', $request->query('keyword', '')),
            'status' => $request->query('status', $request->query('trangthai', '')),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->respond($this->service->create($request->all(), LegacySessionUser::id(), $request));
    }

    public function show(Request $request): JsonResponse
    {
        $venueId = $this->routePositiveInt($request, 'venueId');

        if ($venueId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->find($venueId, LegacySessionUser::id()));
    }

    public function update(Request $request): JsonResponse
    {
        $venueId = $this->routePositiveInt($request, 'venueId');

        if ($venueId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->update($venueId, $request->all(), LegacySessionUser::id(), $request));
    }

    public function deactivate(Request $request): JsonResponse
    {
        $venueId = $this->routePositiveInt($request, 'venueId');

        if ($venueId === null) {
            return $this->notFound();
        }

        return $this->respond($this->service->deactivate($venueId, $request->all(), LegacySessionUser::id(), $request));
    }

    private function respond(array $result): JsonResponse
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        foreach (['locations', 'venues', 'venue'] as $key) {
            if (array_key_exists($key, $result)) {
                $payload['data'] = $result[$key];
                break;
            }
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

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Khong tim thay san dau.',
        ], 404);
    }
}
