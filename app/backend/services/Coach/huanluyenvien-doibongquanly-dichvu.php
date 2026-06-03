<?php

declare(strict_types=1);

namespace App\Backend\Services\Coach;

use App\Backend\Core\Auth\Auth;
use App\Backend\Core\Http\Request;
use App\Backend\Models\Doibong;
use App\Backend\Models\Ketquatrandau;
use App\Backend\Models\Khieunai;
use App\Backend\Models\Yeucaucapnhathoso;
use RuntimeException;
use Throwable;

final class CoachTeamManagementService
{
    private const TEAM_STATUSES = ['HOAT_DONG', 'CHO_DUYET', 'TAM_KHOA', 'GIAI_THE'];
    private const MEMBER_ROLES = ['DOI_TRUONG', 'THANH_VIEN', 'DU_BI'];
    private const LINEUP_STATUSES = ['BAN_NHAP', 'DA_CHOT', 'DA_CAP_NHAT'];
    private const LINEUP_GENDERS = ['NAM', 'NU'];
    private const ATHLETE_POSITIONS = ['CHU_CONG', 'PHU_CONG', 'CHUYEN_HAI', 'DOI_CHUYEN', 'LIBERO', 'DOI_TRU'];
    private const MATCH_STATUSES = ['CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];
    private const RESULT_STATUSES = ['DA_CONG_BO', 'DA_DIEU_CHINH'];
    private const REQUEST_STATUSES = ['CHO_DUYET', 'DA_DUYET', 'TU_CHOI'];
    private const ATHLETE_TARGET_TABLES = ['Nguoidung', 'Taikhoan', 'Vandongvien'];

    private const ATHLETE_ALLOWED_FIELDS = [
        'Nguoidung' => ['ten', 'hodem', 'gioitinh', 'ngaysinh', 'quequan', 'diachi', 'avatar', 'cccd'],
        'Taikhoan' => ['username', 'email', 'sodienthoai'],
        'Vandongvien' => ['mavandongvien', 'chieucao', 'cannang', 'vitri'],
    ];

    public function __construct(
        private ?Doibong $teams = null,
        private ?Ketquatrandau $results = null,
        private ?Khieunai $complaints = null,
        private ?Yeucaucapnhathoso $profileRequests = null
    ) {
        $this->teams ??= new Doibong();
        $this->results ??= new Ketquatrandau();
        $this->complaints ??= new Khieunai();
        $this->profileRequests ??= new Yeucaucapnhathoso();
    }

    public function teams(int $accountId, array $filters = []): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        [$normalized, $errors] = $this->teamFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc doi bong khong hop le.', 422, $errors);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach doi bong cua HLV thanh cong.',
            'teams' => $this->teams->listForCoach((int) $coach['idhuanluyenvien'], $normalized),
            'meta' => [
                'filters' => $normalized,
                'statuses' => self::TEAM_STATUSES,
            ],
        ];
    }

    public function team(int $teamId, int $accountId): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $team = $this->teams->findForCoach((int) $coach['idhuanluyenvien'], $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        $team['members'] = $this->teams->membersForTeam($teamId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin doi bong thanh cong.',
            'team' => $team,
        ];
    }

    public function createTeam(array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        [$data, $errors] = $this->teamPayload($payload, false);

        if ($errors !== []) {
            return $this->failure('Du lieu doi bong khong hop le.', 422, $errors);
        }

        if (!isset($data['idkhuvucdaidien'])) {
            $coachRegionId = $this->sessionCoachWorkRegionId((int) $coach['idhuanluyenvien'])
                ?? $this->positiveInt($coach['idkhuvuccongtac'] ?? null);

            if ($coachRegionId === null) {
                return $this->failure('HLV chua co khu vuc cong tac, khong the tao doi bong.', 409, [
                    'idkhuvucdaidien' => 'Can cap nhat khu vuc cong tac cua HLV truoc khi tao doi bong.',
                ]);
            }

            $data['idkhuvucdaidien'] = $coachRegionId;
        }

        if ($this->teams->teamNameExists($data['tendoibong'])) {
            return $this->failure('Ten doi bong da ton tai.', 409, [
                'tendoibong' => 'Ten doi bong phai duy nhat.',
            ]);
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d tao doi bong "%s".',
            $coachId,
            $data['tendoibong']
        ));

        try {
            $teamId = $this->teams->createForCoach($data, $coachId, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao doi bong thanh cong.',
                'team' => $this->teams->findForCoach($coachId, $teamId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao doi bong.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function updateTeam(int $teamId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $team = $this->teams->findForCoach($coachId, $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        [$data, $errors] = $this->teamPayload($payload, true);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat doi bong khong hop le.', 422, $errors);
        }

        if ($data === []) {
            return $this->failure('Khong co du lieu can cap nhat.', 422);
        }

        if (isset($data['tendoibong']) && $this->teams->teamNameExists($data['tendoibong'], $teamId)) {
            return $this->failure('Ten doi bong da ton tai.', 409, [
                'tendoibong' => 'Ten doi bong phai duy nhat.',
            ]);
        }

        $newStatus = $data['trangthai'] ?? null;
        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d cap nhat doi bong #%d "%s".',
            $coachId,
            $teamId,
            (string) $team['tendoibong']
        ));

        try {
            $this->teams->updateForCoach(
                $teamId,
                $coachId,
                $data,
                (string) $team['trangthai'],
                $newStatus,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat doi bong thanh cong.',
                'team' => $this->teams->findForCoach($coachId, $teamId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'TEAM_NOT_UPDATED') {
                return $this->failure('Thong tin doi bong da thay doi, vui long tai lai.', 409);
            }

            return $this->failure('Khong the cap nhat doi bong.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat doi bong.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function members(int $teamId, int $accountId): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $team = $this->teams->findForCoach((int) $coach['idhuanluyenvien'], $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach thanh vien doi bong thanh cong.',
            'members' => $this->teams->membersForTeam($teamId),
            'team' => $team,
        ];
    }

    public function addMember(int $teamId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $team = $this->teams->findForCoach($coachId, $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        [$data, $errors] = $this->memberPayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu thanh vien khong hop le.', 422, $errors);
        }

        $athlete = $this->teams->athleteForCoachScope($coachId, $data['athlete_identifier']);

        if ($athlete === null) {
            return $this->failure('Khong tim thay van dong vien hop le de them vao doi.', 404);
        }

        $athleteId = (int) $athlete['idvandongvien'];
        $data['idvandongvien'] = $athleteId;

        if ((string) $athlete['trangthaidaugiai'] !== 'DU_DIEU_KIEN') {
            return $this->failure('Chi duoc them van dong vien du dieu kien thi dau.', 409);
        }

        if ($this->teams->membershipForTeamAthlete($teamId, $athleteId) !== null) {
            return $this->failure('Van dong vien da co lich su thanh vien trong doi nay.', 409);
        }

        $activeMembership = $this->teams->activeMembershipForAthlete($athleteId);

        if ($activeMembership !== null) {
            return $this->failure('Van dong vien dang thuoc mot doi bong khac.', 409, [
                'current_team' => $activeMembership['tendoibong'] ?? null,
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d them VDV #%d vao doi #%d "%s".',
            $coachId,
            $athleteId,
            $teamId,
            (string) $team['tendoibong']
        ));

        try {
            $memberId = $this->teams->addMember(
                $teamId,
                $coachId,
                $athleteId,
                $data['vaitro'],
                $data['ngaythamgia'],
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Them thanh vien doi bong thanh cong.',
                'member' => $this->teams->memberForCoach($coachId, $memberId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the them thanh vien doi bong.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function removeMember(int $memberId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $member = $this->teams->memberForCoach($coachId, $memberId);

        if ($member === null) {
            return $this->failure('Khong tim thay thanh vien doi bong.', 404);
        }

        if (!in_array((string) $member['trangthai'], ['CHO_XAC_NHAN', 'DANG_THAM_GIA'], true)) {
            return $this->failure('Chi duoc xoa thanh vien dang tham gia hoac cho xac nhan.', 409);
        }

        $reason = $this->reason($payload, 'HLV xoa thanh vien doi bong');
        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d xoa thanh vien #%d (%s) khoi doi #%d. Ly do: %s',
            $coachId,
            $memberId,
            (string) $member['hoten'],
            (int) $member['iddoibong'],
            $reason
        ));

        try {
            $this->teams->removeMember($memberId, $coachId, $reason, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xoa thanh vien doi bong thanh cong.',
                'member' => $this->teams->memberForCoach($coachId, $memberId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'MEMBER_NOT_REMOVED') {
                return $this->failure('Trang thai thanh vien da thay doi, khong the xoa.', 409);
            }

            return $this->failure('Khong the xoa thanh vien doi bong.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xoa thanh vien doi bong.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function transferMember(int $memberId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $member = $this->teams->memberForCoach($coachId, $memberId);

        if ($member === null) {
            return $this->failure('Khong tim thay thanh vien doi bong.', 404);
        }

        [$data, $errors] = $this->transferPayload($payload, (string) $member['vaitro']);

        if ($errors !== []) {
            return $this->failure('Du lieu chuyen doi thanh vien khong hop le.', 422, $errors);
        }

        if ((int) $member['iddoibong'] === $data['target_team_id']) {
            return $this->failure('Doi dich phai khac doi hien tai.', 422);
        }

        $targetTeam = $this->teams->findForCoach($coachId, $data['target_team_id']);

        if ($targetTeam === null) {
            return $this->failure('Khong tim thay doi bong dich cua HLV.', 404);
        }

        if ($this->teams->membershipForTeamAthlete($data['target_team_id'], (int) $member['idvandongvien']) !== null) {
            return $this->failure('Van dong vien da co lich su thanh vien trong doi dich.', 409);
        }

        $reason = $data['reason'] ?? 'HLV chuyen doi thanh vien doi bong';
        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d chuyen VDV #%d tu doi #%d sang doi #%d. Ly do: %s',
            $coachId,
            (int) $member['idvandongvien'],
            (int) $member['iddoibong'],
            $data['target_team_id'],
            $reason
        ));

        try {
            $newMemberId = $this->teams->transferMember(
                $memberId,
                $coachId,
                $data['target_team_id'],
                $data['vaitro'],
                $reason,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Chuyen doi thanh vien doi bong thanh cong.',
                'member' => $this->teams->memberForCoach($coachId, $newMemberId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'MEMBER_NOT_TRANSFERRED') {
                return $this->failure('Khong the chuyen doi thanh vien voi trang thai hien tai.', 409);
            }

            return $this->failure('Khong the chuyen doi thanh vien doi bong.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the chuyen doi thanh vien doi bong.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function lineups(int $teamId, int $accountId, array $filters = []): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $team = $this->teams->findForCoach((int) $coach['idhuanluyenvien'], $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        $tournamentId = $this->positiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach doi hinh thanh cong.',
            'lineups' => $this->teams->lineupsForTeam($teamId, $tournamentId),
            'details' => $this->teams->lineupDetailsForTeam($teamId, $tournamentId),
            'team' => $team,
        ];
    }

    public function lineupList(int $accountId, array $filters = []): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $teamId = $this->positiveInt($filters['team_id'] ?? $filters['iddoibong'] ?? null);
        $tournamentId = $this->positiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null);

        if ($teamId !== null && $this->teams->findForCoach((int) $coach['idhuanluyenvien'], $teamId) === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach doi hinh thanh cong.',
            'lineups' => $this->teams->lineupsForCoach((int) $coach['idhuanluyenvien'], $teamId, $tournamentId),
            'details' => $this->teams->lineupDetailsForCoach((int) $coach['idhuanluyenvien'], $teamId, $tournamentId),
            'meta' => [
                'team_id' => $teamId,
                'tournament_id' => $tournamentId,
            ],
        ];
    }

    public function lineup(int $lineupId, int $accountId): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $lineup = $this->teams->lineupForCoach((int) $coach['idhuanluyenvien'], $lineupId);

        if ($lineup === null) {
            return $this->failure('Khong tim thay doi hinh.', 404);
        }

        $lineup['details'] = $this->teams->lineupDetails($lineupId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin doi hinh thanh cong.',
            'lineup' => $lineup,
        ];
    }

    public function createLineup(int $teamId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $team = $this->teams->findForCoach($coachId, $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        [$lineup, $details, $errors] = $this->lineupPayload($payload, false, $teamId);

        if ($errors !== []) {
            return $this->failure('Du lieu doi hinh khong hop le.', 422, $errors);
        }

        if ($this->teams->lineupNameExists($teamId, $lineup['tendoihinh'])) {
            return $this->failure('Ten doi hinh da ton tai trong doi bong.', 409);
        }

        $memberErrors = $this->validateLineupMembers($teamId, $details, (string) $lineup['gioitinh']);

        if ($memberErrors !== []) {
            return $this->failure('Thanh vien doi hinh khong hop le.', 422, $memberErrors);
        }

        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d tao doi hinh "%s" cho doi #%d.',
            $coachId,
            $lineup['tendoihinh'],
            $teamId
        ));

        try {
            $lineupId = $this->teams->createLineup($teamId, $lineup['idgiaidau'] ?? null, $lineup, $details, $accountId, $request?->ip(), $logNote);
            $created = $this->teams->lineupForCoach($coachId, $lineupId);
            $created['details'] = $this->teams->lineupDetails($lineupId);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao doi hinh thanh cong.',
                'lineup' => $created,
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao doi hinh.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function updateLineup(int $lineupId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $lineup = $this->teams->lineupForCoach($coachId, $lineupId);

        if ($lineup === null) {
            return $this->failure('Khong tim thay doi hinh.', 404);
        }

        [$changes, $details, $errors] = $this->lineupPayload($payload, true, (int) $lineup['iddoibong'], $lineup);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat doi hinh khong hop le.', 422, $errors);
        }

        if ($changes === [] && $details === null) {
            return $this->failure('Khong co du lieu can cap nhat.', 422);
        }

        $newName = $changes['tendoihinh'] ?? (string) $lineup['tendoihinh'];

        if ($this->teams->lineupNameExists((int) $lineup['iddoibong'], $newName, $lineupId)) {
            return $this->failure('Ten doi hinh da ton tai trong doi bong.', 409);
        }

        if ($details !== null) {
            $memberErrors = $this->validateLineupMembers(
                (int) $lineup['iddoibong'],
                $details,
                (string) ($changes['gioitinh'] ?? $lineup['gioitinh'] ?? 'NAM')
            );

            if ($memberErrors !== []) {
                return $this->failure('Thanh vien doi hinh khong hop le.', 422, $memberErrors);
            }
        }

        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d cap nhat doi hinh #%d "%s".',
            $coachId,
            $lineupId,
            (string) $lineup['tendoihinh']
        ));

        try {
            $this->teams->updateLineup($lineupId, $changes, $details, $accountId, $request?->ip(), $logNote);
            $updated = $this->teams->lineupForCoach($coachId, $lineupId);
            $updated['details'] = $this->teams->lineupDetails($lineupId);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat doi hinh thanh cong.',
                'lineup' => $updated,
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'LINEUP_NOT_UPDATED') {
                return $this->failure('Thong tin doi hinh da thay doi, vui long tai lai.', 409);
            }

            return $this->failure('Khong the cap nhat doi hinh.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat doi hinh.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function schedule(int $teamId, int $accountId, array $filters = [], ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $team = $this->teams->findForCoach($coachId, $teamId);

        if ($team === null) {
            return $this->failure('Khong tim thay doi bong cua HLV.', 404);
        }

        [$normalized, $errors] = $this->scheduleFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc lich thi dau khong hop le.', 422, $errors);
        }

        try {
            $matches = $this->teams->scheduleForCoachTeam($coachId, $teamId, $normalized);
            $this->teams->recordCoachSystemLog(
                $accountId,
                'Xem lich thi dau doi bong',
                'Trandau',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf(
                    'HLV #%d xem lich thi dau doi #%d "%s". So tran: %d.',
                    $coachId,
                    $teamId,
                    (string) $team['tendoibong'],
                    count($matches)
                ))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay lich thi dau doi bong thanh cong.',
                'matches' => $matches,
                'team' => $team,
                'meta' => [
                    'filters' => $normalized,
                    'statuses' => self::MATCH_STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay lich thi dau doi bong.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function results(int $accountId, array $filters = [], ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        [$normalized, $errors] = $this->resultFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc ket qua thi dau khong hop le.', 422, $errors);
        }

        try {
            $items = $this->results->listForCoach((int) $coach['idhuanluyenvien'], $normalized);
            $this->teams->recordCoachSystemLog(
                $accountId,
                'Xem ket qua thi dau',
                'Ketquatrandau',
                null,
                $request?->ip(),
                $this->limitLogNote(sprintf(
                    'HLV #%d xem ket qua thi dau. So ket qua: %d.',
                    (int) $coach['idhuanluyenvien'],
                    count($items)
                ))
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay ket qua thi dau thanh cong.',
                'results' => $items,
                'meta' => [
                    'filters' => $normalized,
                    'statuses' => self::RESULT_STATUSES,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay ket qua thi dau.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function complainResult(int $resultId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $result = $this->results->findForCoach((int) $coach['idhuanluyenvien'], $resultId);

        if ($result === null) {
            return $this->failure('Khong tim thay ket qua thi dau cua doi HLV.', 404);
        }

        [$content, $evidence, $errors] = $this->complaintPayload($payload);

        if ($errors !== []) {
            return $this->failure('Du lieu khieu nai ket qua khong hop le.', 422, $errors);
        }

        $matchCode = trim((string) ($result['ma_tran'] ?? ''));
        $title = 'Khieu nai ket qua tran ' . ($matchCode !== '' ? $matchCode : ('#' . (int) $result['idtrandau']));
        $logNote = $this->limitLogNote(sprintf(
            'HLV #%d gui khieu nai ket qua tran #%d giai "%s".',
            (int) $coach['idhuanluyenvien'],
            (int) $result['idtrandau'],
            (string) $result['tengiaidau']
        ));

        try {
            $complaintId = $this->complaints->createForMatchResult(
                $accountId,
                (int) $result['idgiaidau'],
                (int) $result['idtrandau'],
                $title,
                $content,
                $evidence,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Gui khieu nai ket qua thi dau thanh cong.',
                'complaint' => [
                    'idkhieunai' => $complaintId,
                    'idketqua' => $resultId,
                    'idtrandau' => (int) $result['idtrandau'],
                    'idgiaidau' => (int) $result['idgiaidau'],
                    'trangthai' => 'CHO_TIEP_NHAN',
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the gui khieu nai ket qua thi dau.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function athleteChangeRequests(int $accountId, array $filters = []): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        [$normalized, $pagination, $errors] = $this->profileRequestFilters($filters);

        if ($errors !== []) {
            return $this->failure('Bo loc yeu cau thay doi thong tin VDV khong hop le.', 422, $errors);
        }

        $coachId = (int) $coach['idhuanluyenvien'];

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach yeu cau thay doi thong tin VDV thanh cong.',
            'requests' => $this->profileRequests->listAthleteChangeRequestsForCoach($coachId, $normalized, $pagination['limit'], $pagination['offset']),
            'meta' => [
                'filters' => $normalized,
                'pagination' => $pagination,
                'total' => $this->profileRequests->countAthleteChangeRequestsForCoach($coachId, $normalized),
                'counts' => $this->profileRequests->statusCountsAthleteChangeRequestsForCoach($coachId, $normalized),
                'statuses' => self::REQUEST_STATUSES,
                'target_tables' => self::ATHLETE_TARGET_TABLES,
            ],
        ];
    }

    public function athleteChangeRequest(int $requestId, int $accountId): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $item = $this->profileRequests->findAthleteChangeRequestForCoach((int) $coach['idhuanluyenvien'], $requestId);

        if ($item === null) {
            return $this->failure('Khong tim thay yeu cau thay doi thong tin VDV.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin yeu cau thay doi thong tin VDV thanh cong.',
            'request' => $item,
        ];
    }

    public function approveAthleteChangeRequest(int $requestId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $item = $this->profileRequests->findAthleteChangeRequestForCoach($coachId, $requestId);

        if ($item === null) {
            return $this->failure('Khong tim thay yeu cau thay doi thong tin VDV.', 404);
        }

        if ((string) $item['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc xac nhan yeu cau dang cho duyet.', 409);
        }

        [$value, $errors] = $this->profileNewValue($item);

        if ($errors !== []) {
            return $this->failure('Gia tri thay doi thong tin VDV khong hop le.', 422, $errors);
        }

        $targetTable = (string) $item['banglienquan'];
        $field = (string) $item['truongcapnhat'];

        if ($this->profileRequests->personalUniqueValueExists($targetTable, $field, (string) $value, (int) $item['idnguoidung'])) {
            return $this->failure('Gia tri moi da duoc su dung.', 409, [
                $field => 'Gia tri moi phai duy nhat.',
            ]);
        }

        $note = $this->limitLogNote((string) ($payload['note'] ?? $payload['ghichu'] ?? 'HLV xac nhan thay doi thong tin VDV'));

        try {
            $this->profileRequests->approvePersonalChangeRequest(
                $requestId,
                (int) $item['idnguoidung'],
                $targetTable,
                $field,
                $value,
                (int) $item['iddoituong'],
                $accountId,
                $request?->ip(),
                $note
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xac nhan thay doi thong tin VDV thanh cong.',
                'request' => $this->profileRequests->findAthleteChangeRequestForCoach($coachId, $requestId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REQUEST_NOT_PENDING') {
                return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
            }

            if ($exception->getMessage() === 'TARGET_NOT_UPDATED') {
                return $this->failure('Khong cap nhat duoc thong tin dich.', 409);
            }

            return $this->failure('Khong the xac nhan thay doi thong tin VDV.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xac nhan thay doi thong tin VDV.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function rejectAthleteChangeRequest(int $requestId, array $payload, int $accountId, ?Request $request = null): array
    {
        $coach = $this->activeCoach($accountId);

        if (isset($coach['ok']) && $coach['ok'] === false) {
            return $coach;
        }

        $coachId = (int) $coach['idhuanluyenvien'];
        $item = $this->profileRequests->findAthleteChangeRequestForCoach($coachId, $requestId);

        if ($item === null) {
            return $this->failure('Khong tim thay yeu cau thay doi thong tin VDV.', 404);
        }

        if ((string) $item['trangthai'] !== 'CHO_DUYET') {
            return $this->failure('Chi duoc huy yeu cau dang cho duyet.', 409);
        }

        $note = trim((string) ($payload['note'] ?? $payload['ghichu'] ?? $payload['reason'] ?? $payload['lydo'] ?? ''));

        if ($note === '') {
            return $this->failure('Vui long nhap ghi chu khi huy yeu cau.', 422, [
                'note' => 'Ghi chu bat buoc.',
            ]);
        }

        $note = $this->limitLogNote($note);

        try {
            $this->profileRequests->rejectPersonalChangeRequest($requestId, $accountId, $request?->ip(), $note);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Huy thay doi thong tin VDV thanh cong.',
                'request' => $this->profileRequests->findAthleteChangeRequestForCoach($coachId, $requestId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'REQUEST_NOT_PENDING') {
                return $this->failure('Yeu cau da duoc xu ly truoc do.', 409);
            }

            return $this->failure('Khong the huy thay doi thong tin VDV.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the huy thay doi thong tin VDV.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function activeCoach(int $accountId): array
    {
        $coach = $this->teams->coachByAccountId($accountId);

        if ($coach === null) {
            return $this->failure('Tai khoan khong co ho so huan luyen vien.', 403);
        }

        if ((string) $coach['trangthai'] !== 'DA_XAC_NHAN') {
            return $this->failure('Huan luyen vien chua duoc xac nhan tu cach.', 403);
        }

        return $coach;
    }

    private function sessionCoachWorkRegionId(int $coachId): ?int
    {
        $user = Auth::user();

        if (!is_array($user)) {
            return null;
        }

        $sessionCoachId = $this->positiveInt($user['idhuanluyenvien'] ?? ($user['coach']['idhuanluyenvien'] ?? null));

        if ($sessionCoachId !== $coachId) {
            return null;
        }

        return $this->positiveInt($user['idkhuvuccongtac'] ?? ($user['coach']['idkhuvuccongtac'] ?? null));
    }

    private function teamFilters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $errors = [];

        if ($status !== '' && !in_array($status, self::TEAM_STATUSES, true)) {
            $errors['status'] = 'Trang thai doi bong khong hop le.';
        }

        return [[
            'q' => $keyword,
            'status' => $status,
        ], $errors];
    }

    private function teamPayload(array $payload, bool $partial): array
    {
        $map = [
            'tendoibong' => ['tendoibong', 'name', 'team_name'],
            'logo' => ['logo'],
            'idkhuvucdaidien' => ['idkhuvucdaidien', 'representative_region_id', 'region_id', 'khuvuc_id'],
            'diaphuong' => ['diaphuong', 'local', 'location'],
            'mota' => ['mota', 'description', 'note'],
            'trangthai' => ['trangthai', 'status'],
        ];
        $data = [];
        $errors = [];

        foreach ($map as $field => $keys) {
            $found = false;
            $value = null;

            foreach ($keys as $key) {
                if (array_key_exists($key, $payload)) {
                    $found = true;
                    $value = $payload[$key];
                    break;
                }
            }

            if (!$found) {
                continue;
            }

            if (in_array($field, ['tendoibong', 'logo', 'diaphuong', 'mota'], true)) {
                $value = trim((string) $value);
                $data[$field] = $value === '' && $field !== 'tendoibong' ? null : $value;
                continue;
            }

            if ($field === 'idkhuvucdaidien') {
                $regionId = $this->positiveInt($value);

                if ($regionId === null) {
                    $errors[$field] = 'Khu vuc dai dien cua doi bong khong hop le.';
                } else {
                    $data[$field] = $regionId;
                }

                continue;
            }

            if ($field === 'trangthai') {
                $data[$field] = strtoupper(trim((string) $value));
            }
        }

        if (!$partial && !isset($data['tendoibong'])) {
            $data['tendoibong'] = '';
        }

        if (isset($data['tendoibong'])) {
            if ($data['tendoibong'] === '') {
                $errors['tendoibong'] = 'Ten doi bong bat buoc.';
            } elseif (strlen($data['tendoibong']) > 200) {
                $errors['tendoibong'] = 'Ten doi bong toi da 200 ky tu.';
            }
        }

        if (!$partial && !array_key_exists('logo', $data)) {
            $data['logo'] = null;
        }

        if (!$partial && !array_key_exists('diaphuong', $data)) {
            $data['diaphuong'] = null;
        }

        if (!$partial && !array_key_exists('mota', $data)) {
            $data['mota'] = null;
        }

        if (!$partial && !isset($data['trangthai'])) {
            $data['trangthai'] = 'HOAT_DONG';
        }

        if (isset($data['trangthai']) && !in_array($data['trangthai'], self::TEAM_STATUSES, true)) {
            $errors['trangthai'] = 'Trang thai doi bong khong hop le.';
        }

        foreach (['logo' => 500, 'diaphuong' => 200] as $field => $max) {
            if (isset($data[$field]) && $data[$field] !== null && strlen((string) $data[$field]) > $max) {
                $errors[$field] = "Gia tri toi da {$max} ky tu.";
            }
        }

        return [$data, $errors];
    }

    private function memberPayload(array $payload): array
    {
        $athleteIdentifier = trim((string) ($payload['idvandongvien'] ?? $payload['athlete_id'] ?? $payload['mavandongvien'] ?? $payload['athlete_code'] ?? ''));
        $role = strtoupper(trim((string) ($payload['vaitro'] ?? $payload['role'] ?? 'THANH_VIEN')));
        $joinDate = trim((string) ($payload['ngaythamgia'] ?? $payload['join_date'] ?? date('Y-m-d')));
        $errors = [];

        if ($athleteIdentifier === '') {
            $errors['idvandongvien'] = 'Van dong vien khong hop le.';
        }

        if (!in_array($role, self::MEMBER_ROLES, true)) {
            $errors['vaitro'] = 'Vai tro thanh vien khong hop le.';
        }

        if (!$this->isDate($joinDate)) {
            $errors['ngaythamgia'] = 'Ngay tham gia khong hop le.';
        }

        return [[
            'athlete_identifier' => $athleteIdentifier,
            'idvandongvien' => $this->positiveInt($athleteIdentifier) ?? 0,
            'vaitro' => $role,
            'ngaythamgia' => $joinDate,
        ], $errors];
    }

    private function transferPayload(array $payload, string $defaultRole): array
    {
        $teamId = $this->positiveInt($payload['target_team_id'] ?? $payload['iddoibong_dich'] ?? $payload['to_team_id'] ?? null);
        $role = strtoupper(trim((string) ($payload['vaitro'] ?? $payload['role'] ?? $defaultRole)));
        $reason = trim((string) ($payload['reason'] ?? $payload['lydo'] ?? ''));
        $errors = [];

        if ($teamId === null) {
            $errors['target_team_id'] = 'Doi bong dich khong hop le.';
        }

        if (!in_array($role, self::MEMBER_ROLES, true)) {
            $errors['vaitro'] = 'Vai tro thanh vien khong hop le.';
        }

        if ($reason !== '' && strlen($reason) > 1000) {
            $errors['reason'] = 'Ly do toi da 1000 ky tu.';
        }

        return [[
            'target_team_id' => $teamId ?? 0,
            'vaitro' => $role,
            'reason' => $reason === '' ? null : $reason,
        ], $errors];
    }

    private function lineupPayload(array $payload, bool $partial, int $teamId, ?array $current = null): array
    {
        $changes = [];
        $errors = [];
        $details = null;

        $namePresent = array_key_exists('tendoihinh', $payload) || array_key_exists('name', $payload);
        $tournamentPresent = array_key_exists('idgiaidau', $payload) || array_key_exists('tournament_id', $payload);
        $statusPresent = array_key_exists('trangthai', $payload) || array_key_exists('status', $payload);
        $genderPresent = array_key_exists('gioitinh', $payload) || array_key_exists('gender', $payload);
        $mainPresent = array_key_exists('la_doihinh_chinh', $payload)
            || array_key_exists('is_main', $payload)
            || array_key_exists('main', $payload)
            || array_key_exists('official', $payload);

        if ($namePresent) {
            $name = trim((string) ($payload['tendoihinh'] ?? $payload['name'] ?? ''));

            if ($name === '') {
                $errors['tendoihinh'] = 'Ten doi hinh bat buoc.';
            } elseif (strlen($name) > 300) {
                $errors['tendoihinh'] = 'Ten doi hinh toi da 300 ky tu.';
            } else {
                $changes['tendoihinh'] = $name;
            }
        } elseif (!$partial) {
            $errors['tendoihinh'] = 'Ten doi hinh bat buoc.';
        }

        if ($tournamentPresent) {
            $rawTournamentId = $payload['idgiaidau'] ?? $payload['tournament_id'] ?? null;
            $tournamentId = $this->positiveInt($rawTournamentId);

            if ($rawTournamentId === null || trim((string) $rawTournamentId) === '') {
                $changes['idgiaidau'] = null;
            } elseif ($tournamentId === null) {
                $errors['idgiaidau'] = 'Giai dau khong hop le.';
            } else {
                $changes['idgiaidau'] = $tournamentId;
            }
        }

        if ($statusPresent) {
            $status = strtoupper(trim((string) ($payload['trangthai'] ?? $payload['status'] ?? '')));

            if (!in_array($status, self::LINEUP_STATUSES, true)) {
                $errors['trangthai'] = 'Trang thai doi hinh khong hop le.';
            } else {
                $changes['trangthai'] = $status;
            }
        } elseif (!$partial) {
            $changes['trangthai'] = 'BAN_NHAP';
        }

        if ($genderPresent) {
            $gender = strtoupper(trim((string) ($payload['gioitinh'] ?? $payload['gender'] ?? '')));

            if (!in_array($gender, self::LINEUP_GENDERS, true)) {
                $errors['gioitinh'] = 'Gioi tinh doi hinh khong hop le.';
            } else {
                $changes['gioitinh'] = $gender;
            }
        } elseif (!$partial) {
            $changes['gioitinh'] = 'NAM';
        }

        if ($mainPresent) {
            $changes['la_doihinh_chinh'] = $this->boolInt(
                $payload['la_doihinh_chinh'] ?? $payload['is_main'] ?? $payload['main'] ?? $payload['official'] ?? false
            );
        } elseif (!$partial) {
            $changes['la_doihinh_chinh'] = 0;
        }

        $detailRaw = $payload['details'] ?? $payload['members'] ?? $payload['athletes'] ?? null;

        if ($detailRaw !== null) {
            if (!is_array($detailRaw)) {
                $errors['details'] = 'Danh sach chi tiet doi hinh khong hop le.';
            } else {
                [$details, $detailErrors] = $this->lineupDetailsPayload($detailRaw);
                $errors = array_merge($errors, $detailErrors);
            }
        } elseif (!$partial) {
            $errors['details'] = 'Doi hinh can co danh sach van dong vien.';
        }

        if (!$partial) {
            $changes['tendoihinh'] ??= '';
            $changes['trangthai'] ??= 'BAN_NHAP';
            $changes['gioitinh'] ??= 'NAM';
            $changes['la_doihinh_chinh'] ??= 0;
            $details ??= [];
        }

        return [$changes, $details, $errors];
    }

    private function lineupDetailsPayload(array $items): array
    {
        $details = [];
        $errors = [];
        $seenAthletes = [];
        $seenOrders = [];

        foreach (array_values($items) as $idx => $item) {
            if (!is_array($item)) {
                $errors["details.{$idx}"] = 'Chi tiet doi hinh khong hop le.';
                continue;
            }

            $athleteId = $this->positiveInt($item['idvandongvien'] ?? $item['athlete_id'] ?? null);
            $position = strtoupper(trim((string) ($item['vitri'] ?? $item['position'] ?? '')));
            $orderRaw = $item['sothutu'] ?? $item['order'] ?? $item['number'] ?? ($idx + 1);
            $order = $this->positiveInt($orderRaw);
            $note = trim((string) ($item['ghichu'] ?? $item['note'] ?? ''));

            if ($athleteId === null) {
                $errors["details.{$idx}.idvandongvien"] = 'Van dong vien khong hop le.';
                continue;
            }

            if (isset($seenAthletes[$athleteId])) {
                $errors["details.{$idx}.idvandongvien"] = 'Van dong vien bi trung trong doi hinh.';
            }

            if (!in_array($position, self::ATHLETE_POSITIONS, true)) {
                $errors["details.{$idx}.vitri"] = 'Vi tri thi dau khong hop le.';
            }

            if ($order === null) {
                $errors["details.{$idx}.sothutu"] = 'So thu tu phai lon hon 0.';
            } elseif (isset($seenOrders[$order])) {
                $errors["details.{$idx}.sothutu"] = 'So thu tu bi trung trong doi hinh.';
            }

            if ($note !== '' && strlen($note) > 500) {
                $errors["details.{$idx}.ghichu"] = 'Ghi chu toi da 500 ky tu.';
            }

            $seenAthletes[$athleteId] = true;

            if ($order !== null) {
                $seenOrders[$order] = true;
            }

            $details[] = [
                'idvandongvien' => $athleteId,
                'vitri' => $position,
                'sothutu' => $order,
                'ghichu' => $note === '' ? null : $note,
            ];
        }

        if ($details === []) {
            $errors['details'] = 'Doi hinh can co it nhat 1 van dong vien.';
        }

        return [$details, $errors];
    }

    private function validateLineupMembers(int $teamId, array $details, string $gender): array
    {
        $errors = [];

        foreach ($details as $index => $detail) {
            if (!$this->teams->athleteIsActiveMember($teamId, (int) $detail['idvandongvien'])) {
                $errors["details.{$index}.idvandongvien"] = 'Van dong vien khong phai thanh vien dang tham gia cua doi.';
            } elseif (!$this->teams->athleteIsActiveMemberWithGender($teamId, (int) $detail['idvandongvien'], $gender)) {
                $errors["details.{$index}.gioitinh"] = 'Van dong vien khong dung gioi tinh cua doi hinh.';
            }
        }

        return $errors;
    }

    private function scheduleFilters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $tournamentId = trim((string) ($filters['tournament_id'] ?? $filters['idgiaidau'] ?? ''));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));
        $errors = [];

        if ($status !== '' && !in_array($status, self::MATCH_STATUSES, true)) {
            $errors['status'] = 'Trang thai tran dau khong hop le.';
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
            'tournament_id' => $tournamentId,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function resultFilters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $teamIdRaw = trim((string) ($filters['team_id'] ?? $filters['iddoibong'] ?? ''));
        $tournamentIdRaw = trim((string) ($filters['tournament_id'] ?? $filters['idgiaidau'] ?? ''));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));
        $errors = [];
        $teamId = null;
        $tournamentId = null;

        if ($status !== '' && !in_array($status, self::RESULT_STATUSES, true)) {
            $errors['status'] = 'Trang thai ket qua khong hop le.';
        }

        if ($teamIdRaw !== '') {
            if (!ctype_digit($teamIdRaw) || (int) $teamIdRaw <= 0) {
                $errors['team_id'] = 'Doi bong khong hop le.';
            } else {
                $teamId = (int) $teamIdRaw;
            }
        }

        if ($tournamentIdRaw !== '') {
            if (!ctype_digit($tournamentIdRaw) || (int) $tournamentIdRaw <= 0) {
                $errors['tournament_id'] = 'Giai dau khong hop le.';
            } else {
                $tournamentId = (int) $tournamentIdRaw;
            }
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
            'team_id' => $teamId,
            'tournament_id' => $tournamentId,
            'from' => $from,
            'to' => $to,
        ], $errors];
    }

    private function complaintPayload(array $payload): array
    {
        $errors = [];
        $content = trim((string) ($payload['noidung'] ?? $payload['noi_dung'] ?? $payload['content'] ?? ''));
        $evidence = trim((string) ($payload['minhchung'] ?? $payload['evidence'] ?? $payload['evidence_url'] ?? ''));

        if ($content === '') {
            $errors['noidung'] = 'Noi dung khieu nai la bat buoc.';
        } elseif (strlen($content) > 2000) {
            $errors['noidung'] = 'Noi dung khieu nai khong duoc vuot qua 2000 ky tu.';
        }

        if ($evidence !== '' && strlen($evidence) > 500) {
            $errors['minhchung'] = 'Minh chung khong duoc vuot qua 500 ky tu.';
        }

        return [$content, $evidence === '' ? null : $evidence, $errors];
    }

    private function profileRequestFilters(array $filters): array
    {
        $keyword = trim((string) ($filters['q'] ?? $filters['keyword'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $targetTable = $this->normalizeAthleteTargetTable((string) ($filters['target_table'] ?? $filters['banglienquan'] ?? ''));
        $field = trim((string) ($filters['field'] ?? $filters['truongcapnhat'] ?? ''));
        $teamIdRaw = trim((string) ($filters['team_id'] ?? $filters['iddoibong'] ?? ''));
        $athleteIdRaw = trim((string) ($filters['athlete_id'] ?? $filters['idvandongvien'] ?? ''));
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? $filters['tungay'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? $filters['denngay'] ?? ''));
        $limitRaw = (string) ($filters['limit'] ?? '50');
        $pageRaw = (string) ($filters['page'] ?? '1');
        $offsetRaw = (string) ($filters['offset'] ?? '');
        $errors = [];

        if ($status !== '' && !in_array($status, self::REQUEST_STATUSES, true)) {
            $errors['status'] = 'Trang thai yeu cau khong hop le.';
        }

        if (($filters['target_table'] ?? $filters['banglienquan'] ?? '') !== '' && $targetTable === null) {
            $errors['target_table'] = 'Bang lien quan khong hop le.';
        }

        if ($targetTable !== null && $field !== '' && !in_array($field, self::ATHLETE_ALLOWED_FIELDS[$targetTable], true)) {
            $errors['field'] = 'Truong cap nhat khong hop le voi bang lien quan.';
        }

        $teamId = null;
        if ($teamIdRaw !== '') {
            if (!ctype_digit($teamIdRaw) || (int) $teamIdRaw <= 0) {
                $errors['team_id'] = 'Doi bong khong hop le.';
            } else {
                $teamId = (int) $teamIdRaw;
            }
        }

        $athleteId = null;
        if ($athleteIdRaw !== '') {
            if (!ctype_digit($athleteIdRaw) || (int) $athleteIdRaw <= 0) {
                $errors['athlete_id'] = 'Van dong vien khong hop le.';
            } else {
                $athleteId = (int) $athleteIdRaw;
            }
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

        $limit = ctype_digit($limitRaw) ? max(1, min(100, (int) $limitRaw)) : 50;
        $page = ctype_digit($pageRaw) ? max(1, (int) $pageRaw) : 1;
        $offset = ctype_digit($offsetRaw) ? max(0, (int) $offsetRaw) : (($page - 1) * $limit);

        return [[
            'q' => $keyword,
            'trangthai' => $status,
            'banglienquan' => $targetTable ?? '',
            'truongcapnhat' => $field,
            'iddoibong' => $teamId,
            'idvandongvien' => $athleteId,
            'from' => $from,
            'to' => $to,
        ], [
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
        ], $errors];
    }

    private function profileNewValue(array $item): array
    {
        $targetTable = (string) $item['banglienquan'];
        $field = (string) $item['truongcapnhat'];
        $value = (string) ($item['giatrimoi'] ?? '');
        $errors = [];

        if (!isset(self::ATHLETE_ALLOWED_FIELDS[$targetTable]) || !in_array($field, self::ATHLETE_ALLOWED_FIELDS[$targetTable], true)) {
            return [null, [
                'field' => 'Truong cap nhat khong duoc phep cho van dong vien.',
            ]];
        }

        if (in_array($field, ['ten', 'hodem', 'username', 'email', 'sodienthoai', 'mavandongvien'], true) && trim($value) === '') {
            $errors[$field] = 'Gia tri moi bat buoc.';
        }

        if ($field === 'gioitinh' && !in_array($value, ['NAM', 'NU', 'KHAC'], true)) {
            $errors[$field] = 'Gioi tinh khong hop le.';
        }

        if ($field === 'ngaysinh' && $value !== '' && !$this->isDate($value)) {
            $errors[$field] = 'Ngay sinh khong hop le.';
        }

        if (in_array($field, ['chieucao', 'cannang'], true)) {
            if (!is_numeric($value) || (float) $value <= 0) {
                $errors[$field] = 'Gia tri phai lon hon 0.';
            } else {
                $value = (float) $value;
            }
        }

        if ($field === 'vitri' && !in_array($value, self::ATHLETE_POSITIONS, true)) {
            $errors[$field] = 'Vi tri thi dau khong hop le.';
        }

        if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = 'Email khong hop le.';
        }

        if (is_string($value) && strlen($value) > 500) {
            $errors[$field] = 'Gia tri moi toi da 500 ky tu.';
        }

        return [$value, $errors];
    }

    private function normalizeAthleteTargetTable(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'nguoidung', 'nguoi_dung', 'user' => 'Nguoidung',
            'taikhoan', 'tai_khoan', 'account' => 'Taikhoan',
            'vandongvien', 'van_dong_vien', 'athlete' => 'Vandongvien',
            default => null,
        };
    }

    private function reason(array $payload, string $default): string
    {
        $reason = trim((string) ($payload['reason'] ?? $payload['lydo'] ?? $payload['note'] ?? $payload['ghichu'] ?? ''));

        if ($reason === '') {
            return $default;
        }

        return $this->limitLogNote($reason);
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || !ctype_digit((string) $value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function boolInt(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on', 'co', 'có'], true) ? 1 : 0;
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
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
        $result = [
            'ok' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== []) {
            $result['errors'] = $errors;
        }

        return $result;
    }
}

