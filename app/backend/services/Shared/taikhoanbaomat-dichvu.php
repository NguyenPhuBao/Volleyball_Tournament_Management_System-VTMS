<?php

declare(strict_types=1);

namespace App\Backend\Services\Shared;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Taikhoan;
use Throwable;

final class AccountSecurityService
{
    private const MIN_PASSWORD_LENGTH = 6;
    private const MAX_PASSWORD_LENGTH = 72;

    public function __construct(private ?Taikhoan $accounts = null)
    {
        $this->accounts ??= new Taikhoan();
    }

    public function changePassword(int $accountId, array $payload, ?Request $request = null): array
    {
        if ($accountId <= 0) {
            return $this->failure('Phiên đăng nhập không hợp lệ.', 401);
        }

        $account = $this->accounts->findByIdWithPassword($accountId);

        if ($account === null) {
            return $this->failure('Không tìm thấy tài khoản.', 404);
        }

        if ((string) ($account['trangthai'] ?? '') !== 'HOAT_DONG') {
            return $this->failure('Tài khoản không ở trạng thái hoạt động.', 403);
        }

        $currentPassword = $this->stringValue($payload, [
            'current_password',
            'old_password',
            'matkhau_hientai',
            'matkhaucu',
        ]);
        $newPassword = $this->stringValue($payload, [
            'new_password',
            'password',
            'matkhau_moi',
            'matkhaumoi',
        ]);
        $confirmation = $this->stringValue($payload, [
            'new_password_confirmation',
            'password_confirmation',
            'confirm_password',
            'matkhau_xacnhan',
        ]);

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'] = 'Vui lòng nhập mật khẩu hiện tại.';
        } elseif (!password_verify($currentPassword, (string) $account['password'])) {
            $errors['current_password'] = 'Mật khẩu hiện tại không đúng.';
            $this->recordSystemLog(
                $accountId,
                'Đổi mật khẩu thất bại',
                'Taikhoan',
                $accountId,
                $request?->ip(),
                sprintf('Tài khoản %s nhập sai mật khẩu hiện tại khi đổi mật khẩu.', (string) $account['username'])
            );
        }

        $newLength = strlen($newPassword);
        if ($newPassword === '') {
            $errors['new_password'] = 'Vui lòng nhập mật khẩu mới.';
        } elseif ($newLength < self::MIN_PASSWORD_LENGTH || $newLength > self::MAX_PASSWORD_LENGTH) {
            $errors['new_password'] = 'Mật khẩu mới phải từ 6 đến 72 ký tự.';
        } elseif (password_verify($newPassword, (string) $account['password'])) {
            $errors['new_password'] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        }

        if ($confirmation === '') {
            $errors['new_password_confirmation'] = 'Vui lòng xác nhận mật khẩu mới.';
        } elseif ($newPassword !== '' && $confirmation !== $newPassword) {
            $errors['new_password_confirmation'] = 'Mật khẩu xác nhận không khớp.';
        }

        if ($errors !== []) {
            return $this->failure('Dữ liệu đổi mật khẩu chưa hợp lệ.', 422, $errors);
        }

        try {
            $oldPasswordHash = (string) $account['password'];
            $this->accounts->updateAccount($accountId, [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ], null, $oldPasswordHash);
        } catch (Throwable) {
            return $this->failure('Không thể đổi mật khẩu. Vui lòng thử lại.', 500);
        }

        $this->recordSystemLog(
            $accountId,
            'Đổi mật khẩu',
            'Taikhoan',
            $accountId,
            $request?->ip(),
            sprintf('Tài khoản %s đổi mật khẩu đăng nhập.', (string) $account['username'])
        );

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Đổi mật khẩu thành công.',
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
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            return trim((string) $payload[$key]);
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
            // Logging is best effort; password changes must not be rolled back by audit write failures.
        }
    }
}
