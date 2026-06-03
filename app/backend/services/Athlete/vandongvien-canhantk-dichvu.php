<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use Throwable;

final class AthletePersonalStatsService extends AthleteServiceSupport
{
    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc thong ke ca nhan khong hop le.', 422, $errors);
        }

        try {
            $stats = $this->athletes->statsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $summary = $this->athletes->statsSummaryForAthlete((int) $athlete['idvandongvien'], $normalized);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem thong ke ca nhan VDV',
                'Thongkecanhan',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem thong ke ca nhan. So dong: %d.', (int) $athlete['idvandongvien'], count($stats)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay thong ke ca nhan thanh cong.',
                'stats' => $stats,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'summary' => $summary,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay thong ke ca nhan.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }
}

