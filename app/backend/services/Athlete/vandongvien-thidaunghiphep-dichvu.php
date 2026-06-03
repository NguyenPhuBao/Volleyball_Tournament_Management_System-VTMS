<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use RuntimeException;
use Throwable;

final class AthleteCompetitionLeaveService extends AthleteServiceSupport
{
    private const STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI', 'DA_HUY'];

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters, self::STATUSES);

        if ($errors !== []) {
            return $this->failure('Bo loc don xin nghi phep thi dau khong hop le.', 422, $errors);
        }

        try {
            $leaves = $this->athletes->leaveRequestsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $stats = $this->athletes->leaveRequestStatsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem danh sach don nghi phep thi dau VDV',
                'Donnghivandongvien',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem %d don nghi phep thi dau.', (int) $athlete['idvandongvien'], count($leaves)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach don nghi phep thi dau thanh cong.',
                'leave_requests' => $leaves,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'statuses' => self::STATUSES,
                    'stats' => $stats,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach don nghi phep thi dau.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $leaveId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        try {
            $leave = $this->athletes->leaveRequestForAthlete((int) $athlete['idvandongvien'], $leaveId);

            if ($leave === null) {
                return $this->failure('Khong tim thay don nghi phep thi dau.', 404);
            }

            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem chi tiet don nghi phep thi dau VDV',
                'Donnghivandongvien',
                $leaveId,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem don nghi phep thi dau #%d.', (int) $athlete['idvandongvien'], $leaveId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet don nghi phep thi dau thanh cong.',
                'leave_request' => $leave,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet don nghi phep thi dau.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function create(array $payload, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, true);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$leave, $errors] = $this->leaveFromPayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu xin nghi phep thi dau khong hop le.', 422, $errors);
        }

        if ($leave['match_id'] !== null && $this->athletes->matchForAthlete((int) $athlete['idvandongvien'], $leave['match_id']) === null) {
            return $this->failure('Tran dau khong nam trong lich thi dau ca nhan.', 404);
        }

        if ($this->athletes->hasOverlappingAthleteLeaveRequest((int) $athlete['idvandongvien'], $leave['tungay'], $leave['denngay'])) {
            return $this->failure('Khoang thoi gian nghi bi trung voi don nghi dang cho duyet hoac da duyet.', 409, [
                'date_range' => 'Vui long chon khoang thoi gian khac.',
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'VDV #%d xin nghi phep thi dau tu %s den %s. Tran: %s. Ly do: %s',
            (int) $athlete['idvandongvien'],
            $leave['tungay'],
            $leave['denngay'],
            $leave['match_id'] ?? 'khong gan tran',
            $leave['lydo']
        ));

        try {
            $leaveId = $this->athletes->createAthleteLeaveRequest(
                (int) $athlete['idvandongvien'],
                $leave['match_id'],
                $leave['tungay'],
                $leave['denngay'],
                $leave['lydo'],
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Xin nghi phep thi dau thanh cong.',
                'leave_request' => $this->athletes->leaveRequestForAthlete((int) $athlete['idvandongvien'], $leaveId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the gui don xin nghi phep thi dau.', 500, [
                'database' => 'Loi ghi co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function cancel(int $leaveId, array $payload, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? 'VDV huy don nghi phep thi dau'));

        if ($reason === '') {
            $reason = 'VDV huy don nghi phep thi dau';
        }

        if (strlen($reason) > 1000) {
            return $this->failure('Du lieu huy don nghi phep thi dau khong hop le.', 422, [
                'reason' => 'Ly do huy khong duoc vuot qua 1000 ky tu.',
            ]);
        }

        $leave = $this->athletes->leaveRequestForAthlete((int) $athlete['idvandongvien'], $leaveId);

        if ($leave === null) {
            return $this->failure('Khong tim thay don nghi phep thi dau.', 404);
        }

        if ((string) $leave['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc huy don nghi phep thi dau dang cho duyet.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'VDV #%d huy don nghi phep thi dau #%d. Ly do: %s',
            (int) $athlete['idvandongvien'],
            $leaveId,
            $reason
        ));

        try {
            $updated = $this->athletes->cancelAthleteLeaveRequest(
                (int) $athlete['idvandongvien'],
                $leaveId,
                $reason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            if ($updated === null) {
                return $this->failure('Khong tim thay don nghi phep thi dau.', 404);
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy nghi phep thi dau thanh cong.',
                'leave_request' => $updated,
            ];
        } catch (RuntimeException) {
            return $this->failure('Chi duoc huy don nghi phep thi dau dang cho duyet.', 409);
        } catch (Throwable) {
            return $this->failure('Khong the huy don nghi phep thi dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    private function leaveFromPayload(array $payload): array
    {
        $errors = [];
        $today = date('Y-m-d');
        $from = trim((string) ($payload['tungay'] ?? $payload['from_date'] ?? $payload['from'] ?? ''));
        $to = trim((string) ($payload['denngay'] ?? $payload['to_date'] ?? $payload['to'] ?? ''));
        $reason = trim((string) ($payload['lydo'] ?? $payload['reason'] ?? $payload['note'] ?? ''));
        $matchId = $this->positiveIntOrNull($payload['match_id'] ?? $payload['idtrandau'] ?? null);

        if ($from === '' || !$this->isDate($from)) {
            $errors['tungay'] = 'Tu ngay phai theo dinh dang YYYY-MM-DD.';
        }

        if ($to === '' || !$this->isDate($to)) {
            $errors['denngay'] = 'Den ngay phai theo dinh dang YYYY-MM-DD.';
        }

        if ($from !== '' && $this->isDate($from) && $from < $today) {
            $errors['tungay'] = 'Tu ngay khong duoc nho hon ngay hien tai.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['denngay'] = 'Den ngay phai lon hon hoac bang tu ngay.';
        }

        if ($reason === '') {
            $errors['lydo'] = 'Ly do xin nghi phep thi dau la bat buoc.';
        } elseif (strlen($reason) > 1000) {
            $errors['lydo'] = 'Ly do khong duoc vuot qua 1000 ky tu.';
        }

        return [[
            'match_id' => $matchId,
            'tungay' => $from !== '' ? $from : $today,
            'denngay' => $to !== '' ? $to : ($from !== '' ? $from : $today),
            'lydo' => $reason,
        ], $errors];
    }
}

