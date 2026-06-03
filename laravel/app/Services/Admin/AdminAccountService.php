<?php

namespace App\Services\Admin;

use App\Repositories\Admin\AdminAccountRepository;
use Illuminate\Http\Request;
use Throwable;

final class AdminAccountService
{
    private const STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];
    private const GENDERS = ['NAM', 'NU', 'KHAC'];
    private const PROFILE_FIELDS = ['ten', 'hodem', 'gioitinh', 'ngaysinh', 'quequan', 'diachi', 'avatar', 'cccd'];

    public function __construct(private readonly AdminAccountRepository $accounts)
    {
    }

    public function list(array $filters = []): array
    {
        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'role' => strtoupper(trim((string) ($filters['role'] ?? ''))),
            'trangthai' => strtoupper(trim((string) ($filters['trangthai'] ?? $filters['status'] ?? ''))),
        ];

        if ($normalized['trangthai'] !== '' && !in_array($normalized['trangthai'], self::STATUSES, true)) {
            $normalized['trangthai'] = '';
        }

        return [
            'accounts' => $this->accounts->listAccounts($normalized),
            'filters' => $normalized,
            'statuses' => self::STATUSES,
        ];
    }

    public function roles(): array
    {
        return $this->accounts->roles();
    }

    public function find(int $accountId): ?array
    {
        return $this->accounts->findById($accountId);
    }

    public function create(array $payload, int $adminId, ?Request $request = null): array
    {
        [$account, $profile, $errors] = $this->validatePayload($payload, null, true, null);

        if ($errors !== []) {
            return $this->failure('Du lieu tai khoan khong hop le.', 422, $errors);
        }

        $account['password'] = password_hash((string) $account['password'], PASSWORD_DEFAULT);

        try {
            $accountId = $this->accounts->createAccount($account, $profile);
            $this->recordSystemLog($adminId, 'Tao tai khoan', 'Taikhoan', $accountId, $request?->ip(), 'Quan tri vien tao tai khoan moi.');

            if (isset($account['trangthai'])) {
                $this->recordStatusHistory('TAI_KHOAN', $accountId, null, (string) $account['trangthai'], 'Tao tai khoan', $adminId);
            }

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao tai khoan thanh cong.',
                'account' => $this->accounts->findById($accountId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao tai khoan.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function update(int $accountId, array $payload, int $adminId, ?Request $request = null): array
    {
        $current = $this->accounts->findByIdWithPassword($accountId);

        if ($current === null) {
            return $this->failure('Khong tim thay tai khoan.', 404);
        }

        [$account, $profile, $errors] = $this->validatePayload($payload, $accountId, false, $current);
        $this->guardSelfUpdate($accountId, $adminId, $account, $errors);

        if ($errors !== []) {
            return $this->failure('Du lieu tai khoan khong hop le.', 422, $errors);
        }

        if ($account === [] && $profile === null) {
            return $this->failure('Khong co du lieu de cap nhat.', 422, [
                'payload' => 'Can gui it nhat mot truong can cap nhat.',
            ]);
        }

        $oldStatus = (string) $current['trangthai'];
        $newStatus = isset($account['trangthai']) ? (string) $account['trangthai'] : $oldStatus;
        $oldPasswordHash = null;
        $shouldCloseSessions = false;

        if (isset($account['password'])) {
            $oldPasswordHash = (string) $current['password'];
            $account['password'] = password_hash((string) $account['password'], PASSWORD_DEFAULT);
            $shouldCloseSessions = true;
        }

        if ($newStatus !== 'HOAT_DONG') {
            $shouldCloseSessions = true;
        }

        try {
            $this->accounts->updateAccount($accountId, $account, $profile, $oldPasswordHash);

            if ($shouldCloseSessions && $accountId !== $adminId) {
                $this->accounts->closeActiveSessionsForAccount($accountId);
            }

            $this->recordSystemLog($adminId, 'Cap nhat tai khoan', 'Taikhoan', $accountId, $request?->ip(), 'Quan tri vien cap nhat thong tin tai khoan.');

            if ($newStatus !== $oldStatus) {
                $this->recordStatusHistory('TAI_KHOAN', $accountId, $oldStatus, $newStatus, 'Cap nhat trang thai tai khoan', $adminId);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat tai khoan thanh cong.',
                'account' => $this->accounts->findById($accountId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat tai khoan.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function delete(int $accountId, int $adminId, ?Request $request = null): array
    {
        if ($accountId === $adminId) {
            return $this->failure('Quan tri vien khong the xoa tai khoan dang dang nhap.', 422, [
                'account' => 'Khong duoc xoa tai khoan cua chinh minh.',
            ]);
        }

        $current = $this->accounts->findById($accountId);

        if ($current === null) {
            return $this->failure('Khong tim thay tai khoan.', 404);
        }

        try {
            $oldStatus = (string) $current['trangthai'];
            $this->accounts->softDelete($accountId);
            $this->recordSystemLog($adminId, 'Xoa tai khoan', 'Taikhoan', $accountId, $request?->ip(), 'Quan tri vien khoa mem tai khoan.');

            if ($oldStatus !== 'DA_HUY') {
                $this->recordStatusHistory('TAI_KHOAN', $accountId, $oldStatus, 'DA_HUY', 'Xoa tai khoan', $adminId);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xoa tai khoan thanh cong.',
                'account' => $this->accounts->findById($accountId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the xoa tai khoan.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    private function validatePayload(array $payload, ?int $accountId, bool $creating, ?array $current): array
    {
        $account = [];
        $errors = [];

        $this->validateUsername($payload, $accountId, $creating, $account, $errors);
        $this->validatePassword($payload, $creating, $account, $errors);
        $this->validateEmail($payload, $accountId, $creating, $account, $errors);
        $this->validatePhone($payload, $accountId, $account, $errors);
        $this->validateRole($payload, $creating, $account, $errors);
        $this->validateStatus($payload, $creating, $account, $errors);
        $profile = $this->validateProfile($payload, $accountId, $creating, $current, $errors);

        return [$account, $profile, $errors];
    }

    private function validateUsername(array $payload, ?int $accountId, bool $creating, array &$account, array &$errors): void
    {
        if (!$creating && !array_key_exists('username', $payload)) {
            return;
        }

        $username = trim((string) ($payload['username'] ?? ''));

        if ($username === '') {
            $errors['username'] = 'Ten dang nhap la bat buoc.';
            return;
        }

        if (strlen($username) < 3 || strlen($username) > 100 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $errors['username'] = 'Ten dang nhap phai dai 3-100 ky tu va chi gom chu, so, dau gach duoi, gach ngang hoac dau cham.';
            return;
        }

        if ($this->accounts->accountValueExists('username', $username, $accountId)) {
            $errors['username'] = 'Ten dang nhap da ton tai.';
            return;
        }

        $account['username'] = $username;
    }

    private function validatePassword(array $payload, bool $creating, array &$account, array &$errors): void
    {
        if (!$creating && !array_key_exists('password', $payload)) {
            return;
        }

        $password = (string) ($payload['password'] ?? '');

        if ($password === '') {
            if ($creating) {
                $errors['password'] = 'Mat khau la bat buoc.';
            }

            return;
        }

        if (strlen($password) < 6 || strlen($password) > 72) {
            $errors['password'] = 'Mat khau phai dai tu 6 den 72 ky tu.';
            return;
        }

        if (array_key_exists('password_confirmation', $payload) && $password !== (string) $payload['password_confirmation']) {
            $errors['password_confirmation'] = 'Mat khau xac nhan khong khop.';
            return;
        }

        $account['password'] = $password;
    }

    private function validateEmail(array $payload, ?int $accountId, bool $creating, array &$account, array &$errors): void
    {
        if (!$creating && !array_key_exists('email', $payload)) {
            return;
        }

        $email = trim((string) ($payload['email'] ?? ''));

        if ($email === '') {
            $errors['email'] = 'Email la bat buoc.';
            return;
        }

        if (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email khong hop le.';
            return;
        }

        if ($this->accounts->accountValueExists('email', $email, $accountId)) {
            $errors['email'] = 'Email da ton tai.';
            return;
        }

        $account['email'] = $email;
    }

    private function validatePhone(array $payload, ?int $accountId, array &$account, array &$errors): void
    {
        if (!array_key_exists('sodienthoai', $payload) && !array_key_exists('phone', $payload)) {
            return;
        }

        $phone = $this->nullableString($payload['sodienthoai'] ?? $payload['phone'] ?? null);

        if ($phone !== null && (strlen($phone) > 20 || !preg_match('/^\+?[0-9]{8,20}$/', $phone))) {
            $errors['sodienthoai'] = 'So dien thoai khong hop le.';
            return;
        }

        if ($phone !== null && $this->accounts->accountValueExists('sodienthoai', $phone, $accountId)) {
            $errors['sodienthoai'] = 'So dien thoai da ton tai.';
            return;
        }

        $account['sodienthoai'] = $phone;
    }

    private function validateRole(array $payload, bool $creating, array &$account, array &$errors): void
    {
        $hasRole = array_key_exists('role', $payload) || array_key_exists('namerole', $payload) || array_key_exists('idrole', $payload);

        if (!$creating && !$hasRole) {
            return;
        }

        $role = null;

        if (array_key_exists('idrole', $payload) && trim((string) $payload['idrole']) !== '') {
            $role = $this->accounts->findRoleById((int) $payload['idrole']);
        } else {
            $roleName = strtoupper(trim((string) ($payload['role'] ?? $payload['namerole'] ?? '')));

            if ($roleName !== '') {
                $role = $this->accounts->findRoleByName($roleName);
            }
        }

        if ($role === null) {
            $errors['role'] = 'Vai tro khong hop le.';
            return;
        }

        $account['idrole'] = (int) $role['idrole'];
        $account['role'] = (string) $role['namerole'];
    }

    private function validateStatus(array $payload, bool $creating, array &$account, array &$errors): void
    {
        $hasStatus = array_key_exists('trangthai', $payload) || array_key_exists('status', $payload);

        if (!$creating && !$hasStatus) {
            return;
        }

        $status = strtoupper(trim((string) ($payload['trangthai'] ?? $payload['status'] ?? 'CHUA_KICH_HOAT')));

        if (!in_array($status, self::STATUSES, true)) {
            $errors['trangthai'] = 'Trang thai tai khoan khong hop le.';
            return;
        }

        $account['trangthai'] = $status;
    }

    private function validateProfile(array $payload, ?int $accountId, bool $creating, ?array $current, array &$errors): ?array
    {
        $hasProfileInput = false;

        foreach (self::PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                $hasProfileInput = true;
                break;
            }
        }

        if (!$hasProfileInput) {
            return null;
        }

        $profile = [];
        $profileExists = $current !== null && !empty($current['idnguoidung']);
        $requiredForInsert = $creating || !$profileExists;

        foreach (['ten' => 100, 'hodem' => 200] as $field => $maxLength) {
            if (!$requiredForInsert && !array_key_exists($field, $payload)) {
                continue;
            }

            $value = trim((string) ($payload[$field] ?? ''));

            if ($value === '') {
                $errors[$field] = $field === 'ten' ? 'Ten la bat buoc khi tao ho so nguoi dung.' : 'Ho dem la bat buoc khi tao ho so nguoi dung.';
                continue;
            }

            if (strlen($value) > $maxLength) {
                $errors[$field] = 'Gia tri vuot qua do dai cho phep.';
                continue;
            }

            $profile[$field] = $value;
        }

        if ($requiredForInsert || array_key_exists('gioitinh', $payload)) {
            $gender = strtoupper(trim((string) ($payload['gioitinh'] ?? '')));

            if (!in_array($gender, self::GENDERS, true)) {
                $errors['gioitinh'] = 'Gioi tinh khong hop le.';
            } else {
                $profile['gioitinh'] = $gender;
            }
        }

        foreach (['quequan' => 500, 'diachi' => 500, 'avatar' => 500] as $field => $maxLength) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = $this->nullableString($payload[$field]);

            if ($value !== null && strlen($value) > $maxLength) {
                $errors[$field] = 'Gia tri vuot qua do dai cho phep.';
                continue;
            }

            $profile[$field] = $value;
        }

        if (array_key_exists('ngaysinh', $payload)) {
            $birthday = $this->nullableString($payload['ngaysinh']);

            if ($birthday !== null && !$this->isDate($birthday)) {
                $errors['ngaysinh'] = 'Ngay sinh phai theo dinh dang YYYY-MM-DD.';
            } else {
                $profile['ngaysinh'] = $birthday;
            }
        }

        if (array_key_exists('cccd', $payload)) {
            $identity = $this->nullableString($payload['cccd']);

            if ($identity !== null && (strlen($identity) > 20 || !preg_match('/^[0-9]{9,20}$/', $identity))) {
                $errors['cccd'] = 'CCCD khong hop le.';
            } elseif ($identity !== null && $this->accounts->profileValueExists('cccd', $identity, $accountId)) {
                $errors['cccd'] = 'CCCD da ton tai.';
            } else {
                $profile['cccd'] = $identity;
            }
        }

        return $profile;
    }

    private function guardSelfUpdate(int $accountId, int $adminId, array $account, array &$errors): void
    {
        if ($accountId !== $adminId) {
            return;
        }

        if (isset($account['role']) && $account['role'] !== 'ADMIN') {
            $errors['role'] = 'Khong duoc tu ha vai tro cua tai khoan dang dang nhap.';
        }

        if (isset($account['trangthai']) && $account['trangthai'] !== 'HOAT_DONG') {
            $errors['trangthai'] = 'Khong duoc tu khoa tai khoan dang dang nhap.';
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function isDate(string $value): bool
    {
        $parts = explode('-', $value);

        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    private function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note): void
    {
        try {
            $this->accounts->recordSystemLog($accountId, $action, $targetTable, $targetId, $ipAddress, $note);
        } catch (Throwable) {
        }
    }

    private function recordStatusHistory(string $targetType, int $targetId, ?string $oldStatus, string $newStatus, ?string $reason, ?int $actorId): void
    {
        try {
            $this->accounts->recordStatusHistory($targetType, $targetId, $oldStatus, $newStatus, $reason, $actorId);
        } catch (Throwable) {
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
