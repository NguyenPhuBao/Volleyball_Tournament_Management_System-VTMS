<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;

final class AdminUserRepository
{
    private const PROFILE_FIELDS = ['ten', 'hodem', 'gioitinh', 'ngaysinh', 'quequan', 'diachi', 'avatar', 'cccd'];
    private const ACCOUNT_FIELDS = ['email', 'sodienthoai', 'trangthai'];

    public function listUsers(array $filters = []): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten
                OR nd.cccd LIKE :q_cccd
                OR tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR tk.sodienthoai LIKE :q_phone)";
            $like = '%'.$filters['q'].'%';
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
            $bindings['q_cccd'] = $like;
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_phone'] = $like;
        }

        if (($filters['role'] ?? '') !== '') {
            $where[] = 'r.namerole = :role';
            $bindings['role'] = $filters['role'];
        }

        if (($filters['gioitinh'] ?? '') !== '') {
            $where[] = 'nd.gioitinh = :gender';
            $bindings['gender'] = $filters['gioitinh'];
        }

        if (($filters['trangthai_taikhoan'] ?? '') !== '') {
            $where[] = 'tk.trangthai = :account_status';
            $bindings['account_status'] = $filters['trangthai_taikhoan'];
        }

        $sql = $this->baseSelect();

        if ($where !== []) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $sql .= ' ORDER BY nd.idnguoidung DESC';

        return $this->rows(DB::select($sql, $bindings));
    }

    public function findById(int $userId): ?array
    {
        $row = DB::selectOne(
            $this->baseSelect().' WHERE nd.idnguoidung = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        return $this->row($row);
    }

    public function profileValueExists(string $field, string $value, ?int $excludeUserId = null): bool
    {
        if (!in_array($field, ['cccd'], true)) {
            return false;
        }

        $bindings = ['value' => $value];
        $sql = "SELECT 1 FROM Nguoidung WHERE {$field} = :value";

        if ($excludeUserId !== null) {
            $sql .= ' AND idnguoidung <> :exclude_user_id';
            $bindings['exclude_user_id'] = $excludeUserId;
        }

        return DB::selectOne($sql.' LIMIT 1', $bindings) !== null;
    }

    public function accountValueExists(string $field, string $value, ?int $excludeAccountId = null): bool
    {
        if (!in_array($field, ['email', 'sodienthoai'], true)) {
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

    public function updateUser(int $userId, array $profile, array $account = []): void
    {
        DB::transaction(function () use ($userId, $profile, $account): void {
            if ($profile !== []) {
                $this->updateProfileFields($userId, $profile);
            }

            if ($account !== []) {
                $this->updateAccountFields($userId, $account);
            }
        });
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

    private function updateProfileFields(int $userId, array $profile): void
    {
        $sets = [];
        $bindings = ['user_id' => $userId];

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

        DB::update('UPDATE Nguoidung SET '.implode(', ', $sets).' WHERE idnguoidung = :user_id', $bindings);
    }

    private function updateAccountFields(int $userId, array $account): void
    {
        $sets = [];
        $bindings = ['user_id' => $userId];

        foreach (self::ACCOUNT_FIELDS as $field) {
            if (!array_key_exists($field, $account)) {
                continue;
            }

            $sets[] = "tk.{$field} = :{$field}";
            $bindings[$field] = $account[$field];
        }

        if ($sets === []) {
            return;
        }

        $sets[] = 'tk.ngaycapnhat = CURRENT_TIMESTAMP';

        DB::update(
            'UPDATE Taikhoan tk
             JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
             SET '.implode(', ', $sets).'
             WHERE nd.idnguoidung = :user_id',
            $bindings
        );
    }

    private function baseSelect(): string
    {
        return "SELECT
                nd.idnguoidung,
                nd.idtaikhoan,
                nd.ten,
                nd.hodem,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nd.gioitinh,
                nd.ngaysinh,
                nd.quequan,
                nd.diachi,
                nd.avatar,
                nd.cccd,
                nd.ngaytao,
                nd.ngaycapnhat,
                tk.username,
                tk.email,
                tk.sodienthoai,
                tk.trangthai AS trangthai_taikhoan,
                r.idrole,
                r.namerole AS role,
                r.mota AS role_mota
            FROM Nguoidung nd
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole";
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
