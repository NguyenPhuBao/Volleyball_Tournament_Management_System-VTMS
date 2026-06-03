<?php

namespace App\Repositories\PublicSite;

use Illuminate\Support\Facades\DB;

final class RefereeRegistrationRepository
{
    public function findById(int $refereeId): ?array
    {
        return $this->row(DB::selectOne(
            "SELECT
                tt.idtrongtai,
                tt.idnguoidung,
                tt.capbac,
                tt.kinhnghiem,
                tt.trangthai,
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
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.trangthai AS trangthai_taikhoan
             FROM Trongtai tt
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE tt.idtrongtai = :referee_id
             LIMIT 1",
            ['referee_id' => $refereeId]
        ));
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
             FROM Role
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );

        return $role === null ? null : (int) $role->idrole;
    }

    public function activeRefereeLevels(): array
    {
        return $this->rows(DB::select(
            "SELECT
                idcapgiaidau,
                macapgiaidau,
                tencapgiaidau,
                capkhuvucphamvi,
                thutu_cap
             FROM Capgiaidau
             WHERE trangthai = 'HOAT_DONG'
             ORDER BY COALESCE(thutu_cap, idcapgiaidau), idcapgiaidau"
        ));
    }

    public function activeRefereeLevel(string $level): ?array
    {
        return $this->row(DB::selectOne(
            "SELECT
                idcapgiaidau,
                macapgiaidau,
                tencapgiaidau,
                capkhuvucphamvi,
                thutu_cap
             FROM Capgiaidau
             WHERE trangthai = 'HOAT_DONG'
               AND macapgiaidau = :level
             LIMIT 1",
            ['level' => $level]
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
        array $referee,
        array $confirmation,
        ?string $ipAddress,
        string $logNote
    ): array {
        return DB::transaction(function () use ($account, $profile, $referee, $confirmation, $ipAddress, $logNote): array {
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
                "INSERT INTO Trongtai (idnguoidung, capbac, kinhnghiem, trangthai)
                 VALUES (:idnguoidung, :capbac, :kinhnghiem, 'CHO_DUYET')",
                [
                    'idnguoidung' => $userId,
                    'capbac' => $referee['capbac'],
                    'kinhnghiem' => $referee['kinhnghiem'],
                ]
            );
            $refereeId = (int) DB::getPdo()->lastInsertId();

            DB::insert(
                "INSERT INTO Yeucauxacnhan
                    (loainguoigui, idnguoigui, loainguoinhan, idnguoinhan, loaixacnhan, noidung, trangthai)
                 VALUES
                    ('TRONG_TAI', :referee_id, 'BAN_TO_CHUC', :organizer_id, 'XAC_NHAN_TAI_KHOAN_TRONG_TAI', :content, 'CHO_DUYET')",
                [
                    'referee_id' => $refereeId,
                    'organizer_id' => $confirmation['organizer_id'],
                    'content' => $confirmation['content'],
                ]
            );
            $requestId = (int) DB::getPdo()->lastInsertId();
            $reason = 'Dang ky tai khoan trong tai';

            $this->recordStatusHistory('TAI_KHOAN', $accountId, null, 'CHO_DUYET', $reason, $accountId);
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, null, 'CHO_DUYET', 'Gui yeu cau xac nhan tai khoan trong tai', $accountId);
            $this->recordSystemLog($accountId, 'Dang ky tai khoan trong tai', 'Taikhoan', $accountId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Tao ho so trong tai cho duyet', 'Trongtai', $refereeId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Gui yeu cau xac nhan tai khoan trong tai', 'Yeucauxacnhan', $requestId, $ipAddress, $logNote);

            return [
                'account_id' => $accountId,
                'user_id' => $userId,
                'referee_id' => $refereeId,
                'request_id' => $requestId,
                'organizer_id' => (int) $confirmation['organizer_id'],
                'account_status' => 'CHO_DUYET',
                'referee_status' => 'CHO_DUYET',
                'request_status' => 'CHO_DUYET',
            ];
        });
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
