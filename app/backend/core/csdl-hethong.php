<?php

declare(strict_types=1);

namespace App\Backend\Core;

use PDO;
use PDOException;

final class Database
{
    private static array $config = [];
    private static array $connections = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function connection(string $name = 'default'): PDO
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $config = self::$config[$name] ?? null;

        if (!is_array($config)) {
            throw new PDOException("Database connection [$name] is not configured.");
        }

        $dsn = self::dsn($config);

        return self::$connections[$name] = new PDO(
            $dsn,
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    private static function dsn(array $config): string
    {
        $driver = (string) ($config['driver'] ?? 'mysql');

        if ($driver !== 'mysql') {
            throw new PDOException("Unsupported database driver [$driver].");
        }

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 3306);
        $database = (string) ($config['database'] ?? '');
        $charset = (string) ($config['charset'] ?? 'utf8mb4');

        return "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
    }
}
