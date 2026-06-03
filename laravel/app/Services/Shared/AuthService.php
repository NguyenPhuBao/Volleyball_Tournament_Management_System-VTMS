<?php

namespace App\Services\Shared;

use App\Repositories\AccountRepository;
use App\Support\LegacySessionUser;
use Illuminate\Http\Request;

final class AuthService
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    public function attempt(string $username, string $password, ?Request $request = null): array
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return $this->result(false, 'Vui long nhap ten dang nhap va mat khau.', 422);
        }

        $account = $this->accounts->findByIdentifier($username);

        if ($account === null) {
            return $this->result(false, 'Ten dang nhap hoac mat khau khong dung.', 401);
        }

        $accountId = (int) $account['idtaikhoan'];

        if ((string) $account['trangthai'] !== 'HOAT_DONG') {
            $this->accounts->recordLoginHistory($accountId, 'THAT_BAI', $request?->ip(), $request?->userAgent(), 'Tai khoan khong hoat dong');

            return $this->result(false, 'Tai khoan khong duoc phep dang nhap.', 403);
        }

        if (!password_verify($password, (string) $account['password'])) {
            $this->accounts->recordLoginHistory($accountId, 'THAT_BAI', $request?->ip(), $request?->userAgent(), 'Sai mat khau');

            return $this->result(false, 'Ten dang nhap hoac mat khau khong dung.', 401);
        }

        $sessionToken = bin2hex(random_bytes(32));
        $this->accounts->createLoginSession($accountId, $sessionToken);
        $this->accounts->recordLoginHistory($accountId, 'THANH_CONG', $request?->ip(), $request?->userAgent(), 'Dang nhap thanh cong');

        $user = $this->sessionUser($account);
        LegacySessionUser::login($user, $sessionToken);

        return $this->result(true, 'Dang nhap thanh cong.', 200, $user);
    }

    public function logout(): void
    {
        $sessionToken = LegacySessionUser::sessionToken();

        if ($sessionToken !== null) {
            $this->accounts->closeLoginSession($sessionToken);
        }

        LegacySessionUser::logout();
    }

    private function sessionUser(array $account): array
    {
        $name = trim((string) (($account['hodem'] ?? '').' '.($account['ten'] ?? '')));

        $user = [
            'id' => (int) $account['idtaikhoan'],
            'username' => (string) $account['username'],
            'name' => $name !== '' ? $name : (string) $account['username'],
            'email' => (string) $account['email'],
            'role' => (string) $account['role'],
        ];

        if ((string) $account['role'] === 'HUAN_LUYEN_VIEN') {
            $coachId = isset($account['idhuanluyenvien']) ? (int) $account['idhuanluyenvien'] : 0;
            $workRegionId = isset($account['idkhuvuccongtac']) ? (int) $account['idkhuvuccongtac'] : 0;

            $user['idhuanluyenvien'] = $coachId > 0 ? $coachId : null;
            $user['idkhuvuccongtac'] = $workRegionId > 0 ? $workRegionId : null;
            $user['coach'] = [
                'idhuanluyenvien' => $user['idhuanluyenvien'],
                'idkhuvuccongtac' => $user['idkhuvuccongtac'],
            ];
        }

        if ((string) $account['role'] === 'BAN_TO_CHUC') {
            $organizerId = isset($account['idbantochuc']) ? (int) $account['idbantochuc'] : 0;
            $unitCanOrganize = (int) ($account['duoc_to_chuc_giai_bantochuc'] ?? 0) === 1;
            $organizerActive = (string) ($account['trangthai_bantochuc'] ?? '') === 'HOAT_DONG';
            $unitActive = (string) ($account['trangthai_donvi_bantochuc'] ?? '') === 'HOAT_DONG';
            $unitTypeActive = (string) ($account['trangthai_loaidonvi_bantochuc'] ?? '') === 'HOAT_DONG';
            $unitTypeCode = (string) ($account['maloaidonvi_bantochuc'] ?? '');
            $regionLevel = (string) ($account['capkhuvuc_bantochuc'] ?? '');
            $isHighestOrganizerLevel = (int) ($account['idcapgiaidau_bantochuc'] ?? 0) > 0
                && array_key_exists('idcapgiaidau_cha_bantochuc', $account)
                && $account['idcapgiaidau_cha_bantochuc'] === null;
            $hasOrganizerAuthority = $organizerId > 0 && $organizerActive && $unitActive && $unitTypeActive && $unitCanOrganize;

            $user['organizer'] = [
                'idbantochuc' => $organizerId > 0 ? $organizerId : null,
                'idkhuvucquanly' => isset($account['idkhuvucquanly_bantochuc']) ? (int) $account['idkhuvucquanly_bantochuc'] : null,
                'capkhuvucquanly' => $regionLevel,
                'iddonvi' => isset($account['iddonvi_bantochuc']) ? (int) $account['iddonvi_bantochuc'] : null,
                'madonvi' => (string) ($account['madonvi_bantochuc'] ?? ''),
                'tendonvi' => (string) ($account['tendonvi_bantochuc'] ?? ''),
                'maloaidonvi' => $unitTypeCode,
                'can_higher_eligibility' => $hasOrganizerAuthority,
                'can_approve_coach_accounts' => $hasOrganizerAuthority
                    && $isHighestOrganizerLevel
                    && $unitTypeCode === 'LIEN_DOAN_BONG_CHUYEN_VN',
            ];
        }

        return $user;
    }

    private function result(bool $ok, string $message, int $status, ?array $user = null): array
    {
        return [
            'ok' => $ok,
            'message' => $message,
            'status' => $status,
            'user' => $user,
        ];
    }
}
