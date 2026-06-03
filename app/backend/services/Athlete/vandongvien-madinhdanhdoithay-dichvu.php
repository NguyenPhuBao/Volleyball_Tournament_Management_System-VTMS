<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use RuntimeException;
use Throwable;

final class AthleteIdentifierChangeService extends AthleteServiceSupport
{
    private const STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI'];
    private const FIELD_MAP = [
        'mavandongvien' => 'Vandongvien',
        'cccd' => 'Nguoidung',
    ];

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters, self::STATUSES);
        $field = strtolower(trim((string) ($filters['field'] ?? $filters['truongcapnhat'] ?? '')));

        if ($field !== '' && !array_key_exists($field, self::FIELD_MAP)) {
            $errors['field'] = 'Truong id ca nhan khong hop le.';
        }

        $normalized['field'] = $field;
        $normalized['target_table'] = $field !== '' ? self::FIELD_MAP[$field] : '';

        if ($errors !== []) {
            return $this->failure('Bo loc yeu cau sua id ca nhan khong hop le.', 422, $errors);
        }

        try {
            $items = $this->athletes->profileChangeRequestsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $items = array_values(array_filter($items, static function (array $item): bool {
                $field = (string) ($item['truongcapnhat'] ?? '');

                return array_key_exists($field, self::FIELD_MAP)
                    && (string) ($item['banglienquan'] ?? '') === self::FIELD_MAP[$field];
            }));

            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem danh sach yeu cau sua id ca nhan VDV',
                'Yeucaucapnhathoso',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem %d yeu cau sua id ca nhan.', (int) $athlete['idvandongvien'], count($items)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach yeu cau sua id ca nhan thanh cong.',
                'requests' => $items,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'statuses' => self::STATUSES,
                    'allowed_fields' => array_keys(self::FIELD_MAP),
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach yeu cau sua id ca nhan.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $requestId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        try {
            $item = $this->athletes->profileChangeRequestForAthlete((int) $athlete['idvandongvien'], $requestId);

            if ($item === null || !$this->isIdentifierRequest($item)) {
                return $this->failure('Khong tim thay yeu cau sua id ca nhan.', 404);
            }

            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem chi tiet yeu cau sua id ca nhan VDV',
                'Yeucaucapnhathoso',
                $requestId,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem yeu cau sua id ca nhan #%d.', (int) $athlete['idvandongvien'], $requestId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet yeu cau sua id ca nhan thanh cong.',
                'request' => $item,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet yeu cau sua id ca nhan.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function isIdentifierRequest(array $item): bool
    {
        $field = (string) ($item['truongcapnhat'] ?? '');

        return array_key_exists($field, self::FIELD_MAP)
            && (string) ($item['banglienquan'] ?? '') === self::FIELD_MAP[$field];
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, true);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$field, $newValue, $reason, $errors] = $this->requestPayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu sua id ca nhan khong hop le.', 422, $errors);
        }

        $targetTable = self::FIELD_MAP[$field];
        $oldValue = (string) ($athlete[$field] ?? '');

        if ($newValue === $oldValue) {
            return $this->failure('Gia tri moi phai khac gia tri hien tai.', 422, [
                $field => 'Gia tri moi khong thay doi.',
            ]);
        }

        if ($field === 'mavandongvien' && $this->athletes->athleteCodeExists($newValue)) {
            return $this->failure('Ma van dong vien da duoc su dung.', 409, [
                'mavandongvien' => 'Ma van dong vien phai duy nhat.',
            ]);
        }

        if ($field === 'cccd' && $this->athletes->profileValueExists('cccd', $newValue)) {
            return $this->failure('CCCD da duoc su dung.', 409, [
                'cccd' => 'CCCD phai duy nhat.',
            ]);
        }

        if ($this->athletes->hasPendingProfileChangeRequest((int) $athlete['idvandongvien'], $targetTable, $field)) {
            return $this->failure('Da co yeu cau sua id ca nhan dang cho duyet.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'VDV #%d gui yeu cau sua %s tu "%s" sang "%s". Ly do: %s',
            (int) $athlete['idvandongvien'],
            $field,
            $oldValue,
            $newValue,
            $reason ?: 'Khong co'
        ));

        try {
            $requestId = $this->athletes->createProfileChangeRequestForAthlete(
                (int) $athlete['idvandongvien'],
                $targetTable,
                $field,
                $oldValue,
                $newValue,
                $reason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Gui yeu cau sua id ca nhan thanh cong.',
                'request' => $this->athletes->profileChangeRequestForAthlete((int) $athlete['idvandongvien'], $requestId),
            ];
        } catch (RuntimeException) {
            return $this->failure('Khong the gui yeu cau sua id ca nhan.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the gui yeu cau sua id ca nhan.', 500, [
                'database' => 'Loi ghi co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function requestPayload(array $payload): array
    {
        $errors = [];
        $field = strtolower(trim((string) ($payload['field'] ?? $payload['truongcapnhat'] ?? 'mavandongvien')));
        $newValue = trim((string) ($payload['value'] ?? $payload['giatrimoi'] ?? $payload[$field] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? $payload['lydo'] ?? ''));

        if (!array_key_exists($field, self::FIELD_MAP)) {
            $errors['field'] = 'Chi ho tro sua mavandongvien hoac cccd.';
        }

        if ($newValue === '') {
            $errors['value'] = 'Gia tri moi la bat buoc.';
        } elseif (strlen($newValue) > 1000) {
            $errors['value'] = 'Gia tri moi khong duoc vuot qua 1000 ky tu.';
        }

        if ($field === 'cccd' && $newValue !== '' && !preg_match('/^\d{9,12}$/', $newValue)) {
            $errors['cccd'] = 'CCCD phai gom 9 den 12 chu so.';
        }

        if ($field === 'mavandongvien' && $newValue !== '' && strlen($newValue) > 50) {
            $errors['mavandongvien'] = 'Ma van dong vien khong duoc vuot qua 50 ky tu.';
        }

        if (strlen($reason) > 1000) {
            $errors['reason'] = 'Ly do khong duoc vuot qua 1000 ky tu.';
        }

        return [$field, $newValue, $reason !== '' ? $reason : null, $errors];
    }
}

