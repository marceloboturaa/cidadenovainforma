<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserPresence
{
    private const ONLINE_MINUTES = 5;

    public static function touch(int $userId): void
    {
        self::ensureTable();

        $stmt = Database::connection()->prepare(
            'INSERT INTO user_presence (user_id, last_seen_at, ip_address, user_agent)
             VALUES (:user_id, NOW(), :ip_address, :user_agent)
             ON DUPLICATE KEY UPDATE
                last_seen_at = VALUES(last_seen_at),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    public static function onlineUsers(): array
    {
        self::ensureTable();

        $stmt = Database::connection()->prepare(
            'SELECT users.id, users.name, users.email, roles.name AS role_name, roles.slug AS role_slug,
                    user_presence.last_seen_at, user_presence.ip_address
             FROM user_presence
             INNER JOIN users ON users.id = user_presence.user_id
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.active = 1
               AND user_presence.last_seen_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
             ORDER BY user_presence.last_seen_at DESC'
        );
        $stmt->bindValue('minutes', self::ONLINE_MINUTES, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function onlineUserIds(): array
    {
        return array_map(
            static fn (array $user): int => (int) $user['id'],
            self::onlineUsers()
        );
    }

    public static function onlineWindowMinutes(): int
    {
        return self::ONLINE_MINUTES;
    }

    private static function ensureTable(): void
    {
        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS user_presence (
                user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                last_seen_at DATETIME NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                CONSTRAINT fk_user_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
    }
}
