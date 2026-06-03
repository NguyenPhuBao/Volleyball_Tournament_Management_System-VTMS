<?php

declare(strict_types=1);

namespace App\Backend\Services\Coach;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Vandongvien;
use Throwable;

final class CoachAthleteAccountService
{
    private const GENDERS = ['NAM', 'NU', 'KHAC'];
    private const POSITIONS = ['CHU_CONG', 'PHU_CONG', 'CHUYEN_HAI', 'DOI_CHUYEN', 'LIBERO', 'DOI_TRU'];
    private const TEAM_ROLES = ['DOI_TRUONG', 'THANH_VIEN', 'DU_BI'];
    private const TEAM_MEMBER_STATUSES = ['CHO_XAC_NHAN', 'DANG_THAM_GIA'];

    public function __construct(
        private ?Vandongvien $athletes = null
    ) {
        $this->athletes ??= new Vandongvien();
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->athletes->coachByAccountId($accountId);

        if ($coach === null) {
            return $this->failure('Tai khoan khong co ho so huan luyen vien.', 403);
        }

        if ((string) $coach['trangthai'] !== 'DA_XAC_NHAN') {
            return $this->failure('Chi huan luyen vien da duoc xac nhan moi duoc tao tai khoan van dong vien.', 403);
        }

        [$account, $profile, $athlete, $membership, $errors] = $this->validatePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu van dong vien khong hop le.', 422, $errors);
        }

        $roleId = $this->athletes->roleIdByName('VAN_DONG_VIEN');

        if ($roleId === null) {
            return $this->failure('Khong tim thay vai tro VAN_DONG_VIEN trong he thong.', 500);
        }

        $team = null;
        if ($membership['team_id'] !== null) {
            $team = $this->athletes->teamForCoach((int) $coach['idhuanluyenvien'], $membership['team_id']);

            if ($team === null) {
                return $this->failure('Khong tim thay doi bong cua huan luyen vien.', 404, [
                    'team_id' => 'Doi bong khong ton tai hoac khong thuoc HLV dang dang nhap.',
                ]);
            }

            if ((string) $team['trangthai'] !== 'HOAT_DONG') {
                return $this->failure('Chi duoc them van dong vien vao doi bong dang hoat dong.', 409);
            }
        }

        if ($athlete['mavandongvien'] === null) {
            $athlete['mavandongvien'] = $this->generateAthleteCode();
        } elseif ($this->athletes->athleteCodeExists($athlete['mavandongvien'])) {
            return $this->failure('Ma van dong vien da ton tai.', 422, [
                'mavandongvien' => 'Ma van dong vien da ton tai.',
            ]);
        }

