<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Taikhoan;
use App\Backend\Models\Trongtai;
use RuntimeException;
use Throwable;

final class OrganizerRefereeAccountService
{
    public function __construct(
        private ?Taikhoan $accounts = null,
        private ?Trongtai $referees = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->accounts ??= new Taikhoan();
        $this->referees ??= new Trongtai();
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
            'role' => 'TRONG_TAI',
        ];

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lấy danh sách tài khoản trọng tài thành công.',
            'accounts' => $this->accounts->listAccounts($normalized),
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
            'message' => 'BTC cap quoc gia thuoc Lien doan Bong chuyen VN duoc duyet tai khoan trong tai.',
        ];
    }

    public function find(int $accountId, int $organizerAccountId): array
    {
        $permission = $this->nationalFederationOrganizer($organizerAccountId);

        if (isset($permission['ok']) && $permission['ok'] === false) {
            return $permission;
        }

        $account = $this->accounts->findById($accountId);

        if ($account === null || (string) $account['role'] !== 'TRONG_TAI') {
            return $this->failure('Không tìm thấy tài khoản trọng tài.', 404);
        }

        $referee = $this->referees->findByAccountId($accountId);

        if ($referee !== null) {
            $account['referee'] = $referee;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lấy thông tin tài khoản trọng tài thành công.',
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

        $account = $this->accounts->findById($accountId);

        if ($account === null || (string) $account['role'] !== 'TRONG_TAI') {
            return $this->failure('Không tìm thấy tài khoản trọng tài.', 404);
        }

        if ((string) $account['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chỉ được xử lý tài khoản trọng tài đang ở trạng thái chờ duyệt.', 409);
        }

        $referee = $this->referees->findByAccountId($accountId);

        if ($referee === null) {
            return $this->failure('Tài khoản chưa có hồ sơ trọng tài.', 409);
        }

        if ((string) $referee['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chỉ được xử lý hồ sơ trọng tài đang chờ duyệt.', 409);
        }

        $confirmation = $this->referees->latestRegistrationRequest(
            (int) $referee['idtrongtai'],
            (int) $permission['idbantochuc']
        );
        $requestId = $confirmation !== null && (string) $confirmation['trangthai'] === 'CHO_DUYET'
            ? (int) $confirmation['idyeucau']
            : null;

        $newRefereeStatus = $approved ? 'HOAT_DONG' : 'NGUNG_HOAT_DONG';
        $newAccountStatus = $approved ? 'HOAT_DONG' : 'DA_HUY';
        $requestStatus = $approved ? 'DA_DUYET' : 'TU_CHOI';
        $reason = $approved ? 'Ban tổ chức duyệt tài khoản trọng tài' : 'Ban tổ chức từ chối tài khoản trọng tài';
        $systemAction = $approved ? 'Duyet tai khoan trong tai' : 'Tu choi tai khoan trong tai';
        $logNote = $this->limitLogNote(sprintf(
            'BTC Lien doan Bong chuyen VN #%d %s tai khoan trong tai "%s".',
            (int) $permission['idbantochuc'],
            $approved ? 'duyet' : 'tu choi',
            (string) $account['username']
        ));

        try {
            $this->referees->updateRegistrationReview(
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
                    ? 'Duyệt tài khoản trọng tài thành công.'
                    : 'Từ chối tài khoản trọng tài thành công.',
                'account' => $this->accounts->findById($accountId),
            ];
        } catch (RuntimeException $exception) {
            if (in_array($exception->getMessage(), ['REFEREE_REGISTRATION_NOT_UPDATED', 'REFEREE_ACCOUNT_NOT_UPDATED'], true)) {
                return $this->failure('Trạng thái tài khoản trọng tài đã thay đổi, không thể xử lý.', 409);
            }

            return $this->failure('Không thể xử lý tài khoản trọng tài.', 500);
        } catch (Throwable) {
            return $this->failure('Không thể xử lý tài khoản trọng tài.', 500);
        }
    }

    private function nationalFederationOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

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
