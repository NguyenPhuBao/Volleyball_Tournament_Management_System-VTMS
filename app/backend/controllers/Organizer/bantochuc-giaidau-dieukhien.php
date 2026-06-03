<?php

declare(strict_types=1);

namespace App\Backend\Controllers\Organizer;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Controller;
use App\Backend\Core\Http\Request;
use App\Backend\Core\Http\Response;
use App\Backend\Services\Organizer\OrganizerTournamentService;

final class OrganizerTournamentController extends Controller
{
    private OrganizerTournamentService $service;

    public function __construct()
    {
        $this->service = new OrganizerTournamentService();
    }

    public function page(Request $request): Response
    {
        return $this->view('bantochuc.tournaments', [
            'pageTitle' => 'VTMS - Quan ly giai dau',
            'styles' => ['css/organizer-tournaments.css'],
            'scripts' => ['js/organizer-tournaments.js'],
        ]);
    }

    public function index(Request $request): Response
    {
        return $this->respond(
            $this->service->all($this->accountId(), [
                'q' => $request->query('q', ''),
                'status' => $request->query('status', $request->query('trangthai', '')),
                'registration_status' => $request->query('registration_status', $request->query('reg_status', $request->query('trangthaidangky', ''))),
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
            ])
        );
    }

    public function locations(Request $request): Response
    {
        return $this->respond(
            $this->service->locations($this->accountId(), [
                'q' => $request->query('q', $request->query('keyword', '')),
                'status' => $request->query('status', $request->query('trangthai', 'HOAT_DONG')),
            ])
        );
    }

    public function options(Request $request): Response
    {
        return $this->respond(
            $this->service->options($this->accountId())
        );
    }

    public function eligibilityPreview(Request $request): Response
    {
        return $this->respond(
            $this->service->eligibilityPreview([
                'idcapgiaidau' => $request->query('idcapgiaidau', ''),
                'idkhuvucphamvi' => $request->query('idkhuvucphamvi', ''),
                'dieukien' => [
                    'capdoituongthamgia' => $request->query('capdoituongthamgia', ''),
                    'thanh_tich_duoc_phep' => array_values(array_filter(explode(',', (string) $request->query('thanh_tich_duoc_phep', '')))),
                    'idcapgiaidau_thanh_tich_nguon' => $request->query('idcapgiaidau_thanh_tich_nguon', ''),
                    'so_mua_giai_gan_nhat_duoc_tinh' => $request->query('so_mua_giai_gan_nhat_duoc_tinh', '1'),
                    'chi_tinh_giai_chinh_thuc' => $request->query('chi_tinh_giai_chinh_thuc', '1'),
                    'bat_buoc_cung_khuvuc' => $request->query('bat_buoc_cung_khuvuc', '1'),
                    'cho_phep_btc_duyet_ngoai_le' => $request->query('cho_phep_btc_duyet_ngoai_le', '0'),
                ],
            ], $this->accountId())
        );
    }

    public function store(Request $request): Response
    {
        $payload = $this->payloadWithUploadedImage($request);

        if ($payload instanceof Response) {
            return $payload;
        }

        return $this->respond(
            $this->service->create($payload, $this->accountId(), $request)
        );
    }

