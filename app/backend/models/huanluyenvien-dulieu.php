<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Huanluyenvien extends Model
{
    public function listForOrganizer(int $organizerId, array $filters = []): array
    {
        [$sql, $bindings] = $this->baseCoachQuery($organizerId, $filters);
        $sql .= ' ORDER BY hlv.idhuanluyenvien DESC';

        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function findForOrganizer(int $organizerId, int $coachId): ?array
    {
        [$sql, $bindings] = $this->baseCoachQuery($organizerId, []);
        $sql .= ' AND hlv.idhuanluyenvien = :coach_id LIMIT 1';
        $bindings['coach_id'] = $coachId;

        return $this->first($sql, $bindings);
    }

    public function accountValueExists(string $field, string $value): bool
    {
        if (!in_array($field, ['username', 'email', 'sodienthoai'], true)) {
            return false;
        }

        return $this->first(
            "SELECT 1
             FROM Taikhoan
             WHERE {$field} = :value
             LIMIT 1",
            ['value' => $value]
        ) !== null;
    }

    public function profileValueExists(string $field, string $value): bool
    {
        if (!in_array($field, ['cccd'], true)) {
            return false;
        }

        return $this->first(
            "SELECT 1
             FROM Nguoidung
             WHERE {$field} = :value
             LIMIT 1",
            ['value' => $value]
        ) !== null;
    }

    public function roleIdByName(string $roleName): ?int
    {
        $role = $this->first(
            "SELECT idrole
             FROM `Role`
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );

        return $role === null ? null : (int) $role['idrole'];
    }

    public function activeWorkRegions(): array
    {
        $statement = $this->db()->query(
            "SELECT DISTINCT
                kv.idkhuvuc,
                kv.makhuvuc,
                kv.tenkhuvuc,
                kv.capkhuvuc,
                kv.idkhuvuccha
             FROM Khuvuc kv
             JOIN Bantochuc btc
               ON btc.idkhuvucquanly = kv.idkhuvuc
              AND btc.trangthai = 'HOAT_DONG'
             JOIN Nguoidung nd ON nd.idnguoidung = btc.idnguoidung
             JOIN Taikhoan tk
               ON tk.idtaikhoan = nd.idtaikhoan
              AND tk.trangthai = 'HOAT_DONG'
             LEFT JOIN Capgiaidau cg ON cg.macapgiaidau = kv.capkhuvuc
             WHERE kv.trangthai = 'HOAT_DONG'
             ORDER BY
                COALESCE(cg.thutu_cap, 9999),
                kv.tenkhuvuc ASC"
        );

        return $statement->fetchAll();
    }

    public function activeWorkRegion(int $regionId): ?array
    {
        return $this->first(
            "SELECT
                idkhuvuc,
                makhuvuc,
                tenkhuvuc,
                capkhuvuc,
                idkhuvuccha
             FROM Khuvuc
             WHERE idkhuvuc = :region_id
               AND trangthai = 'HOAT_DONG'
             LIMIT 1",
            ['region_id' => $regionId]
        );
    }

    public function receivingOrganizer(?int $organizerId = null, ?int $workRegionId = null): ?array
    {
        $bindings = [];
        $where = [
            "btc.trangthai = 'HOAT_DONG'",
            "tk.trangthai = 'HOAT_DONG'",
        ];

        if ($organizerId !== null) {
            $where[] = 'btc.idbantochuc = :organizer_id';
            $bindings['organizer_id'] = $organizerId;
        }

        if ($workRegionId !== null) {
            $where[] = 'btc.idkhuvucquanly = :work_region_id';
            $bindings['work_region_id'] = $workRegionId;
        }

        return $this->first(
            "SELECT
                btc.idbantochuc,
                btc.idnguoidung,
                btc.idkhuvucquanly,
                btc.donvi,
                btc.chucvu,
                btc.trangthai,
                tk.idtaikhoan,
                tk.username,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Bantochuc btc
             JOIN Nguoidung nd ON nd.idnguoidung = btc.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE " . implode(' AND ', $where) . "
             ORDER BY btc.idbantochuc ASC
             LIMIT 1",
            $bindings
        );
    }

    public function nationalFederationOrganizer(): ?array
    {
        return $this->first(
            "SELECT
                btc.idbantochuc,
                btc.idnguoidung,
                btc.idkhuvucquanly,
                btc.donvi,
                btc.chucvu,
                btc.trangthai,
                tk.idtaikhoan,
                tk.username,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Bantochuc btc
             JOIN Nguoidung nd ON nd.idnguoidung = btc.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             JOIN Khuvuc kv ON kv.idkhuvuc = btc.idkhuvucquanly
             JOIN Donvi dv ON dv.iddonvi = btc.iddonvi
             JOIN Loaidonvi ldv ON ldv.idloaidonvi = dv.idloaidonvi
             JOIN Capgiaidau cg ON cg.macapgiaidau = kv.capkhuvuc
             WHERE btc.trangthai = 'HOAT_DONG'
               AND tk.trangthai = 'HOAT_DONG'
               AND dv.trangthai = 'HOAT_DONG'
               AND ldv.trangthai = 'HOAT_DONG'
               AND cg.idcapgiaidau_cha IS NULL
               AND ldv.maloaidonvi = 'LIEN_DOAN_BONG_CHUYEN_VN'
             ORDER BY btc.idbantochuc ASC
             LIMIT 1"
        );
    }

    public function registerAccount(
        array $account,
        array $profile,
        array $coach,
        array $confirmation,
        ?string $ipAddress,
        string $logNote
    ): array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Taikhoan (username, password, email, sodienthoai, idrole, trangthai)
                 VALUES (:username, :password, :email, :sodienthoai, :idrole, 'CHO_DUYET')"
            );
            $statement->execute([
                'username' => $account['username'],
                'password' => $account['password'],
                'email' => $account['email'],
                'sodienthoai' => $account['sodienthoai'],
                'idrole' => $account['idrole'],
            ]);

            $accountId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Nguoidung
                    (idtaikhoan, ten, hodem, gioitinh, ngaysinh, quequan, diachi, avatar, cccd)
                 VALUES
                    (:idtaikhoan, :ten, :hodem, :gioitinh, :ngaysinh, :quequan, :diachi, :avatar, :cccd)"
            );
            $statement->execute([
                'idtaikhoan' => $accountId,
                'ten' => $profile['ten'],
                'hodem' => $profile['hodem'],
                'gioitinh' => $profile['gioitinh'],
                'ngaysinh' => $profile['ngaysinh'],
                'quequan' => $profile['quequan'],
                'diachi' => $profile['diachi'],
                'avatar' => $profile['avatar'],
                'cccd' => $profile['cccd'],
            ]);

            $userId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Huanluyenvien
                    (idnguoidung, idkhuvuccongtac, donvicongtac, bangcap, kinhnghiem, trangthai)
                 VALUES
                    (:idnguoidung, :idkhuvuccongtac, :donvicongtac, :bangcap, :kinhnghiem, 'CHO_DUYET')"
            );
            $statement->execute([
                'idnguoidung' => $userId,
                'idkhuvuccongtac' => $coach['idkhuvuccongtac'],
                'donvicongtac' => $coach['donvicongtac'],
                'bangcap' => $coach['bangcap'],
                'kinhnghiem' => $coach['kinhnghiem'],
            ]);

            $coachId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Yeucauxacnhan
                    (loainguoigui, idnguoigui, loainguoinhan, idnguoinhan, loaixacnhan, noidung, trangthai)
                 VALUES
                    ('HUAN_LUYEN_VIEN', :coach_id, 'BAN_TO_CHUC', :organizer_id, 'XAC_NHAN_HLV', :content, 'CHO_DUYET')"
            );
            $statement->execute([
                'coach_id' => $coachId,
                'organizer_id' => $confirmation['organizer_id'],
                'content' => $confirmation['content'],
            ]);

            $requestId = (int) $db->lastInsertId();
            $reason = 'Dang ky tai khoan huan luyen vien';

            $this->recordStatusHistory('TAI_KHOAN', $accountId, null, 'CHO_DUYET', $reason, $accountId);
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, null, 'CHO_DUYET', 'Gui yeu cau xac nhan tu cach HLV', $accountId);
            $this->recordSystemLog($accountId, 'Dang ky tai khoan huan luyen vien', 'Taikhoan', $accountId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Tao ho so huan luyen vien cho duyet', 'Huanluyenvien', $coachId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Gui yeu cau xac nhan tu cach huan luyen vien', 'Yeucauxacnhan', $requestId, $ipAddress, $logNote);

            $db->commit();

            return [
                'account_id' => $accountId,
                'user_id' => $userId,
                'coach_id' => $coachId,
                'request_id' => $requestId,
                'organizer_id' => (int) $confirmation['organizer_id'],
                'account_status' => 'CHO_DUYET',
                'coach_status' => 'CHO_DUYET',
                'request_status' => 'CHO_DUYET',
            ];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function teamsForCoach(int $coachId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                db.iddoibong,
                db.tendoibong,
                db.logo,
                db.diaphuong,
                db.mota,
                db.trangthai,
                db.ngaytao,
                db.ngaycapnhat,
                COALESCE(tv.total_members, 0) AS total_members,
                COALESCE(tv.active_members, 0) AS active_members
             FROM Doibong db
             LEFT JOIN (
                SELECT
                    iddoibong,
                    COUNT(*) AS total_members,
                    SUM(CASE WHEN trangthai = 'DANG_THAM_GIA' THEN 1 ELSE 0 END) AS active_members
                FROM Thanhviendoibong
                GROUP BY iddoibong
             ) tv ON tv.iddoibong = db.iddoibong
             WHERE db.idhuanluyenvien = :coach_id
             ORDER BY db.iddoibong DESC"
        );

        $statement->execute(['coach_id' => $coachId]);

        return $statement->fetchAll();
    }

    public function updateQualification(
        int $coachId,
        string $oldStatus,
        string $newStatus,
        ?int $requestId,
        ?string $requestStatus,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $systemAction,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Huanluyenvien
                 SET trangthai = :new_status
                 WHERE idhuanluyenvien = :coach_id
                   AND trangthai = :old_status"
            );
            $statement->execute([
                'new_status' => $newStatus,
                'coach_id' => $coachId,
                'old_status' => $oldStatus,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('COACH_QUALIFICATION_NOT_UPDATED');
            }

            $this->syncAccountStatusForQualification($coachId, $oldStatus, $newStatus, $reason, $actorAccountId);

            if ($requestId !== null && $requestStatus !== null) {
                $statement = $db->prepare(
                    "UPDATE Yeucauxacnhan
                     SET trangthai = :request_status,
                         ngayxuly = CURRENT_TIMESTAMP,
                         ghichu = :reason
                     WHERE idyeucau = :request_id
                       AND trangthai = 'CHO_DUYET'"
                );
                $statement->execute([
                    'request_status' => $requestStatus,
                    'reason' => $reason,
                    'request_id' => $requestId,
                ]);

                if ($statement->rowCount() === 1) {
                    $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', $requestStatus, $reason, $actorAccountId);
                }
            }

            $this->recordSystemLog($actorAccountId, $systemAction, 'Huanluyenvien', $coachId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function baseCoachQuery(int $organizerId, array $filters): array
    {
        $where = ['hlv.idkhuvuccongtac IN (SELECT scope_region_id FROM organizer_scope_regions)'];
        $bindings = [
            'organizer_scope_seed_id' => $organizerId,
            'organizer_scope_id' => $organizerId,
            'request_organizer_id' => $organizerId,
        ];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(hlv.bangcap LIKE :keyword
                OR hlv.donvicongtac LIKE :keyword
                OR kvct.tenkhuvuc LIKE :keyword
                OR tk.username LIKE :keyword
                OR tk.email LIKE :keyword
                OR tk.sodienthoai LIKE :keyword
                OR nd.cccd LIKE :keyword
                OR TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'hlv.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['account_status'] ?? '') !== '') {
            $where[] = 'tk.trangthai = :account_status';
            $bindings['account_status'] = $filters['account_status'];
        }

        if (($filters['request_status'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :request_status';
            $bindings['request_status'] = $filters['request_status'];
        }

        if (($filters['request_presence'] ?? '') === 'HAS_REQUEST') {
            $where[] = 'yc.idyeucau IS NOT NULL';
        }

        if (($filters['request_presence'] ?? '') === 'NO_REQUEST') {
            $where[] = 'yc.idyeucau IS NULL';
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'COALESCE(yc.ngaygui, nd.ngaytao) >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'COALESCE(yc.ngaygui, nd.ngaytao) <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        $sql = "WITH RECURSIVE organizer_scope_regions(scope_region_id) AS (
                SELECT kv_scope.idkhuvuc
                FROM Khuvuc kv_scope
                JOIN Bantochuc btc_scope_seed
                  ON btc_scope_seed.idkhuvucquanly = kv_scope.idkhuvuc
                 AND btc_scope_seed.idbantochuc = :organizer_scope_seed_id
                WHERE kv_scope.trangthai = 'HOAT_DONG'
                UNION ALL
                SELECT kv_child.idkhuvuc
                FROM Khuvuc kv_child
                JOIN organizer_scope_regions scope_parent
                  ON scope_parent.scope_region_id = kv_child.idkhuvuccha
                WHERE kv_child.trangthai = 'HOAT_DONG'
             )
             SELECT
                hlv.idhuanluyenvien,
                hlv.idnguoidung,
                hlv.idkhuvuccongtac,
                hlv.donvicongtac,
                hlv.bangcap,
                hlv.kinhnghiem,
                hlv.trangthai,
                kvct.makhuvuc AS makhuvuccongtac,
                kvct.tenkhuvuc AS tenkhuvuccongtac,
                kvct.capkhuvuc AS capkhuvuccongtac,
                nd.idtaikhoan,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd,
                nd.ngaytao AS nguoidung_ngaytao,
                nd.ngaycapnhat AS nguoidung_ngaycapnhat,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.trangthai AS trangthai_taikhoan,
                yc.idyeucau,
                yc.noidung AS yeucau_noidung,
                yc.trangthai AS yeucau_trangthai,
                yc.ngaygui AS yeucau_ngaygui,
                yc.ngayxuly AS yeucau_ngayxuly,
                yc.ghichu AS yeucau_ghichu,
                COALESCE(yc.ngaygui, nd.ngaytao) AS ngaythamchieu,
                COALESCE(team_stats.total_teams, 0) AS total_teams,
                COALESCE(team_stats.active_teams, 0) AS active_teams
             FROM Huanluyenvien hlv
             JOIN Bantochuc btc_scope
               ON btc_scope.idbantochuc = :organizer_scope_id
             LEFT JOIN Khuvuc kvct ON kvct.idkhuvuc = hlv.idkhuvuccongtac
             JOIN Nguoidung nd ON nd.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             LEFT JOIN (
                SELECT
                    idnguoigui,
                    MAX(idyeucau) AS latest_request_id
                FROM Yeucauxacnhan
                WHERE loainguoigui = 'HUAN_LUYEN_VIEN'
                  AND loainguoinhan = 'BAN_TO_CHUC'
                  AND loaixacnhan = 'XAC_NHAN_HLV'
                  AND idnguoinhan = :request_organizer_id
                GROUP BY idnguoigui
             ) latest_yc ON latest_yc.idnguoigui = hlv.idhuanluyenvien
             LEFT JOIN Yeucauxacnhan yc ON yc.idyeucau = latest_yc.latest_request_id
             LEFT JOIN (
                SELECT
                    idhuanluyenvien,
                    COUNT(*) AS total_teams,
                    SUM(CASE WHEN trangthai = 'HOAT_DONG' THEN 1 ELSE 0 END) AS active_teams
                FROM Doibong
                GROUP BY idhuanluyenvien
             ) team_stats ON team_stats.idhuanluyenvien = hlv.idhuanluyenvien";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return [$sql, $bindings];
    }

    private function syncAccountStatusForQualification(
        int $coachId,
        string $oldCoachStatus,
        string $newCoachStatus,
        string $reason,
        int $actorAccountId
    ): void {
        $account = $this->accountForCoach($coachId);

        if ($account === null) {
            return;
        }

        $newAccountStatus = null;

        if ($newCoachStatus === 'DA_XAC_NHAN') {
            $newAccountStatus = 'HOAT_DONG';
        }

        if ($newCoachStatus === 'BI_HUY_TU_CACH' && $oldCoachStatus === 'CHO_DUYET') {
            $newAccountStatus = 'DA_HUY';
        }

        if ($newCoachStatus === 'BI_HUY_TU_CACH' && $oldCoachStatus === 'DA_XAC_NHAN') {
            $newAccountStatus = 'TAM_KHOA';
        }

        if ($newAccountStatus === null || (string) $account['trangthai'] === $newAccountStatus) {
            return;
        }

        $statement = $this->db()->prepare(
            "UPDATE Taikhoan
             SET trangthai = :new_status,
                 ngaycapnhat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id"
        );
        $statement->execute([
            'new_status' => $newAccountStatus,
            'account_id' => (int) $account['idtaikhoan'],
        ]);

        $this->recordStatusHistory(
            'TAI_KHOAN',
            (int) $account['idtaikhoan'],
            (string) $account['trangthai'],
            $newAccountStatus,
            $reason,
            $actorAccountId
        );
    }

    private function accountForCoach(int $coachId): ?array
    {
        return $this->first(
            "SELECT tk.idtaikhoan, tk.trangthai
             FROM Huanluyenvien hlv
             JOIN Nguoidung nd ON nd.idnguoidung = hlv.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE hlv.idhuanluyenvien = :coach_id
             LIMIT 1",
            ['coach_id' => $coachId]
        );
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
