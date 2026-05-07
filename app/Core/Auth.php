<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        $userId = self::validUserId();
        return $userId ? User::findWithRole((int) $userId) : null;
    }

    public static function check(): bool
    {
        return self::validUserId() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !$user['active'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('auth_login_at', time());
        Session::put('auth_last_activity', time());
        Session::put('auth_last_regenerated', time());
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

    private static function validUserId(): ?int
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return null;
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $now = time();
        $lastActivity = (int) Session::get('auth_last_activity', $now);
        $timeout = max(300, (int) ($config['session_timeout'] ?? 1800));

        if (($now - $lastActivity) > $timeout) {
            Logger::info('auth.session_expired', 'Sessão encerrada por inatividade.', (int) $userId);
            Session::forget('user_id');
            Session::forget('auth_login_at');
            Session::forget('auth_last_activity');
            Session::forget('auth_last_regenerated');
            Session::regenerate();
            Session::flash('error', 'Sua sessão expirou por inatividade. Faça login novamente.');
            return null;
        }

        if (!User::findWithRole((int) $userId)) {
            Logger::info('auth.session_invalid_user', 'Sessão encerrada porque o usuário está inativo ou não existe.', (int) $userId);
            Session::forget('user_id');
            Session::forget('auth_login_at');
            Session::forget('auth_last_activity');
            Session::forget('auth_last_regenerated');
            Session::regenerate();
            Session::flash('error', 'Sua conta não está ativa. Fale com o administrador.');
            return null;
        }

        $lastRegenerated = (int) Session::get('auth_last_regenerated', $now);
        $regenerateInterval = max(300, (int) ($config['session_regenerate_interval'] ?? 600));

        if (($now - $lastRegenerated) > $regenerateInterval) {
            Session::regenerate();
            Session::put('auth_last_regenerated', $now);
        }

        Session::put('auth_last_activity', $now);

        return (int) $userId;
    }
}
