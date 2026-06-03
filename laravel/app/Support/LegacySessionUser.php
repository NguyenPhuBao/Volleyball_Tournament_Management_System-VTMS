<?php

namespace App\Support;

final class LegacySessionUser
{
    private const USER_KEY = 'auth_user';
    private const TOKEN_KEY = 'auth_session_token';

    public static function check(): bool
    {
        return is_array(session(self::USER_KEY));
    }

    public static function user(): ?array
    {
        $user = session(self::USER_KEY);

        return is_array($user) ? $user : null;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function hasRole(array $roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function login(array $user, ?string $sessionToken = null): void
    {
        session()->regenerate();
        session([self::USER_KEY => $user]);

        if ($sessionToken !== null) {
            session([self::TOKEN_KEY => $sessionToken]);
        }
    }

    public static function logout(): void
    {
        session()->forget([self::USER_KEY, self::TOKEN_KEY]);
        session()->regenerate();
    }

    public static function sessionToken(): ?string
    {
        $token = session(self::TOKEN_KEY);

        return is_string($token) ? $token : null;
    }
}
