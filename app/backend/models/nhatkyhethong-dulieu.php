<?php

declare(strict_types=1);

namespace App\Backend\Models;

use App\Backend\Core\Model;
use PDO;

final class Nhatkyhethong extends Model
{
    public function list(array $filters, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->buildWhere($filters);

        $sql = $this->baseSelect();

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY nk.thoigian DESC, nk.idnhatky DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function count(array $filters): int
    {
        [$where, $bindings] = $this->buildWhere($filters);

        $sql = "SELECT COUNT(*) AS total
            FROM Nhatkyhethong nk
            LEFT JOIN Taikhoan tk ON tk.idtaikhoan = nk.idtaikhoan
            LEFT JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $statement = $this->db()->prepare($sql);
        $this->bindWhere($statement, $bindings);
        $statement->execute();
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function findById(int $logId): ?array
    {
        return $this->first(
            $this->baseSelect() . ' WHERE nk.idnhatky = :log_id LIMIT 1',
            ['log_id' => $logId]
        );
    }

    public function targetTables(): array
    {
        $statement = $this->db()->query(
            "SELECT DISTINCT bangtacdong
             FROM Nhatkyhethong
             ORDER BY bangtacdong"
        );

        return array_map(
            static fn (array $row): string => (string) $row['bangtacdong'],
            $statement->fetchAll()
        );
    }

    public function actions(): array
    {
        $statement = $this->db()->query(
            "SELECT DISTINCT hanhdong
             FROM Nhatkyhethong
             ORDER BY hanhdong"
        );

        return array_map(
            static fn (array $row): string => (string) $row['hanhdong'],
            $statement->fetchAll()
        );
    }

    public function actors(): array
    {
        $statement = $this->db()->query(
            "SELECT DISTINCT
                nk.idtaikhoan,
                tk.username,
                r.namerole AS role,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten
             FROM Nhatkyhethong nk
             LEFT JOIN Taikhoan tk ON tk.idtaikhoan = nk.idtaikhoan
             LEFT JOIN Role r ON r.idrole = tk.idrole
             LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan
             WHERE nk.idtaikhoan IS NOT NULL
             ORDER BY tk.username"
        );

        return $statement->fetchAll();
    }

    private function baseSelect(): string
    {
        return "SELECT
                nk.idnhatky,
                nk.idtaikhoan,
                tk.username,
                tk.email,
                r.namerole AS role,
                nd.idnguoidung,
                nd.hodem,
                nd.ten,
                TRIM(CONCAT(COALESCE(nd.hodem, ''), ' ', COALESCE(nd.ten, ''))) AS hoten,
                nk.hanhdong,
                nk.bangtacdong,
                nk.iddoituong,
                nk.thoigian,
                nk.ipaddress,
                nk.ghichu
            FROM Nhatkyhethong nk
            LEFT JOIN Taikhoan tk ON tk.idtaikhoan = nk.idtaikhoan
            LEFT JOIN Role r ON r.idrole = tk.idrole
            LEFT JOIN Nguoidung nd ON nd.idtaikhoan = tk.idtaikhoan";
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = "(nk.hanhdong LIKE :q_action
                OR nk.bangtacdong LIKE :q_table
                OR nk.ghichu LIKE :q_note
                OR nk.ipaddress LIKE :q_ip
                OR tk.username LIKE :q_username
                OR tk.email LIKE :q_email
                OR nd.hodem LIKE :q_hodem
                OR nd.ten LIKE :q_ten)";
            $like = '%' . $filters['q'] . '%';
            $bindings['q_action'] = $like;
            $bindings['q_table'] = $like;
            $bindings['q_note'] = $like;
            $bindings['q_ip'] = $like;
            $bindings['q_username'] = $like;
            $bindings['q_email'] = $like;
            $bindings['q_hodem'] = $like;
            $bindings['q_ten'] = $like;
        }

        if (($filters['idtaikhoan'] ?? null) !== null) {
            $where[] = 'nk.idtaikhoan = :account_id';
            $bindings['account_id'] = (int) $filters['idtaikhoan'];
        }

        if (($filters['bangtacdong'] ?? '') !== '') {
            $where[] = 'nk.bangtacdong = :target_table';
            $bindings['target_table'] = $filters['bangtacdong'];
        }

        if (($filters['hanhdong'] ?? '') !== '') {
            $where[] = 'nk.hanhdong = :action';
            $bindings['action'] = $filters['hanhdong'];
        }

        if (($filters['from'] ?? '') !== '') {
            $where[] = 'nk.thoigian >= :from_date';
            $bindings['from_date'] = $filters['from'] . ' 00:00:00';
        }

        if (($filters['to'] ?? '') !== '') {
            $where[] = 'nk.thoigian <= :to_date';
            $bindings['to_date'] = $filters['to'] . ' 23:59:59';
        }

        return [$where, $bindings];
    }

    private function bindWhere(\PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $name => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue($name, $value, $type);
        }
    }
}
