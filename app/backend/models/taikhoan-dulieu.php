<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Taikhoan extends Model
{
    private const ACCOUNT_FIELDS = ['username', 'password', 'email', 'sodienthoai', 'idrole', 'trangthai'];
    private const PROFILE_FIELDS = ['ten', 'hodem', 'gioitinh', 'ngaysinh', 'quequan', 'diachi', 'avatar', 'cccd'];

    public function findByIdentifier(string $identifier): ?array
    {
        return $this->first(
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
    }

    public function findByUsername(string $username): ?array
    {
        return $this->findByIdentifier($username);
    }

    public function findById(int $accountId): ?array
    {
        return $this->first(
            "SELECT
                tk.idtaikhoan,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.idrole,
                tk.trangthai,
                tk.ngaytao,
                tk.ngaycapnhat,
                r.namerole AS role,
                r.mota AS role_mota,
                nd.idnguoidung,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd
            FROM Taikhoan tk
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
            WHERE tk.idtaikhoan = :account_id
            LIMIT 1",
            ['account_id' => $accountId]
        );
    }

    public function findByIdWithPassword(int $accountId): ?array
    {
        return $this->first(
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
    }

    public function listAccounts(array $filters = []): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR tk.sodienthoai LIKE :q_phone
                OR nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten)";
            $like = '%' . $filters['q'] . '%';
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_phone'] = $like;
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
        }

        if (($filters['role'] ?? '') !== '') {
            $where[] = 'r.namerole = :role';
            $bindings['role'] = $filters['role'];
        }

        if (($filters['trangthai'] ?? '') !== '') {
            $where[] = 'tk.trangthai = :status';
            $bindings['status'] = $filters['trangthai'];
        }

        $sql = "SELECT
                tk.idtaikhoan,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.idrole,
                tk.trangthai,
                tk.ngaytao,
                tk.ngaycapnhat,
                r.namerole AS role,
                r.mota AS role_mota,
                nd.idnguoidung,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd
            FROM Taikhoan tk
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY tk.idtaikhoan DESC';

        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function roles(): array
    {
        $statement = $this->db()->query(
            "SELECT idrole, namerole, mota
             FROM Role
             ORDER BY idrole"
        );

        return $statement->fetchAll();
    }

    public function findRoleById(int $roleId): ?array
    {
        return $this->first(
            "SELECT idrole, namerole, mota
             FROM Role
             WHERE idrole = :role_id
             LIMIT 1",
            ['role_id' => $roleId]
        );
    }

    public function findRoleByName(string $roleName): ?array
    {
        return $this->first(
            "SELECT idrole, namerole, mota
             FROM Role
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );
    }

    public function findActiveByUsername(string $username): ?array
    {
        return $this->first(
            "SELECT
                tk.idtaikhoan,
                tk.username,
                tk.password,
                tk.email,
                tk.trangthai,
                r.namerole AS role,
                nd.hodem,
                nd.ten
            FROM Taikhoan tk
            JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
            WHERE tk.username = :username
              AND tk.trangthai = 'HOAT_DONG'
            LIMIT 1",
            ['username' => $username]
        );
    }

    public function accountValueExists(string $field, string $value, ?int $excludeAccountId = null): bool
    {
        if (!in_array($field, ['username', 'email', 'sodienthoai'], true)) {
            return false;
        }

        $bindings = ['value' => $value];
        $sql = "SELECT 1
            FROM Taikhoan
            WHERE {$field} = :value";

        if ($excludeAccountId !== null) {
            $sql .= ' AND idtaikhoan <> :exclude_account_id';
            $bindings['exclude_account_id'] = $excludeAccountId;
        }

        $sql .= ' LIMIT 1';

        return $this->first($sql, $bindings) !== null;
    }

    public function profileValueExists(string $field, string $value, ?int $excludeAccountId = null): bool
    {
        if (!in_array($field, ['cccd'], true)) {
            return false;
        }

        $bindings = ['value' => $value];
        $sql = "SELECT 1
            FROM Nguoidung
            WHERE {$field} = :value";

        if ($excludeAccountId !== null) {
            $sql .= ' AND idtaikhoan <> :exclude_account_id';
            $bindings['exclude_account_id'] = $excludeAccountId;
        }

        $sql .= ' LIMIT 1';

        return $this->first($sql, $bindings) !== null;
    }

    public function createAccount(array $account, ?array $profile = null): int
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Taikhoan (username, password, email, sodienthoai, idrole, trangthai)
                 VALUES (:username, :password, :email, :sodienthoai, :idrole, :trangthai)"
            );
            $statement->execute([
                'username' => $account['username'],
                'password' => $account['password'],
                'email' => $account['email'],
                'sodienthoai' => $account['sodienthoai'] ?? null,
                'idrole' => $account['idrole'],
                'trangthai' => $account['trangthai'],
            ]);

            $accountId = (int) $db->lastInsertId();

            if ($profile !== null) {
                $this->insertProfile($accountId, $profile);
            }

            $db->commit();

            return $accountId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function updateAccount(int $accountId, array $account, ?array $profile = null, ?string $oldPasswordHash = null): void
    {
        $db = $this->db();

        try {
            $db->beginTransaction();

            if ($oldPasswordHash !== null) {
                $this->rememberPasswordHistory($accountId, $oldPasswordHash);
            }

            $this->updateAccountFields($accountId, $account);

            if ($profile !== null) {
                $this->upsertProfile($accountId, $profile);
            }

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function softDelete(int $accountId): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Taikhoan
             SET trangthai = 'DA_HUY',
                 ngaycapnhat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id"
        );

        $statement->execute(['account_id' => $accountId]);
        $this->closeActiveSessionsForAccount($accountId);
    }

    public function createLoginSession(int $accountId, string $token): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Phiendangnhap (idtaikhoan, token, trangthai)
             VALUES (:account_id, :token, 'DANG_HOAT_DONG')"
        );

        $statement->execute([
            'account_id' => $accountId,
            'token' => $token,
        ]);
    }

    public function closeLoginSession(string $token): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Phiendangnhap
             SET trangthai = 'DA_DANG_XUAT',
                 thoigiandangxuat = CURRENT_TIMESTAMP
             WHERE token = :token
               AND trangthai = 'DANG_HOAT_DONG'"
        );

        $statement->execute(['token' => $token]);
    }

    public function closeActiveSessionsForAccount(int $accountId): void
    {
        $statement = $this->db()->prepare(
            "UPDATE Phiendangnhap
             SET trangthai = 'HET_HAN',
                 thoigiandangxuat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id
               AND trangthai = 'DANG_HOAT_DONG'"
        );

        $statement->execute(['account_id' => $accountId]);
    }

    public function recordLoginHistory(int $accountId, string $result, ?string $ipAddress, ?string $device, ?string $note = null): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Lichsudangnhap (idtaikhoan, ipaddress, thietbi, ketqua, ghichu)
             VALUES (:account_id, :ip_address, :device, :result, :note)"
        );

        $statement->execute([
            'account_id' => $accountId,
            'ip_address' => $ipAddress,
            'device' => $device,
            'result' => $result,
            'note' => $note,
        ]);
    }

    public function recordSystemLog(?int $accountId, string $action, string $targetTable, ?int $targetId, ?string $ipAddress, ?string $note = null): void
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

    public function recordStatusHistory(string $targetType, int $targetId, ?string $oldStatus, string $newStatus, ?string $reason, ?int $actorId): void
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

    private function rememberPasswordHistory(int $accountId, string $oldPasswordHash): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Lichsumatkhau (idtaikhoan, passwordold)
             VALUES (:account_id, :old_password)"
        );

        $statement->execute([
            'account_id' => $accountId,
            'old_password' => $oldPasswordHash,
        ]);
    }

    private function updateAccountFields(int $accountId, array $account): void
    {
        $sets = [];
        $bindings = ['account_id' => $accountId];

        foreach (self::ACCOUNT_FIELDS as $field) {
            if (!array_key_exists($field, $account)) {
                continue;
            }

            $sets[] = "{$field} = :{$field}";
            $bindings[$field] = $account[$field];
        }

        if ($sets === []) {
            return;
        }

        $sets[] = 'ngaycapnhat = CURRENT_TIMESTAMP';

        $statement = $this->db()->prepare(
            'UPDATE Taikhoan SET ' . implode(', ', $sets) . ' WHERE idtaikhoan = :account_id'
        );

        $statement->execute($bindings);
    }

    private function insertProfile(int $accountId, array $profile): void
    {
        $statement = $this->db()->prepare(
            "INSERT INTO Nguoidung
                (idtaikhoan, ten, hodem, gioitinh, ngaysinh, quequan, diachi, avatar, cccd)
             VALUES
                (:account_id, :ten, :hodem, :gioitinh, :ngaysinh, :quequan, :diachi, :avatar, :cccd)"
        );

        $statement->execute([
            'account_id' => $accountId,
            'ten' => $profile['ten'],
            'hodem' => $profile['hodem'],
            'gioitinh' => $profile['gioitinh'],
            'ngaysinh' => $profile['ngaysinh'] ?? null,
            'quequan' => $profile['quequan'] ?? null,
            'diachi' => $profile['diachi'] ?? null,
            'avatar' => $profile['avatar'] ?? null,
            'cccd' => $profile['cccd'] ?? null,
        ]);
    }

    private function upsertProfile(int $accountId, array $profile): void
    {
        $existing = $this->first(
            "SELECT idnguoidung
             FROM Nguoidung
             WHERE idtaikhoan = :account_id
             LIMIT 1",
            ['account_id' => $accountId]
        );

        if ($existing === null) {
            $this->insertProfile($accountId, $profile);
            return;
        }

        $sets = [];
        $bindings = ['account_id' => $accountId];

        foreach (self::PROFILE_FIELDS as $field) {
            if (!array_key_exists($field, $profile)) {
                continue;
            }

            $sets[] = "{$field} = :{$field}";
            $bindings[$field] = $profile[$field];
        }

        if ($sets === []) {
            return;
        }

        $sets[] = 'ngaycapnhat = CURRENT_TIMESTAMP';

        $statement = $this->db()->prepare(
            'UPDATE Nguoidung SET ' . implode(', ', $sets) . ' WHERE idtaikhoan = :account_id'
        );

        $statement->execute($bindings);
    }
}
