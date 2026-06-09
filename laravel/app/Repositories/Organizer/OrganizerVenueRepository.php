<?php

namespace App\Repositories\Organizer;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class OrganizerVenueRepository
{
    public function listVenues(array $filters = []): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(sd.tensandau LIKE :keyword OR vt.tenvitrithidau LIKE :keyword OR vt.diachi LIKE :keyword OR sd.mota LIKE :keyword)';
            $bindings['keyword'] = '%'.$filters['q'].'%';
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'sd.trangthai = :status';
            $bindings['status'] = $filters['status'];
        }

        $sql = "SELECT
                sd.idsandau,
                sd.idvitrithidau,
                sd.tensandau,
                sd.succhua,
                sd.mota,
                sd.trangthai,
                sd.ngaytao,
                sd.ngaycapnhat,
                vt.tenvitrithidau,
                vt.diachi
            FROM Sandau sd
            JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau";

        if ($where !== []) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        $sql .= ' ORDER BY sd.ngaytao DESC, sd.idsandau DESC';

        return $this->rows(DB::select($sql, $bindings));
    }

    public function findById(int $venueId): ?array
    {
        $row = DB::selectOne(
            "SELECT
                sd.idsandau,
                sd.idvitrithidau,
                sd.tensandau,
                sd.succhua,
                sd.mota,
                sd.trangthai,
                sd.ngaytao,
                sd.ngaycapnhat,
                vt.tenvitrithidau,
                vt.diachi
             FROM Sandau sd
             JOIN Vitrithidau vt ON vt.idvitrithidau = sd.idvitrithidau
             WHERE sd.idsandau = :venue_id
             LIMIT 1",
            ['venue_id' => $venueId]
        );

        return $this->row($row);
    }

    public function existsByNameAndLocation(string $name, int $locationId, ?int $excludeVenueId = null): bool
    {
        $bindings = [
            'name' => $name,
            'location_id' => $locationId,
        ];

        $sql = "SELECT 1
            FROM Sandau
            WHERE tensandau = :name
              AND idvitrithidau = :location_id";

        if ($excludeVenueId !== null) {
            $sql .= ' AND idsandau <> :exclude_venue_id';
            $bindings['exclude_venue_id'] = $excludeVenueId;
        }

        return DB::selectOne($sql.' LIMIT 1', $bindings) !== null;
    }

    public function createVenue(array $venue, int $actorAccountId, ?string $ipAddress, string $logNote): int
    {
        return (int) DB::transaction(function () use ($venue, $actorAccountId, $ipAddress, $logNote): int {
            DB::insert(
                "INSERT INTO Sandau (idvitrithidau, tensandau, succhua, mota, trangthai)
                 VALUES (:location_id, :name, :capacity, :description, :status)",
                [
                    'location_id' => $venue['idvitrithidau'],
                    'name' => $venue['tensandau'],
                    'capacity' => $venue['succhua'],
                    'description' => $venue['mota'],
                    'status' => $venue['trangthai'],
                ]
            );

            $venueId = (int) DB::getPdo()->lastInsertId();

            $this->recordStatusHistory('SAN_DAU', $venueId, null, (string) $venue['trangthai'], 'Bo sung san dau', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Bo sung san dau', 'Sandau', $venueId, $ipAddress, $logNote);

            return $venueId;
        });
    }

    public function updateVenue(
        int $venueId,
        array $changes,
        string $oldStatus,
        ?string $newStatus,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        DB::transaction(function () use ($venueId, $changes, $oldStatus, $newStatus, $actorAccountId, $ipAddress, $logNote): void {
            $sets = [];
            $bindings = ['venue_id' => $venueId];

            foreach (['idvitrithidau', 'tensandau', 'succhua', 'mota', 'trangthai'] as $field) {
                if (!array_key_exists($field, $changes)) {
                    continue;
                }

                $sets[] = "{$field} = :{$field}";
                $bindings[$field] = $changes[$field];
            }

            if ($sets === []) {
                throw new RuntimeException('VENUE_NOT_UPDATED');
            }

            $sets[] = 'ngaycapnhat = CURRENT_TIMESTAMP';

            $affected = DB::update(
                'UPDATE Sandau SET '.implode(', ', $sets).' WHERE idsandau = :venue_id',
                $bindings
            );

            if ($affected !== 1) {
                throw new RuntimeException('VENUE_NOT_UPDATED');
            }

            if ($newStatus !== null && $newStatus !== $oldStatus) {
                $this->recordStatusHistory('SAN_DAU', $venueId, $oldStatus, $newStatus, 'Cap nhat trang thai san dau', $actorAccountId);
            }

            $this->recordSystemLog($actorAccountId, 'Cap nhat san dau', 'Sandau', $venueId, $ipAddress, $logNote);
        });
    }

    public function deactivateVenue(
        int $venueId,
        string $oldStatus,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        DB::transaction(function () use ($venueId, $oldStatus, $reason, $actorAccountId, $ipAddress, $logNote): void {
            $affected = DB::update(
                "UPDATE Sandau
                 SET trangthai = 'NGUNG_SU_DUNG',
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idsandau = :venue_id
                   AND trangthai <> 'NGUNG_SU_DUNG'",
                ['venue_id' => $venueId]
            );

            if ($affected !== 1) {
                throw new RuntimeException('VENUE_NOT_DEACTIVATED');
            }

            $this->recordStatusHistory('SAN_DAU', $venueId, $oldStatus, 'NGUNG_SU_DUNG', $reason, $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Ngung su dung san dau', 'Sandau', $venueId, $ipAddress, $logNote);
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
