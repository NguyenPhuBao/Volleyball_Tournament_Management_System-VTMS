<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Yeucaucapnhathoso;
use RuntimeException;
use Throwable;

final class OrganizerPersonalInfoChangeRequestService
{
    private const STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI'];
    private const ROLES = ['ADMIN', 'TRONG_TAI', 'HUAN_LUYEN_VIEN'];
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    private const TABLE_ALIASES = [
        'nguoidung' => 'Nguoidung',
        'taikhoan' => 'Taikhoan',
        'quantrivien' => 'Quantrivien',
        'trongtai' => 'Trongtai',
        'huanluyenvien' => 'Huanluyenvien',
        'huan_luyen_vien' => 'Huanluyenvien',
    ];

    private const TABLE_FIELDS = [
        'Nguoidung' => ['ten', 'hodem', 'gioitinh', 'ngaysinh', 'quequan', 'diachi', 'avatar', 'cccd'],
        'Taikhoan' => ['username', 'email', 'sodienthoai'],
        'Quantrivien' => ['machucvu', 'ghichu'],
        'Trongtai' => ['capbac', 'kinhnghiem'],
        'Huanluyenvien' => ['bangcap', 'kinhnghiem'],
    ];

    private const ROLE_TABLES = [
        'ADMIN' => ['Nguoidung', 'Taikhoan', 'Quantrivien'],
        'TRONG_TAI' => ['Nguoidung', 'Taikhoan', 'Trongtai'],
        'HUAN_LUYEN_VIEN' => ['Nguoidung', 'Taikhoan', 'Huanluyenvien'],
    ];

