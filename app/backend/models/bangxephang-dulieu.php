<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Bangxephang extends Model
{
    public function tournamentsForOrganizer(int $organizerId, array $filters = []): array
    {
        $where = [
            'gd.idbantochuc = :organizer_id',
            "gd.trangthai IN ('DA_CONG_BO', 'DANG_DIEN_RA', 'DA_KET_THUC')",
        ];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(gd.tengiaidau LIKE :keyword_tournament OR gd.ghichu_diadiem LIKE :keyword_place)';
            $keyword = '%' . $filters['q'] . '%';
            $bindings['keyword_tournament'] = $keyword;
            $bindings['keyword_place'] = $keyword;
        }

        $statement = $this->db()->prepare(
            "SELECT
                gd.idgiaidau,
                gd.tengiaidau,
                gd.thoigianbatdau,
                gd.thoigianketthuc,
                gd.ghichu_diadiem AS diadiem,
                gd.trangthai,
                gd.trangthaidangky,
                COALESCE(published.published_results, 0) AS published_results,
                COALESCE(unresolved.unresolved_results, 0) AS unresolved_results,
                latest.idbangxephang AS latest_ranking_id,
                latest.tenbangxephang AS latest_ranking_name,
                latest.trangthai AS latest_ranking_status,
                latest.ngaycongbo AS latest_published_at
             FROM Giaidau gd
             LEFT JOIN (
                SELECT td.idgiaidau, COUNT(*) AS published_results
                FROM Trandau td
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
                GROUP BY td.idgiaidau
             ) published ON published.idgiaidau = gd.idgiaidau
             LEFT JOIN (
                SELECT td.idgiaidau, COUNT(*) AS unresolved_results
                FROM Trandau td
                LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE td.trangthai = 'DA_KET_THUC'
                  AND (kq.idketqua IS NULL OR kq.trangthai <> 'DA_CONG_BO')
                GROUP BY td.idgiaidau
             ) unresolved ON unresolved.idgiaidau = gd.idgiaidau
             LEFT JOIN (
                SELECT bxh.*
                FROM Bangxephang bxh
                JOIN (
                    SELECT idgiaidau, MAX(idbangxephang) AS idbangxephang
                    FROM Bangxephang
                    GROUP BY idgiaidau
                ) max_bxh ON max_bxh.idbangxephang = bxh.idbangxephang
             ) latest ON latest.idgiaidau = gd.idgiaidau
             WHERE " . implode(' AND ', $where) . "
             ORDER BY gd.thoigianbatdau DESC, gd.idgiaidau DESC"
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function listForOrganizer(int $organizerId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForOrganizer($organizerId, $filters);

        $statement = $this->db()->prepare(
             $this->baseSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY
                bxh.idbangxephang,
                bxh.idgiaidau,
                gd.tengiaidau,
                gd.idbantochuc,
                bxh.tenbangxephang,
                bxh.trangthai,
                bxh.ngaytao,
                bxh.ngaycongbo
             ORDER BY bxh.ngaytao DESC, bxh.idbangxephang DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function findForOrganizer(int $organizerId, int $rankingId): ?array
    {
        return $this->first(
             $this->baseSelect() . '
             WHERE gd.idbantochuc = :organizer_id
               AND bxh.idbangxephang = :ranking_id
             GROUP BY
                bxh.idbangxephang,
                bxh.idgiaidau,
                gd.tengiaidau,
                gd.idbantochuc,
                bxh.tenbangxephang,
                bxh.trangthai,
                bxh.ngaytao,
                bxh.ngaycongbo
             LIMIT 1',
            [
                'organizer_id' => $organizerId,
                'ranking_id' => $rankingId,
            ]
        );
    }

    public function latestPublishedForTournament(int $tournamentId): ?array
    {
        return $this->first(
             $this->baseSelect() . "
             WHERE bxh.idgiaidau = :tournament_id
               AND bxh.trangthai = 'DA_CONG_BO'
               AND gd.trangthai IN ('DA_CONG_BO', 'DANG_DIEN_RA', 'DA_KET_THUC')
             GROUP BY
                bxh.idbangxephang,
                bxh.idgiaidau,
                gd.tengiaidau,
                gd.idbantochuc,
                bxh.tenbangxephang,
                bxh.trangthai,
                bxh.ngaytao,
                bxh.ngaycongbo
             ORDER BY bxh.ngaycongbo DESC, bxh.idbangxephang DESC
             LIMIT 1",
            ['tournament_id' => $tournamentId]
        );
    }

    public function detailsForRanking(int $rankingId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                ct.idchitietbxh,
                ct.idbangxephang,
                ct.iddoibong,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.trangthai AS doibong_trangthai,
                ct.hang,
                ct.sotran,
                ct.thang,
                ct.thua,
                ct.sosetthang,
                ct.sosetthua,
                (ct.sosetthang - ct.sosetthua) AS hieusoset,
                ct.diem
             FROM Chitietbangxephang ct
             JOIN Doibong db ON db.iddoibong = ct.iddoibong
             WHERE ct.idbangxephang = :ranking_id
             ORDER BY ct.hang, ct.idchitietbxh"
        );
        $statement->execute(['ranking_id' => $rankingId]);

        return $statement->fetchAll();
    }

    public function tournamentForOrganizer(int $organizerId, int $tournamentId): ?array
    {
        return $this->first(
            "SELECT
                gd.idgiaidau,
                gd.tengiaidau,
                gd.thoigianbatdau,
                gd.thoigianketthuc,
                gd.ghichu_diadiem AS diadiem,
                gd.trangthai,
                gd.trangthaidangky,
                gd.idbantochuc
             FROM Giaidau gd
             WHERE gd.idgiaidau = :tournament_id
               AND gd.idbantochuc = :organizer_id
             LIMIT 1",
            [
                'tournament_id' => $tournamentId,
                'organizer_id' => $organizerId,
            ]
        );
    }

    public function publishedResultCount(int $tournamentId): int
    {
        $row = $this->first(
            "SELECT COUNT(*) AS total
             FROM Trandau td
             JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
             WHERE td.idgiaidau = :tournament_id
               AND td.trangthai = 'DA_KET_THUC'
               AND kq.trangthai = 'DA_CONG_BO'",
            ['tournament_id' => $tournamentId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function unresolvedEndedResultCount(int $tournamentId): int
    {
        $row = $this->first(
            "SELECT COUNT(*) AS total
             FROM Trandau td
             LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
             WHERE td.idgiaidau = :tournament_id
               AND td.trangthai = 'DA_KET_THUC'
               AND (kq.idketqua IS NULL OR kq.trangthai <> 'DA_CONG_BO')",
            ['tournament_id' => $tournamentId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function rankingTeams(int $tournamentId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                db.iddoibong,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.trangthai
             FROM Doibong db
             JOIN (
                SELECT dk.iddoibong
                FROM Dangkygiaidau dk
                WHERE dk.idgiaidau = :tournament_id_registered
                  AND dk.trangthai = 'DA_DUYET'
                UNION
                SELECT td.iddoibong1 AS iddoibong
                FROM Trandau td
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE td.idgiaidau = :tournament_id_team_one
                  AND td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
                UNION
                SELECT td.iddoibong2 AS iddoibong
                FROM Trandau td
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE td.idgiaidau = :tournament_id_team_two
                  AND td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
             ) teams ON teams.iddoibong = db.iddoibong
             ORDER BY db.tendoibong, db.iddoibong"
        );
        $statement->execute([
            'tournament_id_registered' => $tournamentId,
            'tournament_id_team_one' => $tournamentId,
            'tournament_id_team_two' => $tournamentId,
        ]);

        return $statement->fetchAll();
    }

    public function computedStatsFromPublishedResults(int $tournamentId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                stats.iddoibong,
                SUM(stats.sotran) AS sotran,
                SUM(stats.thang) AS thang,
                SUM(stats.thua) AS thua,
                SUM(stats.sosetthang) AS sosetthang,
                SUM(stats.sosetthua) AS sosetthua,
                SUM(stats.diem) AS diem
             FROM (
                SELECT
                    td.iddoibong1 AS iddoibong,
                    1 AS sotran,
                    CASE WHEN kq.iddoithang = td.iddoibong1 THEN 1 ELSE 0 END AS thang,
                    CASE WHEN kq.iddoithang = td.iddoibong1 THEN 0 ELSE 1 END AS thua,
                    kq.sosetdoi1 AS sosetthang,
                    kq.sosetdoi2 AS sosetthua,
                    CASE
                        WHEN kq.iddoithang = td.iddoibong1 AND kq.sosetdoi1 = 3 AND kq.sosetdoi2 = 2 THEN 2
                        WHEN kq.iddoithang = td.iddoibong1 AND kq.sosetdoi1 = 3 AND kq.sosetdoi2 IN (0, 1) THEN 3
                        WHEN kq.iddoithang = td.iddoibong2 AND kq.sosetdoi2 = 3 AND kq.sosetdoi1 = 2 THEN 1
                        ELSE 0
                    END AS diem
                FROM Trandau td
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE td.idgiaidau = :tournament_id_team_one
                  AND td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
                UNION ALL
                SELECT
                    td.iddoibong2 AS iddoibong,
                    1 AS sotran,
                    CASE WHEN kq.iddoithang = td.iddoibong2 THEN 1 ELSE 0 END AS thang,
                    CASE WHEN kq.iddoithang = td.iddoibong2 THEN 0 ELSE 1 END AS thua,
                    kq.sosetdoi2 AS sosetthang,
                    kq.sosetdoi1 AS sosetthua,
                    CASE
                        WHEN kq.iddoithang = td.iddoibong2 AND kq.sosetdoi2 = 3 AND kq.sosetdoi1 = 2 THEN 2
                        WHEN kq.iddoithang = td.iddoibong2 AND kq.sosetdoi2 = 3 AND kq.sosetdoi1 IN (0, 1) THEN 3
                        WHEN kq.iddoithang = td.iddoibong1 AND kq.sosetdoi1 = 3 AND kq.sosetdoi2 = 2 THEN 1
                        ELSE 0
                    END AS diem
                FROM Trandau td
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE td.idgiaidau = :tournament_id_team_two
                  AND td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
             ) stats
             GROUP BY stats.iddoibong"
        );
        $statement->execute([
            'tournament_id_team_one' => $tournamentId,
            'tournament_id_team_two' => $tournamentId,
        ]);

        $rows = [];

        foreach ($statement->fetchAll() as $row) {
            $rows[(int) $row['iddoibong']] = [
                'iddoibong' => (int) $row['iddoibong'],
                'sotran' => (int) $row['sotran'],
                'thang' => (int) $row['thang'],
                'thua' => (int) $row['thua'],
                'sosetthang' => (int) $row['sosetthang'],
                'sosetthua' => (int) $row['sosetthua'],
                'diem' => (int) $row['diem'],
            ];
        }

        return $rows;
    }

    public function generateRanking(
        int $tournamentId,
        string $name,
        array $rows,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $existing = $this->rankingByName($tournamentId, $name);
            $newStatus = $existing !== null && (string) $existing['trangthai'] === 'DA_CONG_BO'
                ? 'DA_CAP_NHAT'
                : 'BAN_NHAP';

            if ($existing === null) {
                $statement = $db->prepare(
                    "INSERT INTO Bangxephang (idgiaidau, tenbangxephang, trangthai)
                     VALUES (:tournament_id, :name, 'BAN_NHAP')"
                );
                $statement->execute([
                    'tournament_id' => $tournamentId,
                    'name' => $name,
                ]);
                $rankingId = (int) $db->lastInsertId();
            } else {
                $rankingId = (int) $existing['idbangxephang'];
                $statement = $db->prepare(
                    "UPDATE Bangxephang
                     SET trangthai = :status,
                         ngaytao = CURRENT_TIMESTAMP,
                         ngaycongbo = NULL
                     WHERE idbangxephang = :ranking_id"
                );
                $statement->execute([
                    'status' => $newStatus,
                    'ranking_id' => $rankingId,
                ]);
            }

            $this->replaceRankingDetails($rankingId, $rows);
            $this->replaceTeamStats($tournamentId, $rows);
            $this->recordSystemLog($actorAccountId, 'Tao bang xep hang', 'Bangxephang', $rankingId, $ipAddress, $logNote);

            $db->commit();

            return $rankingId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function publishRanking(int $rankingId, int $actorAccountId, ?string $ipAddress, string $logNote): void
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Bangxephang
                 SET trangthai = 'DA_CONG_BO',
                     ngaycongbo = CURRENT_TIMESTAMP
                 WHERE idbangxephang = :ranking_id
                   AND trangthai IN ('BAN_NHAP', 'DA_CAP_NHAT')"
            );
            $statement->execute(['ranking_id' => $rankingId]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('RANKING_NOT_PUBLISHED');
            }

            $this->recordSystemLog($actorAccountId, 'Cong bo bang xep hang', 'Bangxephang', $rankingId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function whereForOrganizer(int $organizerId, array $filters): array
    {
        $where = ['gd.idbantochuc = :organizer_id'];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(bxh.tenbangxephang LIKE :keyword_ranking OR gd.tengiaidau LIKE :keyword_tournament)';
            $keyword = '%' . $filters['q'] . '%';
            $bindings['keyword_ranking'] = $keyword;
            $bindings['keyword_tournament'] = $keyword;
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'bxh.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'bxh.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        return [$where, $bindings];
    }

    private function baseSelect(): string
    {
        return "SELECT
                bxh.idbangxephang,
                bxh.idgiaidau,
                gd.tengiaidau,
                gd.idbantochuc,
                bxh.tenbangxephang,
                bxh.trangthai,
                bxh.ngaytao,
                bxh.ngaycongbo,
                COUNT(ct.idchitietbxh) AS total_teams
             FROM Bangxephang bxh
             JOIN Giaidau gd ON gd.idgiaidau = bxh.idgiaidau
             LEFT JOIN Chitietbangxephang ct ON ct.idbangxephang = bxh.idbangxephang";
    }

    private function rankingByName(int $tournamentId, string $name): ?array
    {
        return $this->first(
            "SELECT idbangxephang, idgiaidau, tenbangxephang, trangthai
             FROM Bangxephang
             WHERE idgiaidau = :tournament_id
               AND tenbangxephang = :name
             LIMIT 1",
            [
                'tournament_id' => $tournamentId,
                'name' => $name,
            ]
        );
    }

    private function replaceRankingDetails(int $rankingId, array $rows): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM Chitietbangxephang
             WHERE idbangxephang = :ranking_id"
        );
        $statement->execute(['ranking_id' => $rankingId]);

        $statement = $this->db()->prepare(
            "INSERT INTO Chitietbangxephang
                (idbangxephang, iddoibong, hang, sotran, thang, thua, sosetthang, sosetthua, diem)
             VALUES
                (:ranking_id, :team_id, :rank, :matches, :wins, :losses, :sets_won, :sets_lost, :points)"
        );

        foreach ($rows as $row) {
            $statement->execute([
                'ranking_id' => $rankingId,
                'team_id' => $row['iddoibong'],
                'rank' => $row['hang'],
                'matches' => $row['sotran'],
                'wins' => $row['thang'],
                'losses' => $row['thua'],
                'sets_won' => $row['sosetthang'],
                'sets_lost' => $row['sosetthua'],
                'points' => $row['diem'],
            ]);
        }
    }

    private function replaceTeamStats(int $tournamentId, array $rows): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM Thongkedoi
             WHERE idgiaidau = :tournament_id"
        );
        $statement->execute(['tournament_id' => $tournamentId]);

        $statement = $this->db()->prepare(
            "INSERT INTO Thongkedoi
                (idgiaidau, iddoibong, sotran, sotranthang, sotranthua, sosetthang, sosetthua, diem)
             VALUES
                (:tournament_id, :team_id, :matches, :wins, :losses, :sets_won, :sets_lost, :points)"
        );

        foreach ($rows as $row) {
            $statement->execute([
                'tournament_id' => $tournamentId,
                'team_id' => $row['iddoibong'],
                'matches' => $row['sotran'],
                'wins' => $row['thang'],
                'losses' => $row['thua'],
                'sets_won' => $row['sosetthang'],
                'sets_lost' => $row['sosetthua'],
                'points' => $row['diem'],
            ]);
        }
    }

    private function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note = null): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Nhatkyhethong (idtaikhoan, hanhdong, bangtacdong, iddoituong, ipaddress, ghichu)
             VALUES (:account_id, :action, :target_table, :target_id, :ip_address, :note)"
        );

        $statement->execute([
            'account_id' => $accountId,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'ip_address' => $ipAddress,
            'note' => $note,
        ]);
    }
}
