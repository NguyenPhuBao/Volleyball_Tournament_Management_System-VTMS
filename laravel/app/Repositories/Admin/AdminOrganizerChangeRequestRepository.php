<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AdminOrganizerChangeRequestRepository
{
    public function listOrganizerChangeRequests(array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->buildOrganizerWhere($filters);
        $sql = $this->baseOrganizerSelect();

        if ($where !== []) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $sql .= ' ORDER BY yc.ngaygui DESC, yc.idyeucaucapnhat DESC LIMIT '.(int) $limit.' OFFSET '.(int) $offset;

        return $this->rows(DB::select($sql, $bindings));
    }

    public function countOrganizerChangeRequests(array $filters): int
    {
        [$where, $bindings] = $this->buildOrganizerWhere($filters);
        $sql = "SELECT COUNT(*) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung";

        if ($where !== []) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $row = DB::selectOne($sql, $bindings);

        return (int) ($row->total ?? 0);
    }

    public function statusCountsOrganizerChangeRequests(array $filters): array
    {
        [$where, $bindings] = $this->buildOrganizerWhere($filters);
        $sql = "SELECT yc.trangthai, COUNT(*) AS total
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung";

        if ($where !== []) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $sql .= ' GROUP BY yc.trangthai';
        $counts = [
            'CHO_DUYET' => 0,
            'DA_DUYET' => 0,
            'TU_CHOI' => 0,
        ];

        foreach (DB::select($sql, $bindings) as $row) {
            $status = (string) $row->trangthai;

            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row->total;
            }
        }

        return $counts;
    }

    public function findOrganizerChangeRequestById(int $requestId): ?array
    {
        $row = DB::selectOne(
            $this->baseOrganizerSelect()." WHERE yc.banglienquan = :target_table AND yc.idyeucaucapnhat = :request_id LIMIT 1",
            [
                'target_table' => 'Bantochuc',
                'request_id' => $requestId,
            ]
        );

        return $this->row($row);
    }

    public function approveOrganizerChangeRequest(
        int $requestId,
        int $organizerId,
        string $field,
        mixed $newValue,
        int $adminId,
        ?string $ipAddress,
        ?string $note
    ): void {
        DB::transaction(function () use ($requestId, $organizerId, $field, $newValue, $adminId, $ipAddress, $note): void {
            $this->updateOrganizerField($organizerId, $field, $newValue);
            $this->markRequestProcessed($requestId, 'DA_DUYET');
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', 'DA_DUYET', 'Duyet thay doi thong tin ban to chuc', $adminId);
            $this->recordSystemLog($adminId, 'Duyet thay doi thong tin ban to chuc', 'Bantochuc', $organizerId, $ipAddress, $note);
        });
    }

    public function rejectOrganizerChangeRequest(
        int $requestId,
        int $adminId,
        ?string $ipAddress,
        ?string $note
    ): void {
        DB::transaction(function () use ($requestId, $adminId, $ipAddress, $note): void {
            $this->markRequestProcessed($requestId, 'TU_CHOI');
            $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', 'TU_CHOI', $note ?: 'Tu choi thay doi thong tin ban to chuc', $adminId);
            $this->recordSystemLog($adminId, 'Tu choi thay doi thong tin ban to chuc', 'Yeucaucapnhathoso', $requestId, $ipAddress, $note);
        });
    }

    private function updateOrganizerField(int $organizerId, string $field, mixed $value): void
    {
        if (!in_array($field, ['donvi', 'chucvu', 'trangthai'], true)) {
            throw new RuntimeException('INVALID_FIELD');
        }

        DB::update(
            "UPDATE Bantochuc
             SET {$field} = :value
             WHERE idbantochuc = :organizer_id",
            [
                'value' => $value,
                'organizer_id' => $organizerId,
            ]
        );
    }

    private function markRequestProcessed(int $requestId, string $status): void
    {
        $affected = DB::update(
            "UPDATE Yeucaucapnhathoso
             SET trangthai = :status,
                 ngayxuly = CURRENT_TIMESTAMP
             WHERE idyeucaucapnhat = :request_id
               AND banglienquan = 'Bantochuc'
               AND trangthai = 'CHO_DUYET'",
            [
                'status' => $status,
                'request_id' => $requestId,
            ]
        );

        if ($affected !== 1) {
            throw new RuntimeException('REQUEST_NOT_PENDING');
        }
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

    private function baseOrganizerSelect(): string
    {
        return "SELECT
                yc.idyeucaucapnhat,
                yc.idnguoidung,
                yc.banglienquan,
                yc.truongcapnhat,
                yc.giatricu,
                yc.giatrimoi,
                yc.lydo,
                yc.trangthai,
                yc.ngaygui,
                yc.ngayxuly,
                btc.idbantochuc,
                btc.donvi AS current_donvi,
                btc.chucvu AS current_chucvu,
                btc.trangthai AS current_trangthai,
                tk.idtaikhoan,
                tk.username,
                tk.email,
                r.namerole AS role,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
            FROM Yeucaucapnhathoso yc
            JOIN Nguoidung nd ON nd.idnguoidung = yc.idnguoidung
            JOIN Taikhoan tk ON tk.idtaikhoan = nd.idtaikhoan
            JOIN Role r ON r.idrole = tk.idrole
            JOIN Bantochuc btc ON btc.idnguoidung = nd.idnguoidung";
    }

    private function buildOrganizerWhere(array $filters): array
    {
        $where = ['yc.banglienquan = :target_table'];
        $bindings = ['target_table' => 'Bantochuc'];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(yc.truongcapnhat LIKE :q_field
                OR yc.giatricu LIKE :q_old_value
                OR yc.giatrimoi LIKE :q_new_value
                OR yc.lydo LIKE :q_reason
                OR tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten
                OR btc.donvi LIKE :q_unit
                OR btc.chucvu LIKE :q_position)";
            $like = '%'.$filters['q'].'%';
            $bindings['q_field'] = $like;
            $bindings['q_old_value'] = $like;
            $bindings['q_new_value'] = $like;
            $bindings['q_reason'] = $like;
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
            $bindings['q_unit'] = $like;
            $bindings['q_position'] = $like;
        }

        if (($filters['trangthai'] ?? '') !== '') {
            $where[] = 'yc.trangthai = :status';
            $bindings['status'] = $filters['trangthai'];
        }

        if (($filters['truongcapnhat'] ?? '') !== '') {
            $where[] = 'yc.truongcapnhat = :field';
            $bindings['field'] = $filters['truongcapnhat'];
        }

        if (($filters['idnguoidung'] ?? null) !== null) {
            $where[] = 'yc.idnguoidung = :user_id';
            $bindings['user_id'] = (int) $filters['idnguoidung'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'yc.ngaygui >= :from_date';
            $bindings['from_date'] = $filters['from'].' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'yc.ngaygui <= :to_date';
            $bindings['to_date'] = $filters['to'].' 23:59:59';
        }

        return [$where, $bindings];
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
