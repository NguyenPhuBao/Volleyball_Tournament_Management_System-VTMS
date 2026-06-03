<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use Throwable;

final class AthletePersonalScheduleService extends AthleteServiceSupport
{
    private const MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters, self::MATCH_STATUSES);

        if ($errors !== []) {
            return $this->failure('Bo loc lich thi dau ca nhan khong hop le.', 422, $errors);
        }

        try {
            $matches = $this->athletes->scheduleForAthlete((int) $athlete['idvandongvien'], $normalized);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem lich thi dau ca nhan',
                'Trandau',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem %d tran dau trong lich ca nhan.', (int) $athlete['idvandongvien'], count($matches)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay lich thi dau ca nhan thanh cong.',
                'matches' => $matches,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'statuses' => self::MATCH_STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay lich thi dau ca nhan.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $matchId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        try {
            $match = $this->athletes->matchForAthlete((int) $athlete['idvandongvien'], $matchId);

            if ($match === null) {
                return $this->failure('Khong tim thay tran dau trong lich ca nhan.', 404);
            }

            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem chi tiet tran dau ca nhan',
                'Trandau',
                $matchId,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem tran dau #%d trong lich ca nhan.', (int) $athlete['idvandongvien'], $matchId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet tran dau ca nhan thanh cong.',
                'match' => $match,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet tran dau ca nhan.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }
}

