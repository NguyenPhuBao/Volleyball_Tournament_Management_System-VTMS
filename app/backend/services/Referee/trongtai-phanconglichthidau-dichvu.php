<?php

declare(strict_types=1);

namespace App\Backend\Services\Referee;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Trongtai;
use Throwable;

final class RefereeAssignmentScheduleService
{
    private const ASSIGNMENT_STATUSES = ['CHO_XAC_NHAN', 'DA_XAC_NHAN', 'TU_CHOI', 'DA_HUY'];
    private const MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];
    private const ASSIGNMENT_ROLES = ['TRONG_TAI_CHINH', 'TRONG_TAI_PHU', 'GIAM_SAT'];

    public function __construct(private ?Trongtai $referees = null)
    {
        $this->referees ??= new Trongtai();
    }

    public function all(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        [$normalized, $errors] = $this->filters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc lich phan cong khong hop le.', 422, $errors);
        }

        try {
            $assignments = $this->referees->assignmentScheduleForReferee((int) $referee['idtrongtai'], $normalized);
            $stats = $this->referees->assignmentScheduleStatsForReferee((int) $referee['idtrongtai'], $normalized);
            $this->referees->recordRefereeScheduleView(
                (int) $referee['idtrongtai'],
                $accountId,
                $request?->ip(),
                $this->listLogNote((int) $referee['idtrongtai'], $normalized, count($assignments))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay lich phan cong tran dau thanh cong.',
                'assignments' => $assignments,
                'meta' => [
                    'referee' => $referee,
                    'filters' => $normalized,
                    'assignment_statuses' => self::ASSIGNMENT_STATUSES,
                    'match_statuses' => self::MATCH_STATUSES,
                    'roles' => self::ASSIGNMENT_ROLES,
                    'stats' => $stats,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay lich phan cong tran dau.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function findByAssignment(int $assignmentId, int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $assignment = $this->referees->assignmentDetailForReferee((int) $referee['idtrongtai'], $assignmentId);

            if ($assignment === null) {
                return $this->failure('Khong tim thay phan cong tran dau.', 404);
            }

            $assignment['co_referees'] = $this->referees->coRefereesForMatch((int) $assignment['idtrandau']);
            $this->referees->recordRefereeAssignmentView(
                $assignmentId,
                $accountId,
                $request?->ip(),
                $this->detailLogNote((int) $referee['idtrongtai'], $assignment)
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet phan cong tran dau thanh cong.',
                'assignment' => $assignment,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet phan cong tran dau.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function tournamentsOfMe(int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $tournaments = $this->referees->tournamentsForReferee((int) $referee['idtrongtai']);
            $this->referees->recordRefereeTournamentListView(
                (int) $referee['idtrongtai'],
                $accountId,
                $request?->ip(),
                $this->limitLogNote('Trong tai #' . (int) $referee['idtrongtai'] . ' xem danh sach giai dau co phan cong. So dong: ' . count($tournaments))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach giai dau cua trong tai thanh cong.',
                'tournaments' => $tournaments,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach giai dau cua trong tai.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function venuesOfMe(int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $venues = $this->referees->venuesForReferee((int) $referee['idtrongtai']);
            $this->referees->recordRefereeVenueListView(
                (int) $referee['idtrongtai'],
                $accountId,
                $request?->ip(),
                $this->limitLogNote('Trong tai #' . (int) $referee['idtrongtai'] . ' xem danh sach san dau co phan cong. So dong: ' . count($venues))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay danh sach san dau cua trong tai thanh cong.',
                'venues' => $venues,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay danh sach san dau cua trong tai.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function findByMatch(int $matchId, int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $assignment = $this->referees->matchAssignmentDetailForReferee((int) $referee['idtrongtai'], $matchId);

            if ($assignment === null) {
                return $this->failure('Khong tim thay tran dau duoc phan cong.', 404);
            }

            $assignment['co_referees'] = $this->referees->coRefereesForMatch($matchId);
            $this->referees->recordRefereeMatchAssignmentView(
                $matchId,
                $accountId,
                $request?->ip(),
                $this->detailLogNote((int) $referee['idtrongtai'], $assignment)
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay chi tiet tran dau duoc phan cong thanh cong.',
                'assignment' => $assignment,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay chi tiet tran dau duoc phan cong.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function matchDetail(int $matchId, int $accountId, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $assignment = $this->referees->matchAssignmentDetailForReferee((int) $referee['idtrongtai'], $matchId);

            if ($assignment === null) {
                return $this->failure('Khong tim thay tran dau duoc phan cong.', 404);
            }

            $sets = ((int) ($assignment['idketqua'] ?? 0)) > 0
                ? $this->referees->setsForResult((int) $assignment['idketqua'])
                : [];
            $coReferees = $this->referees->coRefereesForMatch($matchId);
            $this->referees->recordRefereeMatchDetailView(
                $matchId,
                $accountId,
                $request?->ip(),
                $this->matchDetailLogNote((int) $referee['idtrongtai'], $assignment)
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay thong tin chi tiet tran dau thanh cong.',
                'match' => $this->matchDetailPayload($assignment, $sets, $coReferees),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay thong tin chi tiet tran dau.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function confirm(int $assignmentId, int $accountId, ?Request $request = null): array
    {
        return $this->decideAssignment($assignmentId, $accountId, 'DA_XAC_NHAN', $request);
    }

    public function decline(int $assignmentId, int $accountId, ?Request $request = null): array
    {
        return $this->decideAssignment($assignmentId, $accountId, 'TU_CHOI', $request);
    }

    private function activeReferee(int $accountId): array
    {
        $referee = $this->referees->findByAccountId($accountId);

        if ($referee === null) {
            return $this->failure('Tai khoan khong co ho so trong tai.', 403);
        }

        return $referee;
    }

    private function filters(array $filters): array
    {
        $errors = [];
        $assignmentStatus = strtoupper(trim((string) (
            $filters['assignment_status']
            ?? $filters['trangthai_phancong']
            ?? $filters['status']
            ?? $filters['trangthai']
            ?? ''
        )));
        $matchStatus = strtoupper(trim((string) ($filters['match_status'] ?? $filters['trangthai_trandau'] ?? '')));
        $role = strtoupper(trim((string) ($filters['role'] ?? $filters['vaitro'] ?? '')));
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null);
        $venueId = $this->optionalPositiveInt($filters['venue_id'] ?? $filters['idsandau'] ?? null);
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? ''));

        if ($assignmentStatus !== '' && !in_array($assignmentStatus, self::ASSIGNMENT_STATUSES, true)) {
            $errors['assignment_status'] = 'Trang thai phan cong khong hop le.';
        }

        if ($matchStatus !== '' && !in_array($matchStatus, self::MATCH_STATUSES, true)) {
            $errors['match_status'] = 'Trang thai tran dau khong hop le.';
        }

        if ($role !== '' && !in_array($role, self::ASSIGNMENT_ROLES, true)) {
            $errors['role'] = 'Vai tro trong tai khong hop le.';
        }

        if (($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null) !== null && $tournamentId === null) {
            $errors['tournament_id'] = 'Ma giai dau khong hop le.';
        }

        if (($filters['venue_id'] ?? $filters['idsandau'] ?? null) !== null && $venueId === null) {
            $errors['venue_id'] = 'Ma san dau khong hop le.';
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
            'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            'assignment_status' => $assignmentStatus,
            'match_status' => $matchStatus,
            'role' => $role,
            'tournament_id' => $tournamentId,
            'venue_id' => $venueId,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function decideAssignment(int $assignmentId, int $accountId, string $newStatus, ?Request $request = null): array
    {
        $referee = $this->activeReferee($accountId);

        if (isset($referee['ok']) && $referee['ok'] === false) {
            return $referee;
        }

        try {
            $assignment = $this->referees->assignmentDetailForReferee((int) $referee['idtrongtai'], $assignmentId);

            if ($assignment === null) {
                return $this->failure('Khong tim thay phan cong tran dau.', 404);
            }

            $currentStatus = (string) $assignment['phancong_trangthai'];
            $inputReason = trim((string) ($request?->input('reason', $request?->input('lydo', '')) ?? ''));

            if ($newStatus === 'TU_CHOI') {
                if (!in_array($currentStatus, ['DA_XAC_NHAN', 'CHO_XAC_NHAN'], true)) {
                    return $this->failure('Chi co the huy xac nhan phan cong da xac nhan.', 409);
                }
                if ($inputReason === '') {
                    return $this->failure('Vui long nhap ly do huy xac nhan.', 422);
                }
                $action = 'Huy xac nhan phan cong tran dau';
                $reason = $inputReason;
            } else {
                if ($currentStatus !== 'CHO_XAC_NHAN') {
                    return $this->failure('Chi co the xac nhan phan cong dang cho xac nhan.', 409);
                }
                $action = 'Xac nhan nhan phan cong tran dau';
                $reason = 'Trong tai xac nhan nhan phan cong tran dau';
            }
            $updated = $this->referees->respondToAssignment(
                (int) $referee['idtrongtai'],
                $assignmentId,
                $newStatus,
                $accountId,
                $request?->ip(),
                $this->assignmentDecisionLogNote((int) $referee['idtrongtai'], $assignment, $action),
                $reason
            );
            $updated['co_referees'] = $this->referees->coRefereesForMatch((int) $updated['idtrandau']);

            return [
                'ok' => true,
                'status' => 200,
                'message' => $newStatus === 'DA_XAC_NHAN'
                    ? 'Xac nhan nhan phan cong thanh cong.'
                    : 'Da huy xac nhan phan cong thanh cong.',
                'assignment' => $updated,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat phan hoi phan cong.', 500, [
                'database' => 'Loi cap nhat hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    private function optionalPositiveInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function listLogNote(int $refereeId, array $filters, int $total): string
    {
        $parts = [
            'Trong tai #' . $refereeId . ' xem lich phan cong tran dau',
            'So dong: ' . $total,
        ];

        foreach (['q', 'assignment_status', 'match_status', 'role', 'tournament_id', 'venue_id', 'from', 'to'] as $key) {
            if (($filters[$key] ?? '') !== '' && ($filters[$key] ?? null) !== null) {
                $parts[] = $key . '=' . (string) $filters[$key];
            }
        }

        return $this->limitLogNote(implode('. ', $parts));
    }

    private function detailLogNote(int $refereeId, array $assignment): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d xem phan cong #%d, tran #%d (%s vs %s), vai tro %s.',
            $refereeId,
            (int) $assignment['idphancong'],
            (int) $assignment['idtrandau'],
            (string) ($assignment['doi1'] ?? ''),
            (string) ($assignment['doi2'] ?? ''),
            (string) ($assignment['vaitro'] ?? '')
        ));
    }

    private function assignmentDecisionLogNote(int $refereeId, array $assignment, string $action): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d %s phan cong #%d, tran #%d (%s vs %s), vai tro %s.',
            $refereeId,
            strtolower($action),
            (int) $assignment['idphancong'],
            (int) $assignment['idtrandau'],
            (string) ($assignment['doi1'] ?? ''),
            (string) ($assignment['doi2'] ?? ''),
            (string) ($assignment['vaitro'] ?? '')
        ));
    }

    private function matchDetailLogNote(int $refereeId, array $assignment): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d xem thong tin chi tiet tran #%d (%s vs %s), giai #%d.',
            $refereeId,
            (int) $assignment['idtrandau'],
            (string) ($assignment['doi1'] ?? ''),
            (string) ($assignment['doi2'] ?? ''),
            (int) $assignment['idgiaidau']
        ));
    }

    private function matchDetailPayload(array $assignment, array $sets, array $coReferees): array
    {
        $winnerId = $assignment['iddoithang'] === null ? null : (int) $assignment['iddoithang'];

        return [
            'idtrandau' => (int) $assignment['idtrandau'],
            'vongdau' => $assignment['vongdau'],
            'trangthai' => $assignment['trandau_trangthai'],
            'thoigianbatdau' => $assignment['thoigianbatdau'],
            'thoigianketthuc' => $assignment['thoigianketthuc'],
            'giaidau' => [
                'idgiaidau' => (int) $assignment['idgiaidau'],
                'tengiaidau' => $assignment['tengiaidau'],
                'trangthai' => $assignment['giaidau_trangthai'],
            ],
            'bangdau' => [
                'idbangdau' => $assignment['idbangdau'] === null ? null : (int) $assignment['idbangdau'],
                'tenbang' => $assignment['tenbang'],
            ],
            'sandau' => [
                'idsandau' => (int) $assignment['idsandau'],
                'tensandau' => $assignment['tensandau'],
                'diachi' => $assignment['sandau_diachi'],
                'trangthai' => $assignment['sandau_trangthai'],
            ],
            'doi1' => [
                'iddoibong' => (int) $assignment['iddoibong1'],
                'tendoibong' => $assignment['doi1'],
            ],
            'doi2' => [
                'iddoibong' => (int) $assignment['iddoibong2'],
                'tendoibong' => $assignment['doi2'],
            ],
            'ketqua' => $assignment['idketqua'] === null ? null : [
                'idketqua' => (int) $assignment['idketqua'],
                'trangthai' => $assignment['ketqua_trangthai'],
                'diemdoi1' => (int) $assignment['diemdoi1'],
                'diemdoi2' => (int) $assignment['diemdoi2'],
                'sosetdoi1' => (int) $assignment['sosetdoi1'],
                'sosetdoi2' => (int) $assignment['sosetdoi2'],
                'iddoithang' => $winnerId,
                'doithang' => $winnerId === (int) $assignment['iddoibong1']
                    ? $assignment['doi1']
                    : ($winnerId === (int) $assignment['iddoibong2'] ? $assignment['doi2'] : null),
                'sets' => $sets,
            ],
            'phancong_cua_toi' => [
                'idphancong' => (int) $assignment['idphancong'],
                'idtrongtai' => (int) $assignment['idtrongtai'],
                'vaitro' => $assignment['vaitro'],
                'trangthai' => $assignment['phancong_trangthai'],
                'ngayphancong' => $assignment['ngayphancong'],
                'xacnhanthamgia' => $assignment['xacnhanthamgia'] === null ? null : (bool) $assignment['xacnhanthamgia'],
                'thoigianxacnhan' => $assignment['thoigianxacnhan'],
            ],
            'trongtai_cung_tran' => $coReferees,
        ];
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

