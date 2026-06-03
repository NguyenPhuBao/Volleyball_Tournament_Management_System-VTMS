<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Tucachthamgia extends Model
{
    private static bool $schemaReady = false;

    public function __construct()
    {
        $this->ensureSchema();
    }

    public function syncChampionAchievementsForOrganizer(int $organizerId): void
    {
        $this->syncAchievementsFromPublishedRankings($organizerId);
        $this->syncChampionAchievementsFromPublishedFinalResults($organizerId);
        $this->syncParticipationAchievementsForOrganizer($organizerId);
    }

    public function sourceTournamentsForOrganizer(int $organizerId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                gsrc.idgiaidau,
                gsrc.tengiaidau,
                gsrc.thoigianbatdau,
                gsrc.thoigianketthuc,
                COUNT(DISTINCT tt.idthanhtich) AS total_achievements
             FROM Thanhtichdoibong tt
             JOIN Giaidau gsrc ON gsrc.idgiaidau = tt.idgiaidau
             WHERE gsrc.idbantochuc = :organizer_id
               AND tt.trangthai = 'HOP_LE'
             GROUP BY gsrc.idgiaidau, gsrc.tengiaidau, gsrc.thoigianbatdau, gsrc.thoigianketthuc
             ORDER BY gsrc.thoigianbatdau DESC, gsrc.idgiaidau DESC"
        );
        $statement->execute(['organizer_id' => $organizerId]);

        return $statement->fetchAll();
    }

    public function candidatesForOrganizer(int $organizerId, array $filters = []): array
    {
        $where = [
            'gsrc.idbantochuc = :organizer_id',
            "tt.trangthai = 'HOP_LE'",
            "gsrc.tinhchat IN ('CHINH_THUC', 'PHONG_TRAO')",
        ];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(db.tendoibong LIKE :keyword OR gsrc.tengiaidau LIKE :keyword OR gdich.tengiaidau LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['source_tournament_id'] ?? '') !== '') {
            $where[] = 'tt.idgiaidau = :source_tournament_id';
            $bindings['source_tournament_id'] = (int) $filters['source_tournament_id'];
        }

        if (($filters['achievement'] ?? '') !== '') {
            $where[] = 'tt.danhhieu = :achievement';
            $bindings['achievement'] = (string) $filters['achievement'];
        }

        $statement = $this->db()->prepare(
            "SELECT
                tt.idthanhtich,
                tt.iddoibong,
                tt.idgiaidau AS idgiaidau_nguon,
                cgdecu.idcapgiaidau AS idcapgiaidau_nguon,
                tt.hang_dat_duoc,
                tt.danhhieu,
                tt.ngay_cong_nhan,
                db.tendoibong,
                db.diaphuong,
                kvdoi.tenkhuvuc AS tenkhuvuc_doi,
                kvdoi.capkhuvuc AS capkhuvuc_doi,
                gsrc.tengiaidau AS tengiaidau_nguon,
                gsrc.idbantochuc AS idbantochuc_decu,
                cgdecu.macapgiaidau AS macapgiaidau_nguon,
                cgdecu.tencapgiaidau AS tencapgiaidau_nguon,
                kvbtc.tenkhuvuc AS tenkhuvuc_nguon,
                gdich.idgiaidau AS idgiaidau_dich,
                gdich.tengiaidau AS tengiaidau_dich,
                gdich.idbantochuc AS idbantochuc_nhan,
                cgdich.idcapgiaidau AS idcapgiaidau_dich,
                cgdich.macapgiaidau AS macapgiaidau_dich,
                cgdich.tencapgiaidau AS tencapgiaidau_dich,
                COALESCE(kvdich.tenkhuvuc, kvcha.tenkhuvuc) AS tenkhuvuc_dich,
                btcnhan.donvi AS bantochuc_nhan,
                dc.iddecu,
                dc.trangthai AS trangthai_decu,
                dc.lydo_xet,
                dc.ghichu_decu,
                dc.lydo_xacnhan,
                dc.ngay_danhdau,
                dc.ngay_decu,
                dc.ngay_xacnhan
             FROM Thanhtichdoibong tt
             JOIN Doibong db ON db.iddoibong = tt.iddoibong
             JOIN Khuvuc kvdoi ON kvdoi.idkhuvuc = db.idkhuvucdaidien
             JOIN Giaidau gsrc ON gsrc.idgiaidau = tt.idgiaidau
             JOIN Bantochuc btcdecu ON btcdecu.idbantochuc = gsrc.idbantochuc
             JOIN Capbantochuc cbtcdecu ON cbtcdecu.idcapbantochuc = btcdecu.idcapbantochuc
             JOIN Khuvuc kvbtc ON kvbtc.idkhuvuc = btcdecu.idkhuvucquanly
             JOIN Capgiaidau cgdecu ON cgdecu.macapgiaidau = cbtcdecu.capkhuvucquanly
             JOIN Capgiaidau cgdich ON cgdich.idcapgiaidau = cgdecu.idcapgiaidau_cha
             JOIN Khuvuc kvcha ON kvcha.idkhuvuc = kvbtc.idkhuvuccha
             LEFT JOIN Giaidau gdich ON gdich.idcapgiaidau = cgdich.idcapgiaidau
                AND gdich.idkhuvucphamvi = kvcha.idkhuvuc
                AND gdich.trangthai <> 'DA_HUY'
             LEFT JOIN Khuvuc kvdich ON kvdich.idkhuvuc = gdich.idkhuvucphamvi
             LEFT JOIN Bantochuc btcnhan ON btcnhan.idbantochuc = gdich.idbantochuc
             LEFT JOIN decutucachthamgia dc ON dc.idthanhtich = tt.idthanhtich
                AND dc.idgiaidau_dich = gdich.idgiaidau
             WHERE " . implode(' AND ', $where) . "
             ORDER BY tt.ngay_cong_nhan DESC, tt.idthanhtich DESC, gdich.thoigianbatdau ASC",
            $bindings
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    private function syncAchievementsFromPublishedRankings(int $organizerId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Thanhtichdoibong
                (iddoibong, idgiaidau, idvongdau, idbangxephang, idchitietbxh, idcapgiaidau, idkhuvuc,
                 mua_giai, hang_dat_duoc, danhhieu, ngay_cong_nhan, nguon_ghi_nhan, ghi_chu, trangthai)
             SELECT
                ctbxh.iddoibong,
                gd.idgiaidau,
                bxh.idvongdau,
                bxh.idbangxephang,
                ctbxh.idchitietbxh,
                gd.idcapgiaidau,
                gd.idkhuvucphamvi,
                YEAR(gd.thoigianbatdau),
                ctbxh.hang,
                CASE ctbxh.hang
                    WHEN 1 THEN 'VO_DICH'
                    WHEN 2 THEN 'A_QUAN'
                    WHEN 3 THEN 'HANG_BA'
                END,
                COALESCE(DATE(bxh.ngaycongbo), CURRENT_DATE),
                'BANG_XEP_HANG',
                CONCAT('Tu bang xep hang #', bxh.idbangxephang),
                'HOP_LE'
             FROM Bangxephang bxh
             JOIN Giaidau gd ON gd.idgiaidau = bxh.idgiaidau
             JOIN Chitietbangxephang ctbxh ON ctbxh.idbangxephang = bxh.idbangxephang
             WHERE gd.idbantochuc = :organizer_id
               AND (
                    gd.trangthai = 'DA_KET_THUC'
                    OR (gd.thoigianketthuc IS NOT NULL AND gd.thoigianketthuc <= CURRENT_TIMESTAMP)
               )
               AND bxh.trangthai = 'DA_CONG_BO'
               AND ctbxh.hang IN (1, 2, 3)
             ON DUPLICATE KEY UPDATE
                idbangxephang = VALUES(idbangxephang),
                idchitietbxh = VALUES(idchitietbxh),
                hang_dat_duoc = VALUES(hang_dat_duoc),
                ngay_cong_nhan = VALUES(ngay_cong_nhan),
                nguon_ghi_nhan = VALUES(nguon_ghi_nhan),
                ghi_chu = VALUES(ghi_chu),
                trangthai = 'HOP_LE',
                ngaycapnhat = CURRENT_TIMESTAMP"
        );
        $statement->execute(['organizer_id' => $organizerId]);
    }

    private function syncChampionAchievementsFromPublishedFinalResults(int $organizerId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Thanhtichdoibong
                (iddoibong, idgiaidau, idvongdau, idcapgiaidau, idkhuvuc,
                 mua_giai, hang_dat_duoc, danhhieu, ngay_cong_nhan, nguon_ghi_nhan, ghi_chu, trangthai)
             SELECT
                result_rows.iddoibong,
                result_rows.idgiaidau,
                result_rows.idvongdau,
                result_rows.idcapgiaidau,
                result_rows.idkhuvucphamvi,
                result_rows.mua_giai,
                result_rows.hang_dat_duoc,
                result_rows.danhhieu,
                result_rows.ngay_cong_nhan,
                'HE_THONG_TONG_HOP',
                CONCAT('Tu ket qua tran #', result_rows.idtrandau),
                'HOP_LE'
             FROM (
                SELECT
                    kq.iddoithang AS iddoibong,
                    gd.idgiaidau,
                    td.idtrandau,
                    td.idvongdau,
                    gd.idcapgiaidau,
                    gd.idkhuvucphamvi,
                    YEAR(gd.thoigianbatdau) AS mua_giai,
                    1 AS hang_dat_duoc,
                    'VO_DICH' AS danhhieu,
                    COALESCE(DATE(kq.ngaycongbo), CURRENT_DATE) AS ngay_cong_nhan
                FROM Giaidau gd
                JOIN Trandau td ON td.idgiaidau = gd.idgiaidau
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE gd.idbantochuc = :organizer_id
                  AND (
                        gd.trangthai = 'DA_KET_THUC'
                        OR (gd.thoigianketthuc IS NOT NULL AND gd.thoigianketthuc <= CURRENT_TIMESTAMP)
                  )
                  AND td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
                  AND kq.iddoithang IS NOT NULL
                  AND NOT EXISTS (
                        SELECT 1
                        FROM Trandau newer
                        JOIN Ketquatrandau newer_kq ON newer_kq.idtrandau = newer.idtrandau
                        WHERE newer.idgiaidau = td.idgiaidau
                          AND newer.trangthai = 'DA_KET_THUC'
                          AND newer_kq.trangthai = 'DA_CONG_BO'
                          AND newer_kq.iddoithang IS NOT NULL
                          AND (
                                newer.thoigianbatdau > td.thoigianbatdau
                                OR (newer.thoigianbatdau = td.thoigianbatdau AND newer.idtrandau > td.idtrandau)
                          )
                  )
                UNION ALL
                SELECT
                    CASE
                        WHEN kq.iddoithang = td.iddoibong1 THEN td.iddoibong2
                        ELSE td.iddoibong1
                    END AS iddoibong,
                    gd.idgiaidau,
                    td.idtrandau,
                    td.idvongdau,
                    gd.idcapgiaidau,
                    gd.idkhuvucphamvi,
                    YEAR(gd.thoigianbatdau) AS mua_giai,
                    2 AS hang_dat_duoc,
                    'A_QUAN' AS danhhieu,
                    COALESCE(DATE(kq.ngaycongbo), CURRENT_DATE) AS ngay_cong_nhan
                FROM Giaidau gd
                JOIN Trandau td ON td.idgiaidau = gd.idgiaidau
                JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
                WHERE gd.idbantochuc = :organizer_id_runner
                  AND (
                        gd.trangthai = 'DA_KET_THUC'
                        OR (gd.thoigianketthuc IS NOT NULL AND gd.thoigianketthuc <= CURRENT_TIMESTAMP)
                  )
                  AND td.trangthai = 'DA_KET_THUC'
                  AND kq.trangthai = 'DA_CONG_BO'
                  AND kq.iddoithang IS NOT NULL
                  AND NOT EXISTS (
                        SELECT 1
                        FROM Trandau newer
                        JOIN Ketquatrandau newer_kq ON newer_kq.idtrandau = newer.idtrandau
                        WHERE newer.idgiaidau = td.idgiaidau
                          AND newer.trangthai = 'DA_KET_THUC'
                          AND newer_kq.trangthai = 'DA_CONG_BO'
                          AND newer_kq.iddoithang IS NOT NULL
                          AND (
                                newer.thoigianbatdau > td.thoigianbatdau
                                OR (newer.thoigianbatdau = td.thoigianbatdau AND newer.idtrandau > td.idtrandau)
                          )
                  )
             ) result_rows
             WHERE result_rows.iddoibong IS NOT NULL
             ON DUPLICATE KEY UPDATE
                idvongdau = VALUES(idvongdau),
                hang_dat_duoc = VALUES(hang_dat_duoc),
                ngay_cong_nhan = VALUES(ngay_cong_nhan),
                nguon_ghi_nhan = VALUES(nguon_ghi_nhan),
                ghi_chu = VALUES(ghi_chu),
                trangthai = 'HOP_LE',
                ngaycapnhat = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'organizer_id' => $organizerId,
            'organizer_id_runner' => $organizerId,
        ]);
    }

    private function syncParticipationAchievementsForOrganizer(int $organizerId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Thanhtichdoibong
                (iddoibong, idgiaidau, idcapgiaidau, idkhuvuc,
                 mua_giai, hang_dat_duoc, danhhieu, ngay_cong_nhan, nguon_ghi_nhan, ghi_chu, trangthai)
             SELECT
                participant_rows.iddoibong,
                gd.idgiaidau,
                gd.idcapgiaidau,
                gd.idkhuvucphamvi,
                YEAR(gd.thoigianbatdau),
                999,
                'THAM_DU',
                CURRENT_DATE,
                'HE_THONG_TONG_HOP',
                CONCAT('Tu danh sach doi tham gia giai #', gd.idgiaidau),
                'HOP_LE'
             FROM (
                SELECT dkgd.idgiaidau, dkgd.iddoibong
                FROM Dangkygiaidau dkgd
                WHERE dkgd.trangthai = 'DA_DUYET'
                UNION
                SELECT td.idgiaidau, td.iddoibong1 AS iddoibong
                FROM Trandau td
                WHERE td.iddoibong1 IS NOT NULL
                  AND td.trangthai <> 'DA_HUY'
                UNION
                SELECT td.idgiaidau, td.iddoibong2 AS iddoibong
                FROM Trandau td
                WHERE td.iddoibong2 IS NOT NULL
                  AND td.trangthai <> 'DA_HUY'
             ) participant_rows
             JOIN Giaidau gd ON gd.idgiaidau = participant_rows.idgiaidau
             JOIN Doibong db ON db.iddoibong = participant_rows.iddoibong
             WHERE gd.idbantochuc = :organizer_id
               AND gd.trangthai <> 'DA_HUY'
               AND gd.tinhchat IN ('CHINH_THUC', 'PHONG_TRAO')
               AND db.trangthai = 'HOAT_DONG'
               AND NOT EXISTS (
                    SELECT 1
                    FROM Thanhtichdoibong existing
                    WHERE existing.iddoibong = participant_rows.iddoibong
                      AND existing.idgiaidau = participant_rows.idgiaidau
                      AND existing.trangthai = 'HOP_LE'
                      AND existing.danhhieu IN ('VO_DICH', 'A_QUAN', 'HANG_BA', 'TOP_4', 'TOP_8')
               )
             ON DUPLICATE KEY UPDATE
                ngay_cong_nhan = VALUES(ngay_cong_nhan),
                nguon_ghi_nhan = VALUES(nguon_ghi_nhan),
                ghi_chu = VALUES(ghi_chu),
                trangthai = 'HOP_LE',
                ngaycapnhat = CURRENT_TIMESTAMP"
        );
        $statement->execute(['organizer_id' => $organizerId]);
    }

    public function incomingForOrganizer(int $organizerId, array $filters = []): array
    {
        $where = [
            'dc.idbantochuc_nhan = :organizer_id',
            "dc.trangthai IN ('DA_DE_CU', 'DA_XAC_NHAN', 'TU_CHOI')",
        ];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(db.tendoibong LIKE :keyword OR gsrc.tengiaidau LIKE :keyword OR gdich.tengiaidau LIKE :keyword OR btcdecu.donvi LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        $statement = $this->db()->prepare(
            "SELECT
                dc.*,
                db.tendoibong,
                db.diaphuong,
                kvdoi.tenkhuvuc AS tenkhuvuc_doi,
                kvdoi.capkhuvuc AS capkhuvuc_doi,
                gsrc.tengiaidau AS tengiaidau_nguon,
                gdich.tengiaidau AS tengiaidau_dich,
                cgsrc.macapgiaidau AS macapgiaidau_nguon,
                cgsrc.tencapgiaidau AS tencapgiaidau_nguon,
                cgdich.macapgiaidau AS macapgiaidau_dich,
                cgdich.tencapgiaidau AS tencapgiaidau_dich,
                btcdecu.donvi AS bantochuc_decu,
                tt.danhhieu,
                tt.hang_dat_duoc,
                tt.ngay_cong_nhan
             FROM decutucachthamgia dc
             JOIN Doibong db ON db.iddoibong = dc.iddoibong
             JOIN Khuvuc kvdoi ON kvdoi.idkhuvuc = db.idkhuvucdaidien
             JOIN Giaidau gsrc ON gsrc.idgiaidau = dc.idgiaidau_nguon
             JOIN Giaidau gdich ON gdich.idgiaidau = dc.idgiaidau_dich
             JOIN Capgiaidau cgsrc ON cgsrc.idcapgiaidau = dc.idcapgiaidau_nguon
             JOIN Capgiaidau cgdich ON cgdich.idcapgiaidau = dc.idcapgiaidau_dich
             JOIN Bantochuc btcdecu ON btcdecu.idbantochuc = dc.idbantochuc_decu
             JOIN Thanhtichdoibong tt ON tt.idthanhtich = dc.idthanhtich
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FIELD(dc.trangthai, 'DA_DE_CU', 'DU_DIEU_KIEN', 'DA_XAC_NHAN', 'TU_CHOI'), dc.ngay_decu DESC, dc.iddecu DESC",
            $bindings
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function candidate(int $achievementId, int $targetTournamentId, int $organizerId): ?array
    {
        $rows = $this->candidatesForOrganizer($organizerId);

        foreach ($rows as $row) {
            if ((int) $row['idthanhtich'] === $achievementId && (int) $row['idgiaidau_dich'] === $targetTournamentId) {
                return $row;
            }
        }

        return null;
    }

    public function markEligible(array $candidate, int $accountId, ?string $note): int
    {
        $statement = $this->db()->prepare(
            "INSERT INTO decutucachthamgia
                (iddoibong, idthanhtich, idgiaidau_nguon, idgiaidau_dich, idcapgiaidau_nguon, idcapgiaidau_dich,
                 idbantochuc_decu, idbantochuc_nhan, trangthai, lydo_xet, idnguoi_danhdau, ngay_danhdau)
             VALUES
                (:team_id, :achievement_id, :source_tournament_id, :target_tournament_id, :source_level_id, :target_level_id,
                 :source_organizer_id, :target_organizer_id, 'DU_DIEU_KIEN', :note, :actor_id, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                trangthai = IF(trangthai IN ('DA_XAC_NHAN', 'DA_DE_CU'), trangthai, 'DU_DIEU_KIEN'),
                lydo_xet = VALUES(lydo_xet),
                idnguoi_danhdau = VALUES(idnguoi_danhdau),
                ngay_danhdau = VALUES(ngay_danhdau),
                ngaycapnhat = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'team_id' => (int) $candidate['iddoibong'],
            'achievement_id' => (int) $candidate['idthanhtich'],
            'source_tournament_id' => (int) $candidate['idgiaidau_nguon'],
            'target_tournament_id' => (int) $candidate['idgiaidau_dich'],
            'source_level_id' => (int) $candidate['idcapgiaidau_nguon'],
            'target_level_id' => (int) $candidate['idcapgiaidau_dich'],
            'source_organizer_id' => (int) $candidate['idbantochuc_decu'] ?: $this->sourceOrganizerId((int) $candidate['idgiaidau_nguon']),
            'target_organizer_id' => (int) $candidate['idbantochuc_nhan'],
            'note' => $note,
            'actor_id' => $accountId,
        ]);

        return (int) ($candidate['iddecu'] ?? 0) ?: $this->proposalId((int) $candidate['idthanhtich'], (int) $candidate['idgiaidau_dich']);
    }

    public function nominate(int $proposalId, int $organizerId, int $accountId, ?string $note): bool
    {
        $statement = $this->db()->prepare(
            "UPDATE decutucachthamgia dc
             SET dc.trangthai = 'DA_DE_CU',
                 dc.ghichu_decu = :note,
                 dc.idnguoi_decu = :actor_id,
                 dc.ngay_decu = CURRENT_TIMESTAMP,
                 dc.ngaycapnhat = CURRENT_TIMESTAMP
             WHERE dc.iddecu = :proposal_id
               AND dc.idbantochuc_decu = :organizer_id
               AND dc.trangthai = 'DU_DIEU_KIEN'
               AND EXISTS (
                    SELECT 1
                    FROM Capgiaidau cgnguon
                    WHERE cgnguon.idcapgiaidau = dc.idcapgiaidau_nguon
                      AND cgnguon.idcapgiaidau_cha = dc.idcapgiaidau_dich
               )"
        );
        $statement->execute([
            'proposal_id' => $proposalId,
            'organizer_id' => $organizerId,
            'actor_id' => $accountId,
            'note' => $note,
        ]);

        return $statement->rowCount() === 1;
    }

    public function decide(int $proposalId, int $organizerId, int $accountId, bool $approved, ?string $note): bool
    {
        $newStatus = $approved ? 'DA_XAC_NHAN' : 'TU_CHOI';
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE decutucachthamgia dc
                 SET dc.trangthai = :new_status,
                     dc.lydo_xacnhan = :note,
                     dc.idnguoi_xacnhan = :actor_id,
                     dc.ngay_xacnhan = CURRENT_TIMESTAMP,
                     dc.ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE dc.iddecu = :proposal_id
                   AND dc.idbantochuc_nhan = :organizer_id
                   AND dc.trangthai = 'DA_DE_CU'
                   AND EXISTS (
                        SELECT 1
                        FROM Capgiaidau cgnguon
                        WHERE cgnguon.idcapgiaidau = dc.idcapgiaidau_nguon
                          AND cgnguon.idcapgiaidau_cha = dc.idcapgiaidau_dich
                   )"
            );
            $statement->execute([
                'new_status' => $newStatus,
                'note' => $note,
                'actor_id' => $accountId,
                'proposal_id' => $proposalId,
                'organizer_id' => $organizerId,
            ]);

            if ($statement->rowCount() !== 1) {
                $db->rollBack();
                return false;
            }

            if ($approved) {
                $proposal = $this->proposal($proposalId);
                if ($proposal !== null) {
                    $this->grantHigherTournamentLevel($proposal);

                    if ($this->canCreateExplicitEligibility($proposal)) {
                        $this->grantExplicitEligibility($proposal, $accountId, $note);
                    }
                }
            }

            $db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function proposal(int $proposalId): ?array
    {
        return $this->first(
            "SELECT dc.*, gdich.idgiaidau, cgdich.capdoituongthamgia,
                cgnguon.macapgiaidau AS macapgiaidau_nguon,
                kvdoi.capkhuvuc AS capkhuvuc_doi
             FROM decutucachthamgia dc
             JOIN Giaidau gdich ON gdich.idgiaidau = dc.idgiaidau_dich
             JOIN Capgiaidau cgdich ON cgdich.idcapgiaidau = gdich.idcapgiaidau
             JOIN Capgiaidau cgnguon ON cgnguon.idcapgiaidau = dc.idcapgiaidau_nguon
             JOIN Doibong db ON db.iddoibong = dc.iddoibong
             JOIN Khuvuc kvdoi ON kvdoi.idkhuvuc = db.idkhuvucdaidien
             WHERE dc.iddecu = :proposal_id
             LIMIT 1",
            ['proposal_id' => $proposalId]
        );
    }

    public function acceptedTeamIdsForTournament(int $tournamentId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $statement = $this->db()->prepare(
            "SELECT DISTINCT iddoibong
             FROM decutucachthamgia
             WHERE idgiaidau_dich = :tournament_id
               AND trangthai = 'DA_XAC_NHAN'"
        );
        $statement->execute(['tournament_id' => $tournamentId]);

        return array_map('intval', array_column($statement->fetchAll(), 'iddoibong'));
    }

    private function canCreateExplicitEligibility(array $proposal): bool
    {
        return (string) ($proposal['macapgiaidau_nguon'] ?? '') === (string) ($proposal['capdoituongthamgia'] ?? '');
    }

    private function grantHigherTournamentLevel(array $proposal): void
    {
        (new Doibong())->resetExpiredApprovedTournamentLevels((int) $proposal['iddoibong']);

        $statement = $this->db()->prepare(
            "UPDATE Doibong db
             JOIN Capgiaidau target_level ON target_level.idcapgiaidau = :target_level_id
             JOIN Capgiaidau source_level ON source_level.idcapgiaidau = :source_level_id
             LEFT JOIN Capgiaidau current_level ON current_level.idcapgiaidau = db.idcapgiaidau_duoc_tham_gia
             SET db.ngayhethan_capgiaidau_duoc_tham_gia = CASE
                    WHEN COALESCE(target_level.thutu_cap, target_level.idcapgiaidau)
                        < COALESCE(source_level.thutu_cap, source_level.idcapgiaidau)
                      AND (
                        db.idcapgiaidau_duoc_tham_gia = target_level.idcapgiaidau
                        OR db.idcapgiaidau_duoc_tham_gia IS NULL
                        OR COALESCE(target_level.thutu_cap, target_level.idcapgiaidau)
                            < COALESCE(current_level.thutu_cap, current_level.idcapgiaidau)
                      )
                        THEN DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)
                    ELSE db.ngayhethan_capgiaidau_duoc_tham_gia
                 END,
                 db.idcapgiaidau_duoc_tham_gia = CASE
                    WHEN COALESCE(target_level.thutu_cap, target_level.idcapgiaidau)
                        < COALESCE(source_level.thutu_cap, source_level.idcapgiaidau)
                      AND (
                        db.idcapgiaidau_duoc_tham_gia = target_level.idcapgiaidau
                        OR db.idcapgiaidau_duoc_tham_gia IS NULL
                        OR COALESCE(target_level.thutu_cap, target_level.idcapgiaidau)
                            < COALESCE(current_level.thutu_cap, current_level.idcapgiaidau)
                      )
                        THEN target_level.idcapgiaidau
                    ELSE db.idcapgiaidau_duoc_tham_gia
                 END,
                 db.ngaycapnhat = CURRENT_TIMESTAMP
             WHERE db.iddoibong = :team_id"
        );
        $statement->execute([
            'target_level_id' => (int) $proposal['idcapgiaidau_dich'],
            'source_level_id' => (int) $proposal['idcapgiaidau_nguon'],
            'team_id' => (int) $proposal['iddoibong'],
        ]);
    }

    private function grantExplicitEligibility(array $proposal, int $accountId, ?string $note): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Doidudieukienthamgia
                (idgiaidau, iddoibong, idthanhtich, nguon_dieukien, lydo_dieukien, trangthai, idnguoixacnhan, ghichu)
             VALUES
                (:tournament_id, :team_id, :achievement_id, 'BTC_CHON', :reason, 'DU_DIEU_KIEN', :actor_id, :note)
             ON DUPLICATE KEY UPDATE
                idthanhtich = VALUES(idthanhtich),
                nguon_dieukien = VALUES(nguon_dieukien),
                lydo_dieukien = VALUES(lydo_dieukien),
                trangthai = IF(trangthai IN ('DA_DANG_KY', 'DA_DUYET'), trangthai, 'DU_DIEU_KIEN'),
                idnguoixacnhan = VALUES(idnguoixacnhan),
                ghichu = VALUES(ghichu),
                ngay_xac_nhan = CURRENT_TIMESTAMP"
        );
        $statement->execute([
            'tournament_id' => (int) $proposal['idgiaidau_dich'],
            'team_id' => (int) $proposal['iddoibong'],
            'achievement_id' => (int) $proposal['idthanhtich'],
            'reason' => $note ?: 'BTC cap cao hon xac nhan de cu tu cach tham gia.',
            'actor_id' => $accountId,
            'note' => 'Tu de cu tu cach #' . (int) $proposal['iddecu'],
        ]);
    }

    private function proposalId(int $achievementId, int $targetTournamentId): int
    {
        $row = $this->first(
            "SELECT iddecu
             FROM decutucachthamgia
             WHERE idthanhtich = :achievement_id
               AND idgiaidau_dich = :target_tournament_id
             LIMIT 1",
            [
                'achievement_id' => $achievementId,
                'target_tournament_id' => $targetTournamentId,
            ]
        );

        return (int) ($row['iddecu'] ?? 0);
    }

    private function sourceOrganizerId(int $sourceTournamentId): int
    {
        $row = $this->first(
            "SELECT idbantochuc FROM Giaidau WHERE idgiaidau = :tournament_id LIMIT 1",
            ['tournament_id' => $sourceTournamentId]
        );

        return (int) ($row['idbantochuc'] ?? 0);
    }

    private function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        $this->db()->exec(
            "CREATE TABLE IF NOT EXISTS decutucachthamgia (
                iddecu INT AUTO_INCREMENT PRIMARY KEY,
                iddoibong INT NOT NULL,
                idthanhtich INT NOT NULL,
                idgiaidau_nguon INT NOT NULL,
                idgiaidau_dich INT NOT NULL,
                idcapgiaidau_nguon INT NOT NULL,
                idcapgiaidau_dich INT NOT NULL,
                idbantochuc_decu INT NOT NULL,
                idbantochuc_nhan INT NOT NULL,
                trangthai VARCHAR(50) NOT NULL DEFAULT 'DU_DIEU_KIEN',
                lydo_xet VARCHAR(1000) NULL,
                ghichu_decu VARCHAR(1000) NULL,
                lydo_xacnhan VARCHAR(1000) NULL,
                idnguoi_danhdau INT NULL,
                idnguoi_decu INT NULL,
                idnguoi_xacnhan INT NULL,
                ngay_danhdau DATETIME NULL,
                ngay_decu DATETIME NULL,
                ngay_xacnhan DATETIME NULL,
                ngaytao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngaycapnhat DATETIME NULL,
                UNIQUE KEY uq_decu_thanhtich_giai (idthanhtich, idgiaidau_dich),
                KEY idx_decu_doi (iddoibong),
                KEY idx_decu_nguon (idgiaidau_nguon),
                KEY idx_decu_dich (idgiaidau_dich),
                KEY idx_decu_btc_decu (idbantochuc_decu),
                KEY idx_decu_btc_nhan (idbantochuc_nhan),
                KEY idx_decu_trangthai (trangthai)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        self::$schemaReady = true;
    }

    private function tableExists(): bool
    {
        $statement = $this->db()->query("SHOW TABLES LIKE 'decutucachthamgia'");

        return $statement !== false && $statement->fetch() !== false;
    }
}
