<?php

declare(strict_types=1);

namespace App\Backend\Core;

use PDO;

abstract class Model
{
    protected function db(): PDO
    {
        return Database::connection();
    }

    protected function first(string $sql, array $bindings = []): ?array
    {
        $statement = $this->db()->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
