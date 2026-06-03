<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;

final class AdminAccountRepository
{
    private const ACCOUNT_FIELDS = ['username', 'password', 'email', 'sodienthoai', 'idrole', 'trangthai'];
    private const PROFILE_FIELDS = ['ten', 'hodem', 'gioitinh', 'ngaysinh', 'quequan', 'diachi', 'avatar', 'cccd'];

    public function findById(int $accountId): ?array
    {
        $row = DB::selectOne(
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

        return $this->row($row);
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

        return $this->row($row);
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
            $like = '%'.$filters['q'].'%';
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
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $sql .= ' ORDER BY tk.idtaikhoan DESC';

        return $this->rows(DB::select($sql, $bindings));
    }

    public function roles(): array
    {
        return $this->rows(DB::select(
            "SELECT idrole, namerole, mota
             FROM Role
             ORDER BY idrole"
        ));
    }

    public function findRoleById(int $roleId): ?array
    {
        $row = DB::selectOne(
            "SELECT idrole, namerole, mota
             FROM Role
             WHERE idrole = :role_id
             LIMIT 1",
            ['role_id' => $roleId]
        );

        return $this->row($row);
    }

    public function findRoleByName(string $roleName): ?array
    {
        $row = DB::selectOne(
            "SELECT idrole, namerole, mota
             FROM Role
             WHERE namerole = :role_name
             LIMIT 1",
            ['role_name' => $roleName]
        );

        return $this->row($row);
    }

    public function accountValueExists(string $field, string $value, ?int $excludeAccountId = null): bool
    {
        if (!in_array($field, ['username', 'email', 'sodienthoai'], true)) {
            return false;
        }

        $bindings = ['value' => $value];
        $sql = "SELECT 1 FROM Taikhoan WHERE {$field} = :value";

        if ($excludeAccountId !== null) {
            $sql .= ' AND idtaikhoan <> :exclude_account_id';
            $bindings['exclude_account_id'] = $excludeAccountId;
        }

        return DB::selectOne($sql.' LIMIT 1', $bindings) !== null;
    }

    public function profileValueExists(string $field, string $value, ?int $excludeAccountId = null): bool
    {
        if (!in_array($field, ['cccd'], true)) {
            return false;
        }

        $bindings = ['value' => $value];
        $sql = "SELECT 1 FROM Nguoidung WHERE {$field} = :value";

        if ($excludeAccountId !== null) {
            $sql .= ' AND idtaikhoan <> :exclude_account_id';
            $bindings['exclude_account_id'] = $excludeAccountId;
        }

        return DB::selectOne($sql.' LIMIT 1', $bindings) !== null;
    }

    public function createAccount(array $account, ?array $profile = null): int
    {
        return (int) DB::transaction(function () use ($account, $profile): int {
            DB::insert(
                "INSERT INTO Taikhoan (username, password, email, sodienthoai, idrole, trangthai)
                 VALUES (:username, :password, :email, :sodienthoai, :idrole, :trangthai)",
                [
                    'username' => $account['username'],
                    'password' => $account['password'],
                    'email' => $account['email'],
                    'sodienthoai' => $account['sodienthoai'] ?? null,
                    'idrole' => $account['idrole'],
                    'trangthai' => $account['trangthai'],
                ]
            );

            $accountId = (int) DB::getPdo()->lastInsertId();

            if ($profile !== null) {
                $this->insertProfile($accountId, $profile);
            }

            return $accountId;
        });
    }

    public function updateAccount(int $accountId, array $account, ?array $profile = null, ?string $oldPasswordHash = null): void
    {
        DB::transaction(function () use ($accountId, $account, $profile, $oldPasswordHash): void {
            if ($oldPasswordHash !== null) {
                $this->rememberPasswordHistory($accountId, $oldPasswordHash);
            }

            $this->updateAccountFields($accountId, $account);

            if ($profile !== null) {
                $this->upsertProfile($accountId, $profile);
            }
        });
    }

    public function softDelete(int $accountId): void
    {
        DB::update(
            "UPDATE Taikhoan
             SET trangthai = 'DA_HUY',
                 ngaycapnhat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id",
            ['account_id' => $accountId]
        );

        $this->closeActiveSessionsForAccount($accountId);
    }

    public function closeActiveSessionsForAccount(int $accountId): void
    {
        DB::update(
            "UPDATE Phiendangnhap
             SET trangthai = 'HET_HAN',
                 thoigiandangxuat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id
               AND trangthai = 'DANG_HOAT_DONG'",
            ['account_id' => $accountId]
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

    public function recordStatusHistory(string $targetType, int $targetId, ?string $oldStatus, string $newStatus, ?string $reason, ?int $actorId): void
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

    private function rememberPasswordHistory(int $accountId, string $oldPasswordHash): void
    {
        DB::insert(
            "INSERT INTO Lichsumatkhau (idtaikhoan, passwordold)
             VALUES (:account_id, :old_password)",
            [
                'account_id' => $accountId,
                'old_password' => $oldPasswordHash,
            ]
        );
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

        DB::update('UPDATE Taikhoan SET '.implode(', ', $sets).' WHERE idtaikhoan = :account_id', $bindings);
    }

    private function insertProfile(int $accountId, array $profile): void
    {
        DB::insert(
            "INSERT INTO Nguoidung
                (idtaikhoan, ten, hodem, gioitinh, ngaysinh, quequan, diachi, avatar, cccd)
             VALUES
                (:account_id, :ten, :hodem, :gioitinh, :ngaysinh, :quequan, :diachi, :avatar, :cccd)",
            [
                'account_id' => $accountId,
                'ten' => $profile['ten'],
                'hodem' => $profile['hodem'],
                'gioitinh' => $profile['gioitinh'],
                'ngaysinh' => $profile['ngaysinh'] ?? null,
                'quequan' => $profile['quequan'] ?? null,
                'diachi' => $profile['diachi'] ?? null,
                'avatar' => $profile['avatar'] ?? null,
                'cccd' => $profile['cccd'] ?? null,
            ]
        );
    }

    private function upsertProfile(int $accountId, array $profile): void
    {
        $existing = DB::selectOne(
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

        DB::update('UPDATE Nguoidung SET '.implode(', ', $sets).' WHERE idtaikhoan = :account_id', $bindings);
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
