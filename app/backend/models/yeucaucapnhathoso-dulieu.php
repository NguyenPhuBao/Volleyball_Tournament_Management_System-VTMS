<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use PDO;
use RuntimeException;
use Throwable;

final class Yeucaucapnhathoso extends Model
{
    public function listPersonalChangeRequests(array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->buildPersonalWhere($filters);
        $sql = $this->basePersonalSelect();

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY yc.ngaygui DESC, yc.idyeucaucapnhat DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countPersonalChangeRequests(array $filters): int
    {
        [$where, $bindings] = $this->buildPersonalWhere($filters);

        $sql = "SELECT COUNT(*) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Quantrivien qtv ON qtv.idnguoidung = nd.idnguoidung
            LEFT JOIN Trongtai tt ON tt.idnguoidung = nd.idnguoidung
            LEFT JOIN Huanluyenvien hlv ON hlv.idnguoidung = nd.idnguoidung";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->execute();
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function statusCountsPersonalChangeRequests(array $filters): array
    {
        [$where, $bindings] = $this->buildPersonalWhere($filters);

        $sql = "SELECT yc.trangthai, COUNT(*) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Quantrivien qtv ON qtv.idnguoidung = nd.idnguoidung
            LEFT JOIN Trongtai tt ON tt.idnguoidung = nd.idnguoidung
            LEFT JOIN Huanluyenvien hlv ON hlv.idnguoidung = nd.idnguoidung";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY yc.trangthai';

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->execute();

        $counts = [
            'CHO_DUYET' => 0,
            'DA_DUYET' => 0,
            'TU_CHOI' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $status = (string) $row['trangthai'];

            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    public function findPersonalChangeRequestById(int $requestId): ?array
    {
        return $this->first(
            $this->basePersonalSelect() . ' WHERE yc.idyeucaucapnhat = :request_id AND ' . implode(' AND ', $this->personalBaseWhere()) . ' LIMIT 1',
            ['request_id' => $requestId]
        );
    }

    public function listOrganizerChangeRequests(array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->buildOrganizerWhere($filters);
        $sql = $this->baseOrganizerSelect();

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY yc.ngaygui DESC, yc.idyeucaucapnhat DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countOrganizerChangeRequests(array $filters): int
    {
        [$where, $bindings] = $this->buildOrganizerWhere($filters);

        $sql = "SELECT COUNT(*) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->execute();
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function statusCountsOrganizerChangeRequests(array $filters): array
    {
        [$where, $bindings] = $this->buildOrganizerWhere($filters);

        $sql = "SELECT yc.trangthai, COUNT(*) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY yc.trangthai';

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->execute();

        $counts = [
            'CHO_DUYET' => 0,
            'DA_DUYET' => 0,
            'TU_CHOI' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $status = (string) $row['trangthai'];

            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    public function findOrganizerChangeRequestById(int $requestId): ?array
    {
        return $this->first(
            $this->baseOrganizerSelect() . ' WHERE yc.banglienquan = :target_table AND yc.idyeucaucapnhat = :request_id LIMIT 1',
            [
                'target_table' => 'Bantochuc',
                'request_id' => $requestId,
            ]
        );
    }

    public function listAthleteChangeRequestsForCoach(int $coachId, array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->buildAthleteCoachWhere($coachId, $filters);
        $sql = $this->baseAthleteCoachSelect() . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY yc.ngaygui DESC, yc.idyeucaucapnhat DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countAthleteChangeRequestsForCoach(int $coachId, array $filters): int
    {
        [$where, $bindings] = $this->buildAthleteCoachWhere($coachId, $filters);
        $sql = "SELECT COUNT(DISTINCT yc.idyeucaucapnhat) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Vandongvien vdv ON vdv.idnguoidung = yc.idnguoidung
            JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Thanhviendoibong tv ON tv.idvandongvien = vdv.idvandongvien
            JOIN Doibong db ON db.iddoibong = tv.iddoibong
            LEFT JOIN Thanhviendoibong tv_filter ON tv_filter.idvandongvien = vdv.idvandongvien
            LEFT JOIN Doibong db_filter ON db_filter.iddoibong = tv_filter.iddoibong";

        $statement = $this->db()->prepare($sql . ' WHERE ' . implode(' AND ', $where));
        $this->bindWhere($statement, $bindings);
        $statement->execute();
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function statusCountsAthleteChangeRequestsForCoach(int $coachId, array $filters): array
    {
        [$where, $bindings] = $this->buildAthleteCoachWhere($coachId, $filters);
        $sql = "SELECT yc.trangthai, COUNT(DISTINCT yc.idyeucaucapnhat) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Vandongvien vdv ON vdv.idnguoidung = yc.idnguoidung
            JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Thanhviendoibong tv ON tv.idvandongvien = vdv.idvandongvien
            JOIN Doibong db ON db.iddoibong = tv.iddoibong
            LEFT JOIN Thanhviendoibong tv_filter ON tv_filter.idvandongvien = vdv.idvandongvien
            LEFT JOIN Doibong db_filter ON db_filter.iddoibong = tv_filter.iddoibong
            WHERE " . implode(' AND ', $where) . "
            GROUP BY yc.trangthai";

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->execute();

        $counts = [
            'CHO_DUYET' => 0,
            'DA_DUYET' => 0,
            'TU_CHOI' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $status = (string) $row['trangthai'];

            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    public function findAthleteChangeRequestForCoach(int $coachId, int $requestId): ?array
    {
        return $this->first(
            $this->baseAthleteCoachSelect() . "
             WHERE yc.idyeucaucapnhat = :request_id
               AND db.idhuanluyenvien = :coach_id
               AND tv.trangthai IN ('CHO_XAC_NHAN','DANG_THAM_GIA')
               AND r.namerole = 'VAN_DONG_VIEN'
               AND (
                    yc.banglienquan IN ('Nguoidung','Taikhoan')
                    OR yc.banglienquan = 'Vandongvien'
               )
             LIMIT 1",
            [
                'request_id' => $requestId,
                'coach_id' => $coachId,
            ]
        );
    }

    public function approveOrganizerChangeRequest(
        int $requestId,
        int $organizerId,
        string $field,
        mixed $newValue,
        int $adminId,
        ?string $ipAddress,
        ?string $note
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->updateOrganizerField($organizerId, $field, $newValue);
            $this->markRequestProcessed($requestId, 'DA_DUYET');
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', 'DA_DUYET', 'Duyet thay doi thong tin ban to chuc', $adminId);
            $this->recordSystemLog($adminId, 'Duyet thay doi thong tin ban to chuc', 'Bantochuc', $organizerId, $ipAddress, $note);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function rejectOrganizerChangeRequest(
        int $requestId,
        int $adminId,
        ?string $ipAddress,
        ?string $note
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->markRequestProcessed($requestId, 'TU_CHOI');
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', 'TU_CHOI', $note ?: 'Tu choi thay doi thong tin ban to chuc', $adminId);
            $this->recordSystemLog($adminId, 'Tu choi thay doi thong tin ban to chuc', 'Yeucaucapnhathoso', $requestId, $ipAddress, $note);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function approvePersonalChangeRequest(
        int $requestId,
        int $userId,
        string $targetTable,
        string $field,
        mixed $newValue,
        int $targetId,
        int $actorAccountId,
        ?string $ipAddress,
        ?string $note
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->updatePersonalField($userId, $targetTable, $field, $newValue);
            $this->markPersonalRequestProcessed($requestId, 'DA_DUYET');
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', 'DA_DUYET', 'Duyet thay doi thong tin ca nhan', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Duyet thay doi thong tin ca nhan', $targetTable, $targetId, $ipAddress, $note);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function rejectPersonalChangeRequest(
        int $requestId,
        int $actorAccountId,
        ?string $ipAddress,
        ?string $note
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->markPersonalRequestProcessed($requestId, 'TU_CHOI');
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', 'TU_CHOI', $note ?: 'Huy thay doi thong tin ca nhan', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Huy thay doi thong tin ca nhan', 'Yeucaucapnhathoso', $requestId, $ipAddress, $note);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function personalUniqueValueExists(string $targetTable, string $field, string $value, int $excludeUserId): bool
    {
        if ($value === '') {
            return false;
        }

        if ($targetTable === 'Nguoidung' && $field === 'cccd') {
            return $this->first(
                "SELECT 1
                 FROM Nguoidung
                 WHERE cccd = :value
                   AND idnguoidung <> :exclude_user_id
                 LIMIT 1",
                [
                    'value' => $value,
                    'exclude_user_id' => $excludeUserId,
                ]
            ) !== null;
        }

        if ($targetTable === 'Taikhoan' && in_array($field, ['username', 'email', 'sodienthoai'], true)) {
            return $this->first(
                "SELECT 1
                 FROM Taikhoan tk
                 JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
                 WHERE tk.{$field} = :value
                   AND nd.idnguoidung <> :exclude_user_id
                 LIMIT 1",
                [
                    'value' => $value,
                    'exclude_user_id' => $excludeUserId,
                ]
            ) !== null;
        }

        if ($targetTable === 'Vandongvien' && $field === 'mavandongvien') {
            return $this->first(
                "SELECT 1
                 FROM Vandongvien
                 WHERE mavandongvien = :value
                   AND idnguoidung <> :exclude_user_id
                 LIMIT 1",
                [
                    'value' => $value,
                    'exclude_user_id' => $excludeUserId,
                ]
            ) !== null;
        }

        return false;
    }

    private function updateOrganizerField(int $organizerId, string $field, mixed $value): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Bantochuc
             SET {$field} = :value
             WHERE idbantochuc = :organizer_id"
        );

        $statement->execute([
            'value' => $value,
            'organizer_id' => $organizerId,
        ]);
    }

    private function updatePersonalField(int $userId, string $targetTable, string $field, mixed $value): void
    {
        $sql = match ($targetTable) {
            'Nguoidung' => "UPDATE Nguoidung
                 SET {$field} = :value,
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idnguoidung = :user_id",
            'Taikhoan' => "UPDATE Taikhoan tk
                 JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
                 SET tk.{$field} = :value,
                     tk.ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE nd.idnguoidung = :user_id",
            'Quantrivien' => "UPDATE Quantrivien
                 SET {$field} = :value
                 WHERE idnguoidung = :user_id",
            'Trongtai' => "UPDATE Trongtai
                 SET {$field} = :value
                 WHERE idnguoidung = :user_id",
            'Huanluyenvien' => "UPDATE Huanluyenvien
                 SET {$field} = :value
                 WHERE idnguoidung = :user_id",
            'Vandongvien' => "UPDATE Vandongvien
                 SET {$field} = :value
                 WHERE idnguoidung = :user_id",
            default => throw new RuntimeException('INVALID_TARGET_TABLE'),
        };

        $statement = $this->db()->prepare($sql);
        $statement->execute([
            'value' => $value,
            'user_id' => $userId,
        ]);

        if ($statement->rowCount() < 1) {
            throw new RuntimeException('TARGET_NOT_UPDATED');
        }
    }

    private function markPersonalRequestProcessed(int $requestId, string $status): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Yeucaucapnhathoso
             SET trangthai = :status,
                 ngayxuly = CURRENT_TIMESTAMP
             WHERE idyeucaucapnhat = :request_id
               AND trangthai = 'CHO_DUYET'"
        );

        $statement->execute([
            'status' => $status,
            'request_id' => $requestId,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('REQUEST_NOT_PENDING');
        }
    }

    private function markRequestProcessed(int $requestId, string $status): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Yeucaucapnhathoso
             SET trangthai = :status,
                 ngayxuly = CURRENT_TIMESTAMP
             WHERE idyeucaucapnhat = :request_id
               AND banglienquan = 'Bantochuc'
               AND trangthai = 'CHO_DUYET'"
        );

        $statement->execute([
            'status' => $status,
            'request_id' => $requestId,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('REQUEST_NOT_PENDING');
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

    private function basePersonalSelect(): string
    {
        return "SELECT
                yc.idyeucaucapnhat,
                yc.idnguoidung,
                yc.banglienquan,
                yc.truongcapnhat,
                yc.giatricu,
                yc.giatrimoi,
                yc.lydo,
                yc.trangthai,
                yc.ngaygui,
                yc.ngayxuly,
                tk.idtaikhoan,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.trangthai AS taikhoan_trangthai,
                r.namerole AS role,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd,
                qtv.idquantrivien,
                qtv.machucvu,
                qtv.ghichu AS quantrivien_ghichu,
                tt.idtrongtai,
                tt.capbac,
                tt.kinhnghiem AS trongtai_kinhnghiem,
                tt.trangthai AS trongtai_trangthai,
                hlv.idhuanluyenvien,
                hlv.bangcap,
                hlv.kinhnghiem AS huanluyenvien_kinhnghiem,
                hlv.trangthai AS huanluyenvien_trangthai,
                nd.ten AS current_ten,
                nd.hodem AS current_hodem,
                nd.gioitinh AS current_gioitinh,
                nd.ngaysinh AS current_ngaysinh,
                nd.quequan AS current_quequan,
                nd.diachi AS current_diachi,
                nd.avatar AS current_avatar,
                nd.cccd AS current_cccd,
                tk.username AS current_username,
                tk.email AS current_email,
                tk.sodienthoai AS current_sodienthoai,
                qtv.machucvu AS current_machucvu,
                qtv.ghichu AS current_ghichu,
                tt.capbac AS current_capbac,
                tt.kinhnghiem AS current_kinhnghiem_trongtai,
                hlv.bangcap AS current_bangcap,
                hlv.kinhnghiem AS current_kinhnghiem_huanluyenvien,
                CASE
                    WHEN yc.banglienquan = 'Taikhoan' THEN tk.idtaikhoan
                    WHEN yc.banglienquan = 'Quantrivien' THEN qtv.idquantrivien
                    WHEN yc.banglienquan = 'Trongtai' THEN tt.idtrongtai
                    WHEN yc.banglienquan = 'Huanluyenvien' THEN hlv.idhuanluyenvien
                    ELSE nd.idnguoidung
                END AS iddoituong
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Quantrivien qtv ON qtv.idnguoidung = nd.idnguoidung
            LEFT JOIN Trongtai tt ON tt.idnguoidung = nd.idnguoidung
            LEFT JOIN Huanluyenvien hlv ON hlv.idnguoidung = nd.idnguoidung";
    }

    private function personalBaseWhere(): array
    {
        return [
            "r.namerole IN ('ADMIN','TRONG_TAI','HUAN_LUYEN_VIEN')",
            "(
                yc.banglienquan IN ('Nguoidung','Taikhoan')
                OR (yc.banglienquan = 'Quantrivien' AND r.namerole = 'ADMIN' AND qtv.idquantrivien IS NOT NULL)
                OR (yc.banglienquan = 'Trongtai' AND r.namerole = 'TRONG_TAI' AND tt.idtrongtai IS NOT NULL)
                OR (yc.banglienquan = 'Huanluyenvien' AND r.namerole = 'HUAN_LUYEN_VIEN' AND hlv.idhuanluyenvien IS NOT NULL)
            )",
        ];
    }

    private function baseAthleteCoachSelect(): string
    {
        return "SELECT DISTINCT
                yc.idyeucaucapnhat,
                yc.idnguoidung,
                yc.banglienquan,
                yc.truongcapnhat,
                yc.giatricu,
                yc.giatrimoi,
                yc.lydo,
                yc.trangthai,
                yc.ngaygui,
                yc.ngayxuly,
                tk.idtaikhoan,
                tk.username,
                tk.email,
                tk.sodienthoai,
                r.namerole AS role,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd,
                vdv.idvandongvien,
                vdv.mavandongvien,
                vdv.chieucao,
                vdv.cannang,
                vdv.vitri,
                vdv.trangthaidaugiai,
                db.iddoibong,
                db.tendoibong,
                tv.idthanhvien,
                tv.vaitro AS vaitrotrongdoi,
                tv.trangthai AS trangthaithanhvien,
                nd.ten AS current_ten,
                nd.hodem AS current_hodem,
                nd.gioitinh AS current_gioitinh,
                nd.ngaysinh AS current_ngaysinh,
                nd.quequan AS current_quequan,
                nd.diachi AS current_diachi,
                nd.avatar AS current_avatar,
                nd.cccd AS current_cccd,
                tk.username AS current_username,
                tk.email AS current_email,
                tk.sodienthoai AS current_sodienthoai,
                vdv.mavandongvien AS current_mavandongvien,
                vdv.chieucao AS current_chieucao,
                vdv.cannang AS current_cannang,
                vdv.vitri AS current_vitri,
                CASE
                    WHEN yc.banglienquan = 'Taikhoan' THEN tk.idtaikhoan
                    WHEN yc.banglienquan = 'Vandongvien' THEN vdv.idvandongvien
                    ELSE nd.idnguoidung
                END AS iddoituong
            FROM Yeucaucapnhathoso yc
            JOIN Vandongvien vdv ON vdv.idnguoidung = yc.idnguoidung
            JOIN Nguoidung nd ON nd.idnguoidung = vdv.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Thanhviendoibong tv ON tv.idvandongvien = vdv.idvandongvien
            JOIN Doibong db ON db.iddoibong = tv.iddoibong
            LEFT JOIN Thanhviendoibong tv_filter ON tv_filter.idvandongvien = vdv.idvandongvien
            LEFT JOIN Doibong db_filter ON db_filter.iddoibong = tv_filter.iddoibong";
    }

    private function baseOrganizerSelect(): string
    {
        return "SELECT
                yc.idyeucaucapnhat,
                yc.idnguoidung,
                yc.banglienquan,
                yc.truongcapnhat,
                yc.giatricu,
                yc.giatrimoi,
                yc.lydo,
                yc.trangthai,
                yc.ngaygui,
                yc.ngayxuly,
                btc.idbantochuc,
                btc.donvi AS current_donvi,
                btc.chucvu AS current_chucvu,
                btc.trangthai AS current_trangthai,
                tk.idtaikhoan,
                tk.username,
                tk.email,
                r.namerole AS role,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung";
    }

    private function buildOrganizerWhere(array $filters): array
    {
        $where = ['yc.banglienquan = :target_table'];
        $bindings = ['target_table' => 'Bantochuc'];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(yc.truongcapnhat LIKE :q_field
                OR yc.giatricu LIKE :q_old_value
                OR yc.giatrimoi LIKE :q_new_value
                OR yc.lydo LIKE :q_reason
                OR tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten
                OR btc.donvi LIKE :q_unit
                OR btc.chucvu LIKE :q_position)";
            $like = '%' . $filters['q'] . '%';
            $bindings['q_field'] = $like;
            $bindings['q_old_value'] = $like;
            $bindings['q_new_value'] = $like;
            $bindings['q_reason'] = $like;
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
            $bindings['q_unit'] = $like;
            $bindings['q_position'] = $like;
        }

        if (($filters['trangthai'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :status';
            $bindings['status'] = $filters['trangthai'];
        }

        if (($filters['truongcapnhat'] ?? '') !== '') {
            $where[] = 'yc.truongcapnhat = :field';
            $bindings['field'] = $filters['truongcapnhat'];
        }

        if (($filters['idnguoidung'] ?? null) !== null) {
            $where[] = 'yc.idnguoidung = :user_id';
            $bindings['user_id'] = (int) $filters['idnguoidung'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'yc.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'yc.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function buildAthleteCoachWhere(int $coachId, array $filters): array
    {
        $where = [
            'db.idhuanluyenvien = :coach_id',
            "tv.trangthai IN ('CHO_XAC_NHAN','DANG_THAM_GIA')",
            "r.namerole = 'VAN_DONG_VIEN'",
            "(
                yc.banglienquan IN ('Nguoidung','Taikhoan')
                OR yc.banglienquan = 'Vandongvien'
            )",
        ];
        $bindings = ['coach_id' => $coachId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(yc.banglienquan LIKE :q_target
                OR yc.truongcapnhat LIKE :q_field
                OR yc.giatricu LIKE :q_old_value
                OR yc.giatrimoi LIKE :q_new_value
                OR yc.lydo LIKE :q_reason
                OR tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR tk.sodienthoai LIKE :q_phone
                OR nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten
                OR vdv.mavandongvien LIKE :q_athlete_code
                OR db.tendoibong LIKE :q_team_name)";
            $like = '%' . $filters['q'] . '%';
            $bindings['q_target'] = $like;
            $bindings['q_field'] = $like;
            $bindings['q_old_value'] = $like;
            $bindings['q_new_value'] = $like;
            $bindings['q_reason'] = $like;
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_phone'] = $like;
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
            $bindings['q_athlete_code'] = $like;
            $bindings['q_team_name'] = $like;
        }

        if (($filters['trangthai'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :status';
            $bindings['status'] = $filters['trangthai'];
        }

        if (($filters['banglienquan'] ?? '') !== '') {
            $where[] = 'yc.banglienquan = :target_table';
            $bindings['target_table'] = $filters['banglienquan'];
        }

        if (($filters['truongcapnhat'] ?? '') !== '') {
            $where[] = 'yc.truongcapnhat = :field';
            $bindings['field'] = $filters['truongcapnhat'];
        }

        if (($filters['iddoibong'] ?? null) !== null) {
            $where[] = 'db_filter.iddoibong = :team_id';
            $bindings['team_id'] = (int) $filters['iddoibong'];
        }

        if (($filters['idvandongvien'] ?? null) !== null) {
            $where[] = 'vdv.idvandongvien = :athlete_id';
            $bindings['athlete_id'] = (int) $filters['idvandongvien'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'yc.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'yc.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function buildPersonalWhere(array $filters): array
    {
        $where = $this->personalBaseWhere();
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(yc.banglienquan LIKE :q_target
                OR yc.truongcapnhat LIKE :q_field
                OR yc.giatricu LIKE :q_old_value
                OR yc.giatrimoi LIKE :q_new_value
                OR yc.lydo LIKE :q_reason
                OR tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR tk.sodienthoai LIKE :q_phone
                OR nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten
                OR qtv.machucvu LIKE :q_admin_position
                OR tt.capbac LIKE :q_referee_level
                OR hlv.bangcap LIKE :q_coach_degree)";
            $like = '%' . $filters['q'] . '%';
            $bindings['q_target'] = $like;
            $bindings['q_field'] = $like;
            $bindings['q_old_value'] = $like;
            $bindings['q_new_value'] = $like;
            $bindings['q_reason'] = $like;
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_phone'] = $like;
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
            $bindings['q_admin_position'] = $like;
            $bindings['q_referee_level'] = $like;
            $bindings['q_coach_degree'] = $like;
        }

        if (($filters['trangthai'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :status';
            $bindings['status'] = $filters['trangthai'];
        }

        if (($filters['role'] ?? '') !== '') {
            $where[] = 'r.namerole = :role';
            $bindings['role'] = $filters['role'];
        }

        if (($filters['banglienquan'] ?? '') !== '') {
            $where[] = 'yc.banglienquan = :target_table';
            $bindings['target_table'] = $filters['banglienquan'];
        }

        if (($filters['truongcapnhat'] ?? '') !== '') {
            $where[] = 'yc.truongcapnhat = :field';
            $bindings['field'] = $filters['truongcapnhat'];
        }

        if (($filters['idnguoidung'] ?? null) !== null) {
            $where[] = 'yc.idnguoidung = :user_id';
            $bindings['user_id'] = (int) $filters['idnguoidung'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'yc.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'yc.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function bindWhere(\PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $name => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue($name, $value, $type);
        }
    }
}
