<?php

namespace App\Services\Shared;

use App\Repositories\AccountRepository;
use Illuminate\Http\Request;
use Throwable;

final class AccountSecurityService
{
    private const MIN_PASSWORD_LENGTH = 6;
    private const MAX_PASSWORD_LENGTH = 72;

    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    public function changePassword(int $accountId, array $payload, ?Request $request = null): array
    {
        if ($accountId <= 0) {
            return $this->failure('Phien dang nhap khong hop le.', 401);
        }

        $account = $this->accounts->findByIdWithPassword($accountId);

        if ($account === null) {
            return $this->failure('Khong tim thay tai khoan.', 404);
        }

        if ((string) ($account['trangthai'] ?? '') !== 'HOAT_DONG') {
            return $this->failure('Tai khoan khong o trang thai hoat dong.', 403);
        }

        $currentPassword = $this->stringValue($payload, ['current_password', 'old_password', 'matkhau_hientai', 'matkhaucu']);
        $newPassword = $this->stringValue($payload, ['new_password', 'password', 'matkhau_moi', 'matkhaumoi']);
        $confirmation = $this->stringValue($payload, ['new_password_confirmation', 'password_confirmation', 'confirm_password', 'matkhau_xacnhan']);

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'] = 'Vui long nhap mat khau hien tai.';
        } elseif (!password_verify($currentPassword, (string) $account['password'])) {
            $errors['current_password'] = 'Mat khau hien tai khong dung.';
            $this->recordSystemLog($accountId, 'Doi mat khau that bai', 'Taikhoan', $accountId, $request?->ip(), 'Tai khoan nhap sai mat khau hien tai khi doi mat khau.');
        }

        $newLength = strlen($newPassword);
        if ($newPassword === '') {
            $errors['new_password'] = 'Vui long nhap mat khau moi.';
        } elseif ($newLength < self::MIN_PASSWORD_LENGTH || $newLength > self::MAX_PASSWORD_LENGTH) {
            $errors['new_password'] = 'Mat khau moi phai tu 6 den 72 ky tu.';
        } elseif (password_verify($newPassword, (string) $account['password'])) {
            $errors['new_password'] = 'Mat khau moi phai khac mat khau hien tai.';
        }

        if ($confirmation === '') {
            $errors['new_password_confirmation'] = 'Vui long xac nhan mat khau moi.';
        } elseif ($newPassword !== '' && $confirmation !== $newPassword) {
            $errors['new_password_confirmation'] = 'Mat khau xac nhan khong khop.';
        }

        if ($errors !== []) {
            return $this->failure('Du lieu doi mat khau chua hop le.', 422, $errors);
        }

        try {
            $this->accounts->updatePassword(
                $accountId,
                password_hash($newPassword, PASSWORD_DEFAULT),
                (string) $account['password']
            );
        } catch (Throwable) {
            return $this->failure('Khong the doi mat khau. Vui long thu lai.', 500);
        }

        $this->recordSystemLog($accountId, 'Doi mat khau', 'Taikhoan', $accountId, $request?->ip(), 'Tai khoan doi mat khau dang nhap.');

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Doi mat khau thanh cong.',
            'account' => [
                'idtaikhoan' => $accountId,
                'username' => (string) $account['username'],
                'role' => (string) $account['role'],
            ],
        ];
    }

    private function stringValue(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return trim((string) $payload[$key]);
            }
        }

        return '';
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

    private function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note): void
    {
        try {
            $this->accounts->recordSystemLog($accountId, $action, $targetTable, $targetId, $ipAddress, $note);
        } catch (Throwable) {
            // Audit logging must not break password changes.
        }
    }
}