        $account['idrole'] = $roleId;
        $account['password'] = password_hash((string) $account['password'], PASSWORD_DEFAULT);

        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d tao truc tiep tai khoan VDV "%s %s"%s.',
            (int) $coach['idhuanluyenvien'],
            $profile['hodem'],
            $profile['ten'],
            $team === null ? '' : sprintf(' cho doi #%d "%s"', (int) $team['iddoibong'], (string) $team['tendoibong'])
        ));

        try {
            $created = $this->athletes->createAthleteAccount(
                $account,
                $profile,
                $athlete,
                $membership,
                (int) $coach['idhuanluyenvien'],
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao tai khoan van dong vien thanh cong.',
                'created' => $created,
                'athlete' => $this->athletes->findByAccountId((int) $created['account_id']),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao tai khoan van dong vien.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    private function validatePayload(array $payload): array
    {
        $username = trim((string) $this->read($payload, ['username', 'account.username', 'ten_dang_nhap']));
        $email = strtolower(trim((string) $this->read($payload, ['email', 'account.email'])));
        $phone = trim((string) $this->read($payload, ['phone', 'sodienthoai', 'so_dien_thoai', 'account.phone', 'account.sodienthoai']));
        $password = (string) $this->read($payload, ['password', 'account.password']);
        $passwordConfirmation = (string) $this->read($payload, ['password_confirmation', 'confirm_password', 'account.password_confirmation'], '');

        $lastName = trim((string) $this->read($payload, ['hodem', 'ho_dem', 'profile.hodem', 'profile.ho_dem']));
        $firstName = trim((string) $this->read($payload, ['ten', 'profile.ten']));
        $gender = strtoupper(trim((string) $this->read($payload, ['gioitinh', 'gender', 'profile.gioitinh', 'profile.gender'])));
        $dob = trim((string) $this->read($payload, ['ngaysinh', 'dob', 'profile.ngaysinh', 'profile.dob']));
        $hometown = trim((string) $this->read($payload, ['quequan', 'hometown', 'profile.quequan', 'profile.hometown']));
        $address = trim((string) $this->read($payload, ['diachi', 'address', 'profile.diachi', 'profile.address']));
        $avatar = trim((string) $this->read($payload, ['avatar', 'profile.avatar']));
        $identityNumber = trim((string) $this->read($payload, ['cccd', 'identity_number', 'profile.cccd']));

        $code = strtoupper(trim((string) $this->read($payload, ['mavandongvien', 'code', 'athlete_code', 'athlete.mavandongvien'])));
        $height = $this->nullablePositiveFloat($this->read($payload, ['chieucao', 'height', 'athlete.chieucao', 'athlete.height']));
        $weight = $this->nullablePositiveFloat($this->read($payload, ['cannang', 'weight', 'athlete.cannang', 'athlete.weight']));
        $position = strtoupper(trim((string) $this->read($payload, ['vitri', 'position', 'athlete.vitri', 'athlete.position'])));

        $teamIdRaw = $this->read($payload, ['team_id', 'iddoibong', 'membership.team_id', 'membership.iddoibong'], null);
        $teamRole = strtoupper(trim((string) $this->read($payload, ['team_role', 'vaitrotrongdoi', 'membership.role', 'membership.vaitro'], 'THANH_VIEN')));
        $memberStatus = strtoupper(trim((string) $this->read($payload, ['membership_status', 'trangthaithanhvien', 'membership.status', 'membership.trangthai'], 'DANG_THAM_GIA')));
        $joinDate = trim((string) $this->read($payload, ['ngaythamgia', 'join_date', 'membership.ngaythamgia', 'membership.join_date'], date('Y-m-d')));

        $errors = [];

        if ($username === '') {
            $errors['username'] = 'Vui long nhap username.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $username)) {
            $errors['username'] = 'Username chi gom chu, so, dau gach duoi, dau cham hoac dau gach ngang va dai 3-100 ky tu.';
        } elseif ($this->athletes->accountValueExists('username', $username)) {
            $errors['username'] = 'Username da ton tai.';
        }

        if ($email === '') {
            $errors['email'] = 'Vui long nhap email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors['email'] = 'Email khong hop le.';
        } elseif ($this->athletes->accountValueExists('email', $email)) {
            $errors['email'] = 'Email da ton tai.';
        }

        if ($phone !== '') {
            if (strlen($phone) > 20) {
                $errors['phone'] = 'So dien thoai toi da 20 ky tu.';
            } elseif ($this->athletes->accountValueExists('sodienthoai', $phone)) {
                $errors['phone'] = 'So dien thoai da ton tai.';
            }
        }

        if ($password === '') {
            $errors['password'] = 'Vui long nhap mat khau.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Mat khau toi thieu 6 ky tu.';
        }

        if ($passwordConfirmation !== '' && $passwordConfirmation !== $password) {
            $errors['password_confirmation'] = 'Xac nhan mat khau khong khop.';
        }

        if ($lastName === '') {
            $errors['hodem'] = 'Vui long nhap ho dem.';
        } elseif (strlen($lastName) > 200) {
            $errors['hodem'] = 'Ho dem toi da 200 ky tu.';
        }

        if ($firstName === '') {
            $errors['ten'] = 'Vui long nhap ten.';
        } elseif (strlen($firstName) > 100) {
            $errors['ten'] = 'Ten toi da 100 ky tu.';
        }

        if ($gender === '' || !in_array($gender, self::GENDERS, true)) {
            $errors['gioitinh'] = 'Gioi tinh khong hop le.';
        }

        if ($dob !== '' && !$this->isDate($dob)) {
            $errors['ngaysinh'] = 'Ngay sinh khong hop le.';
        }

        if ($identityNumber !== '') {
            if (strlen($identityNumber) > 20) {
                $errors['cccd'] = 'CCCD toi da 20 ky tu.';
            } elseif ($this->athletes->profileValueExists('cccd', $identityNumber)) {
                $errors['cccd'] = 'CCCD da ton tai.';
            }
        }

        if ($hometown !== '' && strlen($hometown) > 500) {
            $errors['quequan'] = 'Que quan toi da 500 ky tu.';
        }

        if ($address !== '' && strlen($address) > 500) {
            $errors['diachi'] = 'Dia chi toi da 500 ky tu.';
        }

        if ($avatar !== '' && strlen($avatar) > 500) {
            $errors['avatar'] = 'Avatar toi da 500 ky tu.';
        }

        if ($code !== '' && strlen($code) > 100) {
            $errors['mavandongvien'] = 'Ma van dong vien toi da 100 ky tu.';
        }

        if ($height['error'] !== null) {
            $errors['chieucao'] = $height['error'];
        }

        if ($weight['error'] !== null) {
            $errors['cannang'] = $weight['error'];
        }

        if ($position === '' || !in_array($position, self::POSITIONS, true)) {
            $errors['vitri'] = 'Vi tri thi dau khong hop le.';
        }

        $teamId = null;
        if ($teamIdRaw !== null && trim((string) $teamIdRaw) !== '') {
            if (!ctype_digit((string) $teamIdRaw) || (int) $teamIdRaw <= 0) {
                $errors['team_id'] = 'Doi bong khong hop le.';
            } else {
                $teamId = (int) $teamIdRaw;
            }
        }

        if (!in_array($teamRole, self::TEAM_ROLES, true)) {
            $errors['team_role'] = 'Vai tro trong doi khong hop le.';
        }

        if (!in_array($memberStatus, self::TEAM_MEMBER_STATUSES, true)) {
            $errors['membership_status'] = 'Trang thai thanh vien doi bong khong hop le.';
        }

        if ($joinDate !== '' && !$this->isDate($joinDate)) {
            $errors['ngaythamgia'] = 'Ngay tham gia khong hop le.';
        }

        return [[
            'username' => $username,
            'email' => $email,
            'sodienthoai' => $phone === '' ? null : $phone,
            'password' => $password,
        ], [
            'ten' => $firstName,
            'hodem' => $lastName,
            'gioitinh' => $gender,
            'ngaysinh' => $dob === '' ? null : $dob,
            'quequan' => $hometown === '' ? null : $hometown,
            'diachi' => $address === '' ? null : $address,
            'avatar' => $avatar === '' ? null : $avatar,
            'cccd' => $identityNumber === '' ? null : $identityNumber,
        ], [
            'mavandongvien' => $code === '' ? null : $code,
            'chieucao' => $height['value'],
            'cannang' => $weight['value'],
            'vitri' => $position,
        ], [
            'team_id' => $teamId,
            'role' => $teamRole,
            'status' => $memberStatus,
            'join_date' => $joinDate === '' ? date('Y-m-d') : $joinDate,
        ], $errors];
    }

    private function nullablePositiveFloat(mixed $raw): array
    {
        $text = trim((string) ($raw ?? ''));

        if ($text === '') {
            return ['value' => null, 'error' => null];
        }

        if (!is_numeric($text) || (float) $text <= 0) {
            return ['value' => null, 'error' => 'Gia tri phai la so > 0.'];
        }

        return ['value' => (float) $text, 'error' => null];
    }

    private function generateAthleteCode(): string
    {
        do {
            $code = 'VDV' . date('YmdHis') . random_int(10, 99);
        } while ($this->athletes->athleteCodeExists($code));

        return $code;
    }

    private function read(array $payload, array $keys, mixed $default = ''): mixed
    {
        foreach ($keys as $key) {
            if (!str_contains($key, '.')) {
                if (array_key_exists($key, $payload)) {
                    return $payload[$key];
                }

                continue;
            }

            $segments = explode('.', $key);
            $current = $payload;
            foreach ($segments as $segment) {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    continue 2;
                }

                $current = $current[$segment];
            }

            return $current;
        }

        return $default;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
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

