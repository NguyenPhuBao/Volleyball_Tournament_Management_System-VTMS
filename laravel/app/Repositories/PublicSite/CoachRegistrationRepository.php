<?php

namespace App\Repositories\PublicSite;

use Illuminate\Support\Facades\DB;

final class CoachRegistrationRepository
{
    public function findForOrganizer(int $organizerId, int $coachId): ?array
    {
        [$sql, $bindings] = $this->baseCoachQuery($organizerId);
        $sql .= ' AND hlv.idhuanluyenvien = :coach_id LIMIT 1';
        $bindings['coach_id'] = $coachId;

        return $this->row(DB::selectOne($sql, $bindings));
    }

    public function accountValueExists(string $field, string $value): bool
    {
        if (!in_array($field, ['username', 'email', 'sodienthoai'], true)) {
            return false;
        }

        return DB::selectOne(
            "SELECT 1 FROM Taikhoan WHERE {$field} = :value LIMIT 1",
            ['value' => $value]
        ) !== null;
    }

    public function profileValueExists(string $field, string $value): bool
    {
        if (!in_array($field, ['cccd'], true)) {
            return false;
        }

        return DB::selectOne(
            "SELECT 1 FROM Nguoidung WHERE {$field} = :value LIMIT 1",
            ['value' => $value]
        ) !== null;
    }

    public function roleIdByName(string $roleName): ?int
    {
        $role = DB::selectOne(
            "SELECT idrole
             FROM `Role`
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );

        return $role === null ? null : (int) $role->idrole;
    }

    public function activeWorkRegions(): array
    {
        return $this->rows(DB::select(
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
        ));
    }

    public function activeWorkRegion(int $regionId): ?array
    {
        return $this->row(DB::selectOne(
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
        ));
    }

    public function nationalFederationOrganizer(): ?array
    {
        return $this->row(DB::selectOne(
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
        ));
    }

    public function registerAccount(
        array $account,
        array $profile,
        array $coach,
        array $confirmation,
        ?string $ipAddress,
        string $logNote
    ): array {
        return DB::transaction(function () use ($account, $profile, $coach, $confirmation, $ipAddress, $logNote): array {
            DB::insert(
                "INSERT INTO Taikhoan (username, password, email, sodienthoai, idrole, trangthai)
                 VALUES (:username, :password, :email, :sodienthoai, :idrole, 'CHO_DUYET')",
                [
                    'username' => $account['username'],
                    'password' => $account['password'],
                    'email' => $account['email'],
                    'sodienthoai' => $account['sodienthoai'],
                    'idrole' => $account['idrole'],
                ]
            );
            $accountId = (int) DB::getPdo()->lastInsertId();

            DB::insert(
                "INSERT INTO Nguoidung
                    (idtaikhoan, ten, hodem, gioitinh, ngaysinh, quequan, diachi, avatar, cccd)
                 VALUES
                    (:idtaikhoan, :ten, :hodem, :gioitinh, :ngaysinh, :quequan, :diachi, :avatar, :cccd)",
                [
                    'idtaikhoan' => $accountId,
                    'ten' => $profile['ten'],
                    'hodem' => $profile['hodem'],
                    'gioitinh' => $profile['gioitinh'],
                    'ngaysinh' => $profile['ngaysinh'],
                    'quequan' => $profile['quequan'],
                    'diachi' => $profile['diachi'],
                    'avatar' => $profile['avatar'],
                    'cccd' => $profile['cccd'],
                ]
            );
            $userId = (int) DB::getPdo()->lastInsertId();

            DB::insert(
                "INSERT INTO Huanluyenvien
                    (idnguoidung, idkhuvuccongtac, donvicongtac, bangcap, kinhnghiem, trangthai)
                 VALUES
                    (:idnguoidung, :idkhuvuccongtac, :donvicongtac, :bangcap, :kinhnghiem, 'CHO_DUYET')",
                [
                    'idnguoidung' => $userId,
                    'idkhuvuccongtac' => $coach['idkhuvuccongtac'],
                    'donvicongtac' => $coach['donvicongtac'],
                    'bangcap' => $coach['bangcap'],
                    'kinhnghiem' => $coach['kinhnghiem'],
                ]
            );
            $coachId = (int) DB::getPdo()->lastInsertId();

            DB::insert(
                "INSERT INTO Yeucauxacnhan
                    (loainguoigui, idnguoigui, loainguoinhan, idnguoinhan, loaixacnhan, noidung, trangthai)
                 VALUES
                    ('HUAN_LUYEN_VIEN', :coach_id, 'BAN_TO_CHUC', :organizer_id, 'XAC_NHAN_HLV', :content, 'CHO_DUYET')",
                [
                    'coach_id' => $coachId,
                    'organizer_id' => $confirmation['organizer_id'],
                    'content' => $confirmation['content'],
                ]
            );
            $requestId = (int) DB::getPdo()->lastInsertId();
            $reason = 'Dang ky tai khoan huan luyen vien';

            $this->recordStatusHistory('TAI_KHOAN', $accountId, null, 'CHO_DUYET', $reason, $accountId);
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, null, 'CHO_DUYET', 'Gui yeu cau xac nhan tu cach HLV', $accountId);
            $this->recordSystemLog($accountId, 'Dang ky tai khoan huan luyen vien', 'Taikhoan', $accountId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Tao ho so huan luyen vien cho duyet', 'Huanluyenvien', $coachId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Gui yeu cau xac nhan tu cach huan luyen vien', 'Yeucauxacnhan', $requestId, $ipAddress, $logNote);

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
        });
    }

    private function baseCoachQuery(int $organizerId): array
    {
        $bindings = [
            'organizer_scope_seed_id' => $organizerId,
            'organizer_scope_id' => $organizerId,
            'request_organizer_id' => $organizerId,
        ];

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
             ) team_stats ON team_stats.idhuanluyenvien = hlv.idhuanluyenvien
             WHERE hlv.idkhuvuccongtac IN (SELECT scope_region_id FROM organizer_scope_regions)";

        return [$sql, $bindings];
    }

    private function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note = null): void
    {
        DB::insert(
            "INSERT INTO Nhatkyhethong (idtaikhoan, hanhdong, bangtacdong, iddoituong, ipaddress, ghichu)
             VALUES (:account_id, :action, :target_table, :target_id, :ip_address, :note)",
            [
                'account_id' => $accountId,
                'action' => $action,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'ip_address' => $ipAddress,
                'note' => $note,
            ]
        );
    }

    private function recordStatusHistory(string $targetType, int $targetId, ?string $oldStatus, string $newStatus, ?string $reason, ?int $actorId): void
    {
        DB::insert(
            "INSERT INTO Nhatkytrangthai (loaidoituong, iddoituong, trangthaicu, trangthaimoi, lydo, idnguoithuchien)
             VALUES (:target_type, :target_id, :old_status, :new_status, :reason, :actor_id)",
            [
                'target_type' => $targetType,
                'target_id' => $targetId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'actor_id' => $actorId,
            ]
        );
    }

    private function row(object|array|null $row): ?array
    {
        return $row === null ? null : (array) $row;
    }

    private function rows(array $rows): array
    {
        return array_map(fn (object|array $row): array => (array) $row, $rows);
    }
}
