<?php

declare(strict_types=1);

namespace App\Backend\Services\Organizer;

use App\Backend\Core\Http\Request;
use App\Backend\Models\Bangxephang;
use App\Backend\Models\Giaidau;
use App\Backend\Services\Shared\VolleyballCompetitionRules;
use RuntimeException;
use Throwable;

final class OrganizerRankingService
{
    private const STATUSES = ['BAN_NHAP', 'DA_CAP_NHAT', 'DA_CONG_BO'];

    public function __construct(
        private ?Bangxephang $rankings = null,
        private ?Giaidau $tournaments = null
    ) {
        $this->rankings ??= new Bangxephang();
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
            'message' => 'Lay danh sach giai dau xep hang thanh cong.',
            'tournaments' => $this->rankings->tournamentsForOrganizer((int) $organizer['idbantochuc'], [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
            ]),
        ];
    }

    public function all(int $accountId, array $filters = []): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $normalized = $this->filters($filters);

        if ($normalized['errors'] !== []) {
            return $this->failure('Bo loc bang xep hang khong hop le.', 422, $normalized['errors']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay danh sach bang xep hang thanh cong.',
            'rankings' => $this->rankings->listForOrganizer((int) $organizer['idbantochuc'], $normalized['filters']),
            'meta' => [
                'filters' => $normalized['filters'],
                'statuses' => self::STATUSES,
            ],
        ];
    }

    public function find(int $rankingId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $ranking = $this->rankingPayload((int) $organizer['idbantochuc'], $rankingId);

        if ($ranking === null) {
            return $this->failure('Khong tim thay bang xep hang.', 404);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Lay thong tin bang xep hang thanh cong.',
            'ranking' => $ranking,
        ];
    }

    public function generate(int $tournamentId, array $payload, int $accountId, ?Request $request = null): array
    {
        $context = $this->rankingTournament($tournamentId, $accountId);

        if (isset($context['ok']) && $context['ok'] === false) {
            return $context;
        }

        $publishedCount = $this->rankings->publishedResultCount($tournamentId);
        $unresolvedCount = $this->rankings->unresolvedEndedResultCount($tournamentId);

        if ($publishedCount === 0) {
            return $this->failure('Can co it nhat mot ket qua tran dau da cong bo de tao bang xep hang.', 409);
        }

        if ($unresolvedCount > 0) {
            return $this->failure('Can cong bo tat ca ket qua cua cac tran da ket thuc truoc khi tao bang xep hang.', 409, [
                'unresolved_results' => (string) $unresolvedCount,
            ]);
        }

        [$name, $errors] = $this->rankingName($payload, (string) $context['tournament']['tengiaidau']);

        if ($errors !== []) {
            return $this->failure('Du lieu tao bang xep hang khong hop le.', 422, $errors);
        }

        $rows = $this->computeRows($tournamentId);

        if ($rows === []) {
            return $this->failure('Khong co doi bong nao de tao bang xep hang.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d tao bang xep hang "%s" cho giai "%s" tu %d ket qua da cong bo. So doi: %d.',
            (int) $context['organizer']['idbantochuc'],
            $name,
            (string) $context['tournament']['tengiaidau'],
            $publishedCount,
            count($rows)
        ));

        try {
            $rankingId = $this->rankings->generateRanking($tournamentId, $name, $rows, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 201,
                'message' => 'Tao bang xep hang thanh cong.',
                'ranking' => $this->rankingPayload((int) $context['organizer']['idbantochuc'], $rankingId),
            ];
        } catch (Throwable) {
            return $this->failure('Khong the tao bang xep hang.', 500, [
                'database' => 'Loi ghi co so du lieu.',
            ]);
        }
    }

    public function publish(int $rankingId, int $accountId, ?Request $request = null): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $ranking = $this->rankingPayload((int) $organizer['idbantochuc'], $rankingId);

        if ($ranking === null) {
            return $this->failure('Khong tim thay bang xep hang.', 404);
        }

        if ((int) ($ranking['total_teams'] ?? 0) <= 0 || empty($ranking['details'])) {
            return $this->failure('Bang xep hang chua co chi tiet doi bong.', 409);
        }

        if ((string) $ranking['trangthai'] === 'DA_CONG_BO') {
            return $this->failure('Bang xep hang da duoc cong bo.', 409);
        }

        if (!in_array((string) $ranking['trangthai'], ['BAN_NHAP', 'DA_CAP_NHAT'], true)) {
            return $this->failure('Trang thai bang xep hang khong hop le de cong bo.', 409);
        }

        $logNote = $this->limitLogNote(sprintf(
            'Ban to chuc #%d cong bo bang xep hang "%s" cua giai "%s". So doi: %d.',
            (int) $organizer['idbantochuc'],
            (string) $ranking['tenbangxephang'],
            (string) $ranking['tengiaidau'],
            (int) $ranking['total_teams']
        ));

        try {
            $this->rankings->publishRanking($rankingId, $accountId, $request?->ip(), $logNote);

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Cong bo bang xep hang thanh cong.',
                'ranking' => $this->rankingPayload((int) $organizer['idbantochuc'], $rankingId),
            ];
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'RANKING_NOT_PUBLISHED') {
                return $this->failure('Khong the cong bo bang xep hang hien tai.', 409);
            }

            return $this->failure('Khong the cong bo bang xep hang.', 500);
        } catch (Throwable) {
            return $this->failure('Khong the cong bo bang xep hang.', 500, [
                'database' => 'Loi cap nhat co so du lieu.',
            ]);
        }
    }

    private function rankingPayload(int $organizerId, int $rankingId): ?array
    {
        $ranking = $this->rankings->findForOrganizer($organizerId, $rankingId);

        if ($ranking === null) {
            return null;
        }

        $ranking['details'] = $this->rankings->detailsForRanking($rankingId);
        $ranking['rules'] = VolleyballCompetitionRules::meta();
        $ranking['knockout_plan'] = VolleyballCompetitionRules::knockoutPlan($ranking['details']);

        return $ranking;
    }

    private function computeRows(int $tournamentId): array
    {
        $teams = $this->rankings->rankingTeams($tournamentId);
        $stats = $this->rankings->computedStatsFromPublishedResults($tournamentId);
        $rows = [];

        foreach ($teams as $team) {
            $teamId = (int) $team['iddoibong'];
            $stat = $stats[$teamId] ?? [
                'iddoibong' => $teamId,
                'sotran' => 0,
                'thang' => 0,
                'thua' => 0,
                'sosetthang' => 0,
                'sosetthua' => 0,
                'diem' => 0,
            ];

            $rows[] = [
                'iddoibong' => $teamId,
                'tendoibong' => (string) $team['tendoibong'],
                'sotran' => (int) $stat['sotran'],
                'thang' => (int) $stat['thang'],
                'thua' => (int) $stat['thua'],
                'sosetthang' => (int) $stat['sosetthang'],
                'sosetthua' => (int) $stat['sosetthua'],
                'diem' => (int) $stat['diem'],
            ];
        }

        usort($rows, static function (array $left, array $right): int {
            $leftSetDiff = $left['sosetthang'] - $left['sosetthua'];
            $rightSetDiff = $right['sosetthang'] - $right['sosetthua'];

            return ($right['diem'] <=> $left['diem'])
                ?: ($right['thang'] <=> $left['thang'])
                ?: ($rightSetDiff <=> $leftSetDiff)
                ?: ($right['sosetthang'] <=> $left['sosetthang'])
                ?: ($left['thua'] <=> $right['thua'])
                ?: strcmp($left['tendoibong'], $right['tendoibong'])
                ?: ($left['iddoibong'] <=> $right['iddoibong']);
        });

        foreach ($rows as $index => &$row) {
            $row['hang'] = $index + 1;
            unset($row['tendoibong']);
        }

        return $rows;
    }

    private function rankingTournament(int $tournamentId, int $accountId): array
    {
        $organizer = $this->activeOrganizer($accountId);

        if (isset($organizer['ok']) && $organizer['ok'] === false) {
            return $organizer;
        }

        $tournament = $this->rankings->tournamentForOrganizer((int) $organizer['idbantochuc'], $tournamentId);

        if ($tournament === null) {
            return $this->failure('Khong tim thay giai dau.', 404);
        }

        if (!in_array((string) $tournament['trangthai'], ['DA_CONG_BO', 'DANG_DIEN_RA', 'DA_KET_THUC'], true)) {
            return $this->failure('Chi duoc tao bang xep hang cho giai dau da cong bo.', 409);
        }

        return [
            'organizer' => $organizer,
            'tournament' => $tournament,
        ];
    }

    private function filters(array $filters): array
    {
        $errors = [];
        $status = strtoupper(trim((string) ($filters['status'] ?? $filters['trangthai'] ?? '')));
        $tournamentId = $this->optionalPositiveInt($filters['tournament_id'] ?? $filters['idgiaidau'] ?? null, 'tournament_id', $errors);

        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Trang thai bang xep hang khong hop le.';
        }

        return [
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? $filters['keyword'] ?? '')),
                'status' => $status,
                'tournament_id' => $tournamentId,
            ],
            'errors' => $errors,
        ];
    }

    private function rankingName(array $payload, string $tournamentName): array
    {
        $errors = [];
        $name = trim((string) ($payload['tenbangxephang'] ?? $payload['name'] ?? $payload['ten'] ?? ''));

        if ($name === '') {
            $name = 'Bang xep hang ' . $tournamentName;
        }

        if (strlen($name) > 300) {
            $errors['tenbangxephang'] = 'Ten bang xep hang khong duoc vuot qua 300 ky tu.';
        }

        return [$name, $errors];
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

