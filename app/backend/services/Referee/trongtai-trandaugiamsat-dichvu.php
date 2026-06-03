<?php

declare(strict_types=1);

namespace App\Backend\Services\Referee;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Trongtai;
use App\Backend\Services\Shared\VolleyballCompetitionRules;
use RuntimeException;
use Throwable;

final class RefereeMatchSupervisionService
{
    private const STARTABLE_STATUSES = ['DA_XEP_LICH', 'CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT'];
    private const RESULT_MUTABLE_STATUSES = ['CHO_CONG_BO', 'DA_DIEU_CHINH'];

    public function __construct(
        private ?Trongtai $referees = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->referees ??= new Trongtai();
        $this->tournaments ??= new Giaidau();
    }

    public function show(int $matchId, int $accountId, ?Request $request = null): array
    {
        $context = $this->supervisionContext($matchId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        try {
            $this->referees->recordRefereeSupervisionView(
                $matchId,
                $accountId,
                $request?->ip(),
                $this->matchLogNote((int) $context['referee']['idtrongtai'], $context['assignment'], 'xem giao dien giam sat')
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Lay thong tin giam sat tran dau thanh cong.',
                'supervision' => $this->payload($context),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the lay thong tin giam sat tran dau.', 500, [
                'database' => 'Loi doc hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function confirmParticipants(int $matchId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->supervisionContext($matchId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $status = (string) $context['assignment']['trandau_trangthai'];

        if (!in_array($status, self::STARTABLE_STATUSES, true)) {
            return $this->failure('Chi co the chon to trong tai tham gia truoc khi tran dau bat dau.', 409);
        }

        [$refereeIds, $errors] = $this->participantIds($payload, $context);

        if ($errors !== []) {
            return $this->failure('Danh sach trong tai tham gia khong hop le.', 422, $errors);
        }

        try {
            $this->referees->confirmRefereeMatchParticipants(
                $matchId,
                $refereeIds,
                $accountId,
                $request?->ip(),
                $this->limitLogNote(sprintf(
                    'Trong tai #%d xac nhan to trong tai tham gia tran #%d. Danh sach: %s.',
                    (int) $context['referee']['idtrongtai'],
                    $matchId,
                    implode(',', $refereeIds)
                ))
            );

            return $this->freshResponse($matchId, $accountId, 'Xac nhan to trong tai tham gia thanh cong.');
        } catch (Throwable) {
            return $this->failure('Khong the xac nhan to trong tai tham gia.', 500, [
                'database' => 'Loi cap nhat hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function statusAction(int $matchId, string $action, int $accountId, ?Request $request = null): array
    {
        $context = $this->supervisionContext($matchId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $status = (string) $context['assignment']['trandau_trangthai'];
        $transition = match ($action) {
            'start' => ['DANG_DIEN_RA', self::STARTABLE_STATUSES, 'Bat dau giam sat tran dau', 'Bat dau tran dau'],
            'pause' => ['TAM_DUNG', ['DANG_DIEN_RA'], 'Tam dung tran dau', 'Tam dung tran dau'],
            'resume' => ['DANG_DIEN_RA', ['TAM_DUNG'], 'Tiep tuc tran dau', 'Tiep tuc tran dau'],
            default => null,
        };

        if ($transition === null) {
            return $this->failure('Thao tac giam sat khong hop le.', 422);
        }

        [$newStatus, $allowedStatuses, $systemAction, $reason] = $transition;

        if (!in_array($status, $allowedStatuses, true)) {
            return $this->failure('Trang thai tran dau hien tai khong cho phep thao tac nay.', 409);
        }

        if ($action === 'start') {
            $tournamentErrors = $this->tournamentStartErrors($context['assignment']);

            if ($tournamentErrors !== []) {
                return $this->failure('Tran dau chi co the bat dau khi giai dau dang dien ra va thoi gian tran hop le.', 409, $tournamentErrors);
            }

            if (!$this->isMatchDue($context['assignment'])) {
                return $this->failure('Chua toi thoi gian bat dau tran dau.', 409);
            }

            $participantErrors = $this->requiredParticipantErrors($context);

            if ($participantErrors !== []) {
                return $this->failure('Can chon to trong tai tham gia truoc khi bat dau tran dau.', 409, $participantErrors);
            }
        }

        if ($action === 'start' && !$this->currentRefereeConfirmed($context)) {
            return $this->failure('Trong tai can xac nhan tham gia truoc khi bat dau tran dau.', 409);
        }

        try {
            $this->referees->changeSupervisedMatchStatus(
                $matchId,
                $status,
                $newStatus,
                $action === 'start',
                false,
                $accountId,
                $request?->ip(),
                $this->matchLogNote((int) $context['referee']['idtrongtai'], $context['assignment'], $systemAction),
                $reason
            );

            return $this->freshResponse($matchId, $accountId, $reason . ' thanh cong.');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'MATCH_STATUS_NOT_UPDATED') {
                return $this->failure('Trang thai tran dau da thay doi, vui long tai lai.', 409);
            }

            return $this->failure('Khong the cap nhat trang thai tran dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat trang thai tran dau.', 500, [
                'database' => 'Loi cap nhat hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function recordResult(int $matchId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->supervisionContext($matchId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        if (!in_array((string) $context['assignment']['trandau_trangthai'], ['DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC'], true)) {
            return $this->failure('Chi ghi nhan ket qua khi tran dang dien ra, tam dung hoac da ket thuc.', 409);
        }

        [$result, $sets, $errors] = $this->resultFromPayload($payload, $context['assignment'], true);

        if ($errors !== []) {
            return $this->failure('Du lieu ket qua tran dau khong hop le.', 422, $errors);
        }

        try {
            $resultId = $this->referees->saveSupervisedMatchResult(
                $matchId,
                $result,
                $sets,
                $accountId,
                $request?->ip(),
                $this->resultLogNote((int) $context['referee']['idtrongtai'], $context['assignment'], $result, $sets)
            );

            $response = $this->freshResponse($matchId, $accountId, 'Ghi nhan ket qua tran dau thanh cong.');
            $response['meta']['result_id'] = $resultId;

            return $response;
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'RESULT_ALREADY_PUBLISHED') {
                return $this->failure('Ket qua da cong bo, khong the ghi nhan lai.', 409);
            }

            return $this->failure('Khong the ghi nhan ket qua tran dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the ghi nhan ket qua tran dau.', 500, [
                'database' => 'Loi cap nhat hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    public function finish(int $matchId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->supervisionContext($matchId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $status = (string) $context['assignment']['trandau_trangthai'];

        if (!in_array($status, ['DANG_DIEN_RA', 'TAM_DUNG'], true)) {
            return $this->failure('Chi co the ket thuc tran dang dien ra hoac dang tam dung.', 409);
        }

        $hasResultPayload = $this->hasResultPayload($payload);
        $result = null;
        $sets = null;

        if ($hasResultPayload) {
            [$result, $sets, $errors] = $this->resultFromPayload($payload, $context['assignment'], true);

            if ($errors !== []) {
                return $this->failure('Du lieu ket qua tran dau khong hop le.', 422, $errors);
            }
        } elseif ($context['assignment']['idketqua'] === null) {
            return $this->failure('Can ghi nhan ket qua truoc khi ket thuc tran dau.', 409);
        }

        try {
            $resultId = $this->referees->finishSupervisedMatch(
                $matchId,
                $status,
                $result,
                $sets,
                $accountId,
                $request?->ip(),
                $this->matchLogNote((int) $context['referee']['idtrongtai'], $context['assignment'], 'Ket thuc tran dau'),
                $result === null ? null : $this->resultLogNote((int) $context['referee']['idtrongtai'], $context['assignment'], $result, $sets)
            );

            $response = $this->freshResponse($matchId, $accountId, 'Ket thuc tran dau thanh cong.');

            if ($resultId !== null) {
                $response['meta']['result_id'] = $resultId;
            }

            return $response;
        } catch (RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'MATCH_STATUS_NOT_UPDATED' => $this->failure('Trang thai tran dau da thay doi, vui long tai lai.', 409),
                'RESULT_ALREADY_PUBLISHED' => $this->failure('Ket qua da cong bo, khong the ghi nhan lai.', 409),
                default => $this->failure('Khong the ket thuc tran dau.', 500),
            };
        } catch (Throwable) {
            return $this->failure('Khong the ket thuc tran dau.', 500, [
                'database' => 'Loi cap nhat hoac ghi nhat ky co so du lieu.',
            ]);
        }
    }

    private function supervisionContext(int $matchId, int $accountId, bool $requireConfirmedAssignment = false): array
    {
        $referee = $this->referees->findByAccountId($accountId);

        if ($referee === null) {
            return $this->failure('Tai khoan khong co ho so trong tai.', 403);
        }

        $assignment = $this->referees->matchAssignmentDetailForReferee((int) $referee['idtrongtai'], $matchId);

        if ($assignment === null) {
            return $this->failure('Khong tim thay tran dau duoc phan cong.', 404);
        }

        $this->tournaments->syncStartedPublishedTournaments(null, (int) $assignment['idgiaidau']);
        $assignment = $this->referees->matchAssignmentDetailForReferee((int) $referee['idtrongtai'], $matchId);

        if ($assignment === null) {
            return $this->failure('Khong tim thay tran dau duoc phan cong.', 404);
        }

        if (!$this->isSupervisorAssignment($assignment)) {
            return $this->failure('Chi trong tai giam sat moi duoc thuc hien thao tac giam sat tran dau.', 403);
        }

        if ($requireConfirmedAssignment && (string) $assignment['phancong_trangthai'] !== 'DA_XAC_NHAN') {
            return $this->failure('Trong tai can xac nhan phan cong truoc khi giam sat tran dau.', 409);
        }

        $sets = ((int) ($assignment['idketqua'] ?? 0)) > 0
            ? $this->referees->setsForResult((int) $assignment['idketqua'])
            : [];
        $participants = $this->referees->coRefereesForMatch($matchId);

        return [
            'referee' => $referee,
            'assignment' => $assignment,
            'sets' => $sets,
            'participants' => $participants,
        ];
    }

    private function payload(array $context): array
    {
        $assignment = $context['assignment'];
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
                'thoigianbatdau' => $assignment['giaidau_thoigianbatdau'],
                'thoigianketthuc' => $assignment['giaidau_thoigianketthuc'],
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
                'sets' => $context['sets'],
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
            'trongtai_thamgia' => $context['participants'],
            'actions' => $this->availableActions($context),
        ];
    }

    private function availableActions(array $context): array
    {
        $status = (string) $context['assignment']['trandau_trangthai'];
        $assignmentConfirmed = (string) $context['assignment']['phancong_trangthai'] === 'DA_XAC_NHAN';
        $canSupervise = $assignmentConfirmed && $this->isSupervisorAssignment($context['assignment']);
        $hasRequiredParticipants = $this->requiredParticipantErrors($context) === [];
        $canChangeParticipants = in_array($status, self::STARTABLE_STATUSES, true);

        return [
            'confirm_participants' => $canSupervise && $canChangeParticipants,
            'start' => $canSupervise
                && $hasRequiredParticipants
                && in_array($status, self::STARTABLE_STATUSES, true)
                && $this->tournamentStartErrors($context['assignment']) === []
                && $this->isMatchDue($context['assignment']),
            'pause' => $canSupervise && $status === 'DANG_DIEN_RA',
            'resume' => $canSupervise && $status === 'TAM_DUNG',
            'record_result' => $canSupervise && in_array($status, ['DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC'], true),
            'finish' => $canSupervise && in_array($status, ['DANG_DIEN_RA', 'TAM_DUNG'], true),
        ];
    }

    private function isSupervisorAssignment(array $assignment): bool
    {
        return (string) ($assignment['vaitro'] ?? '') === 'GIAM_SAT';
    }

    private function participantIds(array $payload, array $context): array
    {
        $raw = $payload['referee_ids']
            ?? $payload['idtrongtai']
            ?? $payload['participants']
            ?? $payload['trongtai_ids']
            ?? null;
        $errors = [];

        if ($raw === null) {
            return [[], ['referee_ids' => 'Danh sach trong tai tham gia la bat buoc.']];
        }

        if (!is_array($raw)) {
            return [[], ['referee_ids' => 'Danh sach trong tai tham gia khong hop le.']];
        }

        $ids = [];

        foreach ($raw as $index => $value) {
            if (is_array($value)) {
                $value = $value['idtrongtai'] ?? $value['referee_id'] ?? $value['id'] ?? null;
            }

            if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value) || (int) $value <= 0) {
                $errors['referee_ids.' . $index] = 'Ma trong tai khong hop le.';
                continue;
            }

            $ids[(int) $value] = (int) $value;
        }

        $ids = array_values($ids);

        if ($ids === []) {
            $errors['referee_ids'] = 'Can chon it nhat mot trong tai tham gia.';
        }

        $assigned = [];

        foreach ($context['participants'] as $participant) {
            if ((string) $participant['trangthai'] === 'DA_XAC_NHAN') {
                $assigned[(int) $participant['idtrongtai']] = true;
            }
        }

        foreach ($ids as $id) {
            if (!isset($assigned[$id])) {
                $errors['referee_ids'] = 'Chi duoc chon trong tai da xac nhan phan cong trong tran.';
                break;
            }
        }

        $selectedRoles = [];

        foreach ($context['participants'] as $participant) {
            $refereeId = (int) $participant['idtrongtai'];

            if (isset($assigned[$refereeId]) && in_array($refereeId, $ids, true)) {
                $selectedRoles[(string) $participant['vaitro']] = true;
            }
        }

        if (!isset($selectedRoles['GIAM_SAT'])) {
            $errors['giam_sat'] = 'To trong tai tham gia phai co it nhat 1 trong tai giam sat.';
        }

        if (!isset($selectedRoles['TRONG_TAI_CHINH'])) {
            $errors['trong_tai_chinh'] = 'To trong tai tham gia phai co it nhat 1 trong tai chinh.';
        }

        if (!in_array((int) $context['referee']['idtrongtai'], $ids, true)) {
            $errors['referee_ids'] = 'Danh sach tham gia phai bao gom trong tai dang thao tac.';
        }

        return [$ids, $errors];
    }

    private function resultFromPayload(array $payload, array $assignment, bool $setsRequired): array
    {
        $errors = [];
        $teamOneId = (int) $assignment['iddoibong1'];
        $teamTwoId = (int) $assignment['iddoibong2'];
        $sets = $this->sets($payload, $teamOneId, $teamTwoId, $setsRequired, $errors);

        if ($errors !== []) {
            return [[], [], $errors];
        }

        $teamOneScore = 0;
        $teamTwoScore = 0;
        $teamOneSets = 0;
        $teamTwoSets = 0;

        foreach ($sets as $set) {
            $teamOneScore += (int) $set['diemdoi1'];
            $teamTwoScore += (int) $set['diemdoi2'];

            if ((int) $set['doithangset'] === $teamOneId) {
                $teamOneSets++;
            } else {
                $teamTwoSets++;
            }
        }

        $bo5Error = VolleyballCompetitionRules::validateBo5Score($teamOneSets, $teamTwoSets);

        if ($bo5Error !== null) {
            $errors['sets'] = $bo5Error;
            return [[], [], $errors];
        }

        $winnerId = $teamOneSets > $teamTwoSets ? $teamOneId : $teamTwoId;

        return [[
            'iddoithang' => $winnerId,
            'diemdoi1' => $teamOneScore,
            'diemdoi2' => $teamTwoScore,
            'sosetdoi1' => $teamOneSets,
            'sosetdoi2' => $teamTwoSets,
        ], $sets, []];
    }

    private function sets(array $payload, int $teamOneId, int $teamTwoId, bool $required, array &$errors): array
    {
        $source = $payload['sets'] ?? $payload['diemsets'] ?? $payload['diem_set'] ?? null;

        if ($source === null || $source === '') {
            if ($required) {
                $errors['sets'] = 'Danh sach diem set la bat buoc.';
            }

            return [];
        }

        if (!is_array($source) || count($source) < VolleyballCompetitionRules::MIN_SETS || count($source) > VolleyballCompetitionRules::MAX_SETS) {
            $errors['sets'] = 'Một trận Bo5 phải có từ 3 đến 5 set.';
            return [];
        }

        $sets = [];
        $seen = [];

        foreach ($source as $index => $item) {
            if (!is_array($item)) {
                $errors['sets.' . $index] = 'Diem set khong hop le.';
                continue;
            }

            $setNumber = $item['setthu'] ?? $item['set_number'] ?? $item['set'] ?? ($index + 1);
            $teamOneScore = $item['diemdoi1'] ?? $item['team_one_score'] ?? $item['score1'] ?? null;
            $teamTwoScore = $item['diemdoi2'] ?? $item['team_two_score'] ?? $item['score2'] ?? null;

            if (!ctype_digit((string) $setNumber) || (int) $setNumber < 1 || (int) $setNumber > 5) {
                $errors['sets.' . $index . '.setthu'] = 'Thu tu set phai nam trong khoang 1 den 5.';
                continue;
            }

            $setNumber = (int) $setNumber;

            if (isset($seen[$setNumber])) {
                $errors['sets.' . $index . '.setthu'] = 'Thu tu set bi trung.';
                continue;
            }

            $seen[$setNumber] = true;

            if (!ctype_digit((string) $teamOneScore) || !ctype_digit((string) $teamTwoScore)) {
                $errors['sets.' . $index . '.diem'] = 'Diem set phai la so nguyen khong am.';
                continue;
            }

            $teamOneScore = (int) $teamOneScore;
            $teamTwoScore = (int) $teamTwoScore;

            if ($teamOneScore === $teamTwoScore) {
                $errors['sets.' . $index . '.diem'] = 'Diem hai doi trong mot set khong duoc bang nhau.';
                continue;
            }

            if (abs($teamOneScore - $teamTwoScore) < 2) {
                $errors['sets.' . $index . '.diem'] = 'Diem hai doi trong mot set phai chenh lech toi thieu 2 diem.';
                continue;
            }

            $sets[] = [
                'setthu' => $setNumber,
                'diemdoi1' => $teamOneScore,
                'diemdoi2' => $teamTwoScore,
                'doithangset' => $teamOneScore > $teamTwoScore ? $teamOneId : $teamTwoId,
            ];
        }

        usort($sets, static fn (array $a, array $b): int => $a['setthu'] <=> $b['setthu']);

        foreach (array_values($sets) as $index => $set) {
            if ((int) $set['setthu'] !== $index + 1) {
                $errors['sets'] = 'Thu tu set phai lien tiep bat dau tu 1.';
                break;
            }
        }

        return $sets;
    }

    private function hasResultPayload(array $payload): bool
    {
        return array_key_exists('sets', $payload)
            || array_key_exists('diemsets', $payload)
            || array_key_exists('diem_set', $payload);
    }

    private function freshResponse(int $matchId, int $accountId, string $message): array
    {
        $context = $this->supervisionContext($matchId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => $message,
            'supervision' => $this->payload($context),
            'meta' => [],
        ];
    }

    private function currentRefereeConfirmed(array $context): bool
    {
        foreach ($context['participants'] as $participant) {
            if ((int) $participant['idtrongtai'] === (int) $context['referee']['idtrongtai']) {
                return (bool) $participant['xacnhanthamgia'];
            }
        }

        return false;
    }

    private function requiredParticipantErrors(array $context): array
    {
        $hasSupervisor = false;
        $hasMainReferee = false;

        foreach ($context['participants'] as $participant) {
            if ((string) $participant['trangthai'] !== 'DA_XAC_NHAN' || !(bool) $participant['xacnhanthamgia']) {
                continue;
            }

            if ((string) $participant['vaitro'] === 'GIAM_SAT') {
                $hasSupervisor = true;
            }

            if ((string) $participant['vaitro'] === 'TRONG_TAI_CHINH') {
                $hasMainReferee = true;
            }
        }

        $errors = [];

        if (!$hasSupervisor) {
            $errors['giam_sat'] = 'Can chon it nhat 1 trong tai giam sat tham gia.';
        }

        if (!$hasMainReferee) {
            $errors['trong_tai_chinh'] = 'Can chon it nhat 1 trong tai chinh tham gia.';
        }

        return $errors;
    }

    private function isMatchDue(array $assignment): bool
    {
        $scheduled = strtotime((string) $assignment['thoigianbatdau']);

        if ($scheduled === false) {
            return false;
        }

        return $scheduled <= time();
    }

    private function tournamentStartErrors(array $assignment): array
    {
        $errors = [];

        if ((string) ($assignment['giaidau_trangthai'] ?? '') !== 'DANG_DIEN_RA') {
            $errors['giaidau'] = 'Giai dau phai dang dien ra truoc khi bat dau tran dau.';
        }

        $matchStart = strtotime((string) ($assignment['thoigianbatdau'] ?? ''));
        $tournamentStart = strtotime((string) ($assignment['giaidau_thoigianbatdau'] ?? ''));

        if ($matchStart === false || $tournamentStart === false) {
            $errors['thoigianbatdau'] = 'Thoi gian bat dau tran dau hoac giai dau khong hop le.';
        } elseif ($matchStart < $tournamentStart) {
            $errors['thoigianbatdau'] = 'Thoi gian bat dau tran dau phai lon hon hoac bang thoi gian bat dau giai dau.';
        }

        return $errors;
    }

    private function matchLogNote(int $refereeId, array $assignment, string $action): string
    {
        return $this->limitLogNote(sprintf(
            'Trong tai #%d %s tran #%d (%s vs %s), giai #%d.',
            $refereeId,
            $action,
            (int) $assignment['idtrandau'],
            (string) ($assignment['doi1'] ?? ''),
            (string) ($assignment['doi2'] ?? ''),
            (int) $assignment['idgiaidau']
        ));
    }

    private function resultLogNote(int $refereeId, array $assignment, array $result, array $sets): string
    {
        $setParts = [];

        foreach ($sets as $set) {
            $setParts[] = sprintf('%d:%d-%d', (int) $set['setthu'], (int) $set['diemdoi1'], (int) $set['diemdoi2']);
        }

        return $this->limitLogNote(sprintf(
            'Trong tai #%d ghi nhan ket qua tran #%d (%s vs %s): diem %d-%d, set %d-%d, thang doi #%d%s.',
            $refereeId,
            (int) $assignment['idtrandau'],
            (string) ($assignment['doi1'] ?? ''),
            (string) ($assignment['doi2'] ?? ''),
            (int) $result['diemdoi1'],
            (int) $result['diemdoi2'],
            (int) $result['sosetdoi1'],
            (int) $result['sosetdoi2'],
            (int) $result['iddoithang'],
            $setParts === [] ? '' : ', chi tiet [' . implode('; ', $setParts) . ']'
        ));
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

