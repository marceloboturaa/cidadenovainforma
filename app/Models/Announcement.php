<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class Announcement
{
    public static function ensureSchema(): void
    {
        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS announcements (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(160) NOT NULL,
                body TEXT NOT NULL,
                url VARCHAR(255) NULL,
                button_label VARCHAR(80) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_announcements_active_created (active, created_at),
                CONSTRAINT fk_announcements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS announcement_reads (
                announcement_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                read_at DATETIME NOT NULL,
                PRIMARY KEY (announcement_id, user_id),
                CONSTRAINT fk_announcement_reads_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
                CONSTRAINT fk_announcement_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO announcements (title, body, url, button_label, active, created_by, created_at, updated_at)
             VALUES (:title, :body, :url, :button_label, 1, :created_by, NOW(), NOW())'
        );
        $stmt->execute([
            'title' => self::limit((string) ($data['title'] ?? ''), 160),
            'body' => trim((string) ($data['body'] ?? '')),
            'url' => self::nullableLimit($data['url'] ?? null, 255),
            'button_label' => self::nullableLimit($data['button_label'] ?? null, 80),
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function whatsappRecipients(): array
    {
        self::ensureSchema();
        Person::ensureSchema();

        $stmt = Database::connection()->query(
            'SELECT users.id AS user_id,
                    users.name AS user_name,
                    people.id AS person_id,
                    COALESCE(NULLIF(people.whatsapp, ""), NULLIF(people.phone, "")) AS phone
             FROM users
             INNER JOIN people
                ON people.active = 1
               AND people.contact_authorized = 1
               AND (
                    people.id = users.registration_person_id
                    OR (users.registration_person_id IS NULL AND people.email IS NOT NULL AND people.email = users.email)
               )
             WHERE users.active = 1
               AND COALESCE(NULLIF(people.whatsapp, ""), NULLIF(people.phone, "")) IS NOT NULL
             ORDER BY users.id ASC, people.updated_at DESC, people.id DESC'
        );

        $recipients = [];
        $seenUsers = [];
        $seenPhones = [];

        foreach ($stmt->fetchAll() as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $phone = trim((string) ($row['phone'] ?? ''));
            $phoneKey = preg_replace('/\D+/', '', $phone) ?: $phone;

            if ($userId <= 0 || $phone === '' || isset($seenUsers[$userId]) || isset($seenPhones[$phoneKey])) {
                continue;
            }

            $seenUsers[$userId] = true;
            $seenPhones[$phoneKey] = true;
            $recipients[] = [
                'user_id' => $userId,
                'name' => (string) ($row['user_name'] ?? ''),
                'phone' => $phone,
            ];
        }

        return $recipients;
    }

    public static function unreadForUser(int $userId, int $limit = 3): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT announcements.*, users.name AS creator_name
             FROM announcements
             LEFT JOIN users ON users.id = announcements.created_by
             LEFT JOIN announcement_reads
                ON announcement_reads.announcement_id = announcements.id
               AND announcement_reads.user_id = :user_id
             WHERE announcements.active = 1
               AND announcement_reads.announcement_id IS NULL
             ORDER BY announcements.created_at DESC, announcements.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', max(1, min(10, $limit)), \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function markRead(int $announcementId, int $userId): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'INSERT INTO announcement_reads (announcement_id, user_id, read_at)
             VALUES (:announcement_id, :user_id, NOW())
             ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)'
        )->execute([
            'announcement_id' => $announcementId,
            'user_id' => $userId,
        ]);
    }

    public static function canManage(?array $user = null): bool
    {
        $user ??= Auth::user();

        return $user && (Auth::hasRole(['master', 'admin']) || Auth::can('announcements.manage'));
    }

    private static function nullableLimit(mixed $value, int $length): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? self::limit($value, $length) : null;
    }

    private static function limit(string $value, int $length): string
    {
        $value = trim($value);
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }
}
