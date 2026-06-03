<?php

namespace App\Services\PublicSite;

use App\Repositories\PublicSite\CoachRegistrationRepository;
use Illuminate\Http\Request;
use Throwable;

final class CoachRegistrationService
{
    private const GENDERS = ['NAM', 'NU', 'KHAC'];

    public function __construct(private readonly CoachRegistrationRepository $coaches)
    {
    }

    public function options(): array
    {
        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh muc dang ky huan luyen vien thanh cong.',
            'options' => [
                'work_regions' => $this->coaches->activeWorkRegions(),
            ],
        ];
    }

    public function register(array $payload, ?Request $request = null): array
    {
        [$account, $profile, $coach, $confirmation, $errors] = $this->validatePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu dang ky huan luyen vien khong hop le.', 422, $errors);
        }

        $roleId = $this->coaches->roleIdByName('HUAN_LUYEN_VIEN');

        if ($roleId === null) {
            return $this->failure('Khong tim thay vai tro HUAN_LUYEN_VIEN trong he thong.', 500);
        }

        $organizer = $this->coaches->nationalFederationOrganizer();

        if ($organizer === null) {
            return $this->failure('Khong tim thay BTC cap quoc gia thuoc Lien doan Bong chuyen VN de tiep nhan yeu cau.', 409, [
                'organizer_id' => 'Yeu cau dang ky HLV chi duoc gui den BTC Lien doan Bong chuyen VN.',
            ]);
        }

        $account['idrole'] = $roleId;
        $account['password'] = password_hash((string) $account['password'], PASSWORD_DEFAULT);
        $confirmation['organizer_id'] = (int) $organizer['idbantochuc'];

        $logNote = $this->limitLogNote(sprintf(
            'HLV "%s %s" dang ky tai khoan tai khu vuc #%d, gui yeu cau xac nhan tu cach den BTC #%d.',
            $profile['hodem'],
            $profile['ten'],
            (int) $coach['idkhuvuccongtac'],
            $confirmation['organizer_id']
        ));

        try {
            $created = $this->coaches->registerAccount(
                $account,
                $profile,
                $coach,
                $confirmation,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Dang ky tai khoan huan luyen vien thanh cong, cho ban to chuc xac nhan.',
                'registration' => $created,
                'coach' => $this->coaches->findForOrganizer($confirmation['organizer_id'], (int) $created['coach_id']),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the dang ky tai khoan huan luyen vien.', 500, [
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

        $degree = trim((string) $this->read($payload, ['bangcap', 'degree', 'coach.bangcap', 'coach.degree']));
        $workUnit = trim((string) $this->read($payload, ['donvicongtac', 'work_unit', 'club', 'coach.donvicongtac', 'coach.work_unit']));
        $workRegionRaw = $this->read($payload, ['idkhuvuccongtac', 'work_region_id', 'coach.idkhuvuccongtac', 'coach.work_region_id'], null);
        $experienceRaw = $this->read($payload, ['kinhnghiem', 'experience', 'coach.kinhnghiem', 'coach.experience'], 0);
        $experienceRaw = trim((string) $experienceRaw) === '' ? 0 : $experienceRaw;
        $organizerRaw = $this->read($payload, ['organizer_id', 'idbantochuc', 'receiver_organizer_id'], null);
        $content = trim((string) $this->read($payload, ['noidung', 'content', 'request_content'], 'Yeu cau xac nhan tu cach HLV'));

        $errors = [];

        if ($username === '') {
            $errors['username'] = 'Vui long nhap username.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $username)) {
            $errors['username'] = 'Username chi gom chu, so, dau gach duoi, dau cham hoac dau gach ngang va dai 3-100 ky tu.';
        } elseif ($this->coaches->accountValueExists('username', $username)) {
            $errors['username'] = 'Username da ton tai.';
        }

        if ($email === '') {
            $errors['email'] = 'Vui long nhap email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors['email'] = 'Email khong hop le.';
        } elseif ($this->coaches->accountValueExists('email', $email)) {
            $errors['email'] = 'Email da ton tai.';
        }

        if ($phone !== '') {
            if (strlen($phone) > 20) {
                $errors['phone'] = 'So dien thoai toi da 20 ky tu.';
            } elseif ($this->coaches->accountValueExists('sodienthoai', $phone)) {
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
            } elseif ($this->coaches->profileValueExists('cccd', $identityNumber)) {
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

        if ($degree !== '' && strlen($degree) > 300) {
            $errors['bangcap'] = 'Bang cap toi da 300 ky tu.';
        }

        $workRegionId = null;
        if ($workRegionRaw === null || trim((string) $workRegionRaw) === '') {
            $errors['idkhuvuccongtac'] = 'Vui long chon khu vuc cong tac.';
        } elseif (!ctype_digit((string) $workRegionRaw) || (int) $workRegionRaw <= 0) {
            $errors['idkhuvuccongtac'] = 'Khu vuc cong tac khong hop le.';
        } else {
            $workRegionId = (int) $workRegionRaw;
            if ($this->coaches->activeWorkRegion($workRegionId) === null) {
                $errors['idkhuvuccongtac'] = 'Khu vuc cong tac khong ton tai hoac da ngung su dung.';
            }
        }

        if ($workUnit === '') {
            $errors['donvicongtac'] = 'Vui long nhap don vi hoac cau lac bo cong tac.';
        } elseif (strlen($workUnit) > 300) {
            $errors['donvicongtac'] = 'Don vi cong tac toi da 300 ky tu.';
        }

        if (filter_var($experienceRaw, FILTER_VALIDATE_INT) === false || (int) $experienceRaw < 0) {
            $errors['kinhnghiem'] = 'Kinh nghiem phai la so nguyen >= 0.';
        }

        $organizerId = null;
        if ($organizerRaw !== null && trim((string) $organizerRaw) !== '') {
            if (!ctype_digit((string) $organizerRaw) || (int) $organizerRaw <= 0) {
                $errors['organizer_id'] = 'Ma ban to chuc tiep nhan khong hop le.';
            } else {
                $organizerId = (int) $organizerRaw;
            }
        }

        if ($content === '') {
            $content = 'Yeu cau xac nhan tu cach HLV';
        }

        if (strlen($content) > 1000) {
            $errors['noidung'] = 'Noi dung yeu cau toi da 1000 ky tu.';
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
            'idkhuvuccongtac' => $workRegionId,
            'donvicongtac' => $workUnit,
            'bangcap' => $degree === '' ? null : $degree,
            'kinhnghiem' => (int) $experienceRaw,
        ], [
            'organizer_id' => $organizerId,
            'content' => $content,
        ], $errors];
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
        return strlen($note) <= 1000 ? $note : substr($note, 0, 997).'...';
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
