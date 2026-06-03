<?php

declare(strict_types=1);

namespace App\Backend\Services\Admin;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Yeucaucapnhathoso;
use RuntimeException;
use Throwable;

final class AdminOrganizerChangeRequestService
{
    private const STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI'];
    private const FIELDS = ['donvi', 'chucvu', 'trangthai'];
    private const ORGANIZER_STATUSES = ['HOAT_DONG', 'CHO_XAC_NHAN', 'TAM_KHOA', 'NGUNG_HOAT_DONG'];
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    public function __construct(private ?Yeucaucapnhathoso $requests = null)
    {
        $this->requests ??= new Yeucaucapnhathoso();
    }

    public function list(array $filters = []): array
    {
        [$normalized, $page, $perPage] = $this->normalizeFilters($filters);
        $total = $this->requests->countOrganizerChangeRequests($normalized);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'requests' => $this->requests->listOrganizerChangeRequests($normalized, $perPage, ($page - 1) * $perPage),
            'filters' => $normalized,
            'statuses' => self::STATUSES,
            'fields' => self::FIELDS,
            'status_counts' => $this->requests->statusCountsOrganizerChangeRequests($normalized),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function find(int $requestId): ?array
    {
        return $this->requests->findOrganizerChangeRequestById($requestId);
    }

    public function approve(int $requestId, array $payload, int $adminId, ?Request $request = null): array
    {
        $change = $this->find($requestId);

        if ($change === null) {
            return $this->failure('Khong tim thay yeu cau thay doi thong tin ban to chuc.', 404);
        }

        $guard = $this->guardProcessable($change);

        if ($guard !== null) {
            return $guard;
        }

        [$field, $newValue, $errors] = $this->validateApprovedValue($change);

        if ($errors !== []) {
            return $this->failure('Yeu cau thay doi thong tin ban to chuc khong hop le.', 422, $errors);
        }

        if (!$this->oldValueStillCurrent($change, $field)) {
            return $this->failure('Du lieu ban to chuc da thay doi so voi thoi diem gui yeu cau.', 409, [
                'giatricu' => 'Gia tri hien tai khong con khop voi gia tri cu trong yeu cau.',
            ]);
        }

        $note = $this->approvalNote($change, $payload);

        try {
            $this->requests->approveOrganizerChangeRequest(
                $requestId,
                (int) $change['idbantochuc'],
                $field,
                $newValue,
                $adminId,
                $request?->ip(),
                $note
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Duyet thay doi thong tin ban to chuc thanh cong.',
                'request' => $this->find($requestId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REQUEST_NOT_PENDING') {
                return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
            }

            return $this->failure('Khong the duyet yeu cau thay doi thong tin ban to chuc.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the duyet yeu cau thay doi thong tin ban to chuc.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function reject(int $requestId, array $payload, int $adminId, ?Request $request = null): array
    {
        $change = $this->find($requestId);

        if ($change === null) {
            return $this->failure('Khong tim thay yeu cau thay doi thong tin ban to chuc.', 404);
        }

        $guard = $this->guardProcessable($change);

        if ($guard !== null) {
            return $guard;
        }

        $note = $this->adminNote($payload);

        try {
            $this->requests->rejectOrganizerChangeRequest($requestId, $adminId, $request?->ip(), $note);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Tu choi thay doi thong tin ban to chuc thanh cong.',
                'request' => $this->find($requestId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REQUEST_NOT_PENDING') {
                return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
            }

            return $this->failure('Khong the tu choi yeu cau thay doi thong tin ban to chuc.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the tu choi yeu cau thay doi thong tin ban to chuc.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    private function normalizeFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['trangthai'] ?? $filters['status'] ?? '')));

        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $field = strtolower(trim((string) ($filters['truongcapnhat'] ?? $filters['field'] ?? '')));

        if ($field !== '' && !in_array($field, self::FIELDS, true)) {
            $field = '';
        }

        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'trangthai' => $status,
            'truongcapnhat' => $field,
            'idnguoidung' => $this->positiveInt($filters['idnguoidung'] ?? $filters['user_id'] ?? null),
            'from' => $this->dateOrEmpty($filters['from'] ?? $filters['from_date'] ?? ''),
            'to' => $this->dateOrEmpty($filters['to'] ?? $filters['to_date'] ?? ''),
        ];

        $page = $this->positiveInt($filters['page'] ?? null) ?? 1;
        $perPage = $this->positiveInt($filters['per_page'] ?? $filters['limit'] ?? null) ?? self::DEFAULT_PER_PAGE;
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return [$normalized, $page, $perPage];
    }

    private function guardProcessable(array $change): ?array
    {
        if ((string) $change['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
        }

        return null;
    }

    private function validateApprovedValue(array $change): array
    {
        $field = strtolower(trim((string) $change['truongcapnhat']));

        if (!in_array($field, self::FIELDS, true)) {
            return [$field, null, [
                'truongcapnhat' => 'Truong cap nhat khong duoc phep cho ban to chuc.',
            ]];
        }

        $rawValue = trim((string) $change['giatrimoi']);

        if ($field === 'donvi') {
            if ($rawValue === '' || strlen($rawValue) > 300) {
                return [$field, null, [
                    'giatrimoi' => 'Don vi khong duoc rong va khong vuot qua 300 ky tu.',
                ]];
            }

            return [$field, $rawValue, []];
        }

        if ($field === 'chucvu') {
            if (strlen($rawValue) > 200) {
                return [$field, null, [
                    'giatrimoi' => 'Chuc vu khong duoc vuot qua 200 ky tu.',
                ]];
            }

            return [$field, $rawValue === '' ? null : $rawValue, []];
        }

        $status = strtoupper($rawValue);

        if (!in_array($status, self::ORGANIZER_STATUSES, true)) {
            return [$field, null, [
                'giatrimoi' => 'Trang thai ban to chuc khong hop le.',
            ]];
        }

        return [$field, $status, []];
    }

    private function oldValueStillCurrent(array $change, string $field): bool
    {
        $currentKey = 'current_' . $field;
        $current = $this->comparable($change[$currentKey] ?? null);
        $old = $this->comparable($change['giatricu'] ?? null);

        return $current === $old;
    }

    private function approvalNote(array $change, array $payload): string
    {
        $note = $this->adminNote($payload);
        $parts = [
            'Yeu cau #' . (int) $change['idyeucaucapnhat'],
            'Cap nhat ' . (string) $change['truongcapnhat'],
            'Tu "' . $this->comparable($change['giatricu'] ?? null) . '" sang "' . $this->comparable($change['giatrimoi'] ?? null) . '"',
        ];

        if ($note !== null) {
            $parts[] = 'Ghi chu admin: ' . $note;
        }

        return implode('. ', $parts);
    }

    private function adminNote(array $payload): ?string
    {
        $note = trim((string) ($payload['ghichu'] ?? $payload['note'] ?? $payload['lydo'] ?? $payload['reason'] ?? ''));

        if ($note === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($note, 0, 900);
        }

        return substr($note, 0, 900);
    }

    private function comparable(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function dateOrEmpty(mixed $value): string
    {
        $date = trim((string) $value);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year) ? $date : '';
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

