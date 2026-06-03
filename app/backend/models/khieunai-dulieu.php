<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Khieunai extends Model
{
    public function listForOrganizer(int $organizerId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForOrganizer($organizerId, $filters);

        $statement = $this->db()->prepare(
            $this->baseSelect() . '
             WHERE ' . implode(' AND ', $where) . "
             ORDER BY
                CASE kn.trangthai
                    WHEN 'CHO_TIEP_NHAN' THEN 1
                    WHEN 'DANG_XU_LY' THEN 2
                    WHEN 'DA_XU_LY' THEN 3
                    WHEN 'KHONG_XU_LY' THEN 4
                    WHEN 'TU_CHOI' THEN 5
                    ELSE 9
                END,
                kn.ngaygui DESC,
                kn.idkhieunai DESC"
        );

        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function statsForOrganizer(int $organizerId, array $filters = []): array
    {
        [$where, $bindings] = $this->whereForOrganizer($organizerId, $filters);

        $statement = $this->db()->prepare(
            'SELECT kn.trangthai, COUNT(*) AS total
             FROM Khieunai kn
             JOIN Giaidau gd ON gd.idgiaidau = kn.idgiaidau
             JOIN Taikhoan sender ON sender.idtaikhoan = kn.idnguoigui
             LEFT JOIN Nguoidung sender_nd ON sender_nd.idtaikhoan = sender.idtaikhoan
             LEFT JOIN Trandau td ON td.idtrandau = kn.idtrandau
             LEFT JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY kn.trangthai'
        );
        $statement->execute($bindings);

        $stats = [
            'CHO_TIEP_NHAN' => 0,
            'DANG_XU_LY' => 0,
            'DA_XU_LY' => 0,
            'TU_CHOI' => 0,
            'KHONG_XU_LY' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $stats[(string) $row['trangthai']] = (int) $row['total'];
        }

        return $stats;
    }

    public function findForOrganizer(int $organizerId, int $complaintId): ?array
    {
        return $this->first(
            $this->baseSelect() . '
             WHERE gd.idbantochuc = :organizer_id
               AND kn.idkhieunai = :complaint_id
             LIMIT 1',
            [
                'organizer_id' => $organizerId,
                'complaint_id' => $complaintId,
            ]
        );
    }

    public function createForMatchResult(
        int $senderAccountId,
        int $tournamentId,
        int $matchId,
        string $title,
        string $content,
        ?string $evidence,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Khieunai (idnguoigui, idgiaidau, idtrandau, tieude, noidung, minhchung, trangthai)
                 VALUES (:sender_id, :tournament_id, :match_id, :title, :content, :evidence, 'CHO_TIEP_NHAN')"
            );
            $statement->execute([
                'sender_id' => $senderAccountId,
                'tournament_id' => $tournamentId,
                'match_id' => $matchId,
                'title' => $title,
                'content' => $content,
                'evidence' => $evidence,
            ]);

            $complaintId = (int) $db->lastInsertId();
            $this->recordStatusHistory('KHIEU_NAI', $complaintId, null, 'CHO_TIEP_NHAN', 'HLV gui khieu nai ket qua tran dau', $senderAccountId);
            $this->recordSystemLog($senderAccountId, 'Gui khieu nai ket qua tran dau', 'Khieunai', $complaintId, $ipAddress, $logNote);

            $db->commit();

            return $complaintId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function updateStatus(
        int $complaintId,
        string $oldStatus,
        string $newStatus,
        int $actorAccountId,
        ?string $ipAddress,
        string $systemLogNote,
        string $statusReason,
        bool $setProcessedAt
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $processedAtSql = $setProcessedAt ? ', ngayxuly = CURRENT_TIMESTAMP' : '';
            $statement = $db->prepare(
                "UPDATE Khieunai
                 SET trangthai = :new_status,
                     idnguoixuly = :actor_id
                     {$processedAtSql}
                 WHERE idkhieunai = :complaint_id
                   AND trangthai = :old_status"
            );
            $statement->execute([
                'new_status' => $newStatus,
                'actor_id' => $actorAccountId,
                'complaint_id' => $complaintId,
                'old_status' => $oldStatus,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('COMPLAINT_STATUS_NOT_UPDATED');
            }

            $this->recordStatusHistory('KHIEU_NAI', $complaintId, $oldStatus, $newStatus, $statusReason, $actorAccountId);
            $this->recordSystemLog($actorAccountId, $this->actionName($newStatus), 'Khieunai', $complaintId, $ipAddress, $systemLogNote);

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
            $where[] = "(kn.tieude LIKE :keyword_title
                OR kn.noidung LIKE :keyword_content
                OR kn.minhchung LIKE :keyword_evidence
                OR gd.tengiaidau LIKE :keyword_tournament
                OR td.ma_tran LIKE :keyword_match_code
                OR td.ten_tran LIKE :keyword_match_name
                OR d1.tendoibong LIKE :keyword_team_one
                OR d2.tendoibong LIKE :keyword_team_two
                OR sender.username LIKE :keyword_username
                OR CONCAT(COALESCE(sender_nd.hodem, ''), ' ', COALESCE(sender_nd.ten, '')) LIKE :keyword_fullname)";
            $keyword = '%' . $filters['q'] . '%';
            $bindings['keyword_title'] = $keyword;
            $bindings['keyword_content'] = $keyword;
            $bindings['keyword_evidence'] = $keyword;
            $bindings['keyword_tournament'] = $keyword;
            $bindings['keyword_match_code'] = $keyword;
            $bindings['keyword_match_name'] = $keyword;
            $bindings['keyword_team_one'] = $keyword;
            $bindings['keyword_team_two'] = $keyword;
            $bindings['keyword_username'] = $keyword;
            $bindings['keyword_fullname'] = $keyword;
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'kn.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'kn.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['match_id'] ?? null) !== null) {
            $where[] = 'kn.idtrandau = :match_id';
            $bindings['match_id'] = (int) $filters['match_id'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'kn.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'kn.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function baseSelect(): string
    {
        return "SELECT
                kn.idkhieunai,
                kn.idnguoigui,
                sender.username AS nguoigui_username,
                sender.email AS nguoigui_email,
                TRIM(CONCAT(COALESCE(sender_nd.hodem, ''), ' ', COALESCE(sender_nd.ten, ''))) AS nguoigui_hoten,
                kn.idgiaidau,
                gd.tengiaidau,
                gd.idbantochuc,
                kn.idtrandau,
                td.ma_tran,
                td.ten_tran,
                vd.tenvongdau AS trandau_vong,
                td.iddoibong1,
                td.iddoibong2,
                d1.tendoibong AS trandau_doi1,
                d2.tendoibong AS trandau_doi2,
                kq.idketqua,
                kq.iddoithang,
                kq.diemdoi1,
                kq.diemdoi2,
                kq.sosetdoi1,
                kq.sosetdoi2,
                kq.trangthai AS ketqua_trangthai,
                kn.tieude,
                kn.noidung,
                kn.minhchung,
                kn.trangthai,
                kn.ngaygui,
                kn.ngayxuly,
                kn.idnguoixuly,
                handler.username AS nguoixuly_username,
                TRIM(CONCAT(COALESCE(handler_nd.hodem, ''), ' ', COALESCE(handler_nd.ten, ''))) AS nguoixuly_hoten
             FROM Khieunai kn
             JOIN Giaidau gd ON gd.idgiaidau = kn.idgiaidau
             JOIN Taikhoan sender ON sender.idtaikhoan = kn.idnguoigui
             LEFT JOIN Nguoidung sender_nd ON sender_nd.idtaikhoan = sender.idtaikhoan
              LEFT JOIN Trandau td ON td.idtrandau = kn.idtrandau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             LEFT JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau
             LEFT JOIN Taikhoan handler ON handler.idtaikhoan = kn.idnguoixuly
             LEFT JOIN Nguoidung handler_nd ON handler_nd.idtaikhoan = handler.idtaikhoan";
    }

    private function actionName(string $newStatus): string
    {
        return match ($newStatus) {
            'DANG_XU_LY' => 'Tiep nhan khieu nai',
            'DA_XU_LY' => 'Xu ly khieu nai',
            'TU_CHOI' => 'Tu choi khieu nai',
            'KHONG_XU_LY' => 'Khong xu ly khieu nai',
            default => 'Cap nhat khieu nai',
        };
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
}