    public function __construct(
        private ?Yeucaucapnhathoso $requests = null,
        private ?Giaidau $organizers = null
    ) {
        $this->requests ??= new Yeucaucapnhathoso();
        $this->organizers ??= new Giaidau();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        [$normalized, $page, $perPage] = $this->normalizeFilters($filters);
        $total = $this->requests->countPersonalChangeRequests($normalized);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach yeu cau xac nhan thong tin ca nhan thanh cong.',
            'requests' => $this->requests->listPersonalChangeRequests($normalized, $perPage, ($page - 1) * $perPage),
            'meta' => [
                'filters' => $normalized,
                'statuses' => self::STATUSES,
                'roles' => self::ROLES,
                'target_tables' => array_keys(self::TABLE_FIELDS),
                'fields' => self::TABLE_FIELDS,
                'status_counts' => $this->requests->statusCountsPersonalChangeRequests($normalized),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
            ],
        ];
    }

    public function find(int $requestId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $change = $this->requests->findPersonalChangeRequestById($requestId);

        if ($change === null) {
            return $this->failure('Khong tim thay yeu cau xac nhan thong tin ca nhan.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay chi tiet yeu cau xac nhan thong tin ca nhan thanh cong.',
            'request' => $change,
        ];
    }

    public function approve(int $requestId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $change = $this->requests->findPersonalChangeRequestById($requestId);

        if ($change === null) {
            return $this->failure('Khong tim thay yeu cau xac nhan thong tin ca nhan.', 404);
        }

        $guard = $this->guardProcessable($change);

        if ($guard !== null) {
            return $guard;
        }

        [$targetTable, $field, $newValue, $errors] = $this->approvedValue($change);

        if ($errors !== []) {
            return $this->failure('Yeu cau xac nhan thong tin ca nhan khong hop le.', 422, $errors);
        }

        if (!$this->roleCanUpdateTable((string) $change['role'], $targetTable)) {
            return $this->failure('Bang lien quan khong phu hop voi vai tro nguoi gui.', 422, [
                'banglienquan' => 'Bang lien quan khong phu hop voi vai tro.',
            ]);
        }

        $current = $this->currentValue($change, $targetTable, $field);

        if ($this->comparable($change['giatricu'] ?? null) !== $current) {
            return $this->failure('Du lieu hien tai da thay doi so voi thoi diem gui yeu cau.', 409, [
                'giatricu' => 'Gia tri hien tai khong con khop voi gia tri cu trong yeu cau.',
            ]);
        }

        if ($this->comparable($newValue) === $current) {
            return $this->failure('Gia tri moi trung voi gia tri hien tai.', 409, [
                'giatrimoi' => 'Khong co thay doi can xac nhan.',
            ]);
        }

        $note = $this->approvalNote($change, $targetTable, $field, $payload);

        try {
            $this->requests->approvePersonalChangeRequest(
                $requestId,
                (int) $change['idnguoidung'],
                $targetTable,
                $field,
                $newValue,
                (int) $change['iddoituong'],
                $accountId,
                $request?->ip(),
                $note
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xac nhan thay doi thong tin ca nhan thanh cong.',
                'request' => $this->requests->findPersonalChangeRequestById($requestId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REQUEST_NOT_PENDING') {
                return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
            }

            if ($exception->getMessage() === 'TARGET_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat thong tin ca nhan hien tai.', 409);
            }

            return $this->failure('Khong the xac nhan thay doi thong tin ca nhan.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xac nhan thay doi thong tin ca nhan.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function reject(int $requestId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $change = $this->requests->findPersonalChangeRequestById($requestId);

        if ($change === null) {
            return $this->failure('Khong tim thay yeu cau xac nhan thong tin ca nhan.', 404);
        }

        $guard = $this->guardProcessable($change);

        if ($guard !== null) {
            return $guard;
        }

        $note = $this->operatorNote($payload);

        try {
            $this->requests->rejectPersonalChangeRequest($requestId, $accountId, $request?->ip(), $note);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy yeu cau thay doi thong tin ca nhan thanh cong.',
                'request' => $this->requests->findPersonalChangeRequestById($requestId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REQUEST_NOT_PENDING') {
                return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
            }

            return $this->failure('Khong the huy yeu cau thay doi thong tin ca nhan.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the huy yeu cau thay doi thong tin ca nhan.', 500, [
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

        $role = strtoupper(trim((string) ($filters['role'] ?? $filters['vai_tro'] ?? '')));

        if ($role !== '' && !in_array($role, self::ROLES, true)) {
            $role = '';
        }

        $targetTable = $this->normalizeTable((string) ($filters['banglienquan'] ?? $filters['target_table'] ?? '')) ?? '';
        $field = strtolower(trim((string) ($filters['truongcapnhat'] ?? $filters['field'] ?? '')));

        if ($targetTable === '' || !isset(self::TABLE_FIELDS[$targetTable])) {
            $field = '';
        } elseif ($field !== '' && !in_array($field, self::TABLE_FIELDS[$targetTable], true)) {
            $field = '';
        }

        $normalized = [
            'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            'trangthai' => $status,
            'role' => $role,
            'banglienquan' => $targetTable,
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

    private function approvedValue(array $change): array
    {
        $targetTable = $this->normalizeTable((string) $change['banglienquan']);
        $field = strtolower(trim((string) $change['truongcapnhat']));

        if ($targetTable === null || !isset(self::TABLE_FIELDS[$targetTable])) {
            return ['', $field, null, [
                'banglienquan' => 'Bang lien quan khong duoc phep.',
            ]];
        }

        if (!in_array($field, self::TABLE_FIELDS[$targetTable], true)) {
            return [$targetTable, $field, null, [
                'truongcapnhat' => 'Truong cap nhat khong duoc phep.',
            ]];
        }

        [$value, $errors] = $this->validateValue($change, $targetTable, $field);

        return [$targetTable, $field, $value, $errors];
    }

    private function validateValue(array $change, string $targetTable, string $field): array
    {
        $raw = trim((string) $change['giatrimoi']);
        $errors = [];
        $value = $raw;

        if (in_array($field, ['ten', 'hodem', 'username', 'email'], true) && $raw === '') {
            $errors[$field] = 'Gia tri moi khong duoc rong.';
        }

        $maxLengths = [
            'ten' => 100,
            'hodem' => 200,
            'quequan' => 500,
            'diachi' => 500,
            'avatar' => 500,
            'cccd' => 20,
            'username' => 100,
            'email' => 150,
            'sodienthoai' => 20,
            'machucvu' => 100,
            'ghichu' => 500,
            'capbac' => 100,
            'bangcap' => 300,
        ];

        if (isset($maxLengths[$field]) && strlen($raw) > $maxLengths[$field]) {
            $errors[$field] = 'Gia tri moi vuot qua do dai cho phep.';
        }

        if ($field === 'gioitinh') {
            $value = strtoupper($raw);

            if (!in_array($value, ['NAM', 'NU', 'KHAC'], true)) {
                $errors[$field] = 'Gioi tinh khong hop le.';
            }
        }

        if ($field === 'ngaysinh') {
            $value = $raw === '' ? null : $raw;

            if ($value !== null && $this->dateOrEmpty($value) === '') {
                $errors[$field] = 'Ngay sinh khong hop le.';
            }
        }

        if (in_array($field, ['quequan', 'diachi', 'avatar', 'cccd', 'sodienthoai', 'machucvu', 'ghichu', 'capbac', 'bangcap'], true)) {
            $value = $raw === '' ? null : $raw;
        }

        if ($field === 'email' && !str_contains($raw, '@')) {
            $errors[$field] = 'Email khong hop le.';
        }

        if ($field === 'kinhnghiem') {
            if ($raw === '' || !ctype_digit($raw)) {
                $errors[$field] = 'Kinh nghiem phai la so nguyen khong am.';
            } else {
                $value = (int) $raw;
            }
        }

        if (in_array($field, ['username', 'email', 'sodienthoai', 'cccd'], true)
            && $value !== null
            && $this->requests->personalUniqueValueExists($targetTable, $field, (string) $value, (int) $change['idnguoidung'])
        ) {
            $errors[$field] = 'Gia tri moi da ton tai trong he thong.';
        }

        return [$value, $errors];
    }

    private function guardProcessable(array $change): ?array
    {
        if ((string) $change['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
        }

        return null;
    }

    private function roleCanUpdateTable(string $role, string $targetTable): bool
    {
        return in_array($targetTable, self::ROLE_TABLES[$role] ?? [], true);
    }

    private function currentValue(array $change, string $targetTable, string $field): string
    {
        $key = match ($targetTable) {
            'Nguoidung', 'Taikhoan', 'Quantrivien' => 'current_' . $field,
            'Trongtai' => $field === 'kinhnghiem' ? 'current_kinhnghiem_trongtai' : 'current_' . $field,
            'Huanluyenvien' => $field === 'kinhnghiem' ? 'current_kinhnghiem_huanluyenvien' : 'current_' . $field,
            default => '',
        };

        return $this->comparable($change[$key] ?? null);
    }

    private function approvalNote(array $change, string $targetTable, string $field, array $payload): string
    {
        $note = $this->operatorNote($payload);
        $parts = [
            'Yeu cau #' . (int) $change['idyeucaucapnhat'],
            'Nguoi dung #' . (int) $change['idnguoidung'] . ' (' . (string) $change['role'] . ')',
            'Cap nhat ' . $targetTable . '.' . $field,
            'Tu "' . $this->comparable($change['giatricu'] ?? null) . '" sang "' . $this->comparable($change['giatrimoi'] ?? null) . '"',
        ];

        if ($note !== null) {
            $parts[] = 'Ghi chu BTC: ' . $note;
        }

        return $this->limitLogNote(implode('. ', $parts));
    }

    private function activeOrganizer(int $accountId): array
    {
        $organizer = $this->organizers->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) $organizer['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Ban to chuc khong o trang thai hoat dong.', 403);
        }

        return $organizer;
    }

    private function normalizeTable(string $table): ?string
    {
        $key = strtolower(trim($table));

        return self::TABLE_ALIASES[$key] ?? null;
    }

    private function operatorNote(array $payload): ?string
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

