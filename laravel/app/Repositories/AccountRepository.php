<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class AccountRepository
{
    public function findByIdentifier(string $identifier): ?array
    {
        $row = DB::selectOne(
            "SELECT
                tk.idtaikhoan,
                tk.username,
                tk.password,
                tk.email,
                tk.trangthai,
                r.namerole AS role,
                nd.hodem,
                nd.ten,
                hlv.idhuanluyenvien,
                hlv.idkhuvuccongtac,
                btc.idbantochuc,
                btc.idkhuvucquanly AS idkhuvucquanly_bantochuc,
                btc.iddonvi AS iddonvi_bantochuc,
                btc.trangthai AS trangthai_bantochuc,
                kv.capkhuvuc AS capkhuvuc_bantochuc,
                cgkv.idcapgiaidau AS idcapgiaidau_bantochuc,
                cgkv.idcapgiaidau_cha AS idcapgiaidau_cha_bantochuc,
                cgkv.thutu_cap AS thutu_cap_bantochuc,
                dv.madonvi AS madonvi_bantochuc,
                dv.tendonvi AS tendonvi_bantochuc,
                dv.trangthai AS trangthai_donvi_bantochuc,
                ldv.maloaidonvi AS maloaidonvi_bantochuc,
                ldv.tenloaidonvi AS tenloaidonvi_bantochuc,
                ldv.duoc_to_chuc_giai AS duoc_to_chuc_giai_bantochuc,
                ldv.trangthai AS trangthai_loaidonvi_bantochuc
            FROM Taikhoan tk
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
            LEFT JOIN Huanluyenvien hlv ON hlv.idnguoidung = nd.idnguoidung
            LEFT JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung
            LEFT JOIN Khuvuc kv ON kv.idkhuvuc = btc.idkhuvucquanly
            LEFT JOIN Capgiaidau cgkv ON cgkv.macapgiaidau = kv.capkhuvuc
            LEFT JOIN Donvi dv ON dv.iddonvi = btc.iddonvi
            LEFT JOIN Loaidonvi ldv ON ldv.idloaidonvi = dv.idloaidonvi
            WHERE tk.username = :username
               OR tk.email = :email
            LIMIT 1",
            [
                'username' => $identifier,
                'email' => $identifier,
            ]
        );

        return $row === null ? null : (array) $row;
    }

    public function findByIdWithPassword(int $accountId): ?array
    {
        $row = DB::selectOne(
            "SELECT
                tk.idtaikhoan,
                tk.username,
                tk.password,
                tk.email,
                tk.sodienthoai,
                tk.idrole,
                tk.trangthai,
                r.namerole AS role,
                nd.idnguoidung,
                nd.hodem,
                nd.ten,
                nd.gioitinh
            FROM Taikhoan tk
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
            WHERE tk.idtaikhoan = :account_id
            LIMIT 1",
            ['account_id' => $accountId]
        );

        return $row === null ? null : (array) $row;
    }

    public function createLoginSession(int $accountId, string $token): void
    {
        DB::insert(
            "INSERT INTO Phiendangnhap (idtaikhoan, token, trangthai)
             VALUES (:account_id, :token, 'DANG_HOAT_DONG')",
            [
                'account_id' => $accountId,
                'token' => $token,
            ]
        );
    }

    public function closeLoginSession(string $token): void
    {
        DB::update(
            "UPDATE Phiendangnhap
             SET trangthai = 'DA_DANG_XUAT',
                 thoigiandangxuat = CURRENT_TIMESTAMP
             WHERE token = :token
               AND trangthai = 'DANG_HOAT_DONG'",
            ['token' => $token]
        );
    }

    public function recordLoginHistory(int $accountId, string $result, ?string $ipAddress, ?string $device, ?string $note = null): void
    {
        DB::insert(
            "INSERT INTO Lichsudangnhap (idtaikhoan, ipaddress, thietbi, ketqua, ghichu)
             VALUES (:account_id, :ip_address, :device, :result, :note)",
            [
                'account_id' => $accountId,
                'ip_address' => $ipAddress,
                'device' => $device,
                'result' => $result,
                'note' => $note,
            ]
        );
    }

    public function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note = null): void
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

    public function updatePassword(int $accountId, string $passwordHash, string $oldPasswordHash): void
    {
        DB::transaction(function () use ($accountId, $passwordHash, $oldPasswordHash): void {
            DB::insert(
                "INSERT INTO Lichsumatkhau (idtaikhoan, passwordold)
                 VALUES (:account_id, :old_password)",
                [
                    'account_id' => $accountId,
                    'old_password' => $oldPasswordHash,
                ]
            );

            DB::update(
                "UPDATE Taikhoan
                 SET password = :password,
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idtaikhoan = :account_id",
                [
                    'password' => $passwordHash,
                    'account_id' => $accountId,
                ]
            );
        });
    }
}
