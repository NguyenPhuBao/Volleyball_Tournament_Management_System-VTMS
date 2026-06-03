<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Lichthidau;
use App\Backend\Models\Nguoidung;
use Throwable;

final class OrganizerScheduleViewService
{
    private const MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];
    private const RESULT_STATUSES = ['CHO_CONG_BO', 'DA_CONG_BO', 'DA_DIEU_CHINH', 'BI_HUY'];

    public function __construct(
        private ?Lichthidau $schedules = null,
        private ?Giaidau $tournaments = null,
        private ?Nguoidung $users = null
    ) {
        $this->schedules ??= new Lichthidau();
        $this->tournaments ??= new Giaidau();
        $this->users ??= new Nguoidung();
    }

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        [$normalized, $errors] = $this->filters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc lich thi dau khong hop le.', 422, $errors);
        }

        try {
            $matches = $this->schedules->scheduleViewForOrganizer((int) $organizer['idbantochuc'], $normalized);
            $stats = $this->schedules->scheduleViewStatsForOrganizer((int) $organizer['idbantochuc'], $normalized);
            $this->recordLog(
                $accountId,
                'Ban to chuc xem lich thi dau',
                'Trandau',
                null,
                $request,
                sprintf(
                    'BTC #%d xem %d tran dau. Bo loc: %s.',
                    (int) $organizer['idbantochuc'],
                    count($matches),
                    json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
                )
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay lich thi dau thanh cong.',
                'matches' => $matches,
                'meta' => [
                    'organizer' => $organizer,
                    'filters' => $normalized,
                    'match_statuses' => self::MATCH_STATUSES,
                    'result_statuses' => self::RESULT_STATUSES,
                    'stats' => $stats,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay lich thi dau.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $matchId, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        try {
            $match = $this->schedules->scheduleViewMatchForOrganizer((int) $organizer['idbantochuc'], $matchId);

            if ($match === null) {
                return $this->failure('Khong tim thay tran dau trong pham vi ban to chuc.', 404);
            }

            $this->recordLog(
                $accountId,
                'Ban to chuc xem chi tiet lich thi dau',
                'Trandau',
                $matchId,
                $request,
                sprintf('BTC #%d xem chi tiet tran dau #%d.', (int) $organizer['idbantochuc'], $matchId)
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet lich thi dau thanh cong.',
                'match' => $match,
                'meta' => [
                    'organizer' => $organizer,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet lich thi dau.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
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

    private function filters(array $filters): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $resultStatus = strtoupper(trim((string) ($filters['result_status'] ?? $filters['ketqua_trangthai'] ?? '')));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));

        if ($status !== '' && !in_array($status, self::MATCH_STATUSES, true)) {
            $errors['status'] = 'Trang thai tran dau khong hop le.';
        }

        if ($resultStatus !== '' && !in_array($resultStatus, self::RESULT_STATUSES, true)) {
            $errors['result_status'] = 'Trang thai ket qua khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Tu ngay khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Den ngay khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['to'] = 'Den ngay phai lon hon hoac bang tu ngay.';
        }

        return [[
            'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            'status' => $status,
            'result_status' => $resultStatus,
            'tournament_id' => $this->positiveIntOrNull($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null),
            'group_id' => $this->positiveIntOrNull($filters['group_id'] ?? $filters['idbangdau'] ?? null),
            'team_id' => $this->positiveIntOrNull($filters['team_id'] ?? $filters['iddoibong'] ?? null),
            'venue_id' => $this->positiveIntOrNull($filters['venue_id'] ?? $filters['idsandau'] ?? null),
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function recordLog(
        int $accountId,
        string $action,
        string $targetTable,
        ?int $targetId,
        ?Request $request,
        string $note
    ): void {
        $this->users->recordSystemLog(
            $accountId,
            $action,
            $targetTable,
            $targetId,
            $request?->ip(),
            $this->limitLogNote($note)
        );
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

