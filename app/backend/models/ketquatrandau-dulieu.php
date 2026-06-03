<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Ketquatrandau extends Model
{
    public function listForOrganizer(int $organizerId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForOrganizer($organizerId, $filters);

        $statement = $this->db()->prepare(
            $this->baseSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY td.thoigianbatdau DESC, kq.idketqua DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function statsForOrganizer(int $organizerId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForOrganizer($organizerId, $filters);

        $statement = $this->db()->prepare(
            'SELECT kq.trangthai, COUNT(*) AS total
             FROM Ketquatrandau kq
             JOIN Trandau td ON td.idtrandau = kq.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
              LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
              LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
              LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY kq.trangthai'
        );
        $statement->execute($bindings);

        $stats = [
            'CHO_CONG_BO' => 0,
            'DA_DIEU_CHINH' => 0,
            'DA_CONG_BO' => 0,
            'BI_HUY' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $stats[(string) $row['trangthai']] = (int) $row['total'];
        }

        return $stats;
    }

    public function findForOrganizer(int $organizerId, int $resultId): ?array
    {
        return $this->first(
            $this->baseSelect() . '
             WHERE gd.idbantochuc = :organizer_id
               AND kq.idketqua = :result_id
             LIMIT 1',
            [
                'organizer_id' => $organizerId,
                'result_id' => $resultId,
            ]
        );
    }

    public function listForCoach(int $coachId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForCoach($coachId, $filters);

        $statement = $this->db()->prepare(
            $this->baseSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY td.thoigianbatdau DESC, kq.idketqua DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function findForCoach(int $coachId, int $resultId): ?array
    {
        [$where, $bindings] = $this->whereForCoach($coachId, []);
        $where[] = 'kq.idketqua = :result_id';
        $bindings['result_id'] = $resultId;

        return $this->first(
            $this->baseSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1',
            $bindings
        );
    }

    public function setsForResult(int $resultId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                ds.iddiemset,
                ds.idketqua,
                ds.setthu,
                ds.diemdoi1,
                ds.diemdoi2,
                ds.doithangset,
                winner.tendoibong AS doithangset_ten
             FROM Diemset ds
             LEFT JOIN Doibong winner ON winner.iddoibong = ds.doithangset
             WHERE ds.idketqua = :result_id
             ORDER BY ds.setthu"
        );
        $statement->execute(['result_id' => $resultId]);

        return $statement->fetchAll();
    }

    public function adjustmentsForResult(int $resultId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                dc.iddieuchinh,
                dc.idketqua,
                dc.diemcu,
                dc.diemmoi,
                dc.lydo,
                dc.minhchung,
                dc.idnguoichinhsua,
                tk.username AS nguoichinhsua_username,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS nguoichinhsua_hoten,
                dc.ngaychinhsua
             FROM Dieuchinhketqua dc
             LEFT JOIN Taikhoan tk ON tk.idtaikhoan = dc.idnguoichinhsua
             LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
             WHERE dc.idketqua = :result_id
             ORDER BY dc.ngaychinhsua DESC, dc.iddieuchinh DESC"
        );
        $statement->execute(['result_id' => $resultId]);

        return $statement->fetchAll();
    }

    public function adjustResult(
        int $resultId,
        array $result,
        ?array $sets,
        string $oldSnapshot,
        string $newSnapshot,
        string $reason,
        ?string $evidence,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Ketquatrandau
                 SET iddoithang = :winner_team_id,
                     diemdoi1 = :team_one_score,
                     diemdoi2 = :team_two_score,
                     sosetdoi1 = :team_one_sets,
                     sosetdoi2 = :team_two_sets,
                     trangthai = 'DA_DIEU_CHINH',
                     ngaycongbo = NULL,
                     idnguoighinhan = :actor_id
                 WHERE idketqua = :result_id
                   AND trangthai IN ('CHO_CONG_BO', 'DA_CONG_BO', 'DA_DIEU_CHINH')"
            );
            $statement->execute([
                'winner_team_id' => $result['iddoithang'],
                'team_one_score' => $result['diemdoi1'],
                'team_two_score' => $result['diemdoi2'],
                'team_one_sets' => $result['sosetdoi1'],
                'team_two_sets' => $result['sosetdoi2'],
                'actor_id' => $actorAccountId,
                'result_id' => $resultId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('RESULT_NOT_ADJUSTED');
            }

            if ($sets !== null) {
                $this->replaceSets($resultId, $sets);
            }

            $this->recordAdjustment($resultId, $oldSnapshot, $newSnapshot, $reason, $evidence, $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Dieu chinh ket qua tran dau', 'Ketquatrandau', $resultId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function publishResult(
        int $resultId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Ketquatrandau
                 SET trangthai = 'DA_CONG_BO',
                     ngaycongbo = CURRENT_TIMESTAMP,
                     idnguoighinhan = :actor_id
                 WHERE idketqua = :result_id
                   AND trangthai IN ('CHO_CONG_BO', 'DA_DIEU_CHINH')"
            );
            $statement->execute([
                'actor_id' => $actorAccountId,
                'result_id' => $resultId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('RESULT_NOT_PUBLISHED');
            }

            $this->recordSystemLog($actorAccountId, 'Cong bo ket qua tran dau', 'Ketquatrandau', $resultId, $ipAddress, $logNote);

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
        $where = [
            'gd.idbantochuc = :organizer_id',
            "td.trangthai = 'DA_KET_THUC'",
        ];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(gd.tengiaidau LIKE :keyword_tournament
                OR d1.tendoibong LIKE :keyword_team_one
                OR d2.tendoibong LIKE :keyword_team_two
                OR bd.tenbang LIKE :keyword_group
                OR sd.tensandau LIKE :keyword_venue
                OR vd.tenvongdau LIKE :keyword_round)";
            $keyword = '%' . $filters['q'] . '%';
            $bindings['keyword_tournament'] = $keyword;
            $bindings['keyword_team_one'] = $keyword;
            $bindings['keyword_team_two'] = $keyword;
            $bindings['keyword_group'] = $keyword;
            $bindings['keyword_venue'] = $keyword;
            $bindings['keyword_round'] = $keyword;
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'kq.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['match_id'] ?? null) !== null) {
            $where[] = 'td.idtrandau = :match_id';
            $bindings['match_id'] = (int) $filters['match_id'];
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

    private function whereForCoach(int $coachId, array $filters): array
    {
        $where = [
            'EXISTS (
                SELECT 1
                FROM Doibong owned
                WHERE owned.idhuanluyenvien = :coach_id
                  AND owned.iddoibong IN (td.iddoibong1, td.iddoibong2)
            )',
            "td.trangthai = 'DA_KET_THUC'",
            "kq.trangthai IN ('DA_CONG_BO', 'DA_DIEU_CHINH')",
        ];
        $bindings = ['coach_id' => $coachId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(gd.tengiaidau LIKE :keyword_tournament
                OR td.ma_tran LIKE :keyword_match_code
                OR td.ten_tran LIKE :keyword_match_name
                OR d1.tendoibong LIKE :keyword_team_one
                OR d2.tendoibong LIKE :keyword_team_two
                OR bd.tenbang LIKE :keyword_group
                OR sd.tensandau LIKE :keyword_venue
                OR vd.tenvongdau LIKE :keyword_round)";
            $keyword = '%' . $filters['q'] . '%';
            $bindings['keyword_tournament'] = $keyword;
            $bindings['keyword_match_code'] = $keyword;
            $bindings['keyword_match_name'] = $keyword;
            $bindings['keyword_team_one'] = $keyword;
            $bindings['keyword_team_two'] = $keyword;
            $bindings['keyword_group'] = $keyword;
            $bindings['keyword_venue'] = $keyword;
            $bindings['keyword_round'] = $keyword;
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'kq.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['team_id'] ?? null) !== null) {
            $where[] = '(td.iddoibong1 = :team_id OR td.iddoibong2 = :team_id)';
            $bindings['team_id'] = (int) $filters['team_id'];
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
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

    private function baseSelect(): string
    {
        return "SELECT
                kq.idketqua,
                kq.idtrandau,
                td.ma_tran,
                td.ten_tran,
                td.idgiaidau,
                gd.tengiaidau,
                gd.idbantochuc,
                td.idbangdau,
                bd.tenbang,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                td.idsandau,
                sd.tensandau,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai AS trandau_trangthai,
                kq.iddoithang,
                winner.tendoibong AS doithang,
                kq.diemdoi1,
                kq.diemdoi2,
                kq.sosetdoi1,
                kq.sosetdoi2,
                kq.trangthai,
                kq.ngayghinhan,
                kq.ngaycongbo,
                kq.idnguoighinhan,
                recorder.username AS nguoighinhan_username,
                TRIM(CONCAT(COALESCE(recorder_nd.hodem, ''), ' ', COALESCE(recorder_nd.ten, ''))) AS nguoighinhan_hoten
             FROM Ketquatrandau kq
             JOIN Trandau td ON td.idtrandau = kq.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
              LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
              LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
              LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Doibong winner ON winner.iddoibong = kq.iddoithang
             LEFT JOIN Taikhoan recorder ON recorder.idtaikhoan = kq.idnguoighinhan
             LEFT JOIN Nguoidung recorder_nd ON recorder_nd.idtaikhoan = recorder.idtaikhoan";
    }

    private function replaceSets(int $resultId, array $sets): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM Diemset
             WHERE idketqua = :result_id"
        );
        $statement->execute(['result_id' => $resultId]);

        if ($sets === []) {
            return;
        }

        $statement = $this->db()->prepare(
            "INSERT INTO Diemset (idketqua, setthu, diemdoi1, diemdoi2, doithangset)
             VALUES (:result_id, :set_number, :team_one_score, :team_two_score, :winner_team_id)"
        );

        foreach ($sets as $set) {
            $statement->execute([
                'result_id' => $resultId,
                'set_number' => $set['setthu'],
                'team_one_score' => $set['diemdoi1'],
                'team_two_score' => $set['diemdoi2'],
                'winner_team_id' => $set['doithangset'],
            ]);
        }
    }

    private function recordAdjustment(int $resultId, string $oldScore, string $newScore, string $reason, ?string $evidence, int $actorAccountId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Dieuchinhketqua (idketqua, diemcu, diemmoi, lydo, minhchung, idnguoichinhsua)
             VALUES (:result_id, :old_score, :new_score, :reason, :evidence, :actor_id)"
        );
        $statement->execute([
            'result_id' => $resultId,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'reason' => $reason,
            'evidence' => $evidence,
            'actor_id' => $actorAccountId,
        ]);
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
