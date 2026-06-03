<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Taikhoan;
use App\Backend\Models\Huanluyenvien;
use RuntimeException;
use Throwable;

final class OrganizerCoachAccountService
{
    public function __construct(
        private ?Taikhoan $accounts = null,
        private ?Huanluyenvien $coaches = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->accounts ??= new Taikhoan();
        $this->coaches ??= new Huanluyenvien();
        $this->tournaments ??= new Giaidau();
    }

    public function all(int $organizerAccountId, array $filters = []): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'trangthai' => trim((string) ($filters['trangthai'] ?? '')),
            'role' => 'HUAN_LUYEN_VIEN',
        ];

        $accounts = $this->accounts->listAccounts($normalized);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lấy danh sách tài khoản huấn luyện viên thành công.',
            'accounts' => $accounts,
            'meta' => [
                'filters' => [
                    'q' => $normalized['q'],
                    'trangthai' => $normalized['trangthai'],
                ],
                'statuses' => ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'],
            ],
        ];
    }

    public function authorize(int $organizerAccountId): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'BTC cap quoc gia thuoc Lien doan Bong chuyen VN duoc duyet tai khoan HLV.',
        ];
    }

    public function find(int $accountId, int $organizerAccountId): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $account = $this->accounts->findById($accountId);

        if ($account === null || (string) $account['role'] !== 'HUAN_LUYEN_VIEN') {
            return $this->failure('Không tìm thấy tài khoản huấn luyện viên.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lấy thông tin tài khoản thành công.',
            'account' => $account,
        ];
    }

    public function approve(int $accountId, int $organizerId, ?Request $request = null): array
    {
        $permission = $this->nationalFederationOrganizer($organizerId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $account = $this->accounts->findById($accountId);

        if ($account === null || (string) $account['role'] !== 'HUAN_LUYEN_VIEN') {
            return $this->failure('Không tìm thấy tài khoản huấn luyện viên.', 404);
        }

        if ((string) $account['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chỉ được duyệt tài khoản đang ở trạng thái chờ duyệt.', 409);
        }

        try {
            $this->accounts->updateAccount($accountId, ['trangthai' => 'HOAT_DONG']);

            $this->accounts->recordStatusHistory(
                'TAI_KHOAN',
                $accountId,
                'CHO_DUYET',
                'HOAT_DONG',
                'Ban tổ chức duyệt tài khoản HLV',
                $organizerId
            );

            $this->accounts->recordSystemLog(
                $organizerId,
                'DUYET_TAI_KHOAN',
                'Taikhoan',
                $accountId,
                $request?->ip(),
                sprintf('Ban tổ chức duyệt tài khoản HLV "%s".', (string) $account['username'])
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Duyệt tài khoản huấn luyện viên thành công.',
                'account' => $this->accounts->findById($accountId),
            ];
        } catch (Throwable) {
            return $this->failure('Không thể duyệt tài khoản huấn luyện viên.', 500);
        }
    }

    public function reject(int $accountId, int $organizerId, ?Request $request = null): array
    {
        $permission = $this->nationalFederationOrganizer($organizerId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $account = $this->accounts->findById($accountId);

        if ($account === null || (string) $account['role'] !== 'HUAN_LUYEN_VIEN') {
            return $this->failure('Không tìm thấy tài khoản huấn luyện viên.', 404);
        }

        if ((string) $account['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chỉ được từ chối tài khoản đang ở trạng thái chờ duyệt.', 409);
        }

        try {
            $this->accounts->updateAccount($accountId, ['trangthai' => 'DA_HUY']);

            $this->accounts->recordStatusHistory(
                'TAI_KHOAN',
                $accountId,
                'CHO_DUYET',
                'DA_HUY',
                'Ban tổ chức từ chối tài khoản HLV',
                $organizerId
            );

            $this->accounts->recordSystemLog(
                $organizerId,
                'TU_CHOI_TAI_KHOAN',
                'Taikhoan',
                $accountId,
                $request?->ip(),
                sprintf('Ban tổ chức từ chối tài khoản HLV "%s".', (string) $account['username'])
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Từ chối tài khoản huấn luyện viên thành công.',
                'account' => $this->accounts->findById($accountId),
            ];
        } catch (Throwable) {
            return $this->failure('Không thể từ chối tài khoản huấn luyện viên.', 500);
        }
    }

    private function nationalFederationOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) ($organizer['role'] ?? '') !== 'BAN_TO_CHUC') {
            return $this->failure('Chi tai khoan ban to chuc moi duoc duyet tai khoan HLV.', 403);
        }

        $isActive = (string) ($organizer['trangthai'] ?? '') === 'HOAT_DONG'
            && (string) ($organizer['trangthai_donvi'] ?? '') === 'HOAT_DONG'
            && (string) ($organizer['trangthai_loaidonvi'] ?? '') === 'HOAT_DONG';
        $isHighestFederationLevel = (int) ($organizer['idcapgiaidau_quanly'] ?? 0) > 0
            && array_key_exists('idcapgiaidau_cha_quanly', $organizer)
            && $organizer['idcapgiaidau_cha_quanly'] === null
            && (string) ($organizer['maloaidonvi'] ?? '') === 'LIEN_DOAN_BONG_CHUYEN_VN';

        if (!$isActive || !$isHighestFederationLevel) {
            return $this->failure('Chi BTC cap quoc gia thuoc Lien doan Bong chuyen VN duoc duyet tai khoan HLV.', 403);
        }

        return $organizer;
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

