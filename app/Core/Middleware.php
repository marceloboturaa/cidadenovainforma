<?php

namespace App\Core;

class Middleware
{
    public static function auth(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }

        self::adminHeaders();
    }

    public static function permission(string $permission): void
    {
        self::auth();

        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private static function adminHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
