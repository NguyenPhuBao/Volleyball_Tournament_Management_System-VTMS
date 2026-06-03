<?php

declare(strict_types=1);

namespace App\Backend\Services\Shared;

final class VolleyballCompetitionRules
{
    public const REQUIRED_TEAM_COUNT = 10;
    public const REQUIRED_PLAYERS_PER_TEAM = 6;
    public const PRELIMINARY_MATCH_COUNT = 45;
    public const MIN_SETS = 3;
    public const MAX_SETS = 5;
    public const WINNING_SETS = 3;
    public const PRELIMINARY_ROUND = 'Vòng sơ bộ';
    public const PRELIMINARY_GROUP = 'Vòng sơ bộ';

    public static function meta(): array
    {
        return [
            'required_team_count' => self::REQUIRED_TEAM_COUNT,
            'required_players_per_team' => self::REQUIRED_PLAYERS_PER_TEAM,
            'preliminary_match_count' => self::PRELIMINARY_MATCH_COUNT,
            'format' => 'round_robin_then_knockout',
            'set_rule' => 'BO5',
            'preliminary_round' => self::PRELIMINARY_ROUND,
            'scoring' => [
                '3-0' => ['winner' => 3, 'loser' => 0],
                '3-1' => ['winner' => 3, 'loser' => 0],
                '3-2' => ['winner' => 2, 'loser' => 1],
            ],
            'knockout' => [
                'eliminated_ranks' => [9, 10],
                'quarterfinals' => [
                    ['label' => 'Tứ kết 1', 'ranks' => [1, 8]],
                    ['label' => 'Tứ kết 2', 'ranks' => [2, 7]],
                    ['label' => 'Tứ kết 3', 'ranks' => [3, 6]],
                    ['label' => 'Tứ kết 4', 'ranks' => [4, 5]],
                ],
                'semifinals' => [
                    ['label' => 'Bán kết 1', 'from' => ['Tứ kết 1', 'Tứ kết 4']],
                    ['label' => 'Bán kết 2', 'from' => ['Tứ kết 2', 'Tứ kết 3']],
                ],
                'final' => ['label' => 'Chung kết', 'from' => ['Bán kết 1', 'Bán kết 2']],
                'third_place' => ['label' => 'Tranh hạng 3', 'from_losers' => ['Bán kết 1', 'Bán kết 2']],
            ],
        ];
    }

    public static function preliminaryRoundAliases(): array
    {
        return [
            self::PRELIMINARY_ROUND,
            'VONG_SO_BO',
            'Vong so bo',
            'Vòng bảng',
            'VONG_BANG',
            'Vong bang',
        ];
    }

    public static function roundRobinPairs(array $teamIds): array
    {
        $ids = array_values(array_map('intval', $teamIds));
        $pairs = [];

        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $pairs[] = [$ids[$i], $ids[$j]];
            }
        }

        return $pairs;
    }

    public static function validateBo5Score(int $teamOneSets, int $teamTwoSets): ?string
    {
        $totalSets = $teamOneSets + $teamTwoSets;
        $winnerSets = max($teamOneSets, $teamTwoSets);
        $loserSets = min($teamOneSets, $teamTwoSets);

        if ($totalSets < self::MIN_SETS || $totalSets > self::MAX_SETS) {
            return 'Một trận Bo5 phải kết thúc trong 3 đến 5 set.';
        }

        if ($winnerSets !== self::WINNING_SETS || $loserSets > 2) {
            return 'Kết quả Bo5 hợp lệ chỉ có thể là 3-0, 3-1 hoặc 3-2.';
        }

        if ($teamOneSets === $teamTwoSets) {
            return 'Số set thắng của hai đội không được bằng nhau.';
        }

        return null;
    }

    public static function pointsForTeam(int $teamSets, int $opponentSets): int
    {
        if ($teamSets === 3 && $opponentSets === 2) {
            return 2;
        }

        if ($teamSets === 3 && in_array($opponentSets, [0, 1], true)) {
            return 3;
        }

        if ($teamSets === 2 && $opponentSets === 3) {
            return 1;
        }

        return 0;
    }

    public static function knockoutPlan(array $rankingRows): array
    {
        $byRank = [];

        foreach ($rankingRows as $row) {
            $byRank[(int) ($row['hang'] ?? 0)] = [
                'rank' => (int) ($row['hang'] ?? 0),
                'team_id' => (int) ($row['iddoibong'] ?? 0),
                'team_name' => (string) ($row['tendoibong'] ?? ''),
            ];
        }

        $pair = static function (string $label, int $left, int $right) use ($byRank): array {
            return [
                'label' => $label,
                'ranks' => [$left, $right],
                'teams' => [$byRank[$left] ?? null, $byRank[$right] ?? null],
            ];
        };

        return [
            'eliminated' => array_values(array_filter([$byRank[9] ?? null, $byRank[10] ?? null])),
            'quarterfinals' => [
                $pair('Tứ kết 1', 1, 8),
                $pair('Tứ kết 2', 2, 7),
                $pair('Tứ kết 3', 3, 6),
                $pair('Tứ kết 4', 4, 5),
            ],
            'semifinals' => [
                ['label' => 'Bán kết 1', 'from' => ['Tứ kết 1', 'Tứ kết 4']],
                ['label' => 'Bán kết 2', 'from' => ['Tứ kết 2', 'Tứ kết 3']],
            ],
            'final' => ['label' => 'Chung kết', 'from' => ['Bán kết 1', 'Bán kết 2']],
            'third_place' => ['label' => 'Tranh hạng 3', 'from_losers' => ['Bán kết 1', 'Bán kết 2']],
        ];
    }
}
