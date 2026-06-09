<?php

namespace App\Services\Organizer;

use App\Repositories\Organizer\OrganizerRefereeAccountRepository;
use App\Repositories\Organizer\OrganizerRepository;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

final class OrganizerRefereeAccountService
{
    private const STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];

    public function __construct(
        private readonly OrganizerRefereeAccountRepository $accounts,
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
            'message' => 'Lay danh sach tai khoan trong tai thanh cong.',
            'accounts' => $this->accounts->listRefereeAccounts($normalized),
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
            'message' => 'BTC cap quoc gia thuoc Lien doan Bong chuyen VN duoc duyet tai khoan trong tai.',
        ];
    }

    public function find(int $accountId, int $organizerAccountId): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $account = $this->accounts->findRefereeAccountById($accountId);

        if ($account === null) {
            return $this->failure('Khong tim thay tai khoan trong tai.', 404);
        }

        $referee = $this->accounts->findRefereeByAccountId($accountId);

        if ($referee !== null) {
            $account['referee'] = $referee;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin tai khoan trong tai thanh cong.',
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

        $account = $this->accounts->findRefereeAccountById($accountId);

        if ($account === null) {
            return $this->failure('Khong tim thay tai khoan trong tai.', 404);
        }

        if ((string) $account['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc xu ly tai khoan trong tai dang o trang thai cho duyet.', 409);
        }

        $referee = $this->accounts->findRefereeByAccountId($accountId);

        if ($referee === null) {
            return $this->failure('Tai khoan chua co ho so trong tai.', 409);
        }

        if ((string) $referee['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc xu ly ho so trong tai dang cho duyet.', 409);
        }

        $confirmation = $this->accounts->latestRegistrationRequest((int) $referee['idtrongtai'], (int) $permission['idbantochuc']);
        $requestId = $confirmation !== null && (string) $confirmation['trangthai'] === 'CHO_DUYET'
            ? (int) $confirmation['idyeucau']
            : null;

        $newRefereeStatus = $approved ? 'HOAT_DONG' : 'NGUNG_HOAT_DONG';
        $newAccountStatus = $approved ? 'HOAT_DONG' : 'DA_HUY';
        $requestStatus = $approved ? 'DA_DUYET' : 'TU_CHOI';
        $reason = $approved ? 'Ban to chuc duyet tai khoan trong tai' : 'Ban to chuc tu choi tai khoan trong tai';
        $systemAction = $approved ? 'Duyet tai khoan trong tai' : 'Tu choi tai khoan trong tai';
        $logNote = $this->limitLogNote(sprintf(
            'BTC Lien doan Bong chuyen VN #%d %s tai khoan trong tai "%s".',
            (int) $permission['idbantochuc'],
            $approved ? 'duyet' : 'tu choi',
            (string) $account['username']
        ));

        try {
            $this->accounts->updateRegistrationReview(
                (int) $referee['idtrongtai'],
                $accountId,
                $newRefereeStatus,
                $newAccountStatus,
                $requestId,
                $requestId === null ? null : $requestStatus,
                $reason,
                $organizerAccountId,
                $request?->ip(),
                $systemAction,
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => $approved
                    ? 'Duyet tai khoan trong tai thanh cong.'
                    : 'Tu choi tai khoan trong tai thanh cong.',
                'account' => $this->accounts->findRefereeAccountById($accountId),
            ];
        } catch (RuntimeException $exception) {
            if (in_array($exception->getMessage(), ['REFEREE_REGISTRATION_NOT_UPDATED', 'REFEREE_ACCOUNT_NOT_UPDATED'], true)) {
                return $this->failure('Trang thai tai khoan trong tai da thay doi, khong the xu ly.', 409);
            }

            return $this->failure('Khong the xu ly tai khoan trong tai.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xu ly tai khoan trong tai.', 500);
        }
    }

    private function nationalFederationOrganizer(int $accountId): array
    {
        $organizer = $this->organizers->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) ($organizer['role'] ?? '') !== 'BAN_TO_CHUC') {
            return $this->failure('Chi tai khoan ban to chuc moi duoc duyet tai khoan trong tai.', 403);
        }

        $isActive = (string) ($organizer['trangthai'] ?? '') === 'HOAT_DONG'
            && (string) ($organizer['trangthai_donvi'] ?? '') === 'HOAT_DONG'
            && (string) ($organizer['trangthai_loaidonvi'] ?? '') === 'HOAT_DONG';
        $isHighestFederationLevel = (int) ($organizer['idcapgiaidau_quanly'] ?? 0) > 0
            && array_key_exists('idcapgiaidau_cha_quanly', $organizer)
            && $organizer['idcapgiaidau_cha_quanly'] === null
            && (string) ($organizer['maloaidonvi'] ?? '') === 'LIEN_DOAN_BONG_CHUYEN_VN';

        if (!$isActive || !$isHighestFederationLevel) {
            return $this->failure('Chi BTC cap quoc gia thuoc Lien doan Bong chuyen VN duoc duyet tai khoan trong tai.', 403);
        }

        return $organizer;
    }

    private function limitLogNote(string $note): string
    {
        if (strlen($note) <= 1000) {
            return $note;
        }

        return substr($note, 0, 997).'...';
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
