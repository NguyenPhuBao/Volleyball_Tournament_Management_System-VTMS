<?php

declare(strict_types=1);

namespace App\Backend\Services\Athlete;

use App\Backend\Core\Http\Request;
use Throwable;

final class AthleteTeamInfoService extends AthleteServiceSupport
{
    private const MEMBER_STATUSES = ['CHO_XAC_NHAN', 'DANG_THAM_GIA', 'DA_ROI_DOI', 'BI_LOAI'];

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        [$normalized, $errors] = $this->commonFilters($filters, self::MEMBER_STATUSES);
        $normalized['team_status'] = strtoupper(trim((string) ($filters['team_status'] ?? $filters['doibong_trangthai'] ?? '')));

        if ($errors !== []) {
            return $this->failure('Bo loc doi bong khong hop le.', 422, $errors);
        }

        try {
            $teams = $this->athletes->teamsForAthlete((int) $athlete['idvandongvien'], $normalized);
            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem danh sach doi bong cua VDV',
                'Doibong',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem %d doi bong.', (int) $athlete['idvandongvien'], count($teams)))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach doi bong thanh cong.',
                'teams' => $teams,
                'meta' => [
                    'athlete' => $athlete,
                    'filters' => $normalized,
                    'member_statuses' => self::MEMBER_STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay thong tin doi bong.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }

    public function show(int $teamId, int $accountId, ?Request $request = null): array
    {
        $athlete = $this->activeAthlete($accountId, false);

        if ($this->isFailure($athlete)) {
            return $athlete;
        }

        try {
            $team = $this->athletes->teamForAthlete((int) $athlete['idvandongvien'], $teamId);

            if ($team === null) {
                return $this->failure('Khong tim thay doi bong cua VDV.', 404);
            }

            $members = $this->athletes->teamMembersForAthleteTeam((int) $athlete['idvandongvien'], $teamId);
            $tournaments = $this->athletes->teamTournamentsForAthleteTeam((int) $athlete['idvandongvien'], $teamId);

            $this->athletes->recordAthleteSystemLog(
                $accountId,
                'Xem thong tin doi bong',
                'Doibong',
                $teamId,
                $request?->ip(),
                $this->limitLogNote(sprintf('VDV #%d xem doi bong #%d.', (int) $athlete['idvandongvien'], $teamId))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay thong tin doi bong thanh cong.',
                'team' => $team,
                'members' => $members,
                'tournaments' => $tournaments,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay thong tin doi bong.', 500, [
                'database' => 'Loi doc co so du lieu hoac ghi nhat ky.',
            ]);
        }
    }
}

