<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use Throwable;

final class Sandau extends Model
{
    public function list(array $filters = []): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(sd.tensandau LIKE :keyword OR vt.tenvitrithidau LIKE :keyword OR vt.diachi LIKE :keyword OR sd.mota LIKE :keyword)';
            $bindings['keyword'] = '%' . $filters['q'] . '%';
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
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY sd.ngaytao DESC, sd.idsandau DESC';

        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public function findById(int $venueId): ?array
    {
        return $this->first(
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

        $sql .= ' LIMIT 1';

        return $this->first($sql, $bindings) !== null;
    }

    public function createVenue(
        array $venue,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): int {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "INSERT INTO Sandau (idvitrithidau, tensandau, succhua, mota, trangthai)
                 VALUES (:location_id, :name, :capacity, :description, :status)"
            );

            $statement->execute([
                'location_id' => $venue['idvitrithidau'],
                'name' => $venue['tensandau'],
                'capacity' => $venue['succhua'],
                'description' => $venue['mota'],
                'status' => $venue['trangthai'],
            ]);

            $venueId = (int) $db->lastInsertId();

            $this->recordStatusHistory('SAN_DAU', $venueId, null, (string) $venue['trangthai'], 'Bo sung san dau', $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Bo sung san dau', 'Sandau', $venueId, $ipAddress, $logNote);

            $db->commit();

            return $venueId;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
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
        $db = $this->db();

        try {
            $db->beginTransaction();

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
                throw new \RuntimeException('VENUE_NOT_UPDATED');
            }

            $sets[] = 'ngaycapnhat = CURRENT_TIMESTAMP';

            $statement = $db->prepare(
                'UPDATE Sandau SET ' . implode(', ', $sets) . ' WHERE idsandau = :venue_id'
            );

            $statement->execute($bindings);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('VENUE_NOT_UPDATED');
            }

            if ($newStatus !== null && $newStatus !== $oldStatus) {
                $this->recordStatusHistory('SAN_DAU', $venueId, $oldStatus, $newStatus, 'Cap nhat trang thai san dau', $actorAccountId);
            }

            $this->recordSystemLog($actorAccountId, 'Cap nhat san dau', 'Sandau', $venueId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public function deactivateVenue(
        int $venueId,
        string $oldStatus,
        string $reason,
        int $actorAccountId,
        ?string $ipAddress,
        string $logNote
    ): void {
        $db = $this->db();

        try {
            $db->beginTransaction();

            $statement = $db->prepare(
                "UPDATE Sandau
                 SET trangthai = 'NGUNG_SU_DUNG',
                     ngaycapnhat = CURRENT_TIMESTAMP
                 WHERE idsandau = :venue_id
                   AND trangthai <> 'NGUNG_SU_DUNG'"
            );

            $statement->execute(['venue_id' => $venueId]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('VENUE_NOT_DEACTIVATED');
            }

            $this->recordStatusHistory('SAN_DAU', $venueId, $oldStatus, 'NGUNG_SU_DUNG', $reason, $actorAccountId);
            $this->recordSystemLog($actorAccountId, 'Ngung su dung san dau', 'Sandau', $venueId, $ipAddress, $logNote);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
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
}
