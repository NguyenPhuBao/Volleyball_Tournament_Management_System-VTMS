<?php

namespace App\Services\Organizer;

use App\Repositories\Organizer\OrganizerCoachAccountRepository;
use App\Repositories\Organizer\OrganizerRepository;
use Illuminate\Http\Request;
use Throwable;

final class OrganizerCoachAccountService
{
    private const STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];

    public function __construct(
        private readonly OrganizerCoachAccountRepository $accounts,
        private readonly OrganizerRepository $organizers
    ) {
    }

    public function all(int $organizerAccountId, array $filters = []): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'trangthai' => strtoupper(trim((string) ($filters['trangthai'] ?? $filters['status'] ?? ''))),
        ];

        if ($normalized['trangthai'] !== '' && !in_array($normalized['trangthai'], self::STATUSES, true)) {
            $normalized['trangthai'] = '';
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach tai khoan huan luyen vien thanh cong.',
            'accounts' => $this->accounts->listCoachAccounts($normalized),
            'meta' => [
                'filters' => $normalized,
                'statuses' => self::STATUSES,
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

        $account = $this->accounts->findCoachAccountById($accountId);

        if ($account === null) {
            return $this->failure('Khong tim thay tai khoan huan luyen vien.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin tai khoan thanh cong.',
            'account' => $account,
        ];
    }

    public function approve(int $accountId, int $organizerAccountId, ?Request $request = null): array
    {
        return $this->review($accountId, $organizerAccountId, true, $request);
    }

    public function reject(int $accountId, int $organizerAccountId, ?Request $request = null): array
    {
        return $this->review($accountId, $organizerAccountId, false, $request);
    }

    private function review(int $accountId, int $organizerAccountId, bool $approved, ?Request $request): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $account = $this->accounts->findCoachAccountById($accountId);

        if ($account === null) {
            return $this->failure('Khong tim thay tai khoan huan luyen vien.', 404);
        }

        if ((string) $account['trangthai'] !== 'CHO_DUYET') {
            return $this->failure(
                $approved
                    ? 'Chi duoc duyet tai khoan dang o trang thai cho duyet.'
                    : 'Chi duoc tu choi tai khoan dang o trang thai cho duyet.',
                409
            );
        }

        $newStatus = $approved ? 'HOAT_DONG' : 'DA_HUY';
        $historyReason = $approved ? 'Ban to chuc duyet tai khoan HLV' : 'Ban to chuc tu choi tai khoan HLV';
        $logAction = $approved ? 'DUYET_TAI_KHOAN' : 'TU_CHOI_TAI_KHOAN';
        $logNote = sprintf(
            $approved ? 'Ban to chuc duyet tai khoan HLV "%s".' : 'Ban to chuc tu choi tai khoan HLV "%s".',
            (string) $account['username']
        );

        try {
            $this->accounts->updateAccountStatus($accountId, $newStatus);
            $this->accounts->recordStatusHistory('TAI_KHOAN', $accountId, 'CHO_DUYET', $newStatus, $historyReason, $organizerAccountId);
            $this->accounts->recordSystemLog($organizerAccountId, $logAction, 'Taikhoan', $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => $approved
                    ? 'Duyet tai khoan huan luyen vien thanh cong.'
                    : 'Tu choi tai khoan huan luyen vien thanh cong.',
                'account' => $this->accounts->findCoachAccountById($accountId),
            ];
        } catch (Throwable) {
            return $this->failure(
                $approved
                    ? 'Khong the duyet tai khoan huan luyen vien.'
                    : 'Khong the tu choi tai khoan huan luyen vien.',
                500
            );
        }
    }

    private function nationalFederationOrganizer(int $accountId): array
    {
        $organizer = $this->organizers->findOrganizerByAccountId($accountId);

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
