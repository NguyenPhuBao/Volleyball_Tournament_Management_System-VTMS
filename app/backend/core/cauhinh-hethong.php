<?php

declare(strict_types=1);

namespace App\Backend\Core;

final class Config
{
    private static array $items = [];

    public static function load(string $path, array $namedFiles = []): void
    {
        if ($namedFiles !== []) {
            foreach ($namedFiles as $key => $file) {
                $configFile = defined('BACKEND_PATH')
                    ? BACKEND_PATH . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $file)
                    : (string) $file;

                if (is_file($configFile)) {
                    self::$items[(string) $key] = require $configFile;
                }
            }

            return;
        }

        foreach (glob(rtrim($path, '/\\') . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
