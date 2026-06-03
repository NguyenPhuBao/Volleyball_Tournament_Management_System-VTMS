<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Baocaosuco extends Model
{
    public function listForReferee(int $refereeId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForReferee($refereeId, $filters);

        $statement = $this->db()->prepare(
            $this->baseSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY bc.ngaybaocao DESC, bc.idbaocao DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function statsForReferee(int $refereeId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForReferee($refereeId, $filters);

        $statement = $this->db()->prepare(
            'SELECT bc.trangthai, COUNT(*) AS total
             FROM Baocaosuco bc
             JOIN Trandau td ON td.idtrandau = bc.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             JOIN Sandau sd ON sd.idsandau = td.idsandau
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY bc.trangthai'
        );
        $statement->execute($bindings);

        $stats = [
            'DA_GUI' => 0,
            'DA_TIEP_NHAN' => 0,
            'DA_XU_LY' => 0,
            'TU_CHOI' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $stats[(string) $row['trangthai']] = (int) $row['total'];
        }

        return $stats;
    }

    public function findForReferee(int $refereeId, int $reportId): ?array
    {
        return $this->first(
            $this->baseSelect() . '
             WHERE bc.idtrongtai = :referee_id
               AND bc.idbaocao = :report_id
             LIMIT 1',
            [
                'referee_id' => $refereeId,
                'report_id' => $reportId,
            ]
        );
    }

    public function reportableMatchesForReferee(int $refereeId, array $filters = []): array
    {
        $where = ['pctt.idtrongtai = :referee_id'];
        $bindings = ['referee_id' => $refereeId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(gd.tengiaidau LIKE :keyword
                OR vd.tenvongdau LIKE :keyword
                OR d1.tendoibong LIKE :keyword
                OR d2.tendoibong LIKE :keyword
                OR sd.tensandau LIKE :keyword
                OR vt.diachi LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['match_status'] ?? '') !== '') {
            $where[] = 'td.trangthai = :match_status';
            $bindings['match_status'] = $filters['match_status'];
        }

        $statement = $this->db()->prepare(
            "SELECT DISTINCT
                td.idtrandau,
                td.idgiaidau,
                gd.tengiaidau,
                td.idbangdau,
                bd.tenbang,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                td.idsandau,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai AS trandau_trangthai,
                pctt.idphancong,
                pctt.vaitro,
                pctt.trangthai AS phancong_trangthai
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE " . implode(' AND ', $where) . '
             ORDER BY td.thoigianbatdau DESC, td.idtrandau DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function createReport(
        array $report,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Baocaosuco (idtrandau, idtrongtai, tieude, noidung, minhchung, trangthai)
                 VALUES (:match_id, :referee_id, :title, :content, :evidence, 'DA_GUI')"
            );
            $statement->execute([
                'match_id' => $report['idtrandau'],
                'referee_id' => $report['idtrongtai'],
                'title' => $report['tieude'],
                'content' => $report['noidung'],
                'evidence' => $report['minhchung'],
            ]);

            $reportId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Sukientrandau (idtrandau, loaisukien, noidung, idnguoitao)
                 VALUES (:match_id, 'SU_CO', :content, :actor_id)"
            );
            $statement->execute([
                'match_id' => $report['idtrandau'],
                'content' => $this->limitText((string) $report['tieude'] . ': ' . (string) $report['noidung'], 1000),
                'actor_id' => $actorAccountId,
            ]);

            $this->recordSystemLog($actorAccountId, 'Bao cao su co', 'Baocaosuco', $reportId, $ipAddress, $logNote);

            $db->commit();

            return $reportId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function recordIncidentReportListView(
        int $refereeId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem danh sach bao cao su co', 'Trongtai', $refereeId, $ipAddress, $logNote);
    }

    public function recordIncidentReportDetailView(
        int $reportId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem chi tiet bao cao su co', 'Baocaosuco', $reportId, $ipAddress, $logNote);
    }

    public function recordReportableMatchListView(
        int $refereeId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem danh sach tran dau co the bao cao su co', 'Trongtai', $refereeId, $ipAddress, $logNote);
    }

    private function whereForReferee(int $refereeId, array $filters): array
    {
        $where = ['bc.idtrongtai = :referee_id'];
        $bindings = ['referee_id' => $refereeId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(bc.tieude LIKE :keyword
                OR bc.noidung LIKE :keyword
                OR bc.minhchung LIKE :keyword
                OR gd.tengiaidau LIKE :keyword
                OR vd.tenvongdau LIKE :keyword
                OR d1.tendoibong LIKE :keyword
                OR d2.tendoibong LIKE :keyword
                OR sd.tensandau LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'bc.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['match_id'] ?? null) !== null) {
            $where[] = 'bc.idtrandau = :match_id';
            $bindings['match_id'] = (int) $filters['match_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'bc.ngaybaocao >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'bc.ngaybaocao <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function baseSelect(): string
    {
        return "SELECT
                bc.idbaocao,
                bc.idtrandau,
                bc.idtrongtai,
                bc.tieude,
                bc.noidung,
                bc.minhchung,
                bc.trangthai,
                bc.ngaybaocao,
                td.idgiaidau,
                gd.tengiaidau,
                td.idbangdau,
                bd.tenbang,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                td.idsandau,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai AS trandau_trangthai
             FROM Baocaosuco bc
             JOIN Trandau td ON td.idtrandau = bc.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau";
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

    private function limitText(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 3) . '...';
    }
}
