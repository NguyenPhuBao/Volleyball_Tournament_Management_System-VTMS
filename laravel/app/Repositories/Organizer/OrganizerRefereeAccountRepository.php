<?php

namespace App\Repositories\Organizer;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class OrganizerRefereeAccountRepository
{
    public function listRefereeAccounts(array $filters = []): array
    {
        $where = ['r.namerole = :role'];
        $bindings = ['role' => 'TRONG_TAI'];

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

        return $this->rows(DB::select(
            $this->accountSelect().' WHERE '.implode(' AND ', $where).' ORDER BY tk.idtaikhoan DESC',
            $bindings
        ));
    }

    public function findRefereeAccountById(int $accountId): ?array
    {
        $row = DB::selectOne(
            $this->accountSelect().' WHERE tk.idtaikhoan = :account_id AND r.namerole = :role LIMIT 1',
            [
                'account_id' => $accountId,
                'role' => 'TRONG_TAI',
            ]
        );

        return $this->row($row);
    }

    public function findRefereeByAccountId(int $accountId): ?array
    {
        $row = DB::selectOne(
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

        return $this->row($row);
    }

    public function latestRegistrationRequest(int $refereeId, int $organizerId): ?array
    {
        $row = DB::selectOne(
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

        return $this->row($row);
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
        DB::transaction(function () use (
            $refereeId,
            $targetAccountId,
            $newRefereeStatus,
            $newAccountStatus,
            $requestId,
            $requestStatus,
            $reason,
            $actorAccountId,
            $ipAddress,
            $systemAction,
            $logNote
        ): void {
            $updatedReferee = DB::update(
                "UPDATE Trongtai
                 SET trangthai = :new_referee_status
                 WHERE idtrongtai = :referee_id
                   AND trangthai = 'CHO_DUYET'",
                [
                    'new_referee_status' => $newRefereeStatus,
                    'referee_id' => $refereeId,
                ]
            );

            if ($updatedReferee !== 1) {
                throw new RuntimeException('REFEREE_REGISTRATION_NOT_UPDATED');
            }

            $updatedAccount = DB::update(
                "UPDATE Taikhoan
                 SET trangthai = :new_account_status,
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idtaikhoan = :account_id
                   AND trangthai = 'CHO_DUYET'",
                [
                    'new_account_status' => $newAccountStatus,
                    'account_id' => $targetAccountId,
                ]
            );

            if ($updatedAccount !== 1) {
                throw new RuntimeException('REFEREE_ACCOUNT_NOT_UPDATED');
            }

            $this->recordStatusHistory('TAI_KHOAN', $targetAccountId, 'CHO_DUYET', $newAccountStatus, $reason, $actorAccountId);

            if ($requestId !== null && $requestStatus !== null) {
                $updatedRequest = DB::update(
                    "UPDATE Yeucauxacnhan
                     SET trangthai = :request_status,
                         ngayxuly = CURRENT_TIMESTAMP,
                         ghichu = :reason
                     WHERE idyeucau = :request_id
                       AND trangthai = 'CHO_DUYET'",
                    [
                        'request_status' => $requestStatus,
                        'reason' => $reason,
                        'request_id' => $requestId,
                    ]
                );

                if ($updatedRequest === 1) {
                    $this->recordStatusHistory('YEU_CAU_XAC_NHAN', $requestId, 'CHO_DUYET', $requestStatus, $reason, $actorAccountId);
                }
            }

            $this->recordSystemLog($actorAccountId, $systemAction, 'Trongtai', $refereeId, $ipAddress, $logNote);
            $this->recordSystemLog($actorAccountId, $systemAction, 'Taikhoan', $targetAccountId, $ipAddress, $logNote);
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
