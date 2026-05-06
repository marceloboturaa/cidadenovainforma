<?php

namespace App\Core;

class Logger
{
    public static function info(string $action, string $description, ?int $userId = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO logs (user_id, action, description, ip_address, user_agent, created_at)
             VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())'
        );

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
