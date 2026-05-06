<?php

namespace App\Core;

class Middleware
{
    public static function auth(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
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
}
