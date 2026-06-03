<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Trongtai extends Model
{
    public function listForOrganizer(int $organizerId, array $filters = []): array
    {
        $where = ['cq_ref_scope.thutu >= cq_btc_scope.thutu'];
        $bindings = ['organizer_scope_id' => $organizerId];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(tt.capbac LIKE :keyword
                OR tk.username LIKE :keyword
                OR tk.email LIKE :keyword
                OR tk.sodienthoai LIKE :keyword
                OR nd.cccd LIKE :keyword
                OR TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'tt.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['account_status'] ?? '') !== '') {
            $where[] = 'tk.trangthai = :account_status';
            $bindings['account_status'] = $filters['account_status'];
        }

        $sql = "SELECT
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
                tk.trangthai AS trangthai_taikhoan,
                COALESCE(assignments.total_assignments, 0) AS total_assignments,
                COALESCE(assignments.active_assignments, 0) AS active_assignments,
                COALESCE(assignments.pending_assignments, 0) AS pending_assignments,
                COALESCE(leaves.pending_leaves, 0) AS pending_leaves
             FROM Trongtai tt
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             JOIN Bantochuc btc_scope ON btc_scope.idbantochuc = :organizer_scope_id
             JOIN Capbantochuc cb_scope ON cb_scope.idcapbantochuc = btc_scope.idcapbantochuc
             JOIN Capchinhquyen cq_btc_scope ON cq_btc_scope.macap = cb_scope.capkhuvucquanly
             JOIN Capchinhquyen cq_ref_scope ON cq_ref_scope.macap = tt.capbac
             LEFT JOIN (
                SELECT
                    idtrongtai,
                    COUNT(*) AS total_assignments,
                    SUM(CASE WHEN trangthai IN ('CHO_XAC_NHAN','DA_XAC_NHAN') THEN 1 ELSE 0 END) AS active_assignments,
                    SUM(CASE WHEN trangthai = 'CHO_XAC_NHAN' THEN 1 ELSE 0 END) AS pending_assignments
                FROM Phancongtrongtai
                GROUP BY idtrongtai
             ) assignments ON assignments.idtrongtai = tt.idtrongtai
             LEFT JOIN (
                SELECT
                    idtrongtai,
                    COUNT(*) AS pending_leaves
                FROM Donnghitrongtai
                WHERE trangthai = 'CHO_DUYET'
                GROUP BY idtrongtai
             ) leaves ON leaves.idtrongtai = tt.idtrongtai";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY tt.idtrongtai DESC';

        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function refereeVisibleToOrganizer(int $refereeId, int $organizerId): bool
    {
        return $this->first(
            "SELECT 1
             FROM Trongtai tt
             JOIN Bantochuc btc_scope ON btc_scope.idbantochuc = :organizer_scope_id
             JOIN Capbantochuc cb_scope ON cb_scope.idcapbantochuc = btc_scope.idcapbantochuc
             JOIN Capchinhquyen cq_btc_scope ON cq_btc_scope.macap = cb_scope.capkhuvucquanly
             JOIN Capchinhquyen cq_ref_scope ON cq_ref_scope.macap = tt.capbac
             WHERE tt.idtrongtai = :referee_id
               AND cq_ref_scope.thutu >= cq_btc_scope.thutu
             LIMIT 1",
            [
                'referee_id' => $refereeId,
                'organizer_scope_id' => $organizerId,
            ]
        ) !== null;
    }

    public function findById(int $refereeId): ?array
    {
        return $this->first(
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
        );
    }

    public function findByAccountId(int $accountId): ?array
    {
        return $this->first(
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
             WHERE tk.idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );
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
             FROM Role
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );

        return $role === null ? null : (int) $role['idrole'];
    }

    public function activeRefereeLevels(): array
    {
        $statement = $this->db()->query(
            "SELECT
                idcapgiaidau,
                macapgiaidau,
                tencapgiaidau,
                capkhuvucphamvi,
                thutu_cap
             FROM Capgiaidau
             WHERE trangthai = 'HOAT_DONG'
             ORDER BY COALESCE(thutu_cap, idcapgiaidau), idcapgiaidau"
        );

        return $statement->fetchAll();
    }

    public function activeRefereeLevel(string $level): ?array
    {
        return $this->first(
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
        array $referee,
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
                "INSERT INTO Trongtai (idnguoidung, capbac, kinhnghiem, trangthai)
                 VALUES (:idnguoidung, :capbac, :kinhnghiem, 'CHO_DUYET')"
            );
            $statement->execute([
                'idnguoidung' => $userId,
                'capbac' => $referee['capbac'],
                'kinhnghiem' => $referee['kinhnghiem'],
            ]);

            $refereeId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "INSERT INTO Yeucauxacnhan
                    (loainguoigui, idnguoigui, loainguoinhan, idnguoinhan, loaixacnhan, noidung, trangthai)
                 VALUES
                    ('TRONG_TAI', :referee_id, 'BAN_TO_CHUC', :organizer_id, 'XAC_NHAN_TAI_KHOAN_TRONG_TAI', :content, 'CHO_DUYET')"
            );
            $statement->execute([
                'referee_id' => $refereeId,
                'organizer_id' => $confirmation['organizer_id'],
                'content' => $confirmation['content'],
            ]);

            $requestId = (int) $db->lastInsertId();
            $reason = 'Dang ky tai khoan trong tai';

            $this->recordStatusHistory('TAI_KHOAN', $accountId, null, 'CHO_DUYET', $reason, $accountId);
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, null, 'CHO_DUYET', 'Gui yeu cau xac nhan tai khoan trong tai', $accountId);
            $this->recordSystemLog($accountId, 'Dang ky tai khoan trong tai', 'Taikhoan', $accountId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Tao ho so trong tai cho duyet', 'Trongtai', $refereeId, $ipAddress, $logNote);
            $this->recordSystemLog($accountId, 'Gui yeu cau xac nhan tai khoan trong tai', 'Yeucauxacnhan', $requestId, $ipAddress, $logNote);

            $db->commit();

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
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function createReferee(
        array $account,
        array $profile,
        array $referee,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Taikhoan (username, password, email, sodienthoai, idrole, trangthai)
                 VALUES (:username, :password, :email, :sodienthoai, :idrole, 'CHUA_KICH_HOAT')"
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
                "INSERT INTO Trongtai (idnguoidung, capbac, kinhnghiem, trangthai)
                 VALUES (:idnguoidung, :capbac, :kinhnghiem, 'CHO_DUYET')"
            );
            $statement->execute([
                'idnguoidung' => $userId,
                'capbac' => $referee['capbac'],
                'kinhnghiem' => $referee['kinhnghiem'],
            ]);

            $refereeId = (int) $db->lastInsertId();

            $this->recordStatusHistory('TAI_KHOAN', $accountId, null, 'CHUA_KICH_HOAT', 'Them trong tai cho duyet', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Tao tai khoan trong tai', 'Taikhoan', $accountId, $ipAddress, $logNote);
            $this->recordSystemLog($actorAccountId, 'Them trong tai', 'Trongtai', $refereeId, $ipAddress, $logNote);

            $db->commit();

            return $refereeId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function latestRegistrationRequest(int $refereeId, int $organizerId): ?array
    {
        return $this->first(
            "SELECT
                idyeucau,
                loainguoigui,
                idnguoigui,
                loainguoinhan,
                idnguoinhan,
                loaixacnhan,
                noidung,
                trangthai,
                ngaygui,
                ngayxuly,
                ghichu
             FROM Yeucauxacnhan
             WHERE loainguoigui = 'TRONG_TAI'
               AND idnguoigui = :referee_id
               AND loainguoinhan = 'BAN_TO_CHUC'
               AND idnguoinhan = :organizer_id
               AND loaixacnhan = 'XAC_NHAN_TAI_KHOAN_TRONG_TAI'
             ORDER BY idyeucau DESC
             LIMIT 1",
            [
                'referee_id' => $refereeId,
                'organizer_id' => $organizerId,
            ]
        );
    }

    public function updateRegistrationReview(
        int $refereeId,
        int $targetAccountId,
        string $newRefereeStatus,
        string $newAccountStatus,
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
                "UPDATE Trongtai
                 SET trangthai = :new_referee_status
                 WHERE idtrongtai = :referee_id
                   AND trangthai = 'CHO_DUYET'"
            );
            $statement->execute([
                'new_referee_status' => $newRefereeStatus,
                'referee_id' => $refereeId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('REFEREE_REGISTRATION_NOT_UPDATED');
            }

            $statement = $db->prepare(
                "UPDATE Taikhoan
                 SET trangthai = :new_account_status,
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idtaikhoan = :account_id
                   AND trangthai = 'CHO_DUYET'"
            );
            $statement->execute([
                'new_account_status' => $newAccountStatus,
                'account_id' => $targetAccountId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('REFEREE_ACCOUNT_NOT_UPDATED');
            }

            $this->recordStatusHistory('TAI_KHOAN', $targetAccountId, 'CHO_DUYET', $newAccountStatus, $reason, $actorAccountId);

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

            $this->recordSystemLog($actorAccountId, $systemAction, 'Trongtai', $refereeId, $ipAddress, $logNote);
            $this->recordSystemLog($actorAccountId, $systemAction, 'Taikhoan', $targetAccountId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function matchesForOrganizer(int $organizerId, array $filters = []): array
    {
        $where = ['gd.idbantochuc = :organizer_id'];
        $bindings = ['organizer_id' => $organizerId];

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'td.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(gd.tengiaidau LIKE :keyword
                OR d1.tendoibong LIKE :keyword
                OR d2.tendoibong LIKE :keyword
                OR sd.tensandau LIKE :keyword
                OR vd.tenvongdau LIKE :keyword)";
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        $statement = $this->db()->prepare(
            "SELECT
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
                td.trangthai,
                GROUP_CONCAT(
                    CONCAT(pctt.idphancong, ':', pctt.idtrongtai, ':', pctt.vaitro, ':', pctt.trangthai, ':', COALESCE(tk.username, ''))
                    ORDER BY pctt.vaitro SEPARATOR '|'
                ) AS assignments
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             LEFT JOIN Phancongtrongtai pctt
                ON pctt.idtrandau = td.idtrandau
               AND pctt.trangthai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')
             LEFT JOIN Trongtai tt ON tt.idtrongtai = pctt.idtrongtai
             LEFT JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             LEFT JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE " . implode(' AND ', $where) . "
             GROUP BY
                td.idtrandau,
                td.idgiaidau,
                gd.tengiaidau,
                td.idbangdau,
                bd.tenbang,
                td.iddoibong1,
                d1.tendoibong,
                td.iddoibong2,
                d2.tendoibong,
                td.idsandau,
                sd.tensandau,
                vt.diachi,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau,
                td.trangthai
             ORDER BY td.thoigianbatdau, td.idtrandau"
        );

        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function matchForOrganizer(int $organizerId, int $matchId): ?array
    {
        return $this->first(
            "SELECT
                td.idtrandau,
                td.idgiaidau,
                gd.tengiaidau,
                gd.idcapgiaidau,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai,
                d1.tendoibong AS doi1,
                d2.tendoibong AS doi2
             FROM Trandau td
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
              JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
              LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             WHERE td.idtrandau = :match_id
               AND gd.idbantochuc = :organizer_id
             LIMIT 1",
            [
                'match_id' => $matchId,
                'organizer_id' => $organizerId,
            ]
        );
    }

    public function isRefereeEligibleForTournament(int $refereeId, int $tournamentId): bool
    {
        return $this->first(
            "SELECT 1
             FROM Trongtai tt
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             JOIN Giaidau gd ON gd.idgiaidau = :tournament_id
             JOIN Capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
             WHERE tt.idtrongtai = :referee_id
               AND tt.trangthai = 'HOAT_DONG'
               AND tk.trangthai = 'HOAT_DONG'
               AND " . $this->refereeLevelMatchSql('tt', 'cg') . "
             LIMIT 1",
            [
                'referee_id' => $refereeId,
                'tournament_id' => $tournamentId,
            ]
        ) !== null;
    }

    public function activeAssignmentForRole(int $matchId, string $role): ?array
    {
        return $this->first(
            "SELECT
                pctt.idphancong,
                pctt.idtrandau,
                pctt.idtrongtai,
                pctt.vaitro,
                pctt.trangthai,
                tk.username,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Phancongtrongtai pctt
             JOIN Trongtai tt ON tt.idtrongtai = pctt.idtrongtai
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             WHERE pctt.idtrandau = :match_id
               AND pctt.vaitro = :role
               AND pctt.trangthai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')
             ORDER BY pctt.idphancong DESC
             LIMIT 1",
            [
                'match_id' => $matchId,
                'role' => $role,
            ]
        );
    }

    public function assignToMatch(
        int $matchId,
        int $refereeId,
        string $role,
        bool $replace,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            if ($replace) {
                $statement = $db->prepare(
                    "UPDATE Phancongtrongtai
                     SET trangthai = 'DA_HUY'
                     WHERE idtrandau = :match_id
                       AND vaitro = :role
                       AND trangthai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')
                       AND idtrongtai <> :referee_id"
                );
                $statement->execute([
                    'match_id' => $matchId,
                    'role' => $role,
                    'referee_id' => $refereeId,
                ]);

                $statement = $db->prepare(
                    "DELETE tttd
                     FROM Trongtaitrandau tttd
                     JOIN Phancongtrongtai pctt
                       ON pctt.idtrandau = tttd.idtrandau
                      AND pctt.idtrongtai = tttd.idtrongtai
                      AND pctt.vaitro = tttd.vaitro
                     WHERE tttd.idtrandau = :match_id
                       AND tttd.vaitro = :role
                       AND pctt.trangthai = 'DA_HUY'"
                );
                $statement->execute([
                    'match_id' => $matchId,
                    'role' => $role,
                ]);
            }

            $existing = $this->first(
                "SELECT idphancong
                 FROM Phancongtrongtai
                 WHERE idtrandau = :match_id
                   AND idtrongtai = :referee_id
                 LIMIT 1",
                [
                    'match_id' => $matchId,
                    'referee_id' => $refereeId,
                ]
            );

            if ($existing !== null) {
                $assignmentId = (int) $existing['idphancong'];
                $statement = $db->prepare(
                    "UPDATE Phancongtrongtai
                     SET vaitro = :role,
                        trangthai = 'DA_XAC_NHAN',
                         ngayphancong = CURRENT_TIMESTAMP
                     WHERE idphancong = :assignment_id"
                );
                $statement->execute([
                    'role' => $role,
                    'assignment_id' => $assignmentId,
                ]);
            } else {
                $statement = $db->prepare(
                    "INSERT INTO Phancongtrongtai (idtrandau, idtrongtai, vaitro, trangthai)
                     VALUES (:match_id, :referee_id, :role, 'DA_XAC_NHAN')"
                );
                $statement->execute([
                    'match_id' => $matchId,
                    'referee_id' => $refereeId,
                    'role' => $role,
                ]);

                $assignmentId = (int) $db->lastInsertId();
            }

            $detail = $this->first(
                "SELECT idtrongtaitrandau
                 FROM Trongtaitrandau
                 WHERE idtrandau = :match_id
                   AND idtrongtai = :referee_id
                 LIMIT 1",
                [
                    'match_id' => $matchId,
                    'referee_id' => $refereeId,
                ]
            );

            if ($detail !== null) {
                $statement = $db->prepare(
                    "UPDATE Trongtaitrandau
                     SET vaitro = :role,
                        xacnhanthamgia = FALSE,
                        thoigianxacnhan = NULL
                     WHERE idtrongtaitrandau = :detail_id"
                );
                $statement->execute([
                    'role' => $role,
                    'detail_id' => (int) $detail['idtrongtaitrandau'],
                ]);
            } else {
                $statement = $db->prepare(
                    "INSERT INTO Trongtaitrandau (idtrandau, idtrongtai, vaitro, xacnhanthamgia, thoigianxacnhan)
                     VALUES (:match_id, :referee_id, :role, FALSE, NULL)"
                );
                $statement->execute([
                    'match_id' => $matchId,
                    'referee_id' => $refereeId,
                    'role' => $role,
                ]);
            }

            $this->recordSystemLog($actorAccountId, 'Phan cong trong tai', 'Phancongtrongtai', $assignmentId, $ipAddress, $logNote);

            $db->commit();

            return $assignmentId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function findAssignment(int $assignmentId): ?array
    {
        return $this->first(
            "SELECT
                pctt.idphancong,
                pctt.idtrandau,
                pctt.idtrongtai,
                pctt.vaitro,
                pctt.trangthai,
                pctt.ngayphancong,
                tttd.xacnhanthamgia,
                tttd.thoigianxacnhan
             FROM Phancongtrongtai pctt
             LEFT JOIN Trongtaitrandau tttd
                ON tttd.idtrandau = pctt.idtrandau
               AND tttd.idtrongtai = pctt.idtrongtai
             WHERE pctt.idphancong = :assignment_id
             LIMIT 1",
            ['assignment_id' => $assignmentId]
        );
    }

    public function createLeaveRequest(
        int $refereeId,
        int $accountId,
        string $oldAccountStatus,
        string $fromDate,
        string $toDate,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Donnghitrongtai (idtrongtai, tungay, denngay, lydo, trangthai)
                 VALUES (:referee_id, :from_date, :to_date, :reason, 'CHO_DUYET')"
            );
            $statement->execute([
                'referee_id' => $refereeId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => $reason,
            ]);

            $leaveId = (int) $db->lastInsertId();

            $statement = $db->prepare(
                "UPDATE Trongtai
                 SET trangthai = 'DANG_NGHI'
                 WHERE idtrongtai = :referee_id
                   AND trangthai = 'HOAT_DONG'"
            );
            $statement->execute(['referee_id' => $refereeId]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('REFEREE_NOT_MARKED_LEAVE');
            }

            $statement = $db->prepare(
                "UPDATE Taikhoan
                 SET trangthai = 'TAM_KHOA',
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idtaikhoan = :account_id"
            );
            $statement->execute(['account_id' => $accountId]);

            $this->recordStatusHistory('TAI_KHOAN', $accountId, $oldAccountStatus, 'TAM_KHOA', $reason, $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Cho nghi trong tai', 'Donnghitrongtai', $leaveId, $ipAddress, $logNote);
            $this->recordSystemLog($actorAccountId, 'Cap nhat trang thai trong tai nghi', 'Trongtai', $refereeId, $ipAddress, $logNote);

            $db->commit();

            return $leaveId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function findLeaveRequest(int $leaveId): ?array
    {
        return $this->first(
            "SELECT
                dntt.iddonnghi,
                dntt.idtrongtai,
                dntt.tungay,
                dntt.denngay,
                dntt.lydo,
                dntt.trangthai,
                dntt.ngaygui,
                dntt.ngayxuly
             FROM Donnghitrongtai dntt
             WHERE dntt.iddonnghi = :leave_id
             LIMIT 1",
            ['leave_id' => $leaveId]
        );
    }

    public function leaveRequestsForOrganizer(int $organizerId, array $filters = []): array
    {
        $where = [];
        $bindings = [];

        if (($filters['referee_id'] ?? null) !== null) {
            $where[] = 'dntt.idtrongtai = :referee_id';
            $bindings['referee_id'] = (int) $filters['referee_id'];
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'dntt.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'dntt.tungay >= :from_date';
            $bindings['from_date'] = $filters['from'];
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'dntt.tungay <= :to_date';
            $bindings['to_date'] = $filters['to'];
        }

        $sql = "SELECT
                dntt.iddonnghi,
                dntt.idtrongtai,
                dntt.tungay,
                dntt.denngay,
                dntt.lydo,
                dntt.trangthai,
                dntt.ngaygui,
                dntt.ngayxuly,
                tt.capbac,
                tt.kinhnghiem,
                tt.trangthai AS trangthaitrongtai,
                nd.idtaikhoan,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tk.username,
                tk.email,
                tk.trangthai AS trangthai_taikhoan
             FROM Donnghitrongtai dntt
             JOIN Trongtai tt ON tt.idtrongtai = dntt.idtrongtai
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY dntt.ngaygui DESC, dntt.iddonnghi DESC';

        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function leaveRequestsForReferee(int $refereeId, array $filters = []): array
    {
        [$where, $bindings] = $this->leaveRequestWhereForReferee($refereeId, $filters);

        $statement = $this->db()->prepare(
            $this->baseLeaveRequestSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY dntt.ngaygui DESC, dntt.iddonnghi DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function leaveRequestStatsForReferee(int $refereeId, array $filters = []): array
    {
        [$where, $bindings] = $this->leaveRequestWhereForReferee($refereeId, $filters);

        $statement = $this->db()->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN dntt.trangthai = 'CHO_DUYET' THEN 1 ELSE 0 END) AS cho_duyet,
                SUM(CASE WHEN dntt.trangthai = 'DA_DUYET' THEN 1 ELSE 0 END) AS da_duyet,
                SUM(CASE WHEN dntt.trangthai = 'TU_CHOI' THEN 1 ELSE 0 END) AS tu_choi,
                SUM(CASE WHEN dntt.trangthai = 'DA_HUY' THEN 1 ELSE 0 END) AS da_huy,
                SUM(DATEDIFF(dntt.denngay, dntt.tungay) + 1) AS total_days,
                SUM(CASE WHEN dntt.trangthai = 'DA_DUYET' THEN DATEDIFF(dntt.denngay, dntt.tungay) + 1 ELSE 0 END) AS approved_days
             FROM Donnghitrongtai dntt
             WHERE " . implode(' AND ', $where)
        );
        $statement->execute($bindings);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'CHO_DUYET' => (int) ($row['cho_duyet'] ?? 0),
            'DA_DUYET' => (int) ($row['da_duyet'] ?? 0),
            'TU_CHOI' => (int) ($row['tu_choi'] ?? 0),
            'DA_HUY' => (int) ($row['da_huy'] ?? 0),
            'total_days' => (int) ($row['total_days'] ?? 0),
            'approved_days' => (int) ($row['approved_days'] ?? 0),
        ];
    }

    public function findLeaveRequestForReferee(int $refereeId, int $leaveId): ?array
    {
        return $this->first(
            $this->baseLeaveRequestSelect() . '
             WHERE dntt.idtrongtai = :referee_id
               AND dntt.iddonnghi = :leave_id
             LIMIT 1',
            [
                'referee_id' => $refereeId,
                'leave_id' => $leaveId,
            ]
        );
    }

    public function hasOverlappingLeaveRequest(int $refereeId, string $fromDate, string $toDate, ?int $exceptLeaveId = null): bool
    {
        $where = [
            'idtrongtai = :referee_id',
            "trangthai IN ('CHO_DUYET','DA_DUYET')",
            'tungay <= :to_date',
            'denngay >= :from_date',
        ];
        $bindings = [
            'referee_id' => $refereeId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        if ($exceptLeaveId !== null) {
            $where[] = 'iddonnghi <> :except_leave_id';
            $bindings['except_leave_id'] = $exceptLeaveId;
        }

        return $this->first(
            'SELECT 1
             FROM Donnghitrongtai
             WHERE ' . implode(' AND ', $where) . '
             LIMIT 1',
            $bindings
        ) !== null;
    }

    public function createSelfLeaveRequest(
        int $refereeId,
        string $fromDate,
        string $toDate,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Donnghitrongtai (idtrongtai, tungay, denngay, lydo, trangthai)
                 VALUES (:referee_id, :from_date, :to_date, :reason, 'CHO_DUYET')"
            );
            $statement->execute([
                'referee_id' => $refereeId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reason' => $reason,
            ]);

            $leaveId = (int) $db->lastInsertId();

            $this->recordSystemLog($actorAccountId, 'Xin nghi phep trong tai', 'Donnghitrongtai', $leaveId, $ipAddress, $logNote);

            $db->commit();

            return $leaveId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelSelfLeaveRequest(
        int $refereeId,
        int $leaveId,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): ?array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $leave = $this->first(
                $this->baseLeaveRequestSelect() . '
                 WHERE dntt.idtrongtai = :referee_id
                   AND dntt.iddonnghi = :leave_id
                 LIMIT 1
                 FOR UPDATE',
                [
                    'referee_id' => $refereeId,
                    'leave_id' => $leaveId,
                ]
            );

            if ($leave === null) {
                $db->rollBack();
                return null;
            }

            if ((string) $leave['trangthai'] !== 'CHO_DUYET') {
                throw new \RuntimeException('LEAVE_NOT_CANCELABLE');
            }

            $statement = $db->prepare(
                "UPDATE Donnghitrongtai
                 SET trangthai = 'DA_HUY',
                     ngayxuly = CURRENT_TIMESTAMP
                 WHERE iddonnghi = :leave_id
                   AND idtrongtai = :referee_id
                   AND trangthai = 'CHO_DUYET'"
            );
            $statement->execute([
                'leave_id' => $leaveId,
                'referee_id' => $refereeId,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('LEAVE_NOT_CANCELABLE');
            }

            $this->recordSystemLog($actorAccountId, 'Huy don nghi phep trong tai', 'Donnghitrongtai', $leaveId, $ipAddress, $logNote);

            $db->commit();

            return $this->findLeaveRequestForReferee($refereeId, $leaveId);
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function recordRefereeLeaveRequestListView(
        int $refereeId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem danh sach don nghi phep trong tai', 'Trongtai', $refereeId, $ipAddress, $logNote);
    }

    public function recordRefereeLeaveRequestDetailView(
        int $leaveId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem chi tiet don nghi phep trong tai', 'Donnghitrongtai', $leaveId, $ipAddress, $logNote);
    }

    public function tournamentsForReferee(int $refereeId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                gd.idgiaidau,
                gd.tengiaidau,
                gd.trangthai,
                COUNT(DISTINCT pctt.idphancong) AS total_assignments
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             WHERE pctt.idtrongtai = :referee_id
             GROUP BY gd.idgiaidau, gd.tengiaidau, gd.trangthai
             ORDER BY gd.tengiaidau"
        );
        $statement->execute(['referee_id' => $refereeId]);

        return $statement->fetchAll();
    }

    public function venuesForReferee(int $refereeId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                sd.idsandau,
                sd.tensandau,
                vt.diachi AS diachi,
                sd.trangthai,
                COUNT(DISTINCT pctt.idphancong) AS total_assignments
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
              LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
              LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE pctt.idtrongtai = :referee_id
             GROUP BY sd.idsandau, sd.tensandau, vt.diachi, sd.trangthai
             ORDER BY sd.tensandau"
        );
        $statement->execute(['referee_id' => $refereeId]);

        return $statement->fetchAll();
    }

    public function assignmentScheduleForReferee(int $refereeId, array $filters = []): array
    {
        $this->syncSupervisorAttendanceStatuses();

        [$where, $bindings] = $this->assignmentScheduleWhere($refereeId, $filters);

        $statement = $this->db()->prepare(
            $this->baseAssignmentScheduleSelect() . '
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY
                CASE
                    WHEN pctt.trangthai = \'DA_XAC_NHAN\'
                     AND td.trangthai NOT IN (\'DA_HUY\', \'DA_HUY_KHONG_CO_GIAM_SAT\', \'DA_KET_THUC\')
                     AND gd.trangthai <> \'DA_HUY\'
                    THEN 0 ELSE 1
                END,
                td.thoigianbatdau ASC,
                pctt.ngayphancong DESC,
                pctt.idphancong DESC'
        );
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function assignmentScheduleStatsForReferee(int $refereeId, array $filters = []): array
    {
        [$where, $bindings] = $this->assignmentScheduleWhere($refereeId, $filters);

        $statement = $this->db()->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN pctt.trangthai = 'CHO_XAC_NHAN' THEN 1 ELSE 0 END) AS cho_xac_nhan,
                SUM(CASE WHEN pctt.trangthai = 'DA_XAC_NHAN' THEN 1 ELSE 0 END) AS da_xac_nhan,
                SUM(CASE WHEN pctt.trangthai = 'TU_CHOI' THEN 1 ELSE 0 END) AS tu_choi,
                SUM(CASE WHEN pctt.trangthai = 'DA_HUY' THEN 1 ELSE 0 END) AS da_huy,
                SUM(CASE WHEN DATE(td.thoigianbatdau) = CURRENT_DATE THEN 1 ELSE 0 END) AS hom_nay,
                SUM(CASE
                    WHEN td.thoigianbatdau > CURRENT_TIMESTAMP
                     AND td.trangthai NOT IN ('DA_HUY', 'DA_HUY_KHONG_CO_GIAM_SAT', 'DA_KET_THUC')
                     AND pctt.trangthai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')
                    THEN 1 ELSE 0
                END) AS sap_toi
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE " . implode(' AND ', $where)
        );
        $statement->execute($bindings);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'CHO_XAC_NHAN' => (int) ($row['cho_xac_nhan'] ?? 0),
            'DA_XAC_NHAN' => (int) ($row['da_xac_nhan'] ?? 0),
            'TU_CHOI' => (int) ($row['tu_choi'] ?? 0),
            'DA_HUY' => (int) ($row['da_huy'] ?? 0),
            'hom_nay' => (int) ($row['hom_nay'] ?? 0),
            'sap_toi' => (int) ($row['sap_toi'] ?? 0),
        ];
    }

    public function assignmentDetailForReferee(int $refereeId, int $assignmentId): ?array
    {
        $this->syncSupervisorAttendanceStatuses();

        return $this->first(
            $this->baseAssignmentScheduleSelect() . '
             WHERE pctt.idtrongtai = :referee_id
               AND pctt.idphancong = :assignment_id
             LIMIT 1',
            [
                'referee_id' => $refereeId,
                'assignment_id' => $assignmentId,
            ]
        );
    }

    public function matchAssignmentDetailForReferee(int $refereeId, int $matchId): ?array
    {
        $this->syncSupervisorAttendanceStatuses($matchId);

        return $this->first(
            $this->baseAssignmentScheduleSelect() . '
             WHERE pctt.idtrongtai = :referee_id
               AND pctt.idtrandau = :match_id
             ORDER BY pctt.idphancong DESC
             LIMIT 1',
            [
                'referee_id' => $refereeId,
                'match_id' => $matchId,
            ]
        );
    }

    public function coRefereesForMatch(int $matchId): array
    {
        $statement = $this->db()->prepare(
            "SELECT
                pctt.idphancong,
                pctt.idtrandau,
                pctt.idtrongtai,
                pctt.vaitro,
                pctt.trangthai,
                pctt.ngayphancong,
                tttd.xacnhanthamgia,
                tttd.thoigianxacnhan,
                CASE
                    WHEN pctt.trangthai <> 'DA_XAC_NHAN' THEN pctt.trangthai
                    WHEN COALESCE(tttd.xacnhanthamgia, 0) = 1 THEN 'THAM_GIA'
                    WHEN td.trangthai = 'DA_KET_THUC' THEN 'VANG'
                    ELSE 'CHUA_XAC_NHAN_CO_MAT'
                END AS trangthai_thamgia,
                tk.username,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tt.capbac,
                tt.kinhnghiem,
                tt.trangthai AS trongtai_trangthai
             FROM Phancongtrongtai pctt
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Trongtai tt ON tt.idtrongtai = pctt.idtrongtai
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
             LEFT JOIN Trongtaitrandau tttd
                ON tttd.idtrandau = pctt.idtrandau
               AND tttd.idtrongtai = pctt.idtrongtai
             WHERE pctt.idtrandau = :match_id
             ORDER BY pctt.vaitro, pctt.idphancong"
        );
        $statement->execute(['match_id' => $matchId]);

        return $statement->fetchAll();
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
                db.tendoibong AS doithang
             FROM Diemset ds
             JOIN Doibong db ON db.iddoibong = ds.doithangset
             WHERE ds.idketqua = :result_id
             ORDER BY ds.setthu"
        );
        $statement->execute(['result_id' => $resultId]);

        return $statement->fetchAll();
    }

    public function respondToAssignment(
        int $refereeId,
        int $assignmentId,
        string $newStatus,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote,
        string $reason
    ): array {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $assignment = $this->assignmentDetailForReferee($refereeId, $assignmentId);

            if ($assignment === null) {
                throw new \RuntimeException('ASSIGNMENT_NOT_FOUND');
            }

            $oldStatus = (string) $assignment['phancong_trangthai'];
            if ($newStatus === 'TU_CHOI') {
                $statement = $db->prepare(
                    "UPDATE Phancongtrongtai
                     SET trangthai = :new_status
                     WHERE idphancong = :assignment_id
                       AND idtrongtai = :referee_id
                       AND trangthai IN ('DA_XAC_NHAN', 'CHO_XAC_NHAN')"
                );
                $statement->execute([
                    'new_status' => $newStatus,
                    'assignment_id' => $assignmentId,
                    'referee_id' => $refereeId,
                ]);
            } else {
                $statement = $db->prepare(
                    "UPDATE Phancongtrongtai
                     SET trangthai = :new_status
                     WHERE idphancong = :assignment_id
                       AND idtrongtai = :referee_id
                       AND trangthai = 'CHO_XAC_NHAN'"
                );
                $statement->execute([
                    'new_status' => $newStatus,
                    'assignment_id' => $assignmentId,
                    'referee_id' => $refereeId,
                ]);
            }

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('ASSIGNMENT_NOT_ACTIONABLE');
            }

            $detail = $this->first(
                "SELECT idtrongtaitrandau
                 FROM Trongtaitrandau
                 WHERE idtrandau = :match_id
                   AND idtrongtai = :referee_id
                 LIMIT 1",
                [
                    'match_id' => (int) $assignment['idtrandau'],
                    'referee_id' => $refereeId,
                ]
            );

            if ($detail !== null) {
                $statement = $db->prepare(
                    "UPDATE Trongtaitrandau
                     SET vaitro = :role,
                        xacnhanthamgia = 0,
                        thoigianxacnhan = NULL
                     WHERE idtrongtaitrandau = :detail_id"
                );
                $statement->execute([
                    'role' => $assignment['vaitro'],
                    'detail_id' => (int) $detail['idtrongtaitrandau'],
                ]);
            } else {
                $statement = $db->prepare(
                    "INSERT INTO Trongtaitrandau (idtrandau, idtrongtai, vaitro, xacnhanthamgia, thoigianxacnhan)
                     VALUES (:match_id, :referee_id, :role, 0, NULL)"
                );
                $statement->execute([
                    'match_id' => (int) $assignment['idtrandau'],
                    'referee_id' => $refereeId,
                    'role' => $assignment['vaitro'],
                ]);
            }

            if ($newStatus === 'TU_CHOI') {
                $this->markMatchWaitingForRefereesIfNeeded((int) $assignment['idtrandau'], $actorAccountId);
            }

            $action = $newStatus === 'DA_XAC_NHAN' ? 'Xac nhan nhan phan cong tran dau' : 'Huy xac nhan phan cong tran dau';
            $this->recordStatusHistory('TRAN_DAU', (int) $assignment['idtrandau'], $oldStatus, $newStatus, $reason, $actorAccountId);
            $this->recordSystemLog($actorAccountId, $action, 'Phancongtrongtai', $assignmentId, $ipAddress, $logNote);

            $db->commit();

            return $this->assignmentDetailForReferee($refereeId, $assignmentId) ?? [];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private function markMatchWaitingForRefereesIfNeeded(int $matchId, int $actorAccountId): void
    {
        $match = $this->first(
            "SELECT trangthai
             FROM Trandau
             WHERE idtrandau = :match_id
             FOR UPDATE",
            ['match_id' => $matchId]
        );

        if ($match === null || !in_array((string) $match['trangthai'], ['DA_XEP_LICH', 'CHUA_DIEN_RA', 'SAP_DIEN_RA', 'TRONG_TAI_TRE_GIAM_SAT'], true)) {
            return;
        }

        if ($this->hasRequiredConfirmedReferees($matchId)) {
            return;
        }

        $statement = $this->db()->prepare(
            "UPDATE Trandau
             SET trangthai = 'CHO_XEP_LICH',
                 ngaycapnhat = CURRENT_TIMESTAMP
             WHERE idtrandau = :match_id"
        );
        $statement->execute(['match_id' => $matchId]);

        $this->recordStatusHistory(
            'TRAN_DAU',
            $matchId,
            (string) $match['trangthai'],
            'CHO_XEP_LICH',
            'Trong tai huy xac nhan lam tran dau thieu trong tai bat buoc',
            $actorAccountId
        );
    }

    private function hasRequiredConfirmedReferees(int $matchId): bool
    {
        $row = $this->first(
            "SELECT
                SUM(CASE WHEN vaitro = 'TRONG_TAI_CHINH' AND trangthai = 'DA_XAC_NHAN' THEN 1 ELSE 0 END) AS main_count,
                SUM(CASE WHEN vaitro = 'GIAM_SAT' AND trangthai = 'DA_XAC_NHAN' THEN 1 ELSE 0 END) AS supervisor_count
             FROM Phancongtrongtai
             WHERE idtrandau = :match_id",
            ['match_id' => $matchId]
        );

        return (int) ($row['main_count'] ?? 0) > 0 && (int) ($row['supervisor_count'] ?? 0) > 0;
    }

    public function confirmRefereeMatchParticipants(
        int $matchId,
        array $participantRefereeIds,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $assignments = $this->confirmedAssignmentsForMatch($matchId);
            $selected = array_fill_keys($participantRefereeIds, true);
            $statement = $db->prepare(
                "INSERT INTO Trongtaitrandau (idtrandau, idtrongtai, vaitro, xacnhanthamgia, thoigianxacnhan)
                 VALUES (:match_id, :referee_id, :role, :confirmed, :confirmed_at)
                 ON DUPLICATE KEY UPDATE
                    vaitro = VALUES(vaitro),
                    xacnhanthamgia = VALUES(xacnhanthamgia),
                    thoigianxacnhan = VALUES(thoigianxacnhan)"
            );

            foreach ($assignments as $assignment) {
                $isSelected = isset($selected[(int) $assignment['idtrongtai']]);
                $statement->execute([
                    'match_id' => $matchId,
                    'referee_id' => (int) $assignment['idtrongtai'],
                    'role' => $assignment['vaitro'],
                    'confirmed' => $isSelected ? 1 : 0,
                    'confirmed_at' => $isSelected ? date('Y-m-d H:i:s') : null,
                ]);
            }

            $this->recordSystemLog($actorAccountId, 'Xac nhan to trong tai tham gia', 'Trongtaitrandau', $matchId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function changeSupervisedMatchStatus(
        int $matchId,
        string $expectedOldStatus,
        string $newStatus,
        bool $recordStart,
        bool $recordEnd,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote,
        string $reason
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $setParts = [
                'trangthai = :new_status',
                'ngaycapnhat = CURRENT_TIMESTAMP',
            ];

            if ($recordStart) {
                $setParts[] = 'thoigianbatdau = CURRENT_TIMESTAMP';
                $setParts[] = 'thoigianketthuc = NULL';
            }

            if ($recordEnd) {
                $setParts[] = 'thoigianketthuc = CURRENT_TIMESTAMP';
            }

            $statement = $db->prepare(
                'UPDATE Trandau
                 SET ' . implode(', ', $setParts) . '
                 WHERE idtrandau = :match_id
                   AND trangthai = :old_status'
            );
            $statement->execute([
                'new_status' => $newStatus,
                'match_id' => $matchId,
                'old_status' => $expectedOldStatus,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('MATCH_STATUS_NOT_UPDATED');
            }

            $this->recordStatusHistory('TRAN_DAU', $matchId, $expectedOldStatus, $newStatus, $reason, $actorAccountId);
            $this->recordSystemLog($actorAccountId, $reason, 'Trandau', $matchId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function saveSupervisedMatchResult(
        int $matchId,
        array $result,
        array $sets,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $resultId = $this->saveResultInternal($matchId, $result, $sets, $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Ghi nhan ket qua tran dau', 'Ketquatrandau', $resultId, $ipAddress, $logNote);

            $db->commit();

            return $resultId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function finishSupervisedMatch(
        int $matchId,
        string $expectedOldStatus,
        ?array $result,
        ?array $sets,
        int $actorAccountId,
        ?string $ipAddress,
        string $matchLogNote,
        ?string $resultLogNote
    ): ?int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $resultId = null;

            $statement = $db->prepare(
                "UPDATE Trandau
                 SET trangthai = 'DA_KET_THUC',
                     thoigianketthuc = CURRENT_TIMESTAMP,
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idtrandau = :match_id
                   AND trangthai = :old_status"
            );
            $statement->execute([
                'match_id' => $matchId,
                'old_status' => $expectedOldStatus,
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('MATCH_STATUS_NOT_UPDATED');
            }

            $this->ensureAttendanceRowsForConfirmedAssignments($matchId);

            if ($result !== null && $sets !== null) {
                $resultId = $this->saveResultInternal($matchId, $result, $sets, $actorAccountId);
                $this->recordSystemLog($actorAccountId, 'Ghi nhan ket qua tran dau', 'Ketquatrandau', $resultId, $ipAddress, $resultLogNote);
            }

            $this->recordStatusHistory('TRAN_DAU', $matchId, $expectedOldStatus, 'DA_KET_THUC', 'Ket thuc tran dau', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Ket thuc tran dau', 'Trandau', $matchId, $ipAddress, $matchLogNote);

            $db->commit();

            return $resultId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function recordRefereeScheduleView(
        int $refereeId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem lich phan cong trong tai', 'Trongtai', $refereeId, $ipAddress, $logNote);
    }

    public function recordRefereeTournamentListView(
        int $refereeId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem danh sach giai dau duoc phan cong', 'Trongtai', $refereeId, $ipAddress, $logNote);
    }

    public function recordRefereeVenueListView(
        int $refereeId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem danh sach san dau duoc phan cong', 'Trongtai', $refereeId, $ipAddress, $logNote);
    }

    public function recordRefereeAssignmentView(
        int $assignmentId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem chi tiet phan cong trong tai', 'Phancongtrongtai', $assignmentId, $ipAddress, $logNote);
    }

    public function recordRefereeMatchAssignmentView(
        int $matchId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem chi tiet tran dau duoc phan cong', 'Trandau', $matchId, $ipAddress, $logNote);
    }

    public function recordRefereeMatchDetailView(
        int $matchId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem thong tin chi tiet tran dau', 'Trandau', $matchId, $ipAddress, $logNote);
    }

    public function recordRefereeSupervisionView(
        int $matchId,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $this->recordSystemLog($actorAccountId, 'Xem giao dien giam sat tran dau', 'Trandau', $matchId, $ipAddress, $logNote);
    }

    private function baseAssignmentScheduleSelect(): string
    {
        return "SELECT
                pctt.idphancong,
                pctt.idtrandau,
                pctt.idtrongtai,
                pctt.vaitro,
                pctt.trangthai AS phancong_trangthai,
                pctt.ngayphancong,
                tt.capbac,
                tttd.xacnhanthamgia,
                tttd.thoigianxacnhan,
                CASE
                    WHEN pctt.trangthai <> 'DA_XAC_NHAN' THEN pctt.trangthai
                    WHEN COALESCE(tttd.xacnhanthamgia, 0) = 1 THEN 'THAM_GIA'
                    WHEN td.trangthai = 'DA_KET_THUC' THEN 'VANG'
                    ELSE 'CHUA_XAC_NHAN_CO_MAT'
                END AS trangthai_thamgia,
                td.idgiaidau,
                gd.tengiaidau,
                cg.idcapgiaidau,
                cg.macapgiaidau,
                cg.tencapgiaidau,
                gd.trangthai AS giaidau_trangthai,
                gd.thoigianbatdau AS giaidau_thoigianbatdau,
                gd.thoigianketthuc AS giaidau_thoigianketthuc,
                td.idbangdau,
                bd.tenbang,
                td.iddoibong1,
                d1.tendoibong AS doi1,
                td.iddoibong2,
                d2.tendoibong AS doi2,
                td.idsandau,
                sd.tensandau,
                vt.diachi AS sandau_diachi,
                sd.trangthai AS sandau_trangthai,
                td.thoigianbatdau,
                td.thoigianketthuc,
                vd.tenvongdau AS vongdau,
                td.trangthai AS trandau_trangthai,
                kq.idketqua,
                kq.trangthai AS ketqua_trangthai,
                kq.diemdoi1,
                kq.diemdoi2,
                kq.sosetdoi1,
                kq.sosetdoi2,
                kq.iddoithang
             FROM Phancongtrongtai pctt
             JOIN Trongtai tt ON tt.idtrongtai = pctt.idtrongtai
             JOIN Trandau td ON td.idtrandau = pctt.idtrandau
             JOIN Giaidau gd ON gd.idgiaidau = td.idgiaidau
             JOIN Capgiaidau cg ON cg.idcapgiaidau = gd.idcapgiaidau
              LEFT JOIN Bangdau bd ON bd.idbangdau = td.idbangdau
              LEFT JOIN Vongdau vd ON vd.idvongdau = td.idvongdau
             JOIN Doibong d1 ON d1.iddoibong = td.iddoibong1
             JOIN Doibong d2 ON d2.iddoibong = td.iddoibong2
             LEFT JOIN Sandau sd ON sd.idsandau = td.idsandau
             LEFT JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             LEFT JOIN Trongtaitrandau tttd
                ON tttd.idtrandau = pctt.idtrandau
               AND tttd.idtrongtai = pctt.idtrongtai
             LEFT JOIN Ketquatrandau kq ON kq.idtrandau = td.idtrandau";
    }

    private function assignmentScheduleWhere(int $refereeId, array $filters): array
    {
        $where = ['pctt.idtrongtai = :referee_id'];
        $bindings = ['referee_id' => $refereeId];

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

        if (($filters['assignment_status'] ?? '') !== '') {
            $where[] = 'pctt.trangthai = :assignment_status';
            $bindings['assignment_status'] = $filters['assignment_status'];
        }

        if (($filters['match_status'] ?? '') !== '') {
            $where[] = 'td.trangthai = :match_status';
            $bindings['match_status'] = $filters['match_status'];
        }

        if (($filters['role'] ?? '') !== '') {
            $where[] = 'pctt.vaitro = :role';
            $bindings['role'] = $filters['role'];
        }

        if (($filters['tournament_id'] ?? null) !== null) {
            $where[] = 'td.idgiaidau = :tournament_id';
            $bindings['tournament_id'] = (int) $filters['tournament_id'];
        }

        if (($filters['venue_id'] ?? null) !== null) {
            $where[] = 'td.idsandau = :venue_id';
            $bindings['venue_id'] = (int) $filters['venue_id'];
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

    private function confirmedAssignmentsForMatch(int $matchId): array
    {
        $statement = $this->db()->prepare(
            "SELECT idtrandau, idtrongtai, vaitro, trangthai
             FROM Phancongtrongtai
             WHERE idtrandau = :match_id
               AND trangthai = 'DA_XAC_NHAN'
             ORDER BY vaitro, idphancong"
        );
        $statement->execute(['match_id' => $matchId]);

        return $statement->fetchAll();
    }

    private function ensureAttendanceRowsForConfirmedAssignments(int $matchId): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Trongtaitrandau (idtrandau, idtrongtai, vaitro, xacnhanthamgia, thoigianxacnhan)
             SELECT
                pctt.idtrandau,
                pctt.idtrongtai,
                pctt.vaitro,
                0,
                NULL
             FROM Phancongtrongtai pctt
             LEFT JOIN Trongtaitrandau tttd
                ON tttd.idtrandau = pctt.idtrandau
               AND tttd.idtrongtai = pctt.idtrongtai
             WHERE pctt.idtrandau = :match_id
               AND pctt.trangthai = 'DA_XAC_NHAN'
               AND tttd.idtrongtaitrandau IS NULL"
        );

        $statement->execute(['match_id' => $matchId]);
    }

    private function saveResultInternal(int $matchId, array $result, array $sets, int $actorAccountId): int
    {
        $existing = $this->first(
            "SELECT idketqua, trangthai
             FROM Ketquatrandau
             WHERE idtrandau = :match_id
             LIMIT 1",
            ['match_id' => $matchId]
        );

        if ($existing !== null && (string) $existing['trangthai'] === 'DA_CONG_BO') {
            throw new \RuntimeException('RESULT_ALREADY_PUBLISHED');
        }

        if ($existing === null) {
            $statement = $this->db()->prepare(
                "INSERT INTO Ketquatrandau
                    (idtrandau, iddoithang, diemdoi1, diemdoi2, sosetdoi1, sosetdoi2, trangthai, idnguoighinhan)
                 VALUES
                    (:match_id, :winner_team_id, :team_one_score, :team_two_score, :team_one_sets, :team_two_sets, 'CHO_CONG_BO', :actor_id)"
            );
            $statement->execute([
                'match_id' => $matchId,
                'winner_team_id' => $result['iddoithang'],
                'team_one_score' => $result['diemdoi1'],
                'team_two_score' => $result['diemdoi2'],
                'team_one_sets' => $result['sosetdoi1'],
                'team_two_sets' => $result['sosetdoi2'],
                'actor_id' => $actorAccountId,
            ]);

            $resultId = (int) $this->db()->lastInsertId();
        } else {
            $resultId = (int) $existing['idketqua'];
            $statement = $this->db()->prepare(
                "UPDATE Ketquatrandau
                 SET iddoithang = :winner_team_id,
                     diemdoi1 = :team_one_score,
                     diemdoi2 = :team_two_score,
                     sosetdoi1 = :team_one_sets,
                     sosetdoi2 = :team_two_sets,
                     trangthai = 'CHO_CONG_BO',
                     ngayghinhan = CURRENT_TIMESTAMP,
                     ngaycongbo = NULL,
                     idnguoighinhan = :actor_id
                 WHERE idketqua = :result_id
                   AND trangthai <> 'DA_CONG_BO'"
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
        }

        $this->replaceResultSetsInternal($resultId, $sets);

        return $resultId;
    }

    private function replaceResultSetsInternal(int $resultId, array $sets): void
    {
        $statement = $this->db()->prepare(
            "DELETE FROM Diemset
             WHERE idketqua = :result_id"
        );
        $statement->execute(['result_id' => $resultId]);

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

    private function baseLeaveRequestSelect(): string
    {
        return "SELECT
                dntt.iddonnghi,
                dntt.idtrongtai,
                dntt.tungay,
                dntt.denngay,
                DATEDIFF(dntt.denngay, dntt.tungay) + 1 AS songay,
                dntt.lydo,
                dntt.trangthai,
                dntt.ngaygui,
                dntt.ngayxuly,
                tt.capbac,
                tt.kinhnghiem,
                tt.trangthai AS trangthaitrongtai,
                nd.idtaikhoan,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                tk.username,
                tk.email,
                tk.trangthai AS trangthai_taikhoan
             FROM Donnghitrongtai dntt
             JOIN Trongtai tt ON tt.idtrongtai = dntt.idtrongtai
             JOIN Nguoidung nd ON nd.idnguoidung = tt.idnguoidung
             JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan";
    }

    private function leaveRequestWhereForReferee(int $refereeId, array $filters): array
    {
        $where = ['dntt.idtrongtai = :referee_id'];
        $bindings = ['referee_id' => $refereeId];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'dntt.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'dntt.tungay >= :from_date';
            $bindings['from_date'] = $filters['from'];
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'dntt.tungay <= :to_date';
            $bindings['to_date'] = $filters['to'];
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(dntt.lydo LIKE :keyword OR dntt.trangthai LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
        }

        return [$where, $bindings];
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

    private function syncSupervisorAttendanceStatuses(?int $matchId = null): void
    {
        try {
            (new Lichthidau())->syncSupervisorAttendanceStatuses(null, $matchId);
        } catch (Throwable) {
            // Lich phan cong phai van hien thi duoc neu buoc dong bo trang thai tu dong bi loi du lieu.
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
}
