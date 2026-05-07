<?php

namespace App\Models;

use App\Core\Database;

class SiteSetting
{
    public static function get(string $name, string $default = ''): string
    {
        self::ensureTable();

        $stmt = Database::connection()->prepare('SELECT value FROM site_settings WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    public static function set(string $name, string $value): void
    {
        self::ensureTable();

        Database::connection()->prepare(
            'INSERT INTO site_settings (name, value, updated_at)
             VALUES (:name, :value, NOW())
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()'
        )->execute([
            'name' => $name,
            'value' => $value,
        ]);
    }

    public static function registrationEnabled(): bool
    {
        return self::get('registration_enabled', '1') === '1';
    }

    public static function setRegistrationEnabled(bool $enabled): void
    {
        self::set('registration_enabled', $enabled ? '1' : '0');
    }

    private static function ensureTable(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS site_settings (
                name VARCHAR(120) PRIMARY KEY,
                value TEXT NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB'
        );

        $done = true;
    }
}
