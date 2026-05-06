<?php

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';

        session_name($config['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }
}
