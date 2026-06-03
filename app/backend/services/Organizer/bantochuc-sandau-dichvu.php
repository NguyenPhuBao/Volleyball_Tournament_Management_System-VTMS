<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Sandau;
use RuntimeException;
use Throwable;

final class OrganizerVenueService
{
    private const VENUE_STATUSES = ['HOAT_DONG', 'DANG_BAO_TRI', 'NGUNG_SU_DUNG'];

    public function __construct(
        private ?Sandau $venues = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->venues ??= new Sandau();
        $this->tournaments ??= new Giaidau();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $normalizedFilters = $this->filters($filters);

        if (!empty($normalizedFilters['errors'])) {
            return $this->failure('Bo loc san dau khong hop le.', 422, $normalizedFilters['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach san dau thanh cong.',
            'venues' => $this->venues->list($normalizedFilters['filters']),
            'meta' => [
                'filters' => $normalizedFilters['filters'],
                'statuses' => self::VENUE_STATUSES,
            ],
        ];
    }

    public function find(int $venueId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $venue = $this->venues->findById($venueId);

        if ($venue === null) {
            return $this->failure('Khong tim thay san dau.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin san dau thanh cong.',
            'venue' => $venue,
        ];
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        [$venue, $errors] = $this->validateCreatePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu san dau khong hop le.', 422, $errors);
        }

        $location = $this->tournaments->competitionLocationById((int) $venue['idvitrithidau'], (int) $organizer['idbantochuc']);

        if ($location === null) {
            return $this->failure('Vi tri thi dau khong ton tai hoac khong thuoc pham vi quan ly.', 422, [
                'idvitrithidau' => 'Vui long chon vi tri thi dau hop le.',
            ]);
        }

        if ($this->venues->existsByNameAndLocation($venue['tensandau'], (int) $venue['idvitrithidau'])) {
            return $this->failure('San dau da ton tai trong vi tri thi dau nay.', 409, [
                'tensandau' => 'Ten san dau da ton tai trong vi tri thi dau da chon.',
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d bo sung san dau "%s" tai "%s". Trang thai: %s.',
            (int) $organizer['idbantochuc'],
            $venue['tensandau'],
            (string) $location['tenvitrithidau'],
            $venue['trangthai']
        ));

        try {
            $venueId = $this->venues->createVenue($venue, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Bo sung san dau thanh cong.',
                'venue' => $this->venues->findById($venueId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the bo sung san dau.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function update(int $venueId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $current = $this->venues->findById($venueId);

        if ($current === null) {
            return $this->failure('Khong tim thay san dau.', 404);
        }

        [$changes, $errors, $changedFields] = $this->validateUpdatePayload($payload, $current);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat san dau khong hop le.', 422, $errors);
        }

        if ($changes === []) {
            return $this->failure('Can gui it nhat mot truong thay doi.', 422, [
                'payload' => 'Khong co du lieu thay doi.',
            ]);
        }

        $name = (string) ($changes['tensandau'] ?? $current['tensandau']);
        $locationId = (int) ($changes['idvitrithidau'] ?? $current['idvitrithidau']);

        $location = $this->tournaments->competitionLocationById($locationId, (int) $organizer['idbantochuc']);

        if ($location === null) {
            return $this->failure('Vi tri thi dau khong ton tai hoac khong thuoc pham vi quan ly.', 422, [
                'idvitrithidau' => 'Vui long chon vi tri thi dau hop le.',
            ]);
        }

        if ($this->venues->existsByNameAndLocation($name, $locationId, $venueId)) {
            return $this->failure('San dau da ton tai trong vi tri thi dau nay.', 409, [
                'tensandau' => 'Ten san dau da ton tai trong vi tri thi dau da chon.',
            ]);
        }

        $newStatus = array_key_exists('trangthai', $changes) ? (string) $changes['trangthai'] : null;
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cap nhat san dau "%s". Truong thay doi: %s.',
            (int) $organizer['idbantochuc'],
            $name,
            implode(', ', $changedFields)
        ));

        try {
            $this->venues->updateVenue(
                $venueId,
                $changes,
                (string) $current['trangthai'],
                $newStatus,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat san dau thanh cong.',
                'venue' => $this->venues->findById($venueId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'VENUE_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat san dau.', 409);
            }

            return $this->failure('Khong the cap nhat san dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat san dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function deactivate(int $venueId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $current = $this->venues->findById($venueId);

        if ($current === null) {
            return $this->failure('Khong tim thay san dau.', 404);
        }

        if ((string) $current['trangthai'] === 'NGUNG_SU_DUNG') {
            return $this->failure('San dau da ngung su dung.', 409);
        }

        $reason = trim((string) ($payload['lydo'] ?? $payload['ly_do'] ?? $payload['reason'] ?? $payload['note'] ?? 'Ngung su dung san dau'));

        if ($reason === '') {
            $reason = 'Ngung su dung san dau';
        }

        if (strlen($reason) > 1000) {
            return $this->failure('Ly do ngung su dung khong hop le.', 422, [
                'lydo' => 'Ly do khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d ngung su dung san dau "%s". Ly do: %s',
            (int) $organizer['idbantochuc'],
            (string) $current['tensandau'],
            $reason
        ));

        try {
            $this->venues->deactivateVenue(
                $venueId,
                (string) $current['trangthai'],
                $reason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Ngung su dung san dau thanh cong.',
                'venue' => $this->venues->findById($venueId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'VENUE_NOT_DEACTIVATED') {
                return $this->failure('Khong the ngung su dung san dau hien tai.', 409);
            }

            return $this->failure('Khong the ngung su dung san dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the ngung su dung san dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function validateCreatePayload(array $payload): array
    {
        $errors = [];

        $venue = [
            'tensandau' => $this->requiredString($payload, ['tensandau', 'ten', 'name'], 300, 'Ten san dau', $errors),
            'idvitrithidau' => $this->positiveInt($payload['idvitrithidau'] ?? $payload['id_vi_tri_thi_dau'] ?? $payload['location_id'] ?? null, 'idvitrithidau', 'Vi tri thi dau', $errors),
            'succhua' => $this->nonNegativeInt($payload['succhua'] ?? $payload['suc_chua'] ?? $payload['capacity'] ?? 0, 'succhua', 'Suc chua', $errors),
            'mota' => $this->nullableString($payload['mota'] ?? $payload['description'] ?? $payload['desc'] ?? null, 1000, 'Mo ta', 'mota', $errors),
            'trangthai' => $this->statusValue($payload['trangthai'] ?? $payload['status'] ?? 'HOAT_DONG', 'trangthai', $errors),
        ];

        return [$venue, $errors];
    }

    private function validateUpdatePayload(array $payload, array $current): array
    {
        $errors = [];
        $changes = [];
        $changedFields = [];

        if ($this->hasAnyKey($payload, ['tensandau', 'ten', 'name'])) {
            $name = $this->requiredString($payload, ['tensandau', 'ten', 'name'], 300, 'Ten san dau', $errors);

            if ($name !== null && $name !== (string) $current['tensandau']) {
                $changes['tensandau'] = $name;
                $changedFields[] = 'tensandau';
            }
        }

        if ($this->hasAnyKey($payload, ['idvitrithidau', 'id_vi_tri_thi_dau', 'location_id'])) {
            $locationId = $this->positiveInt(
                $payload['idvitrithidau'] ?? $payload['id_vi_tri_thi_dau'] ?? $payload['location_id'] ?? null,
                'idvitrithidau',
                'Vi tri thi dau',
                $errors
            );

            if ($locationId !== null && $locationId !== (int) $current['idvitrithidau']) {
                $changes['idvitrithidau'] = $locationId;
                $changedFields[] = 'idvitrithidau';
            }
        }

        if ($this->hasAnyKey($payload, ['succhua', 'suc_chua', 'capacity'])) {
            $capacity = $this->nonNegativeInt($payload['succhua'] ?? $payload['suc_chua'] ?? $payload['capacity'] ?? null, 'succhua', 'Suc chua', $errors);

            if ($capacity !== null && $capacity !== (int) $current['succhua']) {
                $changes['succhua'] = $capacity;
                $changedFields[] = 'succhua';
            }
        }

        if (array_key_exists('mota', $payload) || array_key_exists('description', $payload) || array_key_exists('desc', $payload)) {
            $description = $this->nullableString($payload['mota'] ?? $payload['description'] ?? $payload['desc'] ?? null, 1000, 'Mo ta', 'mota', $errors);

            if ($description !== ($current['mota'] ?? null)) {
                $changes['mota'] = $description;
                $changedFields[] = 'mota';
            }
        }

        if (array_key_exists('trangthai', $payload) || array_key_exists('status', $payload)) {
            $status = $this->statusValue($payload['trangthai'] ?? $payload['status'] ?? '', 'trangthai', $errors);

            if ($status !== null && $status !== (string) $current['trangthai']) {
                $changes['trangthai'] = $status;
                $changedFields[] = 'trangthai';
            }
        }

        return [$changes, $errors, $changedFields];
    }

    private function filters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $errors = [];

        if ($status !== '' && !in_array($status, self::VENUE_STATUSES, true)) {
            $errors['status'] = 'Trang thai san dau khong hop le.';
        }

        return [
            'filters' => [
                'q' => $keyword,
                'status' => $status,
            ],
            'errors' => $errors,
        ];
    }

    private function requiredString(array $payload, array $keys, int $maxLength, string $label, array &$errors): ?string
    {
        $key = $this->firstExistingKey($payload, $keys);
        $value = trim((string) ($key === null ? '' : $payload[$key]));
        $errorKey = $keys[0];

        if ($value === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (strlen($value) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $value;
    }

    private function nullableString(mixed $value, int $maxLength, string $label, string $errorKey, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (strlen($text) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $text;
    }

    private function nonNegativeInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (!ctype_digit((string) $value)) {
            $errors[$errorKey] = $label . ' phai la so nguyen khong am.';
            return null;
        }

        return (int) $value;
    }

    private function positiveInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            $errors[$errorKey] = $label . ' khong hop le.';
            return null;
        }

        return (int) $value;
    }

    private function statusValue(mixed $value, string $errorKey, array &$errors): ?string
    {
        $status = strtoupper(trim((string) ($value ?? '')));

        if (!in_array($status, self::VENUE_STATUSES, true)) {
            $errors[$errorKey] = 'Trang thai san dau khong hop le.';
            return null;
        }

        return $status;
    }

    private function firstExistingKey(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $key;
            }
        }

        return null;
    }

    private function hasAnyKey(array $payload, array $keys): bool
    {
        return $this->firstExistingKey($payload, $keys) !== null;
    }

    private function activeOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) $organizer['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Ban to chuc khong o trang thai hoat dong.', 403);
        }

        return $organizer;
    }

    private function limitLogNote(string $note): string
    {
        if (strlen($note) <= 1000) {
            return $note;
        }

        return substr($note, 0, 997) . '...';
    }

    private function failure(string $message, int $status, array $errors = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ];
    }
}

