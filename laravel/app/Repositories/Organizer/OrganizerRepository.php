<?php

namespace App\Repositories\Organizer;

use Illuminate\Support\Facades\DB;

final class OrganizerRepository
{
    public function findOrganizerByAccountId(int $accountId): ?array
    {
        $row = DB::selectOne(
            "SELECT
                btc.idbantochuc,
                btc.idnguoidung,
                btc.idcapbantochuc,
                btc.idkhuvucquanly,
                btc.iddonvi,
                btc.idbantochuccha,
                btc.donvi,
                btc.chucvu,
                btc.trangthai,
                cbtc.macapbantochuc,
                cbtc.tencapbantochuc,
                cbtc.capkhuvucquanly,
                kv.makhuvuc AS makhuvucquanly,
                kv.tenkhuvuc AS tenkhuvucquanly,
                kv.capkhuvuc AS capkhuvucquanly_thucte,
                cgkv.idcapgiaidau AS idcapgiaidau_quanly,
                cgkv.idcapgiaidau_cha AS idcapgiaidau_cha_quanly,
                cgkv.thutu_cap AS thutu_cap_quanly,
                dv.madonvi,
                dv.tendonvi,
                dv.trangthai AS trangthai_donvi,
                ldv.maloaidonvi,
                ldv.tenloaidonvi,
                ldv.duoc_to_chuc_giai,
                ldv.trangthai AS trangthai_loaidonvi,
                tk.idtaikhoan,
                tk.username,
                r.namerole AS role,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Bantochuc btc
             JOIN Capbantochuc cbtc ON cbtc.idcapbantochuc = btc.idcapbantochuc
             JOIN Khuvuc kv ON kv.idkhuvuc = btc.idkhuvucquanly
             LEFT JOIN Capgiaidau cgkv ON cgkv.macapgiaidau = kv.capkhuvuc
             LEFT JOIN Donvi dv ON dv.iddonvi = btc.iddonvi
             LEFT JOIN Loaidonvi ldv ON ldv.idloaidonvi = dv.idloaidonvi
             JOIN Nguoidung nd ON nd.idnguoidung = btc.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             JOIN Role r ON r.idrole = tk.idrole
             WHERE tk.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );

        return $this->row($row);
    }

    public function competitionLocations(int $organizerId, array $filters = []): array
    {
        $organizer = $this->organizerById($organizerId);
        $where = ["vt.trangthai = 'HOAT_DONG'"];
        $bindings = [];

        if ($organizer !== null) {
            $where[] = 'fn_khuvuc_la_con(vt.idkhuvuc, :scope_id) = 1';
            $bindings['scope_id'] = (int) $organizer['idkhuvucquanly'];
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'vt.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(vt.tenvitrithidau LIKE :keyword OR vt.diachi LIKE :keyword OR kv.tenkhuvuc LIKE :keyword)';
            $bindings['keyword'] = '%'.$filters['q'].'%';
        }

        $sql = "SELECT
                vt.idvitrithidau,
                vt.tenvitrithidau,
                vt.idkhuvuc,
                kv.tenkhuvuc,
                kv.capkhuvuc,
                vt.diachi,
                vt.mota,
                vt.trangthai,
                s.ngaytao,
                s.ngaycapnhat,
                COALESCE(s.total_sandau, 0) AS total_sandau,
                COALESCE(s.active_sandau, 0) AS active_sandau
             FROM Vitrithidau vt
             JOIN Khuvuc kv ON kv.idkhuvuc = vt.idkhuvuc
             LEFT JOIN (
                SELECT
                    idvitrithidau,
                    COUNT(*) AS total_sandau,
                    SUM(CASE WHEN trangthai = 'HOAT_DONG' THEN 1 ELSE 0 END) AS active_sandau,
                    MIN(ngaytao) AS ngaytao,
                    MAX(ngaycapnhat) AS ngaycapnhat
                FROM Sandau
                WHERE idvitrithidau IS NOT NULL
                GROUP BY idvitrithidau
             ) s ON s.idvitrithidau = vt.idvitrithidau
             WHERE ".implode(' AND ', $where).'
             ORDER BY vt.tenvitrithidau ASC, vt.idvitrithidau ASC';

        return $this->rows(DB::select($sql, $bindings));
    }

    public function competitionLocationById(int $locationId, int $organizerId): ?array
    {
        $organizer = $this->organizerById($organizerId);

        if ($organizer === null) {
            return null;
        }

        $row = DB::selectOne(
            "SELECT
                vt.idvitrithidau,
                vt.tenvitrithidau,
                vt.idkhuvuc,
                kv.tenkhuvuc,
                kv.capkhuvuc,
                vt.diachi,
                vt.mota,
                vt.trangthai
             FROM Vitrithidau vt
             JOIN Khuvuc kv ON kv.idkhuvuc = vt.idkhuvuc
             WHERE vt.idvitrithidau = :location_id
               AND vt.trangthai = 'HOAT_DONG'
               AND fn_khuvuc_la_con(vt.idkhuvuc, :scope_id) = 1
             LIMIT 1",
            [
                'location_id' => $locationId,
                'scope_id' => (int) $organizer['idkhuvucquanly'],
            ]
        );

        return $this->row($row);
    }

    private function organizerById(int $organizerId): ?array
    {
        $row = DB::selectOne(
            "SELECT
                btc.idbantochuc,
                btc.idcapbantochuc,
                btc.idkhuvucquanly,
                btc.trangthai,
                cbtc.macapbantochuc,
                cbtc.tencapbantochuc,
                cbtc.capkhuvucquanly,
                kv.tenkhuvuc AS tenkhuvucquanly,
                kv.capkhuvuc AS capkhuvucquanly_thucte,
                cgkv.idcapgiaidau AS idcapgiaidau_quanly,
                cgkv.idcapgiaidau_cha AS idcapgiaidau_cha_quanly,
                cgkv.thutu_cap AS thutu_cap_quanly
             FROM Bantochuc btc
             JOIN Capbantochuc cbtc ON cbtc.idcapbantochuc = btc.idcapbantochuc
             JOIN Khuvuc kv ON kv.idkhuvuc = btc.idkhuvucquanly
             LEFT JOIN Capgiaidau cgkv ON cgkv.macapgiaidau = kv.capkhuvuc
             WHERE btc.idbantochuc = :organizer_id
             LIMIT 1",
            ['organizer_id' => $organizerId]
        );

        return $this->row($row);
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
