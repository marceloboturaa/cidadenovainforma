<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        $userId = Session::get('user_id');
        return $userId ? User::findWithRole((int) $userId) : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !$user['active'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        User::touchLastLogin((int) $user['id']);
        Logger::info('auth.login', 'Login realizado.', (int) $user['id']);

        return true;
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user) {
            Logger::info('auth.logout', 'Logout realizado.', (int) $user['id']);
        }

        Session::destroy();
    }

    public static function can(string $permission): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        if ($user['role_slug'] === 'master') {
            return true;
        }

        return in_array($permission, User::permissions((int) $user['id']), true);
    }
}
