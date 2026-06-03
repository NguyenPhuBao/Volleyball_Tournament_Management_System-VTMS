<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Ketquatrandau;
use App\Backend\Services\Shared\VolleyballCompetitionRules;
use RuntimeException;
use Throwable;

final class OrganizerMatchResultService
{
    private const RESULT_STATUSES = ['CHO_CONG_BO', 'DA_DIEU_CHINH', 'DA_CONG_BO', 'BI_HUY'];
    private const ADJUSTABLE_STATUSES = ['CHO_CONG_BO', 'DA_CONG_BO', 'DA_DIEU_CHINH'];
    private const PUBLISHABLE_STATUSES = ['CHO_CONG_BO', 'DA_DIEU_CHINH'];
    private const RESULT_STATUS_ALIASES = [
        'CHUA_CONG_BO' => 'CHO_CONG_BO',
    ];

    public function __construct(
        private ?Ketquatrandau $results = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->results ??= new Ketquatrandau();
        $this->tournaments ??= new Giaidau();
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $normalized = $this->filters($filters);

        if ($normalized['errors'] !== []) {
            return $this->failure('Bo loc ket qua tran dau khong hop le.', 422, $normalized['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach ket qua tran dau thanh cong.',
            'results' => $this->results->listForOrganizer((int) $organizer['idbantochuc'], $normalized['filters']),
            'meta' => [
                'filters' => $normalized['filters'],
                'statuses' => self::RESULT_STATUSES,
                'stats' => $this->results->statsForOrganizer((int) $organizer['idbantochuc'], $normalized['filters']),
            ],
        ];
    }

    public function find(int $resultId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $result = $this->resultPayload((int) $organizer['idbantochuc'], $resultId);

        if ($result === null) {
            return $this->failure('Khong tim thay ket qua tran dau.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin ket qua tran dau thanh cong.',
            'result' => $result,
        ];
    }

    public function adjust(int $resultId, array $payload, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $current = $this->resultPayload((int) $organizer['idbantochuc'], $resultId);

        if ($current === null) {
            return $this->failure('Khong tim thay ket qua tran dau.', 404);
        }

        if ((string) $current['trandau_trangthai'] !== 'DA_KET_THUC') {
            return $this->failure('Chi duoc dieu chinh ket qua cua tran dau da ket thuc.', 409);
        }

        if (!in_array((string) $current['trangthai'], self::ADJUSTABLE_STATUSES, true)) {
            return $this->failure('Chi duoc dieu chinh ket qua dang cho cong bo, da cong bo hoac da dieu chinh.', 409);
        }

        [$result, $sets, $reason, $evidence, $errors] = $this->validateAdjustmentPayload($payload, $current);

        if ($errors !== []) {
            return $this->failure('Du lieu dieu chinh ket qua khong hop le.', 422, $errors);
        }

        $oldSnapshot = $this->snapshot($current, $current['sets']);
        $newSnapshot = $this->snapshot($result, $sets ?? $current['sets']);
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d dieu chinh ket qua tran #%d trong giai "%s". %s -> %s. Ly do: %s',
            (int) $organizer['idbantochuc'],
            (int) $current['idtrandau'],
            (string) $current['tengiaidau'],
            $oldSnapshot,
            $newSnapshot,
            $reason
        ));

        try {
            $this->results->adjustResult(
                $resultId,
                $result,
                $sets,
                $this->limitText($oldSnapshot, 500),
                $this->limitText($newSnapshot, 500),
                $reason,
                $evidence,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Dieu chinh ket qua tran dau thanh cong.',
                'result' => $this->resultPayload((int) $organizer['idbantochuc'], $resultId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'RESULT_NOT_ADJUSTED') {
                return $this->failure('Khong the dieu chinh ket qua hien tai.', 409);
            }

            return $this->failure('Khong the dieu chinh ket qua tran dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the dieu chinh ket qua tran dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function publish(int $resultId, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $current = $this->resultPayload((int) $organizer['idbantochuc'], $resultId);

        if ($current === null) {
            return $this->failure('Khong tim thay ket qua tran dau.', 404);
        }

        if ((string) $current['trandau_trangthai'] !== 'DA_KET_THUC') {
            return $this->failure('Chi duoc cong bo ket qua cua tran dau da ket thuc.', 409);
        }

        if (!in_array((string) $current['trangthai'], self::PUBLISHABLE_STATUSES, true)) {
            return $this->failure('Chi duoc cong bo ket qua dang cho cong bo hoac da dieu chinh.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cong bo ket qua tran #%d trong giai "%s": %s.',
            (int) $organizer['idbantochuc'],
            (int) $current['idtrandau'],
            (string) $current['tengiaidau'],
            $this->snapshot($current, $current['sets'])
        ));

        try {
            $this->results->publishResult($resultId, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cong bo ket qua tran dau thanh cong.',
                'result' => $this->resultPayload((int) $organizer['idbantochuc'], $resultId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'RESULT_NOT_PUBLISHED') {
                return $this->failure('Khong the cong bo ket qua hien tai.', 409);
            }

            return $this->failure('Khong the cong bo ket qua tran dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cong bo ket qua tran dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function resultPayload(int $organizerId, int $resultId): ?array
    {
        $result = $this->results->findForOrganizer($organizerId, $resultId);

        if ($result === null) {
            return null;
        }

        $result['sets'] = $this->results->setsForResult($resultId);
        $result['adjustments'] = $this->results->adjustmentsForResult($resultId);

        return $result;
    }

    private function validateAdjustmentPayload(array $payload, array $current): array
    {
        $errors = [];
        $teamOneId = (int) $current['iddoibong1'];
        $teamTwoId = (int) $current['iddoibong2'];

        $result = [
            'iddoithang' => $this->requiredPositiveInt($payload['iddoithang'] ?? $payload['winner_team_id'] ?? $payload['winner_id'] ?? null, 'iddoithang', 'Doi thang', $errors),
            'diemdoi1' => $this->requiredNonNegativeInt($payload['diemdoi1'] ?? $payload['team_one_score'] ?? $payload['score1'] ?? null, 'diemdoi1', 'Diem doi 1', $errors),
            'diemdoi2' => $this->requiredNonNegativeInt($payload['diemdoi2'] ?? $payload['team_two_score'] ?? $payload['score2'] ?? null, 'diemdoi2', 'Diem doi 2', $errors),
            'sosetdoi1' => $this->requiredNonNegativeInt($payload['sosetdoi1'] ?? $payload['team_one_sets'] ?? $payload['sets1'] ?? null, 'sosetdoi1', 'So set doi 1', $errors),
            'sosetdoi2' => $this->requiredNonNegativeInt($payload['sosetdoi2'] ?? $payload['team_two_sets'] ?? $payload['sets2'] ?? null, 'sosetdoi2', 'So set doi 2', $errors),
        ];

        $reason = trim((string) ($payload['lydo'] ?? $payload['ly_do'] ?? $payload['reason'] ?? ''));
        $evidence = $this->nullableString($payload['minhchung'] ?? $payload['evidence'] ?? $payload['evidence_url'] ?? null, 500, 'Minh chung', 'minhchung', $errors);

        if ($reason === '') {
            $errors['lydo'] = 'Ly do dieu chinh la bat buoc.';
        } elseif (strlen($reason) > 1000) {
            $errors['lydo'] = 'Ly do khong duoc vuot qua 1000 ky tu.';
        }

        if ($result['iddoithang'] !== null && !in_array((int) $result['iddoithang'], [$teamOneId, $teamTwoId], true)) {
            $errors['iddoithang'] = 'Doi thang phai la mot trong hai doi thi dau.';
        }

        if ($result['sosetdoi1'] !== null && $result['sosetdoi2'] !== null) {
            $bo5Error = VolleyballCompetitionRules::validateBo5Score(
                (int) $result['sosetdoi1'],
                (int) $result['sosetdoi2']
            );

            if ($bo5Error !== null) {
                $errors['soset'] = $bo5Error;
            }

            if ($result['iddoithang'] !== null) {
                $winnerBySets = (int) $result['sosetdoi1'] > (int) $result['sosetdoi2'] ? $teamOneId : $teamTwoId;

                if ((int) $result['iddoithang'] !== $winnerBySets) {
                    $errors['iddoithang'] = 'Doi thang phai khop voi so set thang.';
                }
            }
        }

        $sets = $this->sets($payload, $teamOneId, $teamTwoId, $errors);

        if ($sets !== null && $result['sosetdoi1'] !== null && $result['sosetdoi2'] !== null) {
            $teamOneSetWins = 0;
            $teamTwoSetWins = 0;

            foreach ($sets as $set) {
                if ((int) $set['doithangset'] === $teamOneId) {
                    $teamOneSetWins++;
                } elseif ((int) $set['doithangset'] === $teamTwoId) {
                    $teamTwoSetWins++;
                }
            }

            if ($teamOneSetWins !== (int) $result['sosetdoi1'] || $teamTwoSetWins !== (int) $result['sosetdoi2']) {
                $errors['sets'] = 'Danh sach diem set phai khop voi so set thang cua tung doi.';
            }

            $bo5Error = VolleyballCompetitionRules::validateBo5Score($teamOneSetWins, $teamTwoSetWins);

            if ($bo5Error !== null) {
                $errors['sets'] = $bo5Error;
            }
        }

        return [$result, $sets, $reason, $evidence, $errors];
    }

    private function sets(array $payload, int $teamOneId, int $teamTwoId, array &$errors): ?array
    {
        $source = $payload['sets'] ?? $payload['diemsets'] ?? $payload['diem_set'] ?? null;

        if ($source === null || $source === '') {
            return null;
        }

        if (!is_array($source)) {
            $errors['sets'] = 'Danh sach diem set khong hop le.';
            return null;
        }

        if (count($source) < VolleyballCompetitionRules::MIN_SETS || count($source) > VolleyballCompetitionRules::MAX_SETS) {
            $errors['sets'] = 'Một trận Bo5 phải có từ 3 đến 5 set.';
            return null;
        }

        $sets = [];
        $seen = [];

        foreach ($source as $index => $item) {
            if (!is_array($item)) {
                $errors['sets.' . $index] = 'Diem set khong hop le.';
                continue;
            }

            $setNumber = $this->requiredPositiveInt($item['setthu'] ?? $item['set_number'] ?? $item['set'] ?? null, 'sets.' . $index . '.setthu', 'Thu tu set', $errors);
            $teamOneScore = $this->requiredNonNegativeInt($item['diemdoi1'] ?? $item['team_one_score'] ?? $item['score1'] ?? null, 'sets.' . $index . '.diemdoi1', 'Diem doi 1', $errors);
            $teamTwoScore = $this->requiredNonNegativeInt($item['diemdoi2'] ?? $item['team_two_score'] ?? $item['score2'] ?? null, 'sets.' . $index . '.diemdoi2', 'Diem doi 2', $errors);
            $winner = $item['doithangset'] ?? $item['winner_team_id'] ?? null;

            if ($setNumber !== null) {
                if ($setNumber < 1 || $setNumber > 5) {
                    $errors['sets.' . $index . '.setthu'] = 'Thu tu set phai nam trong khoang 1 den 5.';
                } elseif (isset($seen[$setNumber])) {
                    $errors['sets.' . $index . '.setthu'] = 'Thu tu set bi trung.';
                }

                $seen[$setNumber] = true;
            }

            if ($teamOneScore !== null && $teamTwoScore !== null) {
                if ($teamOneScore === $teamTwoScore) {
                    $errors['sets.' . $index . '.diem'] = 'Diem hai doi trong mot set khong duoc bang nhau.';
                }

                $winnerByScore = $teamOneScore > $teamTwoScore ? $teamOneId : $teamTwoId;

                if ($winner === null || trim((string) $winner) === '') {
                    $winner = $winnerByScore;
                } elseif (!ctype_digit((string) $winner) || !in_array((int) $winner, [$teamOneId, $teamTwoId], true)) {
                    $errors['sets.' . $index . '.doithangset'] = 'Doi thang set khong hop le.';
                    $winner = null;
                } elseif ((int) $winner !== $winnerByScore) {
                    $errors['sets.' . $index . '.doithangset'] = 'Doi thang set phai khop voi diem set.';
                }
            }

            if ($setNumber !== null && $teamOneScore !== null && $teamTwoScore !== null && $winner !== null) {
                $sets[] = [
                    'setthu' => $setNumber,
                    'diemdoi1' => $teamOneScore,
                    'diemdoi2' => $teamTwoScore,
                    'doithangset' => (int) $winner,
                ];
            }
        }

        if ($sets !== []) {
            usort($sets, static fn (array $a, array $b): int => $a['setthu'] <=> $b['setthu']);

            foreach (array_values($sets) as $index => $set) {
                if ((int) $set['setthu'] !== $index + 1) {
                    $errors['sets'] = 'Thu tu set phai lien tiep bat dau tu 1.';
                    break;
                }
            }
        }

        return $sets;
    }

    private function filters(array $filters): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $status = self::RESULT_STATUS_ALIASES[$status] ?? $status;
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null, 'tournament_id', $errors);
        $matchId = $this->optionalPositiveInt($filters['match_id'] ?? $filters['idtrandau'] ?? null, 'match_id', $errors);
        $from = trim((string) ($filters['from'] ?? $filters['from_date'] ?? ''));
        $to = trim((string) ($filters['to'] ?? $filters['to_date'] ?? ''));

        if ($status !== '' && !in_array($status, self::RESULT_STATUSES, true)) {
            $errors['status'] = 'Trang thai ket qua khong hop le.';
        }

        if ($from !== '' && !$this->isDate($from)) {
            $errors['from'] = 'Ngay bat dau khong hop le.';
        }

        if ($to !== '' && !$this->isDate($to)) {
            $errors['to'] = 'Ngay ket thuc khong hop le.';
        }

        if ($from !== '' && $to !== '' && $this->isDate($from) && $this->isDate($to) && $to < $from) {
            $errors['to'] = 'Ngay ket thuc phai lon hon hoac bang ngay bat dau.';
        }

        return [
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
                'status' => $status,
                'tournament_id' => $tournamentId,
                'match_id' => $matchId,
                'from' => $from,
                'to' => $to,
            ],
            'errors' => $errors,
        ];
    }

    private function requiredPositiveInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value) || (int) $value <= 0) {
            $errors[$errorKey] = $label . ' khong hop le.';
            return null;
        }

        return (int) $value;
    }

    private function requiredNonNegativeInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value) || (int) $value < 0) {
            $errors[$errorKey] = $label . ' khong hop le.';
            return null;
        }

        return (int) $value;
    }

    private function optionalPositiveInt(mixed $value, string $errorKey, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (!ctype_digit((string) $value) || (int) $value <= 0) {
            $errors[$errorKey] = 'Gia tri khong hop le.';
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value, int $maxLength, string $label, string $errorKey, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        if (strlen($text) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $text;
    }

    private function snapshot(array $result, array $sets): string
    {
        $setScores = [];

        foreach ($sets as $set) {
            $setScores[] = sprintf('%d:%d-%d', (int) $set['setthu'], (int) $set['diemdoi1'], (int) $set['diemdoi2']);
        }

        return sprintf(
            'diem %d-%d, set %d-%d, thang #%d%s',
            (int) $result['diemdoi1'],
            (int) $result['diemdoi2'],
            (int) $result['sosetdoi1'],
            (int) $result['sosetdoi2'],
            (int) $result['iddoithang'],
            $setScores === [] ? '' : ', chi tiet [' . implode('; ', $setScores) . ']'
        );
    }

    private function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
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
        return $this->limitText($note, 1000);
    }

    private function limitText(string $text, int $limit): string
    {
        if (strlen($text) <= $limit) {
            return $text;
        }

        return substr($text, 0, $limit - 3) . '...';
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

