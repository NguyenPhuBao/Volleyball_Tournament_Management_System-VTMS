<?php

namespace App\Repositories\Organizer;

use Illuminate\Support\Facades\DB;

final class OrganizerCoachAccountRepository
{
    public function listCoachAccounts(array $filters = []): array
    {
        $where = ['r.namerole = :role'];
        $bindings = ['role' => 'HUAN_LUYEN_VIEN'];

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

        if (($filters['trangthai'] ?? '') !== '') {
            $where[] = 'tk.trangthai = :status';
            $bindings['status'] = $filters['trangthai'];
        }

        $sql = $this->accountSelect().' WHERE '.implode(' AND ', $where).' ORDER BY tk.idtaikhoan DESC';

        return $this->rows(DB::select($sql, $bindings));
    }

    public function findCoachAccountById(int $accountId): ?array
    {
        $row = DB::selectOne(
            $this->accountSelect().' WHERE tk.idtaikhoan = :account_id AND r.namerole = :role LIMIT 1',
            [
                'account_id' => $accountId,
                'role' => 'HUAN_LUYEN_VIEN',
            ]
        );

        return $this->row($row);
    }

    public function updateAccountStatus(int $accountId, string $status): void
    {
        DB::update(
            "UPDATE Taikhoan
             SET trangthai = :status,
                 ngaycapnhat = CURRENT_TIMESTAMP
             WHERE idtaikhoan = :account_id",
            [
                'status' => $status,
                'account_id' => $accountId,
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

    private function accountSelect(): string
    {
        return "SELECT
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