    public function show(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->find($tournamentId, $this->accountId())
        );
    }

    public function update(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        $payload = $this->payloadWithUploadedImage($request);

        if ($payload instanceof Response) {
            return $payload;
        }

        return $this->respond(
            $this->service->update($tournamentId, $payload, $this->accountId(), $request)
        );
    }

    public function destroy(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->delete($tournamentId, $this->accountId(), $request)
        );
    }

    public function publish(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->publish($tournamentId, $this->accountId(), $request)
        );
    }

    public function cancel(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->cancel($tournamentId, $this->accountId(), $request)
        );
    }

    public function registrations(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->registrations($tournamentId, $this->accountId(), [
                'status' => $request->query('status', $request->query('trangthai', '')),
                'q' => $request->query('q', $request->query('keyword', '')),
            ])
        );
    }

    public function openRegistrations(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->openRegistrations($tournamentId, $this->accountId(), $request)
        );
    }

    public function closeRegistrations(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);

        if ($tournamentId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->closeRegistrations($tournamentId, $this->accountId(), $request)
        );
    }

    public function approveRegistration(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);
        $registrationId = $this->routeRegistrationId($request);

        if ($tournamentId === null || $registrationId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->approveRegistration($tournamentId, $registrationId, $this->accountId(), $request)
        );
    }

    public function rejectRegistration(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);
        $registrationId = $this->routeRegistrationId($request);

        if ($tournamentId === null || $registrationId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->rejectRegistration($tournamentId, $registrationId, $request->all(), $this->accountId(), $request)
        );
    }

    public function removeRegistration(Request $request): Response
    {
        $tournamentId = $this->routeTournamentId($request);
        $registrationId = $this->routeRegistrationId($request);

        if ($tournamentId === null || $registrationId === null) {
            return $this->notFound();
        }

        return $this->respond(
            $this->service->removeRegistration($tournamentId, $registrationId, $request->all(), $this->accountId(), $request)
        );
    }

    private function accountId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private function payloadWithUploadedImage(Request $request): array|Response
    {
        $payload = $request->all();
        $uploadedPath = $this->storeUploadedTournamentImage($request);

        if (is_array($uploadedPath)) {
            return Response::json([
                'success' => false,
                'message' => 'Anh giai dau khong hop le.',
                'errors' => [
                    'hinhanh' => $uploadedPath['error'],
                ],
            ], 422);
        }

        if (is_string($uploadedPath) && $uploadedPath !== '') {
            $payload['hinhanh'] = $uploadedPath;
            $payload['image'] = $uploadedPath;
        }

        return $payload;
    }

    private function storeUploadedTournamentImage(Request $request): string|array|null
    {
        $file = $request->file('hinhanh_file');

        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['error' => 'Tai anh len khong thanh cong.'];
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return ['error' => 'Anh khong duoc vuot qua 5MB.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;

        if ($imageInfo === false) {
            return ['error' => 'File tai len phai la anh hop le.'];
        }

        $extension = match ((int) ($imageInfo[2] ?? 0)) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };

        if ($extension === null) {
            return ['error' => 'Chi chap nhan anh JPG, PNG hoac WEBP.'];
        }

        $uploadDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tournaments';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return ['error' => 'Khong the tao thu muc luu anh.'];
        }

        $filename = 'tournament_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            return ['error' => 'Khong the luu anh len may chu.'];
        }

        return '/uploads/tournaments/' . $filename;
    }

    private function routeTournamentId(Request $request): ?int
    {
        $raw = (string) $request->route('id', '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $tournamentId = (int) $raw;

        return $tournamentId > 0 ? $tournamentId : null;
    }

    private function routeRegistrationId(Request $request): ?int
    {
        $raw = (string) $request->route('registrationId', '');

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $registrationId = (int) $raw;

        return $registrationId > 0 ? $registrationId : null;
    }

    private function respond(array $result): Response
    {
        $payload = [
            'success' => $result['ok'],
            'message' => $result['message'],
        ];

        if (array_key_exists('tournament', $result)) {
            $payload['data'] = $result['tournament'];
        }

        if (array_key_exists('tournaments', $result)) {
            $payload['data'] = $result['tournaments'];
        }

        if (array_key_exists('locations', $result)) {
            $payload['data'] = $result['locations'];
        }

        if (array_key_exists('options', $result)) {
            $payload['data'] = $result['options'];
        }

        if (array_key_exists('preview', $result)) {
            $payload['data'] = $result['preview'];
        }

        if (array_key_exists('registrations', $result)) {
            $payload['data'] = $result['registrations'];
        }

        if (array_key_exists('registration', $result)) {
            $payload['data'] = $result['registration'];
        }

        if (array_key_exists('meta', $result)) {
            $payload['meta'] = $result['meta'];
        }

        if (array_key_exists('deleted_id', $result)) {
            $payload['deleted_id'] = $result['deleted_id'];
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
            'message' => 'Khong tim thay giai dau.',
        ], 404);
    }
}

