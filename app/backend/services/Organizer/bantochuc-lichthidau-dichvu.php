<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Giaidau;
use App\Backend\Models\Lichthidau;
use App\Backend\Services\Shared\VolleyballCompetitionRules;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class OrganizerScheduleService
{
    private const GROUP_STATUSES = ['HOAT_DONG', 'DA_XOA', 'DA_KHOA'];
    private const MATCH_STATUSES = ['CHO_DOI_DOI', 'CHO_XEP_LICH', 'DA_XEP_LICH', 'CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT', 'DANG_DIEN_RA', 'TAM_DUNG', 'DA_KET_THUC', 'DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'];
    private const EDITABLE_MATCH_STATUSES = ['CHO_DOI_DOI', 'CHO_XEP_LICH', 'DA_XEP_LICH', 'CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT'];
    private const SLOT_SOURCE_TYPES = ['TEAM', 'WINNER', 'LOSER', 'SEED', 'BYE'];

    public function __construct(
        private ?Lichthidau $schedules = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->schedules ??= new Lichthidau();
        $this->tournaments ??= new Giaidau();
    }

    public function tournaments(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach giai dau lap lich thanh cong.',
            'tournaments' => $this->schedules->scheduleTournaments((int) $organizer['idbantochuc'], [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            ]),
        ];
    }

    public function summary(int $tournamentId, int $accountId): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $tournament = $context['tournament'];
        $tournament['thethuc'] = $this->tournaments->competitionFormatForTournament($tournamentId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin lich thi dau thanh cong.',
            'schedule' => [
                'tournament' => $tournament,
                'teams' => $this->schedules->approvedTeams($tournamentId),
                'venues' => $this->schedules->activeVenues(),
                'referees' => $this->schedules->activeReferees($tournamentId),
                'rounds' => $this->schedules->roundsForTournament($tournamentId),
                'groups' => $this->groupsWithTeams($tournamentId),
                'matches' => $this->schedules->matchesForTournament($tournamentId),
                'rules' => VolleyballCompetitionRules::meta(),
            ],
        ];
    }

    public function groups(int $tournamentId, int $accountId, array $filters = []): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $normalized = $this->groupFilters($filters);

        if ($normalized['errors'] !== []) {
            return $this->failure('Bo loc bang dau khong hop le.', 422, $normalized['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach bang dau thanh cong.',
            'groups' => $this->groupsWithTeams($tournamentId, $normalized['filters']),
            'meta' => [
                'tournament' => $context['tournament'],
                'teams' => $this->schedules->approvedTeams($tournamentId),
            ],
        ];
    }

    public function group(int $tournamentId, int $groupId, int $accountId): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $group = $this->groupPayload($tournamentId, $groupId);

        if ($group === null) {
            return $this->failure('Khong tim thay bang dau.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin bang dau thanh cong.',
            'group' => $group,
        ];
    }

    public function createGroup(int $tournamentId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $payload = $this->ensureGroupRoundPayload($tournamentId, $payload, $context['tournament'], $accountId, $request);
        [$group, $teamIds, $errors] = $this->validateGroupCreatePayload($tournamentId, $payload, $context['tournament']);

        if ($errors !== []) {
            return $this->failure('Du lieu bang dau khong hop le.', 422, $errors);
        }

        if ($this->schedules->existsGroupName($tournamentId, $group['tenbang'])) {
            return $this->failure('Ten bang dau da ton tai trong giai dau.', 409, [
                'tenbang' => 'Ten bang dau da ton tai.',
            ]);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d them bang dau "%s" vao giai dau "%s". So doi: %d.',
            (int) $context['organizer']['idbantochuc'],
            $group['tenbang'],
            (string) $context['tournament']['tengiaidau'],
            count($teamIds)
        ));

        try {
            $groupId = $this->schedules->createGroup($tournamentId, $group, $teamIds, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Them bang dau thanh cong.',
                'group' => $this->groupPayload($tournamentId, $groupId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the them bang dau.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function updateGroup(int $tournamentId, int $groupId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $current = $this->schedules->groupById($tournamentId, $groupId);

        if ($current === null || (string) $current['trangthai'] === 'DA_XOA') {
            return $this->failure('Khong tim thay bang dau.', 404);
        }

        [$changes, $teamIds, $errors, $changedFields] = $this->validateGroupUpdatePayload($tournamentId, $payload, $current, $context['tournament']);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat bang dau khong hop le.', 422, $errors);
        }

        if ($changes === [] && $teamIds === null) {
            return $this->failure('Can gui it nhat mot truong thay doi.', 422, [
                'payload' => 'Khong co du lieu thay doi.',
            ]);
        }

        $name = (string) ($changes['tenbang'] ?? $current['tenbang']);

        if ($this->schedules->existsGroupName($tournamentId, $name, $groupId)) {
            return $this->failure('Ten bang dau da ton tai trong giai dau.', 409, [
                'tenbang' => 'Ten bang dau da ton tai.',
            ]);
        }

        if ($teamIds !== null && $this->schedules->activeMatchCountForGroup($groupId) > 0) {
            return $this->failure('Khong the cap nhat danh sach doi khi bang dau da co tran dau.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cap nhat bang dau "%s". Truong thay doi: %s.',
            (int) $context['organizer']['idbantochuc'],
            $name,
            implode(', ', $changedFields)
        ));

        try {
            $this->schedules->updateGroup($groupId, $changes, $teamIds, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat bang dau thanh cong.',
                'group' => $this->groupPayload($tournamentId, $groupId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'GROUP_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat bang dau.', 409);
            }

            return $this->failure('Khong the cap nhat bang dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat bang dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function deleteGroup(int $tournamentId, int $groupId, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $current = $this->schedules->groupById($tournamentId, $groupId);

        if ($current === null || (string) $current['trangthai'] === 'DA_XOA') {
            return $this->failure('Khong tim thay bang dau.', 404);
        }

        if ($this->schedules->activeMatchCountForGroup($groupId) > 0) {
            return $this->failure('Khong the xoa bang dau da co tran dau.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d xoa bang dau "%s" trong giai dau "%s".',
            (int) $context['organizer']['idbantochuc'],
            (string) $current['tenbang'],
            (string) $context['tournament']['tengiaidau']
        ));

        try {
            $this->schedules->deleteGroup($groupId, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xoa bang dau thanh cong.',
                'deleted_id' => $groupId,
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'GROUP_NOT_DELETED') {
                return $this->failure('Khong the xoa bang dau hien tai.', 409);
            }

            return $this->failure('Khong the xoa bang dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xoa bang dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function matches(int $tournamentId, int $accountId, array $filters = []): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $normalized = $this->matchFilters($filters);

        if ($normalized['errors'] !== []) {
            return $this->failure('Bo loc tran dau khong hop le.', 422, $normalized['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach tran dau thanh cong.',
            'matches' => $this->schedules->matchesForTournament($tournamentId, $normalized['filters']),
            'meta' => [
                'tournament' => $context['tournament'],
                'groups' => $this->groupsWithTeams($tournamentId),
                'teams' => $this->schedules->approvedTeams($tournamentId),
                'venues' => $this->schedules->activeVenues(),
            ],
        ];
    }

    public function match(int $tournamentId, int $matchId, int $accountId): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $match = $this->schedules->matchById($tournamentId, $matchId);

        if ($match === null) {
            return $this->failure('Khong tim thay tran dau.', 404);
        }

        $match['slots'] = $this->schedules->matchSlots($matchId);
        $match['referee_assignments'] = $this->schedules->matchAssignments($matchId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin tran dau thanh cong.',
            'match' => $match,
        ];
    }

    public function createMatch(int $tournamentId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        [$match, $errors] = $this->validateMatchCreatePayload($tournamentId, $payload, $context['tournament']);

        if ($errors !== []) {
            return $this->failure('Du lieu tran dau khong hop le.', 422, $errors);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d them tran dau giai "%s": slot 1 %s, slot 2 %s, san %s, bat dau %s.',
            (int) $context['organizer']['idbantochuc'],
            (string) $context['tournament']['tengiaidau'],
            $this->slotLogLabel($match['slots'][0] ?? []),
            $this->slotLogLabel($match['slots'][1] ?? []),
            $match['idsandau'] === null ? 'chua xep' : ('#' . (string) $match['idsandau']),
            $match['thoigianbatdau'] === null ? 'chua xep' : (string) $match['thoigianbatdau']
        ));

        try {
            $matchId = $this->schedules->createMatch($tournamentId, $match, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Them tran dau thanh cong.',
                'match' => $this->schedules->matchById($tournamentId, $matchId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the them tran dau.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function generateStandardPreliminarySchedule(int $tournamentId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $payload = $this->ensureGenerateRoundPayload($tournamentId, $payload, $context['tournament'], $accountId, $request);

        $errors = [];
        $roundId = $this->requiredPositiveInt($payload['idvongdau'] ?? $payload['round_id'] ?? null, 'idvongdau', 'Vong dau', $errors);

        if ($errors !== []) {
            return $this->failure('Can chon vong dau de tao tran tu dong.', 422, $errors);
        }

        $round = $this->schedules->roundById($tournamentId, (int) $roundId);

        if ($round === null) {
            return $this->failure('Khong tim thay vong dau.', 404);
        }

        if ((string) $round['loaivongdau'] === 'VONG_DIEM') {
            $groups = $this->groupsWithTeams($tournamentId, ['round_id' => (int) $roundId]);
            $units = [];

            if ($groups !== []) {
                foreach ($groups as $group) {
                    $teamIds = array_map(static fn (array $team): int => (int) $team['iddoibong'], $group['teams'] ?? []);

                    if (count($teamIds) < 2) {
                        return $this->failure('Moi bang can co it nhat 2 doi de sinh tran.', 409);
                    }

                    $units[] = [
                        'group_id' => (int) $group['idbangdau'],
                        'label' => (string) $group['tenbang'],
                        'team_ids' => $teamIds,
                    ];
                }
            } else {
                $teams = $this->schedules->roundTeams((int) $roundId);

                if ($teams === [] && (int) ($round['thutu'] ?? 0) === 1) {
                    $this->schedules->seedRoundTeamsFromApprovedRegistrations((int) $roundId, $tournamentId);
                    $teams = $this->schedules->roundTeams((int) $roundId);
                }

                if (count($teams) < 2) {
                    return $this->failure('Vong dau can co it nhat 2 doi hop le de sinh tran.', 409);
                }

                $units = [[
                    'group_id' => null,
                    'label' => (string) $round['tenvongdau'],
                    'team_ids' => array_map(static fn (array $team): int => (int) $team['iddoibong'], $teams),
                ]];
            }
        }

        if ($this->schedules->matchesForTournament($tournamentId, ['round_id' => (int) $roundId]) !== []) {
            return $this->failure('Vong dau da co tran dau, khong tao tu dong lap lai.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d tao tran tu dong cho vong "%s" cua giai "%s".',
            (int) $context['organizer']['idbantochuc'],
            (string) $round['tenvongdau'],
            (string) $context['tournament']['tengiaidau'],
        ));

        try {
            if ((string) $round['loaivongdau'] === 'VONG_DIEM') {
                $result = $this->schedules->createRoundRobinMatches(
                    $tournamentId,
                    (int) $roundId,
                    $units,
                    (int) ($round['so_luot_dau'] ?? 1),
                    $accountId,
                    $request?->ip(),
                    $logNote
                );
            } else {
                $teams = $this->schedules->roundTeams((int) $roundId);

                if ($teams === [] && (int) ($round['thutu'] ?? 0) === 1) {
                    $this->schedules->seedRoundTeamsFromApprovedRegistrations((int) $roundId, $tournamentId);
                    $teams = $this->schedules->roundTeams((int) $roundId);
                }

                if (count($teams) < 2) {
                    return $this->failure('Vong loai can co it nhat 2 doi hop le de sinh nhanh dau.', 409);
                }

                $blueprint = $this->buildKnockoutBlueprint($round, $teams);

                if ($blueprint === []) {
                    return $this->failure('Khong du thong tin de tao nhanh loai truc tiep.', 409);
                }

                $result = $this->schedules->createKnockoutMatches(
                    $tournamentId,
                    (int) $roundId,
                    $blueprint,
                    (string) ($round['cach_xep_cap_dau'] ?? 'HYBRID'),
                    $accountId,
                    $request?->ip(),
                    $logNote
                );
            }

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao tran tu dong thanh cong.',
                'schedule' => [
                    'tournament' => $context['tournament'],
                    'teams' => $this->schedules->approvedTeams($tournamentId),
                    'venues' => $this->schedules->activeVenues(),
                    'referees' => $this->schedules->activeReferees($tournamentId),
                    'rounds' => $this->schedules->roundsForTournament($tournamentId),
                    'groups' => $this->groupsWithTeams($tournamentId),
                    'matches' => $this->schedules->matchesForTournament($tournamentId),
                    'rules' => VolleyballCompetitionRules::meta(),
                    'generated' => $result,
                ],
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao tran tu dong.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    private function buildKnockoutBlueprint(array $round, array $teams): array
    {
        $teamCount = count($teams);
        $bracketSize = $this->nextPowerOfTwo($teamCount);
        $seedOrder = $this->seedOrder($bracketSize);
        $teamSources = [];

        foreach (array_values($teams) as $index => $team) {
            $teamSources[$index + 1] = [
                'source_type' => 'TEAM',
                'iddoibong' => (int) $team['iddoibong'],
                'source_seed_no' => $index + 1,
            ];
        }

        $sources = array_map(
            static fn (int $seed): ?array => $teamSources[$seed] ?? null,
            $seedOrder
        );
        $matches = [];
        $stage = 1;
        $order = 0;
        $semifinalKeys = [];

        while (count($sources) > 1) {
            $nextSources = [];
            $remainingTeams = count($sources);
            $stageName = $this->knockoutStageName($remainingTeams);

            for ($index = 0; $index < count($sources); $index += 2) {
                $left = $sources[$index] ?? null;
                $right = $sources[$index + 1] ?? null;

                if ($left === null && $right === null) {
                    $nextSources[] = null;
                    continue;
                }

                if ($left === null || $right === null) {
                    $nextSources[] = $left ?? $right;
                    continue;
                }

                $order++;
                $clientKey = 'stage_' . $stage . '_match_' . (($index / 2) + 1);
                $matches[] = [
                    'client_key' => $clientKey,
                    'ma_tran' => sprintf('R%d-K%03d', (int) $round['idvongdau'], $order),
                    'ten_tran' => $stageName,
                    'iddoibong1' => $left['source_type'] === 'TEAM' ? (int) $left['iddoibong'] : null,
                    'iddoibong2' => $right['source_type'] === 'TEAM' ? (int) $right['iddoibong'] : null,
                    'thutu_tran' => $order,
                    'trangthai' => $left['source_type'] === 'TEAM' && $right['source_type'] === 'TEAM'
                        ? 'CHO_XEP_LICH'
                        : 'CHO_DOI_DOI',
                    'slots' => [
                        $this->slotFromSource(1, $left),
                        $this->slotFromSource(2, $right),
                    ],
                ];
                $nextSources[] = [
                    'source_type' => 'WINNER',
                    'source_client_key' => $clientKey,
                ];

                if ($remainingTeams === 4) {
                    $semifinalKeys[] = $clientKey;
                }
            }

            $sources = $nextSources;
            $stage++;
        }

        if ((int) ($round['co_tranh_hang_ba'] ?? 0) === 1 && count($semifinalKeys) === 2) {
            $order++;
            $matches[] = [
                'client_key' => 'third_place',
                'ma_tran' => sprintf('R%d-K%03d', (int) $round['idvongdau'], $order),
                'ten_tran' => 'Tranh hạng 3',
                'iddoibong1' => null,
                'iddoibong2' => null,
                'thutu_tran' => $order,
                'trangthai' => 'CHO_DOI_DOI',
                'slots' => [
                    [
                        'slot_so' => 1,
                        'source_type' => 'LOSER',
                        'source_client_key' => $semifinalKeys[0],
                        'source_result' => 'LOSER',
                    ],
                    [
                        'slot_so' => 2,
                        'source_type' => 'LOSER',
                        'source_client_key' => $semifinalKeys[1],
                        'source_result' => 'LOSER',
                    ],
                ],
            ];
        }

        return $matches;
    }

    private function slotFromSource(int $slotNo, array $source): array
    {
        return [
            'slot_so' => $slotNo,
            'source_type' => $source['source_type'],
            'iddoibong' => $source['iddoibong'] ?? null,
            'source_seed_no' => $source['source_seed_no'] ?? null,
            'source_client_key' => $source['source_client_key'] ?? null,
            'source_result' => $source['source_type'] === 'WINNER'
                ? 'WINNER'
                : ($source['source_type'] === 'LOSER' ? 'LOSER' : null),
        ];
    }

    private function nextPowerOfTwo(int $value): int
    {
        $power = 1;

        while ($power < $value) {
            $power *= 2;
        }

        return $power;
    }

    private function seedOrder(int $bracketSize): array
    {
        $order = [1, 2];

        while (count($order) < $bracketSize) {
            $next = [];
            $maxSeed = count($order) * 2 + 1;

            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $maxSeed - $seed;
            }

            $order = $next;
        }

        return $order;
    }

    private function knockoutStageName(int $remainingTeams): string
    {
        return match ($remainingTeams) {
            2 => 'Chung kết',
            4 => 'Bán kết',
            8 => 'Tứ kết',
            default => 'Vòng loại ' . (string) $remainingTeams,
        };
    }

    public function updateMatch(int $tournamentId, int $matchId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $current = $this->schedules->matchById($tournamentId, $matchId);

        if ($current === null) {
            return $this->failure('Khong tim thay tran dau.', 404);
        }

        if (!in_array((string) $current['trangthai'], self::EDITABLE_MATCH_STATUSES, true)) {
            return $this->failure('Chi duoc cap nhat tran dau chua dien ra hoac sap dien ra.', 409);
        }

        [$changes, $slots, $assignments, $errors, $changedFields] = $this->validateMatchUpdatePayload($tournamentId, $payload, $current, $context['tournament']);

        if ($errors !== []) {
            return $this->failure('Du lieu cap nhat tran dau khong hop le.', 422, $errors);
        }

        if ($changes === [] && $slots === null && $assignments === null) {
            return $this->failure('Can gui it nhat mot truong thay doi.', 422, [
                'payload' => 'Khong co du lieu thay doi.',
            ]);
        }

        $newStatus = array_key_exists('trangthai', $changes) ? (string) $changes['trangthai'] : null;
        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cap nhat tran dau #%d. Truong thay doi: %s.',
            (int) $context['organizer']['idbantochuc'],
            $matchId,
            implode(', ', $changedFields)
        ));

        try {
            $this->schedules->updateMatch(
                $matchId,
                $changes,
                $slots,
                $assignments,
                (string) $current['trangthai'],
                $newStatus,
                $accountId,
                $request?->ip(),
                $logNote
            );

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cap nhat tran dau thanh cong.',
                'match' => $this->schedules->matchById($tournamentId, $matchId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'MATCH_NOT_UPDATED') {
                return $this->failure('Khong the cap nhat tran dau.', 409);
            }

            return $this->failure('Khong the cap nhat tran dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cap nhat tran dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    public function deleteMatch(int $tournamentId, int $matchId, int $accountId, ?Request $request = null): array
    {
        $context = $this->scheduleTournament($tournamentId, $accountId, true);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $current = $this->schedules->matchById($tournamentId, $matchId);

        if ($current === null || in_array((string) $current['trangthai'], ['DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT'], true)) {
            return $this->failure('Khong tim thay tran dau.', 404);
        }

        if (!in_array((string) $current['trangthai'], self::EDITABLE_MATCH_STATUSES, true)) {
            return $this->failure('Chi duoc xoa tran dau chua dien ra hoac sap dien ra.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d xoa tran dau #%d trong giai "%s".',
            (int) $context['organizer']['idbantochuc'],
            $matchId,
            (string) $context['tournament']['tengiaidau']
        ));

        try {
            $this->schedules->deleteMatch($matchId, (string) $current['trangthai'], $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Xoa tran dau thanh cong.',
                'deleted_id' => $matchId,
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'MATCH_NOT_DELETED') {
                return $this->failure('Khong the xoa tran dau hien tai.', 409);
            }

            return $this->failure('Khong the xoa tran dau.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the xoa tran dau.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function ensureGroupRoundPayload(
        int $tournamentId,
        array $payload,
        array $tournament,
        int $accountId,
        ?Request $request
    ): array {
        $unusedErrors = [];
        $roundId = $this->optionalPositiveInt($payload['idvongdau'] ?? $payload['round_id'] ?? null, 'idvongdau', $unusedErrors);

        if ($roundId !== null) {
            return $payload;
        }

        $format = $this->tournaments->competitionFormatForTournament($tournamentId) ?? [];
        $ensuredRoundId = $this->schedules->ensurePointRoundFromFormat(
            $tournamentId,
            $format,
            (int) ($tournament['quymo'] ?? 0),
            $accountId,
            $request?->ip()
        );

        if ($ensuredRoundId !== null) {
            $payload['idvongdau'] = $ensuredRoundId;
            $payload['round_id'] = $ensuredRoundId;
        }

        return $payload;
    }

    private function ensureGenerateRoundPayload(
        int $tournamentId,
        array $payload,
        array $tournament,
        int $accountId,
        ?Request $request
    ): array {
        $unusedErrors = [];
        $roundId = $this->optionalPositiveInt($payload['idvongdau'] ?? $payload['round_id'] ?? null, 'idvongdau', $unusedErrors);

        if ($roundId !== null) {
            return $payload;
        }

        $roundType = strtoupper((string) ($payload['loaivongdau'] ?? $payload['round_type'] ?? ''));

        if (!in_array($roundType, ['VONG_DIEM', 'VONG_LOAI'], true)) {
            return $payload;
        }

        $format = $this->tournaments->competitionFormatForTournament($tournamentId) ?? [];
        $ensuredRoundId = $this->schedules->ensureRoundFromFormat(
            $tournamentId,
            $format,
            $roundType,
            (int) ($tournament['quymo'] ?? 0),
            $accountId,
            $request?->ip()
        );

        if ($ensuredRoundId !== null) {
            $payload['idvongdau'] = $ensuredRoundId;
            $payload['round_id'] = $ensuredRoundId;
        }

        return $payload;
    }

    private function validateGroupCreatePayload(int $tournamentId, array $payload, array $tournament): array
    {
        $errors = [];
        $group = [
            'idvongdau' => $this->requiredPositiveInt($payload['idvongdau'] ?? $payload['round_id'] ?? null, 'idvongdau', 'Vong dau', $errors),
            'tenbang' => $this->requiredString($payload, ['tenbang', 'ten', 'name'], 100, 'Ten bang dau', $errors),
            'mota' => $this->nullableString($payload['mota'] ?? $payload['description'] ?? $payload['desc'] ?? null, 500, 'Mo ta', 'mota', $errors),
            'thoigianbatdau' => (string) $tournament['thoigianbatdau'],
            'thoigianketthuc' => $this->groupEndDate($payload['thoigianketthuc'] ?? $payload['end_date'] ?? null, $tournament, $errors),
            'trangthai' => $this->groupStatus($payload['trangthai'] ?? $payload['status'] ?? 'HOAT_DONG', 'trangthai', $errors),
        ];
        $teamIds = $this->teamIds($payload, $errors);

        $this->validateApprovedTeams($tournamentId, $teamIds, $errors);
        $this->validateGroupRound($tournamentId, $group['idvongdau'], $errors);

        return [$group, $teamIds, $errors];
    }

    private function validateGroupUpdatePayload(int $tournamentId, array $payload, array $current, array $tournament): array
    {
        $errors = [];
        $changes = [];
        $changedFields = [];
        $teamIds = null;

        if ($this->hasAnyKey($payload, ['tenbang', 'ten', 'name'])) {
            $name = $this->requiredString($payload, ['tenbang', 'ten', 'name'], 100, 'Ten bang dau', $errors);

            if ($name !== null && $name !== (string) $current['tenbang']) {
                $changes['tenbang'] = $name;
                $changedFields[] = 'tenbang';
            }
        }

        if ($this->hasAnyKey($payload, ['idvongdau', 'round_id'])) {
            $roundId = $this->requiredPositiveInt($payload['idvongdau'] ?? $payload['round_id'] ?? null, 'idvongdau', 'Vong dau', $errors);
            $this->validateGroupRound($tournamentId, $roundId, $errors);

            if ($roundId !== null && $roundId !== (int) $current['idvongdau']) {
                $changes['idvongdau'] = $roundId;
                $changedFields[] = 'idvongdau';
            }
        }

        if (array_key_exists('mota', $payload) || array_key_exists('description', $payload) || array_key_exists('desc', $payload)) {
            $description = $this->nullableString($payload['mota'] ?? $payload['description'] ?? $payload['desc'] ?? null, 500, 'Mo ta', 'mota', $errors);

            if ($description !== ($current['mota'] ?? null)) {
                $changes['mota'] = $description;
                $changedFields[] = 'mota';
            }
        }

        if (array_key_exists('thoigianketthuc', $payload) || array_key_exists('end_date', $payload)) {
            $endDate = $this->groupEndDate($payload['thoigianketthuc'] ?? $payload['end_date'] ?? null, $tournament, $errors);

            if ($endDate !== ($current['thoigianketthuc'] ?? null)) {
                $changes['thoigianketthuc'] = $endDate;
                $changedFields[] = 'thoigianketthuc';
            }
        }

        if (array_key_exists('trangthai', $payload) || array_key_exists('status', $payload)) {
            $status = $this->groupStatus($payload['trangthai'] ?? $payload['status'] ?? '', 'trangthai', $errors);

            if ($status !== null && $status !== (string) $current['trangthai']) {
                $changes['trangthai'] = $status;
                $changedFields[] = 'trangthai';
            }
        }

        if ($this->hasAnyKey($payload, ['team_ids', 'teams', 'dois', 'iddoibong'])) {
            $teamIds = $this->teamIds($payload, $errors);
            $this->validateApprovedTeams($tournamentId, $teamIds, $errors);

            $currentTeamIds = $this->schedules->teamIdsInGroup((int) $current['idbangdau']);
            sort($currentTeamIds);
            $compareTeamIds = $teamIds;
            sort($compareTeamIds);

            if ($compareTeamIds === $currentTeamIds) {
                $teamIds = null;
            } else {
                $changedFields[] = 'teams';
            }
        }

        return [$changes, $teamIds, $errors, array_values(array_unique($changedFields))];
    }

    private function groupEndDate(mixed $value, array $tournament, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $text);

        if (!$date || $date->format('Y-m-d') !== $text) {
            $errors['thoigianketthuc'] = 'Thoi gian ket thuc bang dau phai theo dinh dang YYYY-MM-DD.';
            return null;
        }

        $tournamentStart = DateTimeImmutable::createFromFormat('Y-m-d', substr((string) $tournament['thoigianbatdau'], 0, 10));
        $tournamentEnd = DateTimeImmutable::createFromFormat('Y-m-d', substr((string) $tournament['thoigianketthuc'], 0, 10));

        if ($tournamentStart !== false && $date <= $tournamentStart) {
            $errors['thoigianketthuc'] = 'Thoi gian ket thuc bang dau phai sau thoi gian bat dau bang dau.';
        }

        if ($tournamentEnd !== false && $date > $tournamentEnd) {
            $errors['thoigianketthuc'] = 'Thoi gian ket thuc bang dau khong duoc sau ngay ket thuc giai dau.';
        }

        return $text;
    }

    private function validateMatchCreatePayload(int $tournamentId, array $payload, array $tournament): array
    {
        $errors = [];
        $slots = $this->matchSlotsPayload($tournamentId, $payload, $errors);
        [$teamOneId, $teamTwoId] = $this->directTeamIdsFromSlots($slots);
        $match = [
            'idvongdau' => $this->requiredPositiveInt($payload['idvongdau'] ?? $payload['round_id'] ?? null, 'idvongdau', 'Vong dau', $errors),
            'idbangdau' => $this->optionalPositiveInt($payload['idbangdau'] ?? $payload['group_id'] ?? null, 'idbangdau', $errors),
            'iddoibong1' => $teamOneId,
            'iddoibong2' => $teamTwoId,
            'idsandau' => $this->optionalPositiveInt($payload['idsandau'] ?? $payload['venue_id'] ?? null, 'idsandau', $errors),
            'thoigianbatdau' => $this->nullableDateTime($payload['thoigianbatdau'] ?? $payload['start_at'] ?? null, 'thoigianbatdau', 'Thoi gian bat dau', $errors),
            'thoigianketthuc' => $this->nullableDateTime($payload['thoigianketthuc'] ?? $payload['end_at'] ?? null, 'thoigianketthuc', 'Thoi gian ket thuc', $errors),
            'ma_tran' => $this->nullableString($payload['ma_tran'] ?? $payload['match_code'] ?? null, 100, 'Ma tran', 'ma_tran', $errors),
            'ten_tran' => $this->nullableString($payload['ten_tran'] ?? $payload['match_name'] ?? null, 300, 'Ten tran', 'ten_tran', $errors),
            'thutu_tran' => $this->optionalPositiveInt($payload['thutu_tran'] ?? $payload['match_order'] ?? null, 'thutu_tran', $errors),
            'trangthai' => $this->matchStatus($payload['trangthai'] ?? $payload['status'] ?? 'CHO_DOI_DOI', 'trangthai', $errors),
            'slots' => $slots,
            'referee_assignments' => $this->refereeAssignments($payload, $errors),
        ];
        $match['ma_tran'] ??= sprintf('R%d-%s', (int) ($match['idvongdau'] ?? 0), date('YmdHis'));
        $match['thutu_tran'] ??= 1;

        $this->validateRefereeAssignmentLevels($tournamentId, $match['referee_assignments'], $errors);
        $this->validateMatchCandidate($tournamentId, $match, $tournament, null, $errors);

        return [$match, $errors];
    }

    private function validateMatchUpdatePayload(int $tournamentId, array $payload, array $current, array $tournament): array
    {
        $errors = [];
        $slots = null;
        $assignments = null;
        $candidate = [
            'idbangdau' => $current['idbangdau'] === null ? null : (int) $current['idbangdau'],
            'idvongdau' => (int) $current['idvongdau'],
            'iddoibong1' => $current['iddoibong1'] === null ? null : (int) $current['iddoibong1'],
            'iddoibong2' => $current['iddoibong2'] === null ? null : (int) $current['iddoibong2'],
            'idsandau' => $current['idsandau'] === null ? null : (int) $current['idsandau'],
            'thoigianbatdau' => $current['thoigianbatdau'] === null ? null : (string) $current['thoigianbatdau'],
            'thoigianketthuc' => $current['thoigianketthuc'] === null ? null : (string) $current['thoigianketthuc'],
            'trangthai' => (string) $current['trangthai'],
        ];
        $changes = [];
        $changedFields = [];

        $fieldMap = [
            'idbangdau' => ['idbangdau', 'group_id'],
            'idvongdau' => ['idvongdau', 'round_id'],
            'iddoibong1' => ['iddoibong1', 'team_one_id', 'team1'],
            'iddoibong2' => ['iddoibong2', 'team_two_id', 'team2'],
            'idsandau' => ['idsandau', 'venue_id'],
        ];

        foreach ($fieldMap as $field => $keys) {
            if (!$this->hasAnyKey($payload, $keys)) {
                continue;
            }

            $value = in_array($field, ['idbangdau', 'idsandau'], true)
                ? $this->optionalPositiveInt($payload[$this->firstExistingKey($payload, $keys)] ?? null, $field, $errors)
                : $this->requiredPositiveInt($payload[$this->firstExistingKey($payload, $keys)] ?? null, $field, $field, $errors);

            $candidate[$field] = $value;
        }

        if ($this->hasAnyKey($payload, ['thoigianbatdau', 'start_at'])) {
            $candidate['thoigianbatdau'] = $this->dateTimeValue($payload['thoigianbatdau'] ?? $payload['start_at'] ?? null, 'thoigianbatdau', 'Thoi gian bat dau', $errors);
        }

        if ($this->hasAnyKey($payload, ['thoigianketthuc', 'end_at'])) {
            $candidate['thoigianketthuc'] = $this->nullableDateTime($payload['thoigianketthuc'] ?? $payload['end_at'] ?? null, 'thoigianketthuc', 'Thoi gian ket thuc', $errors);
        }

        if ($this->hasAnyKey($payload, ['trangthai', 'status'])) {
            $candidate['trangthai'] = $this->matchStatus($payload['trangthai'] ?? $payload['status'] ?? '', 'trangthai', $errors);
        }

        if (array_key_exists('slots', $payload)) {
            $slots = $this->matchSlotsPayload($tournamentId, $payload, $errors);
            [$candidate['iddoibong1'], $candidate['iddoibong2']] = $this->directTeamIdsFromSlots($slots);
            $changedFields[] = 'slots';
        }

        if (array_key_exists('referee_assignments', $payload) || array_key_exists('trongtai', $payload)) {
            $assignments = $this->refereeAssignments($payload, $errors);
            $this->validateRefereeAssignmentLevels($tournamentId, $assignments, $errors);
            $changedFields[] = 'referee_assignments';
        }

        $this->validateMatchCandidate($tournamentId, $candidate, $tournament, (int) $current['idtrandau'], $errors);

        foreach ($candidate as $field => $value) {
            $currentValue = $current[$field] ?? null;

            if ($field === 'idbangdau') {
                $currentValue = $currentValue === null ? null : (int) $currentValue;
            } elseif (in_array($field, ['iddoibong1', 'iddoibong2', 'idsandau'], true)) {
                $currentValue = $currentValue === null ? null : (int) $currentValue;
            }

            if ($value !== $currentValue) {
                $changes[$field] = $value;
                $changedFields[] = $field;
            }
        }

        return [$changes, $slots, $assignments, $errors, array_values(array_unique($changedFields))];
    }

    private function validateMatchCandidate(int $tournamentId, array $match, array $tournament, ?int $excludeMatchId, array &$errors): void
    {
        foreach (['idvongdau', 'trangthai'] as $field) {
            if ($match[$field] === null) {
                return;
            }
        }

        if ($match['iddoibong1'] !== null && $match['iddoibong2'] !== null && (int) $match['iddoibong1'] === (int) $match['iddoibong2']) {
            $errors['iddoibong2'] = 'Hai doi thi dau phai khac nhau.';
        }

        $round = $this->schedules->roundById($tournamentId, (int) $match['idvongdau']);

        if ($round === null) {
            $errors['idvongdau'] = 'Vong dau khong thuoc giai dau.';
        }

        if ($match['thoigianketthuc'] !== null && $match['thoigianbatdau'] !== null && $match['thoigianketthuc'] <= $match['thoigianbatdau']) {
            $errors['thoigianketthuc'] = 'Thoi gian ket thuc phai lon hon thoi gian bat dau.';
        }

        $this->validateMatchTimeWithinTournament($match, $tournament, $errors);

        if ($match['idsandau'] !== null && $this->schedules->activeVenueById((int) $match['idsandau']) === null) {
            $errors['idsandau'] = 'San dau khong ton tai hoac khong o trang thai hoat dong.';
        }

        $teamIds = array_values(array_filter([
            $match['iddoibong1'] === null ? null : (int) $match['iddoibong1'],
            $match['iddoibong2'] === null ? null : (int) $match['iddoibong2'],
        ], static fn (?int $teamId): bool => $teamId !== null));
        $approved = $this->schedules->approvedTeamIds($tournamentId, $teamIds);

        foreach ($teamIds as $teamId) {
            if (!in_array($teamId, $approved, true)) {
                $errors['iddoibong'] = 'Doi bong phai duoc duyet tham gia giai va dang hoat dong.';
                break;
            }
        }

        if ($match['idbangdau'] !== null) {
            $group = $this->schedules->groupById($tournamentId, (int) $match['idbangdau']);

            if ($group === null || (string) $group['trangthai'] !== 'HOAT_DONG') {
                $errors['idbangdau'] = 'Bang dau khong ton tai hoac khong hoat dong.';
            } else {
                $groupTeamIds = $this->schedules->teamIdsInGroup((int) $match['idbangdau']);

                foreach ($teamIds as $teamId) {
                    if (!in_array($teamId, $groupTeamIds, true)) {
                        $errors['iddoibong'] = 'Hai doi thi dau phai thuoc bang dau da chon.';
                        break;
                    }
                }

                if ((int) $group['idvongdau'] !== (int) $match['idvongdau']) {
                    $errors['idbangdau'] = 'Bang dau phai thuoc vong dau da chon.';
                }
            }
        }

        if ($errors !== [] || $match['idsandau'] === null || $match['thoigianbatdau'] === null || count($teamIds) < 2) {
            return;
        }

        $conflict = $this->schedules->hasScheduleConflict(
            (int) $match['idsandau'],
            (int) $match['iddoibong1'],
            (int) $match['iddoibong2'],
            (string) $match['thoigianbatdau'],
            $match['thoigianketthuc'] === null ? null : (string) $match['thoigianketthuc'],
            $excludeMatchId
        );

        if ($conflict !== null) {
            $errors['thoigianbatdau'] = 'Thoi gian bi trung san dau hoac doi bong voi tran dau #' . (string) $conflict['idtrandau'] . '.';
        }
    }

    private function validateMatchTimeWithinTournament(array $match, array $tournament, array &$errors): void
    {
        $tournamentStart = $this->dateTimeFromDatabaseValue($tournament['thoigianbatdau'] ?? null, '00:00:00');
        $tournamentEnd = $this->dateTimeFromDatabaseValue($tournament['thoigianketthuc'] ?? null, '23:59:59');

        if ($tournamentStart === null || $tournamentEnd === null) {
            return;
        }

        $matchStart = $this->dateTimeFromDatabaseValue($match['thoigianbatdau'] ?? null);
        $matchEnd = $this->dateTimeFromDatabaseValue($match['thoigianketthuc'] ?? null);

        if ($matchStart !== null && $matchStart < $tournamentStart) {
            $errors['thoigianbatdau'] = 'Thoi gian bat dau tran dau khong duoc truoc ngay bat dau giai dau.';
        }

        if ($matchStart !== null && $matchStart > $tournamentEnd) {
            $errors['thoigianbatdau'] = 'Thoi gian bat dau tran dau khong duoc sau ngay ket thuc giai dau.';
        }

        if ($matchEnd !== null && $matchEnd < $tournamentStart) {
            $errors['thoigianketthuc'] = 'Thoi gian ket thuc tran dau khong duoc truoc ngay bat dau giai dau.';
        }

        if ($matchEnd !== null && $matchEnd > $tournamentEnd) {
            $errors['thoigianketthuc'] = 'Thoi gian ket thuc tran dau khong duoc sau ngay ket thuc giai dau.';
        }
    }

    private function dateTimeFromDatabaseValue(mixed $value, string $dateOnlyTime = '00:00:00'): ?DateTimeImmutable
    {
        $text = str_replace('T', ' ', trim((string) ($value ?? '')));

        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            $text .= ' ' . $dateOnlyTime;
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $text)) {
            $text .= ':00';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $text);

        return $date === false ? null : $date;
    }

    private function standardScheduleOptions(array $payload, array $tournament, array $venues): array
    {
        $errors = [];
        $venueId = $this->optionalPositiveInt($payload['idsandau'] ?? $payload['venue_id'] ?? null, 'idsandau', $errors);

        if ($venueId === null) {
            $venueId = (int) $venues[0]['idsandau'];
        } elseif ($this->schedules->activeVenueById($venueId) === null) {
            $errors['idsandau'] = 'San dau khong ton tai hoac khong o trang thai hoat dong.';
        }

        $startAt = $this->standardStartAt($payload, $tournament, $errors);
        $matchMinutes = $this->boundedInteger($payload['match_minutes'] ?? null, 90, 30, 300, 'match_minutes', $errors);
        $gapMinutes = $this->boundedInteger($payload['gap_minutes'] ?? null, 30, 0, 180, 'gap_minutes', $errors);
        $matchesPerDay = $this->boundedInteger($payload['matches_per_day'] ?? null, 5, 1, 10, 'matches_per_day', $errors);

        if ($startAt !== null) {
            $lastSlotIndex = VolleyballCompetitionRules::PRELIMINARY_MATCH_COUNT - 1;
            $lastDayOffset = intdiv($lastSlotIndex, $matchesPerDay);
            $lastSlot = $lastSlotIndex % $matchesPerDay;
            $lastEnd = $startAt
                ->modify('+' . $lastDayOffset . ' days')
                ->modify('+' . ($lastSlot * ($matchMinutes + $gapMinutes) + $matchMinutes) . ' minutes');
            $tournamentEnd = $this->dateTimeFromDatabaseValue($tournament['thoigianketthuc'] ?? null, '23:59:59');

            if ($tournamentEnd !== null && $lastEnd > $tournamentEnd) {
                $errors['thoigianketthuc'] = 'Lich so bo 45 tran vuot qua thoi gian ket thuc giai dau.';
            }
        }

        return [[
            'venue_id' => $venueId,
            'start_at' => $startAt,
            'match_minutes' => $matchMinutes,
            'gap_minutes' => $gapMinutes,
            'matches_per_day' => $matchesPerDay,
        ], $errors];
    }

    private function buildPreliminaryMatches(array $teamIds, array $options): array
    {
        $pairs = VolleyballCompetitionRules::roundRobinPairs($teamIds);
        $matches = [];
        $startAt = $options['start_at'];
        $matchMinutes = (int) $options['match_minutes'];
        $gapMinutes = (int) $options['gap_minutes'];
        $matchesPerDay = (int) $options['matches_per_day'];

        foreach ($pairs as $index => [$teamOneId, $teamTwoId]) {
            $dayOffset = intdiv($index, $matchesPerDay);
            $slot = $index % $matchesPerDay;
            $start = $startAt
                ->modify('+' . $dayOffset . ' days')
                ->modify('+' . ($slot * ($matchMinutes + $gapMinutes)) . ' minutes');
            $end = $start->modify('+' . $matchMinutes . ' minutes');

            $matches[] = [
                'iddoibong1' => $teamOneId,
                'iddoibong2' => $teamTwoId,
                'idsandau' => (int) $options['venue_id'],
                'thoigianbatdau' => $start->format('Y-m-d H:i:s'),
                'thoigianketthuc' => $end->format('Y-m-d H:i:s'),
                'vongdau' => VolleyballCompetitionRules::PRELIMINARY_ROUND,
                'trangthai' => 'CHUA_DIEN_RA',
            ];
        }

        return $matches;
    }

    private function standardStartAt(array $payload, array $tournament, array &$errors): ?DateTimeImmutable
    {
        $raw = trim((string) ($payload['thoigianbatdau'] ?? $payload['start_at'] ?? ''));

        if ($raw === '') {
            $raw = (string) ($tournament['thoigianbatdau'] ?? '');
        }

        $normalized = str_replace('T', ' ', $raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            $normalized .= ' 08:00:00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
            $normalized .= ':00';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $normalized);

        if (!$date || $date->format('Y-m-d H:i:s') !== $normalized) {
            $errors['thoigianbatdau'] = 'Thoi gian bat dau phai theo dinh dang YYYY-MM-DD HH:MM[:SS].';
            return null;
        }

        $tournamentStart = $this->dateTimeFromDatabaseValue($tournament['thoigianbatdau'] ?? null, '08:00:00');

        if ($tournamentStart !== null && $date < $tournamentStart) {
            $errors['thoigianbatdau'] = 'Thoi gian bat dau lich khong duoc truoc ngay bat dau giai dau.';
        }

        return $date;
    }

    private function boundedInteger(mixed $value, int $default, int $min, int $max, string $errorKey, array &$errors): int
    {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        if (!ctype_digit((string) $value)) {
            $errors[$errorKey] = 'Gia tri phai la so nguyen khong am.';
            return $default;
        }

        $number = (int) $value;

        if ($number < $min || $number > $max) {
            $errors[$errorKey] = 'Gia tri phai nam trong khoang ' . $min . ' den ' . $max . '.';
        }

        return $number;
    }

    private function groupPayload(int $tournamentId, int $groupId): ?array
    {
        $group = $this->schedules->groupById($tournamentId, $groupId);

        if ($group === null) {
            return null;
        }

        $group['teams'] = $this->schedules->groupTeams($groupId);
        $group['matches'] = $this->schedules->matchesForTournament($tournamentId, ['group_id' => $groupId]);

        return $group;
    }

    private function groupsWithTeams(int $tournamentId, array $filters = []): array
    {
        $groups = $this->schedules->groupsForTournament($tournamentId, $filters);

        foreach ($groups as &$group) {
            $group['teams'] = $this->schedules->groupTeams((int) $group['idbangdau']);
        }

        return $groups;
    }

    private function scheduleTournament(int $tournamentId, int $accountId, bool $requireClosedRegistration = false): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $tournament = $this->schedules->tournamentForOrganizer((int) $organizer['idbantochuc'], $tournamentId);

        if ($tournament === null) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if ((string) $tournament['trangthai'] === 'DA_HUY') {
            return $this->failure('Khong the quan ly lich thi dau cua giai dau da huy.', 409);
        }

        if ($requireClosedRegistration && (string) $tournament['trangthaidangky'] !== 'DA_DONG') {
            return $this->failure('Can dong dang ky giai dau truoc khi quan ly lich thi dau.', 409);
        }

        return [
            'organizer' => $organizer,
            'tournament' => $tournament,
        ];
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

    private function groupFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $errors = [];

        if ($status !== '' && !in_array($status, self::GROUP_STATUSES, true)) {
            $errors['status'] = 'Trang thai bang dau khong hop le.';
        }

        return [
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
                'status' => $status,
                'round_id' => $this->optionalPositiveInt($filters['round_id'] ?? $filters['idvongdau'] ?? null, 'round_id', $errors),
            ],
            'errors' => $errors,
        ];
    }

    private function matchFilters(array $filters): array
    {
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $errors = [];
        $groupId = $this->optionalPositiveInt($filters['group_id'] ?? $filters['idbangdau'] ?? null, 'group_id', $errors);
        $roundId = $this->optionalPositiveInt($filters['round_id'] ?? $filters['idvongdau'] ?? null, 'round_id', $errors);

        if ($status !== '' && !in_array($status, self::MATCH_STATUSES, true)) {
            $errors['status'] = 'Trang thai tran dau khong hop le.';
        }

        return [
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
                'status' => $status,
                'group_id' => $groupId,
                'round_id' => $roundId,
            ],
            'errors' => $errors,
        ];
    }

    private function validateApprovedTeams(int $tournamentId, array $teamIds, array &$errors): void
    {
        if ($teamIds === []) {
            return;
        }

        $approved = $this->schedules->approvedTeamIds($tournamentId, $teamIds);

        foreach ($teamIds as $teamId) {
            if (!in_array($teamId, $approved, true)) {
                $errors['team_ids'] = 'Tat ca doi trong bang phai duoc duyet tham gia giai va dang hoat dong.';
                return;
            }
        }
    }

    private function validateGroupRound(int $tournamentId, ?int $roundId, array &$errors): void
    {
        if ($roundId === null) {
            return;
        }

        $round = $this->schedules->roundById($tournamentId, $roundId);

        if ($round === null) {
            $errors['idvongdau'] = 'Vong dau khong thuoc giai dau.';
            return;
        }

        if ((string) ($round['loaivongdau'] ?? '') !== 'VONG_DIEM') {
            $errors['idvongdau'] = 'Chi duoc tao bang cho vong diem.';
        }
    }

    private function matchSlotsPayload(int $tournamentId, array $payload, array &$errors): array
    {
        if (!array_key_exists('slots', $payload)) {
            return $this->teamSlots($payload);
        }

        if (!is_array($payload['slots'])) {
            $errors['slots'] = 'Danh sach nguon doi khong hop le.';
            return [];
        }

        $slots = [];
        $seenSlotNumbers = [];

        foreach ($payload['slots'] as $index => $item) {
            if (!is_array($item)) {
                $errors['slots.' . $index] = 'Nguon doi khong hop le.';
                continue;
            }

            $slotNo = $this->requiredPositiveInt($item['slot_so'] ?? $item['slot_no'] ?? null, 'slots.' . $index . '.slot_so', 'Slot', $errors);
            $sourceType = strtoupper(trim((string) ($item['source_type'] ?? 'TEAM')));

            if ($slotNo !== null && !in_array($slotNo, [1, 2], true)) {
                $errors['slots.' . $index . '.slot_so'] = 'Slot chi duoc la 1 hoac 2.';
            }

            if (!in_array($sourceType, self::SLOT_SOURCE_TYPES, true)) {
                $errors['slots.' . $index . '.source_type'] = 'Loai nguon doi khong hop le.';
                continue;
            }

            if ($slotNo !== null && in_array($slotNo, $seenSlotNumbers, true)) {
                $errors['slots.' . $index . '.slot_so'] = 'Moi slot chi duoc khai bao mot lan.';
            }

            $slot = [
                'slot_so' => $slotNo,
                'source_type' => $sourceType,
                'iddoibong' => null,
                'source_match_id' => null,
                'source_result' => null,
                'source_seed_no' => null,
                'ghichu' => $this->nullableString($item['ghichu'] ?? $item['note'] ?? null, 500, 'Ghi chu slot', 'slots.' . $index . '.ghichu', $errors),
            ];

            if ($sourceType === 'TEAM') {
                $slot['iddoibong'] = $this->requiredPositiveInt(
                    $item['iddoibong'] ?? $item['team_id'] ?? null,
                    'slots.' . $index . '.iddoibong',
                    'Doi bong',
                    $errors
                );
            } elseif (in_array($sourceType, ['WINNER', 'LOSER'], true)) {
                $sourceMatchId = $this->requiredPositiveInt(
                    $item['source_match_id'] ?? $item['match_id'] ?? null,
                    'slots.' . $index . '.source_match_id',
                    'Tran nguon',
                    $errors
                );

                if ($sourceMatchId !== null && $this->schedules->matchById($tournamentId, $sourceMatchId) === null) {
                    $errors['slots.' . $index . '.source_match_id'] = 'Tran nguon khong thuoc giai dau.';
                }

                $slot['source_match_id'] = $sourceMatchId;
                $slot['source_result'] = $sourceType;
            } elseif ($sourceType === 'SEED') {
                $slot['source_seed_no'] = $this->requiredPositiveInt(
                    $item['source_seed_no'] ?? $item['seed_no'] ?? null,
                    'slots.' . $index . '.source_seed_no',
                    'So hat giong',
                    $errors
                );
            }

            if ($slotNo !== null) {
                $seenSlotNumbers[] = $slotNo;
            }

            $slots[] = $slot;
        }

        $normalizedSlotNumbers = array_values(array_unique($seenSlotNumbers));
        sort($normalizedSlotNumbers);

        if (count($slots) !== 2 || $normalizedSlotNumbers !== [1, 2]) {
            $errors['slots'] = 'Can khai bao day du 2 slot cho tran dau.';
        }

        return $slots;
    }

    private function directTeamIdsFromSlots(array $slots): array
    {
        $ids = [null, null];

        foreach ($slots as $slot) {
            $slotNo = (int) ($slot['slot_so'] ?? 0);

            if ($slotNo < 1 || $slotNo > 2 || ($slot['source_type'] ?? null) !== 'TEAM') {
                continue;
            }

            $ids[$slotNo - 1] = isset($slot['iddoibong']) ? (int) $slot['iddoibong'] : null;
        }

        return $ids;
    }

    private function teamSlots(array $payload): array
    {
        return [
            [
                'slot_so' => 1,
                'source_type' => 'TEAM',
                'iddoibong' => isset($payload['iddoibong1']) ? (int) $payload['iddoibong1'] : null,
            ],
            [
                'slot_so' => 2,
                'source_type' => 'TEAM',
                'iddoibong' => isset($payload['iddoibong2']) ? (int) $payload['iddoibong2'] : null,
            ],
        ];
    }

    private function slotLogLabel(array $slot): string
    {
        $sourceType = (string) ($slot['source_type'] ?? 'TEAM');

        return match ($sourceType) {
            'TEAM' => isset($slot['iddoibong']) && $slot['iddoibong'] !== null
                ? ('doi #' . (string) $slot['iddoibong'])
                : 'doi chua xac dinh',
            'WINNER', 'LOSER' => strtolower($sourceType) . ' tran #' . (string) ($slot['source_match_id'] ?? '?'),
            'SEED' => 'seed #' . (string) ($slot['source_seed_no'] ?? '?'),
            'BYE' => 'bye',
            default => 'khong xac dinh',
        };
    }

    private function refereeAssignments(array $payload, array &$errors): array
    {
        $source = $payload['referee_assignments'] ?? $payload['trongtai'] ?? [];

        if ($source === null || $source === '') {
            return [];
        }

        if (!is_array($source)) {
            $errors['referee_assignments'] = 'Danh sach trong tai khong hop le.';
            return [];
        }

        $assignments = [];
        $allowedRoles = ['TRONG_TAI_CHINH', 'TRONG_TAI_PHU', 'GIAM_SAT'];
        $seenReferees = [];

        foreach ($source as $index => $item) {
            if (!is_array($item)) {
                $errors['referee_assignments.' . $index] = 'Phan cong trong tai khong hop le.';
                continue;
            }

            $assignmentStatus = strtoupper(trim((string) ($item['trangthai'] ?? $item['status'] ?? $item['phancong_trangthai'] ?? 'DA_XAC_NHAN')));

            if (in_array($assignmentStatus, ['TU_CHOI', 'DA_HUY'], true)) {
                continue;
            }

            $refereeId = $this->requiredPositiveInt($item['idtrongtai'] ?? $item['referee_id'] ?? null, 'referee_assignments.' . $index . '.idtrongtai', 'Trong tai', $errors);
            $role = strtoupper(trim((string) ($item['vaitro'] ?? $item['role'] ?? '')));

            if (!in_array($role, $allowedRoles, true)) {
                $errors['referee_assignments.' . $index . '.vaitro'] = 'Vai tro trong tai khong hop le.';
            }

            if ($refereeId !== null && in_array($role, $allowedRoles, true)) {
                if (in_array($refereeId, $seenReferees, true)) {
                    $errors['referee_assignments.' . $index . '.idtrongtai'] = 'Moi trong tai chi duoc phan cong mot lan trong mot tran.';
                    continue;
                }

                $seenReferees[] = $refereeId;
                $assignments[] = [
                    'idtrongtai' => $refereeId,
                    'vaitro' => $role,
                ];
            }
        }

        return $assignments;
    }

    private function validateRefereeAssignmentLevels(int $tournamentId, array $assignments, array &$errors): void
    {
        if ($assignments === []) {
            return;
        }

        $refereeIds = array_values(array_unique(array_map(
            static fn (array $assignment): int => (int) ($assignment['idtrongtai'] ?? 0),
            $assignments
        )));
        $refereeIds = array_values(array_filter($refereeIds, static fn (int $refereeId): bool => $refereeId > 0));

        if ($refereeIds === []) {
            return;
        }

        $eligibleIds = $this->schedules->eligibleRefereeIdsForTournament($tournamentId, $refereeIds);
        $invalidIds = array_values(array_diff($refereeIds, $eligibleIds));

        if ($invalidIds !== []) {
            $errors['referee_assignments'] = 'Trong tai duoc phan cong phai cung cap voi giai dau cua tran.';
        }
    }

    private function teamIds(array $payload, array &$errors): array
    {
        $source = $payload['team_ids'] ?? $payload['teams'] ?? $payload['dois'] ?? $payload['iddoibong'] ?? [];

        if (is_string($source)) {
            $source = array_filter(array_map('trim', explode(',', $source)), static fn (string $item): bool => $item !== '');
        }

        if (!is_array($source)) {
            $errors['team_ids'] = 'Danh sach doi bong khong hop le.';
            return [];
        }

        $teamIds = [];

        foreach ($source as $index => $item) {
            $value = is_array($item) ? ($item['iddoibong'] ?? $item['team_id'] ?? $item['id'] ?? null) : $item;

            if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value) || (int) $value <= 0) {
                $errors['team_ids.' . $index] = 'Ma doi bong khong hop le.';
                continue;
            }

            $teamIds[] = (int) $value;
        }

        return array_values(array_unique($teamIds));
    }

    private function requiredString(array $payload, array $keys, int $maxLength, string $label, array &$errors): ?string
    {
        $key = $this->firstExistingKey($payload, $keys);
        $value = trim((string) ($key === null ? '' : $payload[$key]));
        $errorKey = $keys[0];

        if ($value === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        if (strlen($value) > $maxLength) {
            $errors[$errorKey] = $label . ' khong duoc vuot qua ' . $maxLength . ' ky tu.';
            return null;
        }

        return $value;
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

    private function requiredPositiveInt(mixed $value, string $errorKey, string $label, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '' || !ctype_digit((string) $value) || (int) $value <= 0) {
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

    private function dateTimeValue(mixed $value, string $errorKey, string $label, array &$errors): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            $errors[$errorKey] = $label . ' la bat buoc.';
            return null;
        }

        $normalized = str_replace('T', ' ', $text);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
            $normalized .= ':00';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $normalized);

        if (!$date || $date->format('Y-m-d H:i:s') !== $normalized) {
            $errors[$errorKey] = $label . ' phai theo dinh dang YYYY-MM-DD HH:MM[:SS].';
            return null;
        }

        return $normalized;
    }

    private function nullableDateTime(mixed $value, string $errorKey, string $label, array &$errors): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->dateTimeValue($value, $errorKey, $label, $errors);
    }

    private function groupStatus(mixed $value, string $errorKey, array &$errors): ?string
    {
        $status = strtoupper(trim((string) ($value ?? '')));

        if (!in_array($status, self::GROUP_STATUSES, true)) {
            $errors[$errorKey] = 'Trang thai bang dau khong hop le.';
            return null;
        }

        return $status;
    }

    private function matchStatus(mixed $value, string $errorKey, array &$errors): ?string
    {
        $status = strtoupper(trim((string) ($value ?? '')));

        if (!in_array($status, self::MATCH_STATUSES, true)) {
            $errors[$errorKey] = 'Trang thai tran dau khong hop le.';
            return null;
        }

        return $status;
    }

    private function firstExistingKey(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $key;
            }
        }

        return null;
    }

    private function hasAnyKey(array $payload, array $keys): bool
    {
        return $this->firstExistingKey($payload, $keys) !== null;
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

