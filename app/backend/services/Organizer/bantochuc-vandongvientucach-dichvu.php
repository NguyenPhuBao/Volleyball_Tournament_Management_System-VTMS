<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Vandongvien;
use RuntimeException;
use Throwable;

final class OrganizerAthleteQualificationService
{
    private const ATHLETE_STATUSES = ['DU_DIEU_KIEN', 'CHO_XAC_NHAN', 'BI_HUY_TU_CACH', 'DANG_NGHI_PHEP'];
    private const ACCOUNT_STATUSES = ['HOAT_DONG', 'CHUA_KICH_HOAT', 'TAM_KHOA', 'DA_HUY', 'CHO_DUYET'];
    private const REQUEST_STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];
    private const REQUEST_PRESENCE_FILTERS = ['HAS_REQUEST', 'NO_REQUEST'];

    public function __construct(
        private ?Vandongvien $athletes = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->athletes ??= new Vandongvien();
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
            return $this->failure('Bo loc van dong vien khong hop le.', 422, $errors);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach van dong vien thanh cong.',
            'athletes' => $this->athletes->listForOrganizer((int) $organizerResult['idbantochuc'], $normalized),
            'meta' => [
                'filters' => $normalized,
                'athlete_statuses' => self::ATHLETE_STATUSES,
                'account_statuses' => self::ACCOUNT_STATUSES,
                'request_statuses' => self::REQUEST_STATUSES,
                'request_presence_filters' => self::REQUEST_PRESENCE_FILTERS,
            ],
        ];
    }

    public function find(int $athleteId, int $accountId): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $athlete = $this->athletes->findForOrganizer((int) $organizerResult['idbantochuc'], $athleteId);

        if ($athlete === null) {
            return $this->failure('Khong tim thay van dong vien.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin van dong vien thanh cong.',
            'athlete' => $this->withDetails((int) $organizerResult['idbantochuc'], $athlete),
        ];
    }

    public function approve(int $athleteId, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizerId = (int) $organizerResult['idbantochuc'];
        $athlete = $this->athletes->findForOrganizer($organizerId, $athleteId);

        if ($athlete === null) {
            return $this->failure('Khong tim thay van dong vien.', 404);
        }

        if ((string) $athlete['trangthaidaugiai'] !== 'CHO_XAC_NHAN') {
            return $this->failure('Chi duoc xac nhan van dong vien dang cho xac nhan.', 409);
        }

        $reason = 'Xac nhan tu cach thi dau van dong vien';
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d xac nhan tu cach thi dau VDV #%d "%s".',
            $organizerId,
            $athleteId,
            (string) $athlete['hoten']
        ));

        try {
            $this->athletes->updateCompetitionQualification(
                $athleteId,
                'CHO_XAC_NHAN',
                'DU_DIEU_KIEN',
                $this->pendingRequestId($athlete),
                'DA_DUYET',
                $reason,
                $accountId,
                $request?->ip(),
                'Xac nhan tu cach thi dau van dong vien',
                $logNote
            );

            $updated = $this->athletes->findForOrganizer($organizerId, $athleteId);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xac nhan tu cach thi dau van dong vien thanh cong.',
                'athlete' => $updated === null ? null : $this->withDetails($organizerId, $updated),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'ATHLETE_QUALIFICATION_NOT_UPDATED') {
                return $this->failure('Trang thai van dong vien da thay doi, khong the xac nhan.', 409);
            }

            return $this->failure('Khong the xac nhan tu cach thi dau van dong vien.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xac nhan tu cach thi dau van dong vien.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function cancel(int $athleteId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizerResult = $this->activeOrganizer($accountId);

        if (isset($organizerResult['ok']) && $organizerResult['ok'] === false) {
            return $organizerResult;
        }

        $organizerId = (int) $organizerResult['idbantochuc'];
        $athlete = $this->athletes->findForOrganizer($organizerId, $athleteId);

        if ($athlete === null) {
            return $this->failure('Khong tim thay van dong vien.', 404);
        }

        $oldStatus = (string) $athlete['trangthaidaugiai'];

        if (!in_array($oldStatus, ['CHO_XAC_NHAN', 'DU_DIEU_KIEN'], true)) {
            return $this->failure('Chi duoc huy tu cach VDV dang cho xac nhan hoac du dieu kien thi dau.', 409);
        }

        $reason = $this->reason($payload, 'Huy bo tu cach thi dau van dong vien');
        $requestStatus = $oldStatus === 'CHO_XAC_NHAN' ? 'TU_CHOI' : null;
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d huy tu cach thi dau VDV #%d "%s". Ly do: %s',
            $organizerId,
            $athleteId,
            (string) $athlete['hoten'],
            $reason
        ));

        try {
            $this->athletes->updateCompetitionQualification(
                $athleteId,
                $oldStatus,
                'BI_HUY_TU_CACH',
                $requestStatus === null ? null : $this->pendingRequestId($athlete),
                $requestStatus,
                $reason,
                $accountId,
                $request?->ip(),
                'Huy tu cach thi dau van dong vien',
                $logNote
            );

            $updated = $this->athletes->findForOrganizer($organizerId, $athleteId);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy tu cach thi dau van dong vien thanh cong.',
                'athlete' => $updated === null ? null : $this->withDetails($organizerId, $updated),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'ATHLETE_QUALIFICATION_NOT_UPDATED') {
                return $this->failure('Trang thai van dong vien da thay doi, khong the huy tu cach.', 409);
            }

            return $this->failure('Khong the huy tu cach thi dau van dong vien.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the huy tu cach thi dau van dong vien.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function withDetails(int $organizerId, array $athlete): array
    {
        $athleteId = (int) $athlete['idvandongvien'];
        $athlete['memberships'] = $this->athletes->membershipsForOrganizer($organizerId, $athleteId);
        $athlete['lineups'] = $this->athletes->lineupsForOrganizer($organizerId, $athleteId);
        $athlete['stats'] = $this->athletes->statsForOrganizer($organizerId, $athleteId);

        return $athlete;
    }

    private function pendingRequestId(array $athlete): ?int
    {
        if (empty($athlete['idyeucau']) || (string) ($athlete['yeucau_trangthai'] ?? '') !== 'CHO_DUYET') {
            return null;
        }

        return (int) $athlete['idyeucau'];
    }

    private function filters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthaidaugiai'] ?? '')));
        $accountStatus = strtoupper(trim((string) ($filters['account_status'] ?? $filters['trangthai_taikhoan'] ?? '')));
        $requestStatus = strtoupper(trim((string) ($filters['request_status'] ?? $filters['trangthai_yeucau'] ?? '')));
        $requestPresence = strtoupper(trim((string) ($filters['request_presence'] ?? $filters['request_filter'] ?? $filters['yeucau'] ?? '')));
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $teamId = trim((string) ($filters['team_id'] ?? $filters['iddoibong'] ?? ''));
        $tournamentId = trim((string) ($filters['tournament_id'] ?? $filters['idgiaidau'] ?? ''));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::ATHLETE_STATUSES, true)) {
            $errors['status'] = 'Trang thai thi dau van dong vien khong hop le.';
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

        if ($teamId !== '' && (!ctype_digit($teamId) || (int) $teamId <= 0)) {
            $errors['team_id'] = 'Doi bong khong hop le.';
        }

        if ($tournamentId !== '' && (!ctype_digit($tournamentId) || (int) $tournamentId <= 0)) {
            $errors['tournament_id'] = 'Giai dau khong hop le.';
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
            'team_id' => $teamId,
            'tournament_id' => $tournamentId,
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

