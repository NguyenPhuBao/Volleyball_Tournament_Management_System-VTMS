<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use Throwable;

final class AthleteLineupViewService extends AthleteServiceSupport
{
    private const STATUSES = ['BAN_NHAP', 'DA_CHOT', 'DA_CAP_NHAT'];

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters, self::STATUSES);

        if ($errors !== []) {
            return $this->failure('Bo loc doi hinh khong hop le.', 422, $errors);
        }

        try {
            $lineups = $this->athletes->lineupsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem danh sach doi hinh',
                'Doihinh',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem %d doi hinh.', (int) $athlete['idvandongvien'], count($lineups)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach doi hinh thanh cong.',
                'lineups' => $lineups,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'statuses' => self::STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach doi hinh.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $lineupId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        try {
            $lineup = $this->athletes->lineupForAthlete((int) $athlete['idvandongvien'], $lineupId);

            if ($lineup === null) {
                return $this->failure('Khong tim thay doi hinh cua doi bong VDV.', 404);
            }

            $details = $this->athletes->lineupDetailsForAthlete((int) $athlete['idvandongvien'], $lineupId);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem chi tiet doi hinh',
                'Doihinh',
                $lineupId,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem doi hinh #%d.', (int) $athlete['idvandongvien'], $lineupId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet doi hinh thanh cong.',
                'lineup' => $lineup,
                'details' => $details,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet doi hinh.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }
}

