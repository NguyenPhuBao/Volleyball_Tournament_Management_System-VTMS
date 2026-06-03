<?php

declare(strict_types=1);

namespace App\Backend\Core\Auth;

final class Auth
{
    private const SESSION_KEY = 'auth_user';
    private const TOKEN_KEY = 'auth_session_token';

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY]);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION[self::SESSION_KEY] : null;
    }

    public static function login(array $user, ?string $sessionToken = null): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $user;

        if ($sessionToken !== null) {
            $_SESSION[self::TOKEN_KEY] = $sessionToken;
        }
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION[self::TOKEN_KEY]);
        session_regenerate_id(true);
    }

    public static function sessionToken(): ?string
    {
        return isset($_SESSION[self::TOKEN_KEY]) ? (string) $_SESSION[self::TOKEN_KEY] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function hasRole(array $roles): bool
    {
        return in_array(self::role(), $roles, true);
    }
}
