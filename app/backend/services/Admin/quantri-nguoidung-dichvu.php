<?php

declare(strict_types=1);

namespace App\Backend\Services\Admin;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Nguoidung;
use Throwable;

final class AdminUserService
{
    private const GENDERS = ['NAM', 'NU', 'KHAC'];
    private const ACCOUNT_STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];

    public function __construct(private ?Nguoidung $users = null)
    {
        $this->users ??= new Nguoidung();
    }

    public function list(array $filters = []): array
    {
        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'role' => strtoupper(trim((string) ($filters['role'] ?? ''))),
            'gioitinh' => strtoupper(trim((string) ($filters['gioitinh'] ?? $filters['gender'] ?? ''))),
            'trangthai_taikhoan' => strtoupper(trim((string) ($filters['trangthai_taikhoan'] ?? $filters['status'] ?? ''))),
        ];

        if ($normalized['gioitinh'] !== '' && !in_array($normalized['gioitinh'], self::GENDERS, true)) {
            $normalized['gioitinh'] = '';
        }

        if ($normalized['trangthai_taikhoan'] !== '' && !in_array($normalized['trangthai_taikhoan'], self::ACCOUNT_STATUSES, true)) {
            $normalized['trangthai_taikhoan'] = '';
        }

        return [
            'users' => $this->users->listUsers($normalized),
            'filters' => $normalized,
            'genders' => self::GENDERS,
            'account_statuses' => self::ACCOUNT_STATUSES,
        ];
    }

    public function find(int $userId): ?array
    {
        return $this->users->findById($userId);
    }

    public function update(int $userId, array $payload, int $adminId, ?Request $request = null): array
    {
        $current = $this->users->findById($userId);

        if ($current === null) {
            return $this->failure('Khong tim thay nguoi dung.', 404);
        }

        [$profile, $account, $errors] = $this->validatePayload($payload, $current, $adminId);

        if ($errors !== []) {
            return $this->failure('Du lieu nguoi dung khong hop le.', 422, $errors);
        }

        if ($profile === [] && $account === []) {
            return $this->failure('Khong co du lieu de cap nhat.', 422, [
                'payload' => 'Can gui it nhat mot truong can cap nhat.',
            ]);
        }

        try {
            $this->users->updateUser($userId, $profile, $account);

            if (($account['trangthai'] ?? 'HOAT_DONG') !== 'HOAT_DONG') {
                $this->users->closeActiveSessionsForAccount((int) $current['idtaikhoan']);
            }

            $this->recordSystemLog($adminId, 'Cap nhat nguoi dung', 'Nguoidung', $userId, $request?->ip(), 'Quan tri vien cap nhat ho so nguoi dung.');

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat nguoi dung thanh cong.',
                'user' => $this->users->findById($userId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat nguoi dung.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    private function validatePayload(array $payload, array $current, int $adminId): array
    {
        $profile = [];
        $account = [];
        $errors = [];

        $this->validateRequiredString($payload, 'ten', 100, 'Ten', $profile, $errors);
        $this->validateRequiredString($payload, 'hodem', 200, 'Ho dem', $profile, $errors);
        $this->validateGender($payload, $profile, $errors);
        $this->validateBirthday($payload, $profile, $errors);
        $this->validateNullableString($payload, 'quequan', 500, $profile, $errors);
        $this->validateNullableString($payload, 'diachi', 500, $profile, $errors);
        $this->validateNullableString($payload, 'avatar', 500, $profile, $errors);
        $this->validateIdentityNumber($payload, (int) $current['idnguoidung'], $profile, $errors);
        $this->validateEmail($payload, (int) $current['idtaikhoan'], $account, $errors);
        $this->validatePhone($payload, (int) $current['idtaikhoan'], $account, $errors);
        $this->validateAccountStatus($payload, (int) $current['idtaikhoan'], $adminId, $account, $errors);

        return [$profile, $account, $errors];
    }

    private function validateRequiredString(array $payload, string $field, int $maxLength, string $label, array &$profile, array &$errors): void
    {
        if (!array_key_exists($field, $payload)) {
            return;
        }

        $value = trim((string) $payload[$field]);

        if ($value === '') {
            $errors[$field] = $label . ' la bat buoc.';
            return;
        }

        if (strlen($value) > $maxLength) {
            $errors[$field] = $label . ' vuot qua do dai cho phep.';
            return;
        }

        $profile[$field] = $value;
    }

    private function validateGender(array $payload, array &$profile, array &$errors): void
    {
        if (!array_key_exists('gioitinh', $payload) && !array_key_exists('gender', $payload)) {
            return;
        }

        $gender = strtoupper(trim((string) ($payload['gioitinh'] ?? $payload['gender'] ?? '')));

        if (!in_array($gender, self::GENDERS, true)) {
            $errors['gioitinh'] = 'Gioi tinh khong hop le.';
            return;
        }

        $profile['gioitinh'] = $gender;
    }

    private function validateBirthday(array $payload, array &$profile, array &$errors): void
    {
        if (!array_key_exists('ngaysinh', $payload) && !array_key_exists('birthday', $payload)) {
            return;
        }

        $birthday = $this->nullableString($payload['ngaysinh'] ?? $payload['birthday'] ?? null);

        if ($birthday !== null && !$this->isDate($birthday)) {
            $errors['ngaysinh'] = 'Ngay sinh phai theo dinh dang YYYY-MM-DD.';
            return;
        }

        $profile['ngaysinh'] = $birthday;
    }

    private function validateNullableString(array $payload, string $field, int $maxLength, array &$profile, array &$errors): void
    {
        if (!array_key_exists($field, $payload)) {
            return;
        }

        $value = $this->nullableString($payload[$field]);

        if ($value !== null && strlen($value) > $maxLength) {
            $errors[$field] = 'Gia tri vuot qua do dai cho phep.';
            return;
        }

        $profile[$field] = $value;
    }

    private function validateIdentityNumber(array $payload, int $userId, array &$profile, array &$errors): void
    {
        if (!array_key_exists('cccd', $payload)) {
            return;
        }

        $identity = $this->nullableString($payload['cccd']);

        if ($identity !== null && (strlen($identity) > 20 || !preg_match('/^[0-9]{9,20}$/', $identity))) {
            $errors['cccd'] = 'CCCD khong hop le.';
            return;
        }

        if ($identity !== null && $this->users->profileValueExists('cccd', $identity, $userId)) {
            $errors['cccd'] = 'CCCD da ton tai.';
            return;
        }

        $profile['cccd'] = $identity;
    }

    private function validateEmail(array $payload, int $accountId, array &$account, array &$errors): void
    {
        if (!array_key_exists('email', $payload)) {
            return;
        }

        $email = trim((string) $payload['email']);

        if ($email === '') {
            $errors['email'] = 'Email la bat buoc.';
            return;
        }

        if (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email khong hop le.';
            return;
        }

        if ($this->users->accountValueExists('email', $email, $accountId)) {
            $errors['email'] = 'Email da ton tai.';
            return;
        }

        $account['email'] = $email;
    }

    private function validatePhone(array $payload, int $accountId, array &$account, array &$errors): void
    {
        if (!array_key_exists('sodienthoai', $payload) && !array_key_exists('phone', $payload)) {
            return;
        }

        $phone = $this->nullableString($payload['sodienthoai'] ?? $payload['phone'] ?? null);

        if ($phone !== null && (strlen($phone) > 20 || !preg_match('/^\+?[0-9]{8,20}$/', $phone))) {
            $errors['sodienthoai'] = 'So dien thoai khong hop le.';
            return;
        }

        if ($phone !== null && $this->users->accountValueExists('sodienthoai', $phone, $accountId)) {
            $errors['sodienthoai'] = 'So dien thoai da ton tai.';
            return;
        }

        $account['sodienthoai'] = $phone;
    }

    private function validateAccountStatus(array $payload, int $accountId, int $adminId, array &$account, array &$errors): void
    {
        if (!array_key_exists('trangthai_taikhoan', $payload) && !array_key_exists('status', $payload) && !array_key_exists('trangthai', $payload)) {
            return;
        }

        $status = strtoupper(trim((string) ($payload['trangthai_taikhoan'] ?? $payload['status'] ?? $payload['trangthai'] ?? '')));

        if (!in_array($status, self::ACCOUNT_STATUSES, true)) {
            $errors['trangthai_taikhoan'] = 'Trang thai tai khoan khong hop le.';
            return;
        }

        if ($accountId === $adminId && $status !== 'HOAT_DONG') {
            $errors['trangthai_taikhoan'] = 'Khong duoc tu khoa tai khoan dang dang nhap.';
            return;
        }

        $account['trangthai'] = $status;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note): void
    {
        try {
            $this->users->recordSystemLog($accountId, $action, $targetTable, $targetId, $ipAddress, $note);
        } catch (Throwable) {
            // Logging is best effort; user profile updates must not fail because of audit writes.
        }
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

