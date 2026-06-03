<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Lichthidau extends Model
{
    public function scheduleTournaments(int $organizerId, array $filters = []): array
    {
        $where = [
            'gd.idbantochuc = :organizer_id',
            "gd.trangthai <> 'DA_HUY'",
        ];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(gd.tengiaidau LIKE :keyword OR gd.ghichu_diadiem LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        $statement = $this->db()->prepare(
            "SELECT
                gd.idgiaidau,
                gd.tengiaidau,
                gd.thoigianbatdau,
                gd.thoigianketthuc,
                gd.ghichu_diadiem AS diadiem,
                gd.quymo,
                gd.trangthai,
                gd.trangthaidangky,
                gd.ngaytao,
                gd.ngaycapnhat,
                COALESCE(team_stats.total_teams, 0) AS total_teams,
                COALESCE(group_stats.total_groups, 0) AS total_groups,
                COALESCE(match_stats.total_matches, 0) AS total_matches
             FROM Giaidau gd
             LEFT JOIN (
                SELECT idgiaidau, COUNT(*) AS total_teams
                FROM Dangkygiaidau
                WHERE trangthai = 'DA_DUYET'
                GROUP BY idgiaidau
             ) team_stats ON team_stats.idgiaidau = gd.idgiaidau
             LEFT JOIN (
                SELECT idgiaidau, COUNT(*) AS total_groups
                FROM Bangdau
                WHERE trangthai <> 'DA_XOA'
                GROUP BY idgiaidau
             ) group_stats ON group_stats.idgiaidau = gd.idgiaidau
             LEFT JOIN (
                SELECT idgiaidau, COUNT(*) AS total_matches
                FROM Trandau
                WHERE trangthai <> 'DA_HUY'
                GROUP BY idgiaidau
             ) match_stats ON match_stats.idgiaidau = gd.idgiaidau
             WHERE " . implode(' AND ', $where) . "
             ORDER BY gd.thoigianbatdau DESC, gd.idgiaidau DESC"
        );

        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function tournamentForOrganizer(int $organizerId, int $tournamentId): ?array
    {
        return $this->first(
            "SELECT
                gd.idgiaidau,
                gd.tengiaidau,
                gd.mota,
                gd.thoigianbatdau,
                gd.thoigianketthuc,
                gd.ghichu_diadiem AS diadiem,
                gd.quymo,
                gd.trangthai,
                gd.trangthaidangky,
                gd.idcapgiaidau,
                gd.idbantochuc,
                gd.ngaytao,
                gd.ngaycapnhat
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

    public function syncSupervisorAttendanceStatuses(?int $tournamentId = null, ?int $matchId = null): void
    {
        $bindings = [];
        $scope = [];

        if ($tournamentId !== null) {
            $scope[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = $tournamentId;
        }

        if ($matchId !== null) {
            $scope[] = 'td.idtrandau = :match_id';
            $bindings['match_id'] = $matchId;
        }

        $scopeSql = $scope === [] ? '' : ' AND ' . implode(' AND ', $scope);
        $noSupervisorSql = "NOT EXISTS (
            SELECT 1
            FROM Phancongtrongtai pctt
            JOIN Trongtaitrandau tttd
              ON tttd.idtrandau = pctt.idtrandau
             AND tttd.idtrongtai = pctt.idtrongtai
            WHERE pctt.idtrandau = td.idtrandau
              AND pctt.vaitro = 'GIAM_SAT'
              AND pctt.trangthai = 'DA_XAC_NHAN'
              AND tttd.xacnhanthamgia = 1
        )";
        $noSupervisorBeforeEndSql = "NOT EXISTS (
            SELECT 1
            FROM Phancongtrongtai pctt
            JOIN Trongtaitrandau tttd
              ON tttd.idtrandau = pctt.idtrandau
             AND tttd.idtrongtai = pctt.idtrongtai
            WHERE pctt.idtrandau = td.idtrandau
              AND pctt.vaitro = 'GIAM_SAT'
              AND pctt.trangthai = 'DA_XAC_NHAN'
              AND tttd.xacnhanthamgia = 1
              AND (tttd.thoigianxacnhan IS NULL OR tttd.thoigianxacnhan <= td.thoigianketthuc)
        )";

        $cancelWhere = "td.trangthai IN ('CHO_XEP_LICH', 'DA_SAN_SANG', 'DA_XEP_LICH', 'CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT')
            AND td.thoigianketthuc IS NOT NULL
            AND td.thoigianketthuc <= CURRENT_TIMESTAMP
            AND {$noSupervisorBeforeEndSql}{$scopeSql}";
        $lateWhere = "td.trangthai IN ('CHO_XEP_LICH', 'DA_SAN_SANG', 'DA_XEP_LICH', 'CHUA_DIEN_RA', 'SAP_DIEN_RA')
            AND td.thoigianbatdau IS NOT NULL
            AND td.thoigianbatdau <= CURRENT_TIMESTAMP
            AND (td.thoigianketthuc IS NULL OR td.thoigianketthuc > CURRENT_TIMESTAMP)
            AND {$noSupervisorSql}{$scopeSql}";

        $db = $this->db();

        $startedTransaction = !$db->inTransaction();

        try {
            if ($startedTransaction) {
                $db->beginTransaction();
            }

            $this->recordAutomaticMatchStatus($cancelWhere, $bindings, 'DA_HUY_KHONG_CO_GIAM_SAT', 'Huy tran dau do khong co trong tai giam sat');
            $this->recordAutomaticMatchStatus($lateWhere, $bindings, 'TRONG_TAI_TRE_GIAM_SAT', 'Trong tai tre giam sat tran dau');

            if ($startedTransaction) {
                $db->commit();
            }
        } catch (Throwable $exception) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function approvedTeams(int $tournamentId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                dk.iddangky,
                dk.idgiaidau,
                dk.iddoibong,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.trangthai AS trangthaidoibong,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS huanluyenvien_hoten,
                tk.username AS huanluyenvien_username
             FROM Dangkygiaidau dk
             JOIN Doibong db ON db.iddoibong = dk.iddoibong
             JOIN Huanluyenvien hlv ON hlv.idhuanluyenvien = dk.idhuanluyenvien
             JOIN Nguoidung nd ON nd.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE dk.idgiaidau = :tournament_id
               AND dk.trangthai = 'DA_DUYET'
               AND db.trangthai = 'HOAT_DONG'
             ORDER BY db.tendoibong"
        );

        $statement->execute(['tournament_id' => $tournamentId]);

        return $statement->fetchAll();
    }

    public function activeVenues(): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                sd.idsandau,
                sd.idvitrithidau,
                sd.tensandau,
                vt.tenvitrithidau,
                vt.diachi,
                sd.succhua,
                sd.mota,
                sd.trangthai
             FROM Sandau sd
             JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE sd.trangthai = 'HOAT_DONG'
             ORDER BY vt.tenvitrithidau, sd.tensandau, sd.idsandau"
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function roundsForTournament(int $tournamentId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                vd.idvongdau,
                vd.idgiaidau,
                vd.tenvongdau,
                vd.loaivongdau,
                vd.thutu,
                vd.so_doi_tham_gia,
                vd.co_bangdau,
                vd.so_bang_dau,
                vd.so_luot_dau,
                vd.so_doi_vao_vong_sau,
                vd.so_doi_vao_moi_bang,
                vd.cach_chon_doi_di_tiep,
                vd.cach_xep_cap_dau,
                vd.seed_source,
                vd.co_tranh_hang_ba,
                vd.trangthai,
                COALESCE(team_stats.total_teams, 0) AS total_teams,
                COALESCE(group_stats.total_groups, 0) AS total_groups,
                COALESCE(match_stats.total_matches, 0) AS total_matches
             FROM Vongdau vd
             LEFT JOIN (
                SELECT idvongdau, COUNT(*) AS total_teams
                FROM Doitrongvongdau
                WHERE trangthai = 'HOP_LE'
                GROUP BY idvongdau
             ) team_stats ON team_stats.idvongdau = vd.idvongdau
             LEFT JOIN (
                SELECT idvongdau, COUNT(*) AS total_groups
                FROM Bangdau
                WHERE trangthai <> 'DA_XOA'
                GROUP BY idvongdau
             ) group_stats ON group_stats.idvongdau = vd.idvongdau
             LEFT JOIN (
                SELECT idvongdau, COUNT(*) AS total_matches
                FROM Trandau
                WHERE trangthai <> 'DA_HUY'
                GROUP BY idvongdau
             ) match_stats ON match_stats.idvongdau = vd.idvongdau
             WHERE vd.idgiaidau = :tournament_id
             ORDER BY vd.thutu, vd.idvongdau"
        );
        $statement->execute(['tournament_id' => $tournamentId]);

        return $statement->fetchAll();
    }

    public function roundById(int $tournamentId, int $roundId): ?array
    {
        return $this->first(
            "SELECT *
             FROM Vongdau
             WHERE idgiaidau = :tournament_id
               AND idvongdau = :round_id
             LIMIT 1",
            [
                'tournament_id' => $tournamentId,
                'round_id' => $roundId,
            ]
        );
    }

    public function firstRoundByType(int $tournamentId, string $roundType): ?array
    {
        return $this->first(
            "SELECT *
             FROM Vongdau
             WHERE idgiaidau = :tournament_id
               AND loaivongdau = :round_type
             ORDER BY thutu ASC, idvongdau ASC
             LIMIT 1",
            [
                'tournament_id' => $tournamentId,
                'round_type' => $roundType,
            ]
        );
    }

    public function ensurePointRoundFromFormat(
        int $tournamentId,
        array $format,
        int $teamCount,
        int $actorAccountId,
        ?string $ipAddress
    ): ?int {
        return $this->ensureRoundFromFormat($tournamentId, $format, 'VONG_DIEM', $teamCount, $actorAccountId, $ipAddress);
    }

    public function ensureRoundFromFormat(
        int $tournamentId,
        array $format,
        string $roundType,
        int $teamCount,
        int $actorAccountId,
        ?string $ipAddress
    ): ?int {
        $roundType = strtoupper($roundType);

        if (!in_array($roundType, ['VONG_DIEM', 'VONG_LOAI'], true)) {
            return null;
        }

        $existing = $this->firstRoundByType($tournamentId, $roundType);

        if ($existing !== null) {
            return (int) $existing['idvongdau'];
        }

        $formatName = strtolower((string) ($format['tenthethuc'] ?? ''));
        $hasRound = $roundType === 'VONG_DIEM'
            ? ((int) ($format['co_vong_diem'] ?? 0) === 1
                || strpos($formatName, 'diem') !== false
                || strpos($formatName, 'điểm') !== false)
            : ((int) ($format['co_vong_loai'] ?? 0) === 1
                || strpos($formatName, 'loai') !== false
                || strpos($formatName, 'loại') !== false);

        if (!$hasRound) {
            return null;
        }

        $hasPointRound = (int) ($format['co_vong_diem'] ?? 0) === 1
            || strpos($formatName, 'diem') !== false
            || strpos($formatName, 'điểm') !== false;
        $roundName = $roundType === 'VONG_DIEM' ? 'Vòng điểm' : 'Vòng loại trực tiếp';
        $order = $roundType === 'VONG_DIEM' ? 1 : ($hasPointRound ? 2 : 1);
        $selectRule = $roundType === 'VONG_DIEM' ? 'KHONG_AP_DUNG' : 'THANG_DI_TIEP';
        $pairRule = $roundType === 'VONG_DIEM'
            ? 'KHONG_AP_DUNG'
            : (string) ($format['cach_xep_cap_dau'] ?? $format['cach_xep_mac_dinh'] ?? 'HYBRID');

        $statement = $this->db()->prepare(
            "INSERT INTO Vongdau (
                idgiaidau,
                tenvongdau,
                loaivongdau,
                thutu,
                so_doi_tham_gia,
                co_bangdau,
                so_bang_dau,
                so_luot_dau,
                so_doi_vao_vong_sau,
                so_doi_vao_moi_bang,
                cach_chon_doi_di_tiep,
                cach_xep_cap_dau,
                seed_source,
                co_tranh_hang_ba,
                trangthai
             ) VALUES (
                :tournament_id,
                :name,
                :round_type,
                :round_order,
                :team_count,
                0,
                0,
                1,
                :next_count,
                NULL,
                :select_rule,
                :pair_rule,
                'DANG_KY',
                0,
                'NHAP'
             )"
        );
        $statement->execute([
            'tournament_id' => $tournamentId,
            'name' => $roundName,
            'round_type' => $roundType,
            'round_order' => $order,
            'team_count' => max(0, $teamCount),
            'next_count' => $roundType === 'VONG_DIEM' ? null : 1,
            'select_rule' => $selectRule,
            'pair_rule' => $pairRule,
        ]);

        $roundId = (int) $this->db()->lastInsertId();
        $this->recordSystemLog(
            $actorAccountId,
            'Tao vong dau tu the thuc',
            'Vongdau',
            $roundId,
            $ipAddress,
            'He thong tao ' . $roundName . ' tu the thuc da luu de phuc vu tao bang/tran.'
        );

        return $roundId;
    }

    public function roundTeams(int $roundId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                dv.iddoitrongvong,
                dv.idvongdau,
                dv.iddoibong,
                dv.seed_no,
                dv.thuhang_vongtruoc,
                dv.nguonvao,
                dv.trangthai,
                db.tendoibong,
                db.logo,
                db.diaphuong
             FROM Doitrongvongdau dv
             JOIN Doibong db ON db.iddoibong = dv.iddoibong
             WHERE dv.idvongdau = :round_id
               AND dv.trangthai = 'HOP_LE'
             ORDER BY COALESCE(dv.seed_no, 999999), db.tendoibong"
        );
        $statement->execute(['round_id' => $roundId]);

        return $statement->fetchAll();
    }

    public function seedRoundTeamsFromApprovedRegistrations(int $roundId, int $tournamentId): int
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Doitrongvongdau
                (idvongdau, iddoibong, seed_no, nguonvao, trangthai)
             SELECT
                :round_id_insert,
                missing.iddoibong,
                max_seed.max_seed + ROW_NUMBER() OVER (ORDER BY missing.iddangky, missing.iddoibong),
                'DANG_KY',
                'HOP_LE'
             FROM (
                SELECT dk.iddangky, dk.iddoibong
                FROM Dangkygiaidau dk
                WHERE dk.idgiaidau = :tournament_id
                  AND dk.trangthai = 'DA_DUYET'
                  AND NOT EXISTS (
                    SELECT 1
                    FROM Doitrongvongdau existing
                    WHERE existing.idvongdau = :round_id_exists
                      AND existing.iddoibong = dk.iddoibong
                  )
             ) missing
             CROSS JOIN (
                SELECT COALESCE(MAX(seed_no), 0) AS max_seed
                FROM Doitrongvongdau
                WHERE idvongdau = :round_id_seed
             ) max_seed"
        );
        $statement->execute([
            'round_id_insert' => $roundId,
            'tournament_id' => $tournamentId,
            'round_id_exists' => $roundId,
            'round_id_seed' => $roundId,
        ]);

        $inserted = $statement->rowCount();

        $statement = $this->db()->prepare(
            "UPDATE Vongdau
             SET so_doi_tham_gia = GREATEST(2, (
                    SELECT COUNT(*)
                    FROM Doitrongvongdau
                    WHERE idvongdau = :round_id_count
                      AND trangthai = 'HOP_LE'
                 ))
             WHERE idvongdau = :round_id_update"
        );
        $statement->execute([
            'round_id_count' => $roundId,
            'round_id_update' => $roundId,
        ]);

        return $inserted;
    }

    public function groupsForTournament(int $tournamentId, array $filters = []): array
    {
        $where = ['bd.idgiaidau = :tournament_id'];
        $bindings = ['tournament_id' => $tournamentId];

        if (($filters['round_id'] ?? null) !== null) {
            $where[] = 'bd.idvongdau = :round_id';
            $bindings['round_id'] = (int) $filters['round_id'];
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'bd.trangthai = :status';
            $bindings['status'] = $filters['status'];
        } else {
            $where[] = "bd.trangthai <> 'DA_XOA'";
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(bd.tenbang LIKE :keyword OR bd.mota LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        $statement = $this->db()->prepare(
            "SELECT
                bd.idbangdau,
                bd.idgiaidau,
                bd.idvongdau,
                vd.tenvongdau,
                bd.tenbang,
                bd.mota,
                bd.thoigianbatdau,
                bd.thoigianketthuc,
                bd.trangthai,
                bd.ngaytao,
                COALESCE(team_stats.total_teams, 0) AS total_teams,
                COALESCE(match_stats.total_matches, 0) AS total_matches
             FROM Bangdau bd
             JOIN Vongdau vd ON vd.idvongdau = bd.idvongdau
             LEFT JOIN (
                SELECT idbangdau, COUNT(*) AS total_teams
                FROM Doitrongbang
                GROUP BY idbangdau
             ) team_stats ON team_stats.idbangdau = bd.idbangdau
             LEFT JOIN (
                SELECT idbangdau, COUNT(*) AS total_matches
                FROM Trandau
                WHERE trangthai <> 'DA_HUY'
                GROUP BY idbangdau
             ) match_stats ON match_stats.idbangdau = bd.idbangdau
             WHERE " . implode(' AND ', $where) . "
             ORDER BY bd.tenbang, bd.idbangdau"
        );

        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function groupById(int $tournamentId, int $groupId): ?array
    {
        return $this->first(
            "SELECT
                bd.idbangdau,
                bd.idgiaidau,
                bd.idvongdau,
                vd.tenvongdau,
                bd.tenbang,
                bd.mota,
                bd.thoigianbatdau,
                bd.thoigianketthuc,
                bd.trangthai,
                bd.ngaytao
             FROM Bangdau bd
             JOIN Vongdau vd ON vd.idvongdau = bd.idvongdau
             WHERE bd.idgiaidau = :tournament_id
               AND bd.idbangdau = :group_id
             LIMIT 1",
            [
                'tournament_id' => $tournamentId,
                'group_id' => $groupId,
            ]
        );
    }

    public function groupTeams(int $groupId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                dtb.iddoitrongbang,
                dtb.idbangdau,
                dtb.iddoibong,
                dtb.ngaythem,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.trangthai AS trangthaidoibong
             FROM Doitrongbang dtb
             JOIN Doibong db ON db.iddoibong = dtb.iddoibong
             WHERE dtb.idbangdau = :group_id
             ORDER BY db.tendoibong"
        );

        $statement->execute(['group_id' => $groupId]);

        return $statement->fetchAll();
    }

    public function existsGroupName(int $tournamentId, string $name, ?int $excludeGroupId = null): bool
    {
        $bindings = [
            'tournament_id' => $tournamentId,
            'name' => $name,
        ];
        $sql = "SELECT 1
                FROM Bangdau
                WHERE idgiaidau = :tournament_id
                  AND tenbang = :name";

        if ($excludeGroupId !== null) {
            $sql .= ' AND idbangdau <> :exclude_group_id';
            $bindings['exclude_group_id'] = $excludeGroupId;
        }

        $sql .= ' LIMIT 1';

        return $this->first($sql, $bindings) !== null;
    }

    public function approvedTeamIds(int $tournamentId, array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $statement = $this->db()->prepare(
            "SELECT dk.iddoibong
             FROM Dangkygiaidau dk
             JOIN Doibong db ON db.iddoibong = dk.iddoibong
             WHERE dk.idgiaidau = ?
               AND dk.trangthai = 'DA_DUYET'
               AND db.trangthai = 'HOAT_DONG'
               AND dk.iddoibong IN ($placeholders)"
        );
        $statement->execute(array_merge([$tournamentId], $teamIds));

        return array_map('intval', array_column($statement->fetchAll(), 'iddoibong'));
    }

    public function teamIdsInGroup(int $groupId): array
    {
        $statement = $this->db()->prepare(
            "SELECT iddoibong
             FROM Doitrongbang
             WHERE idbangdau = :group_id"
        );
        $statement->execute(['group_id' => $groupId]);

        return array_map('intval', array_column($statement->fetchAll(), 'iddoibong'));
    }

    public function activeReferees(?int $tournamentId = null): array
    {
        $bindings = [];
        $levelSelect = '';
        $levelJoin = '';
        $levelWhere = '';

        if ($tournamentId !== null) {
            $bindings['tournament_id'] = $tournamentId;
            $levelSelect = ",
                cg_filter.idcapgiaidau AS idcapgiaidau_tran,
                cg_filter.macapgiaidau AS macapgiaidau_tran,
                cg_filter.tencapgiaidau AS tencapgiaidau_tran";
            $levelJoin = "
              JOIN Giaidau gd_filter ON gd_filter.idgiaidau = :tournament_id
              JOIN Capgiaidau cg_filter ON cg_filter.idcapgiaidau = gd_filter.idcapgiaidau";
            $levelWhere = ' AND ' . $this->refereeLevelMatchSql('tt', 'cg_filter');
        }

        $statement = $this->db()->prepare(
            "SELECT
                tt.idtrongtai,
                tt.capbac,
                tt.kinhnghiem,
                tt.trangthai,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tk.username
                {$levelSelect}
             FROM Trongtai tt
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             {$levelJoin}
             WHERE tt.trangthai = 'HOAT_DONG'
               AND tk.trangthai = 'HOAT_DONG'
               {$levelWhere}
              ORDER BY hoten, tt.idtrongtai"
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function eligibleRefereeIdsForTournament(int $tournamentId, array $refereeIds): array
    {
        $refereeIds = array_values(array_unique(array_filter(
            array_map('intval', $refereeIds),
            static fn (int $refereeId): bool => $refereeId > 0
        )));

        if ($refereeIds === []) {
            return [];
        }

        $bindings = ['tournament_id' => $tournamentId];
        $placeholders = [];

        foreach ($refereeIds as $index => $refereeId) {
            $key = 'referee_' . $index;
            $placeholders[] = ':' . $key;
            $bindings[$key] = $refereeId;
        }

        $statement = $this->db()->prepare(
            "SELECT tt.idtrongtai
             FROM Trongtai tt
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             JOIN Giaidau gd ON gd.idgiaidau = :tournament_id
             JOIN Capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
             WHERE tt.idtrongtai IN (" . implode(',', $placeholders) . ")
               AND tt.trangthai = 'HOAT_DONG'
               AND tk.trangthai = 'HOAT_DONG'
               AND " . $this->refereeLevelMatchSql('tt', 'cg')
        );
        $statement->execute($bindings);

        return array_map('intval', array_column($statement->fetchAll(), 'idtrongtai'));
    }

    public function matchSlots(int $matchId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                ts.idslot,
                ts.idtrandau,
                ts.slot_so,
                ts.source_type,
                ts.iddoibong,
                db.tendoibong,
                ts.source_match_id,
                src.ma_tran AS source_match_code,
                ts.source_result,
                ts.source_seed_no,
                ts.ghichu
             FROM TrandauSlot ts
             LEFT JOIN Doibong db ON db.iddoibong = ts.iddoibong
             LEFT JOIN Trandau src ON src.idtrandau = ts.source_match_id
             WHERE ts.idtrandau = :match_id
             ORDER BY ts.slot_so"
        );
        $statement->execute(['match_id' => $matchId]);

        return $statement->fetchAll();
    }

    public function matchAssignments(int $matchId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                pc.idphancong,
                pc.idtrandau,
                pc.idtrongtai,
                pc.vaitro,
                pc.trangthai,
                pc.ngayphancong,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tk.username
             FROM Phancongtrongtai pc
             JOIN Trongtai tt ON tt.idtrongtai = pc.idtrongtai
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE pc.idtrandau = :match_id
             ORDER BY pc.vaitro, hoten"
        );
        $statement->execute(['match_id' => $matchId]);

        return $statement->fetchAll();
    }

    public function createGroup(
        int $tournamentId,
        array $group,
        array $teamIds,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->markRoundAsGrouped((int) $group['idvongdau']);
            $this->seedRoundTeamsFromApprovedRegistrations((int) $group['idvongdau'], $tournamentId);

            $statement = $db->prepare(
                "INSERT INTO Bangdau (idgiaidau, idvongdau, tenbang, mota, thoigianbatdau, thoigianketthuc, trangthai)
                 VALUES (:tournament_id, :round_id, :name, :description, :start_at, :end_at, :status)"
            );
            $statement->execute([
                'tournament_id' => $tournamentId,
                'round_id' => $group['idvongdau'],
                'name' => $group['tenbang'],
                'description' => $group['mota'],
                'start_at' => $group['thoigianbatdau'],
                'end_at' => $group['thoigianketthuc'],
                'status' => $group['trangthai'],
            ]);

            $groupId = (int) $db->lastInsertId();
            $this->replaceGroupTeams($groupId, $teamIds);
            $this->refreshRoundGroupCount((int) $group['idvongdau']);
            $this->recordSystemLog($actorAccountId, 'Them bang dau', 'Bangdau', $groupId, $ipAddress, $logNote);

            $db->commit();

            return $groupId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function markRoundAsGrouped(int $roundId): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Vongdau
             SET co_bangdau = 1,
                 so_bang_dau = GREATEST(COALESCE(so_bang_dau, 0), 1),
                 trangthai = CASE
                    WHEN trangthai IN ('NHAP', 'DA_TAO_DOI', 'CHO_PHAN_CONG_BANG') THEN 'CHO_PHAN_CONG_BANG'
                    ELSE trangthai
                 END
             WHERE idvongdau = :round_id"
        );
        $statement->execute(['round_id' => $roundId]);
    }

    private function refreshRoundGroupCount(int $roundId): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Vongdau
             SET so_bang_dau = (
                    SELECT COUNT(*)
                    FROM Bangdau
                    WHERE idvongdau = :round_id_for_count
                      AND trangthai <> 'DA_XOA'
                 ),
                 trangthai = CASE
                    WHEN trangthai IN ('NHAP', 'DA_TAO_DOI', 'CHO_PHAN_CONG_BANG') THEN 'DA_TAO_BANG'
                    ELSE trangthai
                 END
             WHERE idvongdau = :round_id"
        );
        $statement->execute([
            'round_id_for_count' => $roundId,
            'round_id' => $roundId,
        ]);
    }

    public function updateGroup(
        int $groupId,
        array $changes,
        ?array $teamIds,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            if ($changes !== []) {
                $sets = [];
                $bindings = ['group_id' => $groupId];

                foreach ($changes as $field => $value) {
                    $sets[] = "{$field} = :{$field}";
                    $bindings[$field] = $value;
                }

                $statement = $db->prepare(
                    'UPDATE Bangdau SET ' . implode(', ', $sets) . ' WHERE idbangdau = :group_id'
                );
                $statement->execute($bindings);

                if ($statement->rowCount() !== 1) {
                    throw new \RuntimeException('GROUP_NOT_UPDATED');
                }
            }

            if ($teamIds !== null) {
                $this->replaceGroupTeams($groupId, $teamIds);
            }

            $this->recordSystemLog($actorAccountId, 'Cap nhat bang dau', 'Bangdau', $groupId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function deleteGroup(
        int $groupId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Bangdau
                 SET trangthai = 'DA_XOA'
                 WHERE idbangdau = :group_id
                   AND trangthai <> 'DA_XOA'"
            );
            $statement->execute(['group_id' => $groupId]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('GROUP_NOT_DELETED');
            }

            $this->recordSystemLog($actorAccountId, 'Xoa bang dau', 'Bangdau', $groupId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function activeMatchCountForGroup(int $groupId): int
    {
        $row = $this->first(
            "SELECT COUNT(*) AS total
             FROM Trandau
             WHERE idbangdau = :group_id
               AND trangthai <> 'DA_HUY'",
            ['group_id' => $groupId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function activeMatchCountForTournament(int $tournamentId): int
    {
        $row = $this->first(
            "SELECT COUNT(*) AS total
             FROM Trandau
             WHERE idgiaidau = :tournament_id
               AND trangthai <> 'DA_HUY'",
            ['tournament_id' => $tournamentId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function teamActiveMemberCounts(array $teamIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $teamIds)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->db()->prepare(
            "SELECT iddoibong, COUNT(*) AS total
             FROM Thanhviendoibong
             WHERE trangthai = 'DANG_THAM_GIA'
               AND iddoibong IN ($placeholders)
             GROUP BY iddoibong"
        );
        $statement->execute($ids);

        $counts = array_fill_keys($ids, 0);

        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['iddoibong']] = (int) $row['total'];
        }

        return $counts;
    }

    public function matchesForTournament(int $tournamentId, array $filters = []): array
    {
        $this->syncSupervisorAttendanceStatuses($tournamentId);

        $where = ['td.idgiaidau = :tournament_id'];
        $bindings = ['tournament_id' => $tournamentId];

        if (($filters['round_id'] ?? null) !== null) {
            $where[] = 'td.idvongdau = :round_id';
            $bindings['round_id'] = (int) $filters['round_id'];
        }

        if (($filters['group_id'] ?? null) !== null) {
            $where[] = 'td.idbangdau = :group_id';
            $bindings['group_id'] = (int) $filters['group_id'];
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'td.trangthai = :status';
            $bindings['status'] = $filters['status'];
        } else {
            $where[] = "td.trangthai NOT IN ('DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT')";
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(bd.tenbang LIKE :keyword
                OR d1.tendoibong LIKE :keyword
                OR d2.tendoibong LIKE :keyword
                OR sd.tensandau LIKE :keyword
                OR vd.tenvongdau LIKE :keyword
                OR td.ma_tran LIKE :keyword
                OR td.ten_tran LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        $statement = $this->db()->prepare(
            "SELECT
                td.idtrandau,
                td.idgiaidau,
                td.idvongdau,
                td.idbangdau,
                bd.tenbang,
                vd.tenvongdau AS tenvong,
                vd.loaivongdau,
                td.ma_tran,
                td.ten_tran,
                td.thutu_tran,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                td.idsandau,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                td.thoigianbatdau,
                td.thoigianketthuc,
                td.trangthai,
                (
                    SELECT COUNT(*)
                    FROM Phancongtrongtai pctt
                    WHERE pctt.idtrandau = td.idtrandau
                      AND pctt.vaitro = 'TRONG_TAI_CHINH'
                      AND pctt.trangthai = 'DA_XAC_NHAN'
                ) AS confirmed_main_referees,
                (
                    SELECT COUNT(*)
                    FROM Phancongtrongtai pctt
                    WHERE pctt.idtrandau = td.idtrandau
                      AND pctt.vaitro = 'GIAM_SAT'
                      AND pctt.trangthai = 'DA_XAC_NHAN'
                ) AS confirmed_supervisors,
                td.ngaytao,
                td.ngaycapnhat
             FROM Trandau td
             JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             LEFT JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE " . implode(' AND ', $where) . "
             ORDER BY td.thoigianbatdau, td.idtrandau"
        );

        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function matchById(int $tournamentId, int $matchId): ?array
    {
        $this->syncSupervisorAttendanceStatuses($tournamentId, $matchId);

        return $this->first(
            "SELECT
                td.idtrandau,
                td.idgiaidau,
                td.idvongdau,
                td.idbangdau,
                bd.tenbang,
                vd.tenvongdau AS tenvong,
                vd.loaivongdau,
                td.ma_tran,
                td.ten_tran,
                td.thutu_tran,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                td.idsandau,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                td.thoigianbatdau,
                td.thoigianketthuc,
                td.trangthai,
                (
                    SELECT COUNT(*)
                    FROM Phancongtrongtai pctt
                    WHERE pctt.idtrandau = td.idtrandau
                      AND pctt.vaitro = 'TRONG_TAI_CHINH'
                      AND pctt.trangthai = 'DA_XAC_NHAN'
                ) AS confirmed_main_referees,
                (
                    SELECT COUNT(*)
                    FROM Phancongtrongtai pctt
                    WHERE pctt.idtrandau = td.idtrandau
                      AND pctt.vaitro = 'GIAM_SAT'
                      AND pctt.trangthai = 'DA_XAC_NHAN'
                ) AS confirmed_supervisors,
                td.ngaytao,
                td.ngaycapnhat
             FROM Trandau td
             JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             LEFT JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE td.idgiaidau = :tournament_id
               AND td.idtrandau = :match_id
             LIMIT 1",
            [
                'tournament_id' => $tournamentId,
                'match_id' => $matchId,
            ]
        );
    }

    public function scheduleViewForOrganizer(int $organizerId, array $filters = []): array
    {
        $this->syncSupervisorAttendanceStatuses();

        [$where, $bindings] = $this->whereForOrganizerScheduleView($organizerId, $filters);

        $statement = $this->db()->prepare(
            $this->organizerScheduleViewSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY td.thoigianbatdau, td.idtrandau'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function scheduleViewStatsForOrganizer(int $organizerId, array $filters = []): array
    {
        $this->syncSupervisorAttendanceStatuses();

        [$where, $bindings] = $this->whereForOrganizerScheduleView($organizerId, $filters);

        $statement = $this->db()->prepare(
            'SELECT td.trangthai, COUNT(*) AS total
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY td.trangthai'
        );
        $statement->execute($bindings);

        $stats = [
            'CHUA_DIEN_RA' => 0,
            'SAP_DIEN_RA' => 0,
            'DANG_DIEN_RA' => 0,
            'TAM_DUNG' => 0,
            'DA_KET_THUC' => 0,
            'TRONG_TAI_TRE_GIAM_SAT' => 0,
            'DA_HUY_KHONG_CO_GIAM_SAT' => 0,
            'DA_HUY' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $stats[(string) $row['trangthai']] = (int) $row['total'];
        }

        return $stats;
    }

    public function scheduleViewMatchForOrganizer(int $organizerId, int $matchId): ?array
    {
        $this->syncSupervisorAttendanceStatuses(null, $matchId);

        return $this->first(
            $this->organizerScheduleViewSelect() . '
             WHERE gd.idbantochuc = :organizer_id
               AND td.idtrandau = :match_id
             LIMIT 1',
            [
                'organizer_id' => $organizerId,
                'match_id' => $matchId,
            ]
        );
    }

    public function activeVenueById(int $venueId): ?array
    {
        return $this->first(
            "SELECT sd.idsandau, sd.tensandau, vt.diachi, sd.trangthai
             FROM Sandau sd
             JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE sd.idsandau = :venue_id
               AND sd.trangthai = 'HOAT_DONG'
             LIMIT 1",
            ['venue_id' => $venueId]
        );
    }

    public function hasScheduleConflict(
        int $venueId,
        int $teamOneId,
        int $teamTwoId,
        string $startAt,
        ?string $endAt,
        ?int $excludeMatchId = null
    ): ?array {
        $bindings = [
            'venue_id' => $venueId,
            'team_one_a' => $teamOneId,
            'team_two_a' => $teamTwoId,
            'team_one_b' => $teamOneId,
            'team_two_b' => $teamTwoId,
            'start_at' => $startAt,
            'end_at' => $endAt ?? $startAt,
        ];
        $exclude = '';

        if ($excludeMatchId !== null) {
            $exclude = 'AND idtrandau <> :exclude_match_id';
            $bindings['exclude_match_id'] = $excludeMatchId;
        }

        return $this->first(
            "SELECT idtrandau, idsandau, iddoibong1, iddoibong2, thoigianbatdau, thoigianketthuc
             FROM Trandau
             WHERE trangthai NOT IN ('DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT')
               $exclude
               AND (
                    idsandau = :venue_id
                    OR iddoibong1 IN (:team_one_a, :team_two_a)
                    OR iddoibong2 IN (:team_one_b, :team_two_b)
               )
               AND thoigianbatdau < :end_at
               AND COALESCE(thoigianketthuc, thoigianbatdau) > :start_at
             LIMIT 1",
            $bindings
        );
    }

    private function whereForOrganizerScheduleView(int $organizerId, array $filters): array
    {
        $where = ['gd.idbantochuc = :organizer_id'];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'td.trangthai = :status';
            $bindings['status'] = $filters['status'];
        } else {
            $where[] = "td.trangthai NOT IN ('DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT')";
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(gd.tengiaidau LIKE :keyword
                OR bd.tenbang LIKE :keyword
                OR d1.tendoibong LIKE :keyword
                OR d2.tendoibong LIKE :keyword
                OR sd.tensandau LIKE :keyword
                OR vt.diachi LIKE :keyword
                OR vd.tenvongdau LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['group_id'] ?? null) !== null) {
            $where[] = 'td.idbangdau = :group_id';
            $bindings['group_id'] = (int) $filters['group_id'];
        }

        if (($filters['team_id'] ?? null) !== null) {
            $where[] = '(td.iddoibong1 = :team_id OR td.iddoibong2 = :team_id)';
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        if (($filters['venue_id'] ?? null) !== null) {
            $where[] = 'td.idsandau = :venue_id';
            $bindings['venue_id'] = (int) $filters['venue_id'];
        }

        if (($filters['result_status'] ?? '') !== '') {
            $where[] = 'kq.trangthai = :result_status';
            $bindings['result_status'] = $filters['result_status'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'td.thoigianbatdau >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'td.thoigianbatdau <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function organizerScheduleViewSelect(): string
    {
        return "SELECT
                td.idtrandau,
                td.idgiaidau,
                gd.tengiaidau,
                gd.trangthai AS trangthaigiaidau,
                gd.trangthaidangky AS trangthaidangkygiaidau,
                gd.thoigianbatdau AS giaidau_batdau,
                gd.thoigianketthuc AS giaidau_ketthuc,
                gd.ghichu_diadiem AS giaidau_diadiem,
                gd.idbantochuc,
                td.idvongdau,
                vd.tenvongdau AS tenvong,
                vd.loaivongdau,
                td.idbangdau,
                bd.tenbang,
                bd.trangthai AS bangdau_trangthai,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                d1.logo AS doi1_logo,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                d2.logo AS doi2_logo,
                td.idsandau,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                sd.trangthai AS sandau_trangthai,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai,
                td.ngaytao,
                td.ngaycapnhat,
                kq.idketqua,
                kq.trangthai AS ketqua_trangthai,
                kq.sosetdoi1,
                kq.sosetdoi2,
                kq.diemdoi1,
                kq.diemdoi2,
                kq.iddoithang,
                winner.tendoibong AS doithang
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             LEFT JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
             LEFT JOIN Doibong winner ON winner.iddoibong = kq.iddoithang";
    }

    public function createMatch(
        int $tournamentId,
        array $match,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->seedRoundTeamsFromApprovedRegistrations((int) $match['idvongdau'], $tournamentId);

            $statement = $db->prepare(
                "INSERT INTO Trandau
                    (idgiaidau, idvongdau, idbangdau, ma_tran, ten_tran, iddoibong1, iddoibong2, idsandau, thoigianbatdau, thoigianketthuc, thutu_tran, trangthai)
                 VALUES
                    (:tournament_id, :round_id, :group_id, :match_code, :match_name, :team_one, :team_two, :venue_id, :start_at, :end_at, :match_order, :status)"
            );
            $statement->execute([
                'tournament_id' => $tournamentId,
                'round_id' => $match['idvongdau'],
                'group_id' => $match['idbangdau'],
                'match_code' => $match['ma_tran'],
                'match_name' => $match['ten_tran'],
                'team_one' => $match['iddoibong1'],
                'team_two' => $match['iddoibong2'],
                'venue_id' => $match['idsandau'],
                'start_at' => $match['thoigianbatdau'],
                'end_at' => $match['thoigianketthuc'],
                'match_order' => $match['thutu_tran'],
                'status' => $match['trangthai'],
            ]);

            $matchId = (int) $db->lastInsertId();
            $this->replaceMatchSlots($matchId, $match['slots'] ?? [
                ['slot_so' => 1, 'source_type' => 'TEAM', 'iddoibong' => $match['iddoibong1']],
                ['slot_so' => 2, 'source_type' => 'TEAM', 'iddoibong' => $match['iddoibong2']],
            ]);
            $this->replaceRefereeAssignments($matchId, $match['referee_assignments'] ?? []);

            $this->recordStatusHistory('TRAN_DAU', $matchId, null, (string) $match['trangthai'], 'Them tran dau', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Them tran dau', 'Trandau', $matchId, $ipAddress, $logNote);

            $db->commit();

            return $matchId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function createStandardPreliminarySchedule(
        int $tournamentId,
        string $groupName,
        ?string $groupDescription,
        array $teamIds,
        array $matches,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): array {
        $db = $this->db();

        try {
            $db->beginTransaction();
            $round = $this->firstRoundByType($tournamentId, 'VONG_DIEM');

            if ($round === null) {
                throw new \RuntimeException('ROUND_NOT_FOUND');
            }

            $roundId = (int) $round['idvongdau'];

            $existingGroup = $this->first(
                "SELECT idbangdau
                 FROM Bangdau
                 WHERE idgiaidau = :tournament_id
                   AND idvongdau = :round_id
                   AND tenbang = :group_name
                 LIMIT 1",
                [
                    'tournament_id' => $tournamentId,
                    'round_id' => $roundId,
                    'group_name' => $groupName,
                ]
            );

            if ($existingGroup === null) {
                $statement = $db->prepare(
                    "INSERT INTO Bangdau (idgiaidau, idvongdau, tenbang, mota, trangthai)
                     VALUES (:tournament_id, :round_id, :name, :description, 'HOAT_DONG')"
                );
                $statement->execute([
                    'tournament_id' => $tournamentId,
                    'round_id' => $roundId,
                    'name' => $groupName,
                    'description' => $groupDescription,
                ]);
                $groupId = (int) $db->lastInsertId();
            } else {
                $groupId = (int) $existingGroup['idbangdau'];
                $statement = $db->prepare(
                    "UPDATE Bangdau
                     SET mota = :description,
                         trangthai = 'HOAT_DONG'
                     WHERE idbangdau = :group_id"
                );
                $statement->execute([
                    'description' => $groupDescription,
                    'group_id' => $groupId,
                ]);
            }

            $this->replaceGroupTeams($groupId, $teamIds);

            $statement = $db->prepare(
                "INSERT INTO Trandau
                    (idgiaidau, idvongdau, idbangdau, iddoibong1, iddoibong2, idsandau, thoigianbatdau, thoigianketthuc, trangthai)
                 VALUES
                    (:tournament_id, :round_id, :group_id, :team_one, :team_two, :venue_id, :start_at, :end_at, :status)"
            );

            $matchIds = [];

            foreach ($matches as $match) {
                $statement->execute([
                    'tournament_id' => $tournamentId,
                    'round_id' => $roundId,
                    'group_id' => $groupId,
                    'team_one' => $match['iddoibong1'],
                    'team_two' => $match['iddoibong2'],
                    'venue_id' => $match['idsandau'],
                    'start_at' => $match['thoigianbatdau'],
                    'end_at' => $match['thoigianketthuc'],
                    'status' => $match['trangthai'],
                ]);

                $matchId = (int) $db->lastInsertId();
                $matchIds[] = $matchId;
                $this->recordStatusHistory('TRAN_DAU', $matchId, null, (string) $match['trangthai'], 'Tao lich so bo chuan', $actorAccountId);
            }

            $this->recordSystemLog($actorAccountId, 'Tao lich so bo chuan', 'Trandau', null, $ipAddress, $logNote);

            $db->commit();

            return [
                'group_id' => $groupId,
                'match_ids' => $matchIds,
                'created_matches' => count($matchIds),
            ];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function createRoundRobinMatches(
        int $tournamentId,
        int $roundId,
        array $units,
        int $legs,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): array {
        $db = $this->db();

        try {
            $db->beginTransaction();
            $statement = $db->prepare(
                "INSERT INTO Trandau
                    (idgiaidau, idvongdau, idbangdau, ma_tran, ten_tran, iddoibong1, iddoibong2, thutu_tran, trangthai)
                 VALUES
                    (:tournament_id, :round_id, :group_id, :match_code, :match_name, :team_one, :team_two, :match_order, 'CHO_XEP_LICH')"
            );
            $session = $db->prepare(
                "INSERT INTO Phiensinhtran
                    (idgiaidau, idvongdau, kieu_sinh, cach_xep_cap_dau, ghichu, trangthai, idnguoitao, ngayxacnhan)
                 VALUES
                    (:tournament_id, :round_id, 'VONG_DIEM', 'KHONG_AP_DUNG', :note, 'DA_XAC_NHAN', :actor_id, CURRENT_TIMESTAMP)"
            );
            $session->execute([
                'tournament_id' => $tournamentId,
                'round_id' => $roundId,
                'note' => $logNote,
                'actor_id' => $actorAccountId,
            ]);
            $sessionId = (int) $db->lastInsertId();
            $matchIds = [];
            $order = $this->nextMatchOrder($roundId);
            $legs = max(1, min(2, $legs));

            foreach ($units as $unit) {
                $teamIds = array_values(array_map('intval', $unit['team_ids'] ?? []));

                for ($leg = 1; $leg <= $legs; $leg++) {
                    for ($i = 0; $i < count($teamIds) - 1; $i++) {
                        for ($j = $i + 1; $j < count($teamIds); $j++) {
                            $teamOne = $leg === 1 ? $teamIds[$i] : $teamIds[$j];
                            $teamTwo = $leg === 1 ? $teamIds[$j] : $teamIds[$i];
                        $order++;
                        $code = sprintf('R%d-M%03d', $roundId, $order);
                        $statement->execute([
                            'tournament_id' => $tournamentId,
                            'round_id' => $roundId,
                            'group_id' => $unit['group_id'] ?? null,
                            'match_code' => $code,
                            'match_name' => $unit['label'] ?? null,
                            'team_one' => $teamOne,
                            'team_two' => $teamTwo,
                            'match_order' => $order,
                        ]);
                        $matchId = (int) $db->lastInsertId();
                        $matchIds[] = $matchId;
                        $this->replaceMatchSlots($matchId, [
                            ['slot_so' => 1, 'source_type' => 'TEAM', 'iddoibong' => $teamOne],
                            ['slot_so' => 2, 'source_type' => 'TEAM', 'iddoibong' => $teamTwo],
                        ]);
                        $this->recordStatusHistory('TRAN_DAU', $matchId, null, 'CHO_XEP_LICH', 'Tao tran tu dong vong diem', $actorAccountId);
                        }
                    }
                }
            }

            $this->recordSystemLog($actorAccountId, 'Tao tran tu dong', 'Trandau', null, $ipAddress, $logNote);
            $db->commit();

            return [
                'session_id' => $sessionId,
                'match_ids' => $matchIds,
                'created_matches' => count($matchIds),
            ];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function createKnockoutMatches(
        int $tournamentId,
        int $roundId,
        array $matches,
        string $pairingRule,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): array {
        $db = $this->db();

        try {
            $db->beginTransaction();
            $statement = $db->prepare(
                "INSERT INTO Trandau
                    (idgiaidau, idvongdau, idbangdau, ma_tran, ten_tran, iddoibong1, iddoibong2, thutu_tran, trangthai)
                 VALUES
                    (:tournament_id, :round_id, NULL, :match_code, :match_name, :team_one, :team_two, :match_order, :status)"
            );
            $session = $db->prepare(
                "INSERT INTO Phiensinhtran
                    (idgiaidau, idvongdau, kieu_sinh, cach_xep_cap_dau, ghichu, trangthai, idnguoitao, ngayxacnhan)
                 VALUES
                    (:tournament_id, :round_id, 'VONG_LOAI', :pairing_rule, :note, 'DA_XAC_NHAN', :actor_id, CURRENT_TIMESTAMP)"
            );
            $session->execute([
                'tournament_id' => $tournamentId,
                'round_id' => $roundId,
                'pairing_rule' => $pairingRule,
                'note' => $logNote,
                'actor_id' => $actorAccountId,
            ]);
            $sessionId = (int) $db->lastInsertId();
            $createdMatches = [];

            foreach ($matches as $match) {
                $statement->execute([
                    'tournament_id' => $tournamentId,
                    'round_id' => $roundId,
                    'match_code' => $match['ma_tran'],
                    'match_name' => $match['ten_tran'],
                    'team_one' => $match['iddoibong1'] ?? null,
                    'team_two' => $match['iddoibong2'] ?? null,
                    'match_order' => $match['thutu_tran'],
                    'status' => $match['trangthai'],
                ]);
                $matchId = (int) $db->lastInsertId();
                $createdMatches[$match['client_key']] = $matchId;
                $slots = array_map(function (array $slot) use (&$createdMatches): array {
                    if (($slot['source_client_key'] ?? null) !== null) {
                        $slot['source_match_id'] = $createdMatches[$slot['source_client_key']] ?? null;
                    }

                    unset($slot['source_client_key']);

                    return $slot;
                }, $match['slots']);
                $this->replaceMatchSlots($matchId, $slots);
                $this->recordStatusHistory('TRAN_DAU', $matchId, null, $match['trangthai'], 'Tao tran tu dong vong loai', $actorAccountId);
            }

            $this->recordSystemLog($actorAccountId, 'Tao nhanh loai truc tiep', 'Trandau', null, $ipAddress, $logNote);
            $db->commit();

            return [
                'session_id' => $sessionId,
                'match_ids' => array_values($createdMatches),
                'created_matches' => count($createdMatches),
            ];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function updateMatch(
        int $matchId,
        array $changes,
        ?array $slots,
        ?array $assignments,
        string $oldStatus,
        ?string $newStatus,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $sets = [];
            $bindings = ['match_id' => $matchId];

            foreach ($changes as $field => $value) {
                $sets[] = "{$field} = :{$field}";
                $bindings[$field] = $value;
            }

            if ($sets === [] && $slots === null && $assignments === null) {
                throw new \RuntimeException('MATCH_NOT_UPDATED');
            }

            if ($sets !== []) {
                $sets[] = 'ngaycapnhat = CURRENT_TIMESTAMP';

                $statement = $db->prepare(
                    'UPDATE Trandau SET ' . implode(', ', $sets) . ' WHERE idtrandau = :match_id'
                );
                $statement->execute($bindings);

                if ($statement->rowCount() !== 1) {
                    throw new \RuntimeException('MATCH_NOT_UPDATED');
                }
            }

            if ($slots !== null) {
                $this->replaceMatchSlots($matchId, $slots);
            }

            if ($assignments !== null) {
                $this->replaceRefereeAssignments($matchId, $assignments);
            }

            if ($newStatus !== null && $newStatus !== $oldStatus) {
                $this->recordStatusHistory('TRAN_DAU', $matchId, $oldStatus, $newStatus, 'Cap nhat trang thai tran dau', $actorAccountId);
            }

            $this->recordSystemLog($actorAccountId, 'Cap nhat tran dau', 'Trandau', $matchId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function deleteMatch(
        int $matchId,
        string $oldStatus,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Trandau
                 SET trangthai = 'DA_HUY',
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idtrandau = :match_id
                   AND trangthai <> 'DA_HUY'"
            );
            $statement->execute(['match_id' => $matchId]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('MATCH_NOT_DELETED');
            }

            $this->recordStatusHistory('TRAN_DAU', $matchId, $oldStatus, 'DA_HUY', 'Xoa tran dau', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Xoa tran dau', 'Trandau', $matchId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function replaceGroupTeams(int $groupId, array $teamIds): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM Doitrongbang
             WHERE idbangdau = :group_id"
        );
        $statement->execute(['group_id' => $groupId]);

        if ($teamIds === []) {
            return;
        }

        $statement = $this->db()->prepare(
            "INSERT INTO Doitrongbang (idbangdau, iddoibong)
             VALUES (:group_id, :team_id)"
        );

        foreach ($teamIds as $teamId) {
            $statement->execute([
                'group_id' => $groupId,
                'team_id' => $teamId,
            ]);
        }
    }

    private function nextMatchOrder(int $roundId): int
    {
        $row = $this->first(
            "SELECT COALESCE(MAX(thutu_tran), 0) AS max_order
             FROM Trandau
             WHERE idvongdau = :round_id",
            ['round_id' => $roundId]
        );

        return (int) ($row['max_order'] ?? 0);
    }

    private function replaceMatchSlots(int $matchId, array $slots): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM TrandauSlot
             WHERE idtrandau = :match_id"
        );
        $statement->execute(['match_id' => $matchId]);

        if ($slots === []) {
            return;
        }

        $statement = $this->db()->prepare(
            "INSERT INTO TrandauSlot
                (idtrandau, slot_so, source_type, iddoibong, source_match_id, source_result, source_seed_no, ghichu)
             VALUES
                (:match_id, :slot_no, :source_type, :team_id, :source_match_id, :source_result, :source_seed_no, :note)"
        );

        foreach ($slots as $slot) {
            $statement->execute([
                'match_id' => $matchId,
                'slot_no' => $slot['slot_so'],
                'source_type' => $slot['source_type'],
                'team_id' => $slot['iddoibong'] ?? null,
                'source_match_id' => $slot['source_match_id'] ?? null,
                'source_result' => $slot['source_result'] ?? null,
                'source_seed_no' => $slot['source_seed_no'] ?? null,
                'note' => $slot['ghichu'] ?? null,
            ]);
        }
    }

    private function replaceRefereeAssignments(int $matchId, array $assignments): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM Phancongtrongtai
             WHERE idtrandau = :match_id"
        );
        $statement->execute(['match_id' => $matchId]);

        $statement = $this->db()->prepare(
            "DELETE FROM Trongtaitrandau
             WHERE idtrandau = :match_id"
        );
        $statement->execute(['match_id' => $matchId]);

        if ($assignments === []) {
            return;
        }

        $assignmentStatement = $this->db()->prepare(
            "INSERT INTO Phancongtrongtai (idtrandau, idtrongtai, vaitro, trangthai)
             VALUES (:match_id, :referee_id, :role, 'DA_XAC_NHAN')"
        );
        $detailStatement = $this->db()->prepare(
            "INSERT INTO Trongtaitrandau (idtrandau, idtrongtai, vaitro, xacnhanthamgia, thoigianxacnhan)
             VALUES (:match_id, :referee_id, :role, FALSE, NULL)"
        );

        foreach ($assignments as $assignment) {
            $params = [
                'match_id' => $matchId,
                'referee_id' => $assignment['idtrongtai'],
                'role' => $assignment['vaitro'],
            ];
            $assignmentStatement->execute($params);
            $detailStatement->execute($params);
        }
    }

    private function refereeLevelMatchSql(string $refereeAlias, string $levelAlias): string
    {
        $refereeLevel = $this->normalizedSql($refereeAlias . '.capbac');
        $levelCandidates = [
            'CAST(' . $levelAlias . '.idcapgiaidau AS CHAR)',
            $levelAlias . '.macapgiaidau',
            $levelAlias . '.tencapgiaidau',
            $levelAlias . '.capkhuvucphamvi',
        ];

        $comparisons = array_map(
            fn (string $candidate): string => $refereeLevel . ' = ' . $this->normalizedSql($candidate),
            $levelCandidates
        );

        return '(' . implode(' OR ', $comparisons) . ')';
    }

    private function normalizedSql(string $expression): string
    {
        return "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(CONVERT({$expression} USING utf8mb4), '')), ' ', '_'), '-', '_'), '/', '_'), '__', '_')) COLLATE utf8mb4_unicode_ci";
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

    private function recordStatusHistory(string $targetType, int $targetId, ?string $oldStatus, string $newStatus, ?string $reason, ?int $actorId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Nhatkytrangthai (loaidoituong, iddoituong, trangthaicu, trangthaimoi, lydo, idnguoithuchien)
             VALUES (:target_type, :target_id, :old_status, :new_status, :reason, :actor_id)"
        );

        $statement->execute([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'actor_id' => $actorId,
        ]);
    }

    private function recordAutomaticMatchStatus(string $whereSql, array $bindings, string $newStatus, string $reason): void
    {
        $history = $this->db()->prepare(
            "INSERT INTO Nhatkytrangthai (loaidoituong, iddoituong, trangthaicu, trangthaimoi, lydo, idnguoithuchien)
             SELECT 'TRAN_DAU', td.idtrandau, td.trangthai, :history_new_status, :history_reason, NULL
             FROM Trandau td
             WHERE {$whereSql}"
        );
        $history->execute($bindings + [
            'history_new_status' => $newStatus,
            'history_reason' => $reason,
        ]);

        $statement = $this->db()->prepare(
            "UPDATE Trandau td
             SET td.trangthai = :new_status,
                 td.ngaycapnhat = CURRENT_TIMESTAMP
             WHERE {$whereSql}"
        );
        $statement->execute($bindings + ['new_status' => $newStatus]);
    }
}
