<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Trongtai;
use RuntimeException;
use Throwable;

final class OrganizerRefereeService
{
    private const REFEREE_STATUSES = ['HOAT_DONG', 'CHO_DUYET', 'DANG_NGHI', 'NGUNG_HOAT_DONG'];
    private const ACCOUNT_STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];
    private const GENDERS = ['NAM', 'NU', 'KHAC'];
    private const ASSIGNMENT_ROLES = ['TRONG_TAI_CHINH', 'TRONG_TAI_PHU', 'GIAM_SAT'];
    private const MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];
    private const ASSIGNABLE_MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT'];
    private const LEAVE_STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];

    public function __construct(
        private ?Trongtai $referees = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->referees ??= new Trongtai();
        $this->tournaments ??= new Giaidau();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        [$normalized, $errors] = $this->refereeFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc trong tai khong hop le.', 422, $errors);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach trong tai thanh cong.',
            'referees' => $this->referees->listForOrganizer((int) $organizerResult['idbantochuc'], $normalized),
            'meta' => [
                'filters' => $normalized,
                'statuses' => self::REFEREE_STATUSES,
                'account_statuses' => self::ACCOUNT_STATUSES,
            ],
        ];
    }

    public function find(int $refereeId, int $accountId): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        if (!$this->referees->refereeVisibleToOrganizer($refereeId, (int) $organizerResult['idbantochuc'])) {
            return $this->failure('Khong tim thay trong tai.', 404);
        }

        $referee = $this->referees->findById($refereeId);

        if ($referee === null) {
            return $this->failure('Khong tim thay trong tai.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin trong tai thanh cong.',
            'referee' => $referee,
        ];
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        [$account, $profile, $referee, $errors] = $this->validateCreatePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu trong tai khong hop le.', 422, $errors);
        }

        $roleId = $this->referees->roleIdByName('TRONG_TAI');

        if ($roleId === null) {
            return $this->failure('Khong tim thay vai tro TRONG_TAI trong he thong.', 500);
        }

        $account['idrole'] = $roleId;
        $account['password'] = password_hash((string) $account['password'], PASSWORD_DEFAULT);

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d them trong tai "%s %s", tai khoan o trang thai CHUA_KICH_HOAT va ho so trong tai CHO_DUYET.',
            (int) $organizerResult['idbantochuc'],
            $profile['hodem'],
            $profile['ten']
        ));

        try {
            $refereeId = $this->referees->createReferee(
                $account,
                $profile,
                $referee,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Them trong tai thanh cong, cho duyet.',
                'referee' => $this->referees->findById($refereeId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the them trong tai.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function matches(int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        [$normalized, $errors] = $this->matchFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc tran dau khong hop le.', 422, $errors);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach tran dau phan cong trong tai thanh cong.',
            'matches' => $this->referees->matchesForOrganizer((int) $organizerResult['idbantochuc'], $normalized),
            'meta' => [
                'filters' => $normalized,
                'roles' => self::ASSIGNMENT_ROLES,
            ],
        ];
    }

    public function assign(int $refereeId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $assignment = $this->validateAssignmentPayload($payload);

        if ($assignment['errors'] !== []) {
            return $this->failure('Du lieu phan cong khong hop le.', 422, $assignment['errors']);
        }

        if (!$this->referees->refereeVisibleToOrganizer($refereeId, (int) $organizerResult['idbantochuc'])) {
            return $this->failure('Khong tim thay trong tai.', 404);
        }

        $referee = $this->referees->findById($refereeId);

        if ($referee === null) {
            return $this->failure('Khong tim thay trong tai.', 404);
        }

        if ((string) $referee['trangthai'] !== 'HOAT_DONG' || (string) $referee['trangthai_taikhoan'] !== 'HOAT_DONG') {
            return $this->failure('Chi duoc phan cong trong tai da duoc xac nhan va dang hoat dong.', 409);
        }

        $match = $this->referees->matchForOrganizer((int) $organizerResult['idbantochuc'], $assignment['idtrandau']);

        if ($match === null) {
            return $this->failure('Khong tim thay tran dau cua ban to chuc.', 404);
        }

        if (!$this->referees->isRefereeEligibleForTournament($refereeId, (int) $match['idgiaidau'])) {
            return $this->failure('Trong tai chi duoc phan cong cho tran dau cung cap voi cap bac trong tai.', 409);
        }

        if (!in_array((string) $match['trangthai'], self::ASSIGNABLE_MATCH_STATUSES, true)) {
            return $this->failure('Chi duoc phan cong trong tai cho tran dau chua dien ra hoac sap dien ra.', 409);
        }

        $roleConflict = $this->referees->activeAssignmentForRole($assignment['idtrandau'], $assignment['vaitro']);

        if (
            $roleConflict !== null
            && (int) $roleConflict['idtrongtai'] !== $refereeId
            && !$assignment['replace']
        ) {
            return $this->failure('Tran dau da co trong tai o vai tro nay.', 409, [
                'vaitro' => 'Gui replace=true de thay doi trong tai dang duoc phan cong.',
                'current_assignment' => $roleConflict,
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d phan cong trong tai #%d vao tran #%d vai tro %s.',
            (int) $organizerResult['idbantochuc'],
            $refereeId,
            $assignment['idtrandau'],
            $assignment['vaitro']
        ));

        try {
            $assignmentId = $this->referees->assignToMatch(
                $assignment['idtrandau'],
                $refereeId,
                $assignment['vaitro'],
                $assignment['replace'],
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Phan cong trong tai thanh cong.',
                'assignment' => $this->referees->findAssignment($assignmentId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the phan cong trong tai.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function leave(int $refereeId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        if (!$this->referees->refereeVisibleToOrganizer($refereeId, (int) $organizerResult['idbantochuc'])) {
            return $this->failure('Khong tim thay trong tai.', 404);
        }

        $referee = $this->referees->findById($refereeId);

        if ($referee === null) {
            return $this->failure('Khong tim thay trong tai.', 404);
        }

        if ((string) $referee['trangthai'] !== 'HOAT_DONG' || (string) $referee['trangthai_taikhoan'] !== 'HOAT_DONG') {
            return $this->failure('Chi duoc cho nghi trong tai dang hoat dong.', 409);
        }

        [$leave, $errors] = $this->validateLeavePayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu cho nghi trong tai khong hop le.', 422, $errors);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cho nghi trong tai #%d tu %s den %s. Ly do: %s',
            (int) $organizerResult['idbantochuc'],
            $refereeId,
            $leave['tungay'],
            $leave['denngay'],
            $leave['lydo']
        ));

        try {
            $leaveId = $this->referees->createLeaveRequest(
                $refereeId,
                (int) $referee['idtaikhoan'],
                (string) $referee['trangthai_taikhoan'],
                $leave['tungay'],
                $leave['denngay'],
                $leave['lydo'],
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Da ghi nhan cho nghi trong tai, cho quan tri vien duyet dong tai khoan.',
                'leave_request' => $this->referees->findLeaveRequest($leaveId),
                'referee' => $this->referees->findById($refereeId),
            ];
        } catch (RuntimeException) {
            return $this->failure('Khong the cap nhat trang thai trong tai.', 409);
        } catch (Throwable) {
            return $this->failure('Khong the cho nghi trong tai.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function leaves(int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        [$normalized, $errors] = $this->leaveFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc don nghi trong tai khong hop le.', 422, $errors);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach don nghi trong tai thanh cong.',
            'leave_requests' => $this->referees->leaveRequestsForOrganizer((int) $organizerResult['idbantochuc'], $normalized),
            'meta' => [
                'filters' => $normalized,
                'statuses' => self::LEAVE_STATUSES,
            ],
        ];
    }

    private function refereeFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $accountStatus = strtoupper(trim((string) ($filters['account_status'] ?? $filters['trangthai_taikhoan'] ?? '')));
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::REFEREE_STATUSES, true)) {
            $errors['status'] = 'Trang thai trong tai khong hop le.';
        }

        if ($accountStatus !== '' && !in_array($accountStatus, self::ACCOUNT_STATUSES, true)) {
            $errors['account_status'] = 'Trang thai tai khoan khong hop le.';
        }

        return [[
            'q' => $keyword,
            'status' => $status,
            'account_status' => $accountStatus,
        ], $errors];
    }

    private function matchFilters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null);
        $errors = [];

        if ($status !== '' && !in_array($status, self::MATCH_STATUSES, true)) {
            $errors['status'] = 'Trang thai tran dau khong hop le.';
        }

        if (($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null) !== null && $tournamentId === null) {
            $errors['tournament_id'] = 'Ma giai dau khong hop le.';
        }

        return [[
            'q' => $keyword,
            'status' => $status,
            'tournament_id' => $tournamentId,
        ], $errors];
    }

    private function leaveFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $refereeId = $this->optionalPositiveInt($filters['referee_id'] ?? $filters['idtrongtai'] ?? null);
        $from = trim((string) ($filters['from'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['denngay'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::LEAVE_STATUSES, true)) {
            $errors['status'] = 'Trang thai don nghi khong hop le.';
        }

        if (($filters['referee_id'] ?? $filters['idtrongtai'] ?? null) !== null && $refereeId === null) {
            $errors['referee_id'] = 'Ma trong tai khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Tu ngay loc khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Den ngay loc khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['to'] = 'Den ngay loc phai lon hon hoac bang tu ngay loc.';
        }

        return [[
            'status' => $status,
            'referee_id' => $refereeId,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function validateCreatePayload(array $payload): array
    {
        $errors = [];

        $account = [
            'username' => $this->requiredString($payload, ['username', 'tendangnhap'], 100, 'Ten dang nhap', $errors),
            'password' => $this->password($payload['password'] ?? $payload['matkhau'] ?? null, $errors),
            'email' => $this->email($payload['email'] ?? null, $errors),
            'sodienthoai' => $this->phone($payload['sodienthoai'] ?? $payload['phone'] ?? null, $errors),
        ];

        if ($account['username'] !== null && !preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $account['username'])) {
            $errors['username'] = 'Ten dang nhap phai dai 3-100 ky tu va chi gom chu, so, dau gach duoi, gach ngang hoac dau cham.';
        } elseif ($account['username'] !== null && $this->referees->accountValueExists('username', $account['username'])) {
            $errors['username'] = 'Ten dang nhap da ton tai.';
        }

        if ($account['email'] !== null && $this->referees->accountValueExists('email', $account['email'])) {
            $errors['email'] = 'Email da ton tai.';
        }

        if ($account['sodienthoai'] !== null && $this->referees->accountValueExists('sodienthoai', $account['sodienthoai'])) {
            $errors['sodienthoai'] = 'So dien thoai da ton tai.';
        }

        $profile = [
            'ten' => $this->requiredString($payload, ['ten', 'firstname', 'first_name'], 100, 'Ten', $errors),
            'hodem' => $this->requiredString($payload, ['hodem', 'lastname', 'last_name'], 200, 'Ho dem', $errors),
            'gioitinh' => $this->gender($payload['gioitinh'] ?? $payload['gender'] ?? null, $errors),
            'ngaysinh' => $this->nullableDate($payload['ngaysinh'] ?? $payload['birthday'] ?? null, 'ngaysinh', 'Ngay sinh', $errors),
            'quequan' => $this->nullableString($payload['quequan'] ?? null, 500, 'Que quan', 'quequan', $errors),
            'diachi' => $this->nullableString($payload['diachi'] ?? $payload['address'] ?? null, 500, 'Dia chi', 'diachi', $errors),
            'avatar' => $this->nullableString($payload['avatar'] ?? null, 500, 'Avatar', 'avatar', $errors),
            'cccd' => $this->identity($payload['cccd'] ?? null, $errors),
        ];

        if ($profile['cccd'] !== null && $this->referees->profileValueExists('cccd', $profile['cccd'])) {
            $errors['cccd'] = 'CCCD da ton tai.';
        }

        $referee = [
            'capbac' => $this->nullableString($payload['capbac'] ?? $payload['level'] ?? null, 100, 'Cap bac', 'capbac', $errors),
            'kinhnghiem' => $this->nonNegativeInt($payload['kinhnghiem'] ?? $payload['experience'] ?? 0, 'kinhnghiem', 'Kinh nghiem', $errors),
        ];

        return [$account, $profile, $referee, $errors];
    }

    private function validateAssignmentPayload(array $payload): array
    {
        $errors = [];
        $matchId = $this->optionalPositiveInt($payload['idtrandau'] ?? $payload['match_id'] ?? null);
        $role = strtoupper(trim((string) ($payload['vaitro'] ?? $payload['role'] ?? '')));

        if ($matchId === null) {
            $errors['idtrandau'] = 'Ma tran dau la bat buoc va phai hop le.';
        }

        if (!in_array($role, self::ASSIGNMENT_ROLES, true)) {
            $errors['vaitro'] = 'Vai tro trong tai khong hop le.';
        }

        return [
            'idtrandau' => $matchId ?? 0,
            'vaitro' => $role,
            'replace' => $this->boolean($payload['replace'] ?? $payload['thaydoi'] ?? false),
            'errors' => $errors,
        ];
    }

    private function validateLeavePayload(array $payload): array
    {
        $errors = [];
        $today = date('Y-m-d');
        $from = $this->dateOrDefault($payload['tungay'] ?? $payload['from_date'] ?? null, $today, 'tungay', 'Tu ngay', $errors);
        $to = $this->dateOrDefault($payload['denngay'] ?? $payload['to_date'] ?? null, $from ?? $today, 'denngay', 'Den ngay', $errors);
        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? ''));

        if ($reason === '') {
            $errors['lydo'] = 'Ly do cho nghi la bat buoc.';
        } elseif (strlen($reason) > 1000) {
            $errors['lydo'] = 'Ly do cho nghi khong duoc vuot qua 1000 ky tu.';
        }

        if ($from !== null && $to !== null && $to < $from) {
            $errors['denngay'] = 'Den ngay phai lon hon hoac bang tu ngay.';
        }

        return [[
            'tungay' => $from ?? $today,
            'denngay' => $to ?? ($from ?? $today),
            'lydo' => $reason,
        ], $errors];
    }

    private function activeOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) $organizer['trangthai'] !== 'HOAT_DONG') {
            return $this->failure('Ban to chuc khong o trang thai hoat dong.', 403);
        }

        return $organizer;
    }

    private function requiredString(array $payload, array $keys, int $maxLength, string $label, array &$errors): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $value = trim((string) $payload[$key]);

                if ($value === '') {
                    break;
                }

                if (strlen($value) > $maxLength) {
                    $errors[$keys[0]] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
                    return null;
                }

                return $value;
            }
        }

        $errors[$keys[0]] = $label . ' la bat buoc.';

        return null;
    }

    private function nullableString(mixed $value, int $maxLength, string $label, string $errorKey, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (strlen($text) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $text;
    }

    private function email(mixed $value, array &$errors): ?string
    {
        $email = trim((string) ($value ?? ''));

        if ($email === '') {
            $errors['email'] = 'Email la bat buoc.';
            return null;
        }

        if (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email khong hop le.';
            return null;
        }

        return $email;
    }

    private function phone(mixed $value, array &$errors): ?string
    {
        $phone = trim((string) ($value ?? ''));

        if ($phone === '') {
            return null;
        }

        if (strlen($phone) > 20 || !preg_match('/^\+?[0-9]{8,20}$/', $phone)) {
            $errors['sodienthoai'] = 'So dien thoai khong hop le.';
            return null;
        }

        return $phone;
    }

    private function password(mixed $value, array &$errors): ?string
    {
        $password = (string) ($value ?? '');

        if ($password === '') {
            $errors['password'] = 'Mat khau la bat buoc.';
            return null;
        }

        if (strlen($password) < 6 || strlen($password) > 72) {
            $errors['password'] = 'Mat khau phai dai tu 6 den 72 ky tu.';
            return null;
        }

        return $password;
    }

    private function gender(mixed $value, array &$errors): ?string
    {
        $gender = strtoupper(trim((string) ($value ?? '')));

        if (!in_array($gender, self::GENDERS, true)) {
            $errors['gioitinh'] = 'Gioi tinh khong hop le.';
            return null;
        }

        return $gender;
    }

    private function identity(mixed $value, array &$errors): ?string
    {
        $identity = trim((string) ($value ?? ''));

        if ($identity === '') {
            return null;
        }

        if (strlen($identity) > 20 || !preg_match('/^[0-9]{9,20}$/', $identity)) {
            $errors['cccd'] = 'CCCD khong hop le.';
            return null;
        }

        return $identity;
    }

    private function nullableDate(mixed $value, string $errorKey, string $label, array &$errors): ?string
    {
        $date = trim((string) ($value ?? ''));

        if ($date === '') {
            return null;
        }

        if (!$this->isDate($date)) {
            $errors[$errorKey] = $label . ' phai theo dinh dang YYYY-MM-DD.';
            return null;
        }

        return $date;
    }

    private function dateOrDefault(mixed $value, string $default, string $errorKey, string $label, array &$errors): ?string
    {
        $date = trim((string) ($value ?? ''));

        if ($date === '') {
            return $default;
        }

        if (!$this->isDate($date)) {
            $errors[$errorKey] = $label . ' phai theo dinh dang YYYY-MM-DD.';
            return null;
        }

        return $date;
    }

    private function nonNegativeInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        $text = trim((string) ($value ?? '0'));

        if ($text === '' || !ctype_digit($text)) {
            $errors[$errorKey] = $label . ' phai la so nguyen khong am.';
            return null;
        }

        return (int) $text;
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!ctype_digit((string) $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function isDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

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

