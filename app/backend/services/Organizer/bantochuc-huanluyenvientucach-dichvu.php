<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Huanluyenvien;
use RuntimeException;
use Throwable;

final class OrganizerCoachQualificationService
{
    private const COACH_STATUSES = ['CHO_DUYET', 'DA_XAC_NHAN', 'BI_HUY_TU_CACH', 'NGUNG_HOAT_DONG'];
    private const ACCOUNT_STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];
    private const REQUEST_STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];
    private const REQUEST_PRESENCE_FILTERS = ['HAS_REQUEST', 'NO_REQUEST'];

    public function __construct(
        private ?Huanluyenvien $coaches = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->coaches ??= new Huanluyenvien();
        $this->tournaments ??= new Giaidau();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        [$normalized, $errors] = $this->filters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc huan luyen vien khong hop le.', 422, $errors);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach huan luyen vien thanh cong.',
            'coaches' => $this->coaches->listForOrganizer((int) $organizerResult['idbantochuc'], $normalized),
            'meta' => [
                'filters' => $normalized,
                'coach_statuses' => self::COACH_STATUSES,
                'account_statuses' => self::ACCOUNT_STATUSES,
                'request_statuses' => self::REQUEST_STATUSES,
                'request_presence_filters' => self::REQUEST_PRESENCE_FILTERS,
            ],
        ];
    }

    public function find(int $coachId, int $accountId): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $coach = $this->coaches->findForOrganizer((int) $organizerResult['idbantochuc'], $coachId);

        if ($coach === null) {
            return $this->failure('Khong tim thay huan luyen vien.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin huan luyen vien thanh cong.',
            'coach' => $this->withDetails($coach),
        ];
    }

    public function approve(int $coachId, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->nationalFederationOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $coach = $this->coaches->findForOrganizer((int) $organizerResult['idbantochuc'], $coachId);

        if ($coach === null) {
            return $this->failure('Khong tim thay huan luyen vien.', 404);
        }

        if ((string) $coach['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc xac nhan huan luyen vien dang cho duyet.', 409);
        }

        $reason = 'Xac nhan tu cach huan luyen vien';
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d xac nhan tu cach HLV #%d "%s".',
            (int) $organizerResult['idbantochuc'],
            $coachId,
            (string) $coach['hoten']
        ));

        try {
            $this->coaches->updateQualification(
                $coachId,
                'CHO_DUYET',
                'DA_XAC_NHAN',
                $this->pendingRequestId($coach),
                'DA_DUYET',
                $reason,
                $accountId,
                $request?->ip(),
                'Xac nhan tu cach huan luyen vien',
                $logNote
            );

            $updated = $this->coaches->findForOrganizer((int) $organizerResult['idbantochuc'], $coachId);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xac nhan tu cach huan luyen vien thanh cong.',
                'coach' => $updated === null ? null : $this->withDetails($updated),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'COACH_QUALIFICATION_NOT_UPDATED') {
                return $this->failure('Trang thai huan luyen vien da thay doi, khong the xac nhan.', 409);
            }

            return $this->failure('Khong the xac nhan tu cach huan luyen vien.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xac nhan tu cach huan luyen vien.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function cancel(int $coachId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $coach = $this->coaches->findForOrganizer((int) $organizerResult['idbantochuc'], $coachId);

        if ($coach === null) {
            return $this->failure('Khong tim thay huan luyen vien.', 404);
        }

        $oldStatus = (string) $coach['trangthai'];

        if (!in_array($oldStatus, ['CHO_DUYET', 'DA_XAC_NHAN'], true)) {
            return $this->failure('Chi duoc huy tu cach HLV dang cho duyet hoac da xac nhan.', 409);
        }

        if ($oldStatus === 'CHO_DUYET') {
            $permission = $this->nationalFederationOrganizer($accountId);

            if (isset($permission['ok']) && $permission['ok'] === false) {
                return $permission;
            }

            $organizerResult = $permission;
            $coach = $this->coaches->findForOrganizer((int) $organizerResult['idbantochuc'], $coachId);

            if ($coach === null) {
                return $this->failure('Khong tim thay huan luyen vien.', 404);
            }
        }

        $reason = $this->reason($payload, 'Huy bo tu cach huan luyen vien');
        $requestStatus = $oldStatus === 'CHO_DUYET' ? 'TU_CHOI' : null;
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d huy tu cach HLV #%d "%s". Ly do: %s',
            (int) $organizerResult['idbantochuc'],
            $coachId,
            (string) $coach['hoten'],
            $reason
        ));

        try {
            $this->coaches->updateQualification(
                $coachId,
                $oldStatus,
                'BI_HUY_TU_CACH',
                $requestStatus === null ? null : $this->pendingRequestId($coach),
                $requestStatus,
                $reason,
                $accountId,
                $request?->ip(),
                'Huy tu cach huan luyen vien',
                $logNote
            );

            $updated = $this->coaches->findForOrganizer((int) $organizerResult['idbantochuc'], $coachId);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy tu cach huan luyen vien thanh cong.',
                'coach' => $updated === null ? null : $this->withDetails($updated),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'COACH_QUALIFICATION_NOT_UPDATED') {
                return $this->failure('Trang thai huan luyen vien da thay doi, khong the huy tu cach.', 409);
            }

            return $this->failure('Khong the huy tu cach huan luyen vien.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the huy tu cach huan luyen vien.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function withDetails(array $coach): array
    {
        $coach['teams'] = $this->coaches->teamsForCoach((int) $coach['idhuanluyenvien']);

        return $coach;
    }

    private function pendingRequestId(array $coach): ?int
    {
        if (empty($coach['idyeucau']) || (string) ($coach['yeucau_trangthai'] ?? '') !== 'CHO_DUYET') {
            return null;
        }

        return (int) $coach['idyeucau'];
    }

    private function filters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $accountStatus = strtoupper(trim((string) ($filters['account_status'] ?? $filters['trangthai_taikhoan'] ?? '')));
        $requestStatus = strtoupper(trim((string) ($filters['request_status'] ?? $filters['trangthai_yeucau'] ?? '')));
        $requestPresence = strtoupper(trim((string) ($filters['request_presence'] ?? $filters['request_filter'] ?? $filters['yeucau'] ?? '')));
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::COACH_STATUSES, true)) {
            $errors['status'] = 'Trang thai huan luyen vien khong hop le.';
        }

        if ($accountStatus !== '' && !in_array($accountStatus, self::ACCOUNT_STATUSES, true)) {
            $errors['account_status'] = 'Trang thai tai khoan khong hop le.';
        }

        if ($requestStatus !== '' && !in_array($requestStatus, self::REQUEST_STATUSES, true)) {
            $errors['request_status'] = 'Trang thai yeu cau khong hop le.';
        }

        if ($requestPresence !== '' && !in_array($requestPresence, self::REQUEST_PRESENCE_FILTERS, true)) {
            $errors['request_presence'] = 'Bo loc yeu cau xac nhan khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Tu ngay khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Den ngay khong hop le.';
        }

        if ($from !== '' && $to !== '' && empty($errors['from']) && empty($errors['to']) && $from > $to) {
            $errors['date_range'] = 'Tu ngay phai nho hon hoac bang den ngay.';
        }

        return [[
            'q' => $keyword,
            'status' => $status,
            'account_status' => $accountStatus,
            'request_status' => $requestStatus,
            'request_presence' => $requestPresence,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function reason(array $payload, string $default): string
    {
        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? $payload['ghichu'] ?? ''));

        $reason = $reason === '' ? $default : $reason;

        if (strlen($reason) <= 500) {
            return $reason;
        }

        return substr($reason, 0, 497) . '...';
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

    private function nationalFederationOrganizer(int $accountId): array
    {
        $organizer = $this->tournaments->findOrganizerByAccountId($accountId);

        if ($organizer === null) {
            return $this->failure('Tai khoan khong co ho so ban to chuc.', 403);
        }

        if ((string) ($organizer['role'] ?? '') !== 'BAN_TO_CHUC') {
            return $this->failure('Chi tai khoan ban to chuc moi duoc xac nhan HLV.', 403);
        }

        $isActive = (string) ($organizer['trangthai'] ?? '') === 'HOAT_DONG'
            && (string) ($organizer['trangthai_donvi'] ?? '') === 'HOAT_DONG'
            && (string) ($organizer['trangthai_loaidonvi'] ?? '') === 'HOAT_DONG';
        $isHighestFederationLevel = (int) ($organizer['idcapgiaidau_quanly'] ?? 0) > 0
            && array_key_exists('idcapgiaidau_cha_quanly', $organizer)
            && $organizer['idcapgiaidau_cha_quanly'] === null
            && (string) ($organizer['maloaidonvi'] ?? '') === 'LIEN_DOAN_BONG_CHUYEN_VN';

        if (!$isActive || !$isHighestFederationLevel) {
            return $this->failure('Chi BTC cap quoc gia thuoc Lien doan Bong chuyen VN duoc xac nhan HLV dang cho duyet.', 403);
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

