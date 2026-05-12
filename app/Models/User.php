<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findWithRole(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT users.*, roles.name AS role_name, roles.slug AS role_slug
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id AND users.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT users.id, users.name, users.email, users.active, users.created_at, roles.name AS role_name, roles.slug AS role_slug
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             ORDER BY users.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function activeForAccessLists(): array
    {
        $stmt = Database::connection()->query(
            'SELECT users.id, users.name, users.email, roles.name AS role_name, roles.slug AS role_slug
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.active = 1
             ORDER BY roles.level DESC, users.name ASC'
        );

        return $stmt->fetchAll();
    }

    public static function pending(): array
    {
        $stmt = Database::connection()->query(
            'SELECT users.id, users.name, users.email, users.created_at, roles.name AS role_name
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.active = 0
             ORDER BY users.created_at ASC'
        );

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (role_id, region_id, name, email, password_hash, active, created_at, updated_at)
             VALUES (:role_id, :region_id, :name, :email, :password_hash, :active, NOW(), NOW())'
        );

        $stmt->execute([
            'role_id' => $data['role_id'],
            'region_id' => $data['region_id'] ?? null,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'active' => (int) ($data['active'] ?? 1),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function touchLastLogin(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function permissions(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT permissions.slug
             FROM users
             INNER JOIN role_permissions ON role_permissions.role_id = users.role_id
             INNER JOIN permissions ON permissions.id = role_permissions.permission_id
             WHERE users.id = :id'
        );
        $stmt->execute(['id' => $userId]);
        return array_column($stmt->fetchAll(), 'slug');
    }

    public static function storeResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        self::ensurePasswordResetTable();

        Database::connection()
            ->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL')
            ->execute(['user_id' => $userId]);

        $stmt = Database::connection()->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function findValidReset(string $token): ?array
    {
        self::ensurePasswordResetTable();

        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $hash = hash('sha256', $token);
        $stmt = Database::connection()->prepare(
            'SELECT * FROM password_resets
             WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $hash]);
        return $stmt->fetch() ?: null;
    }

    public static function updatePassword(int $userId, string $password): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id' => $userId,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public static function activate(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET active = 1, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function markResetUsed(int $resetId): void
    {
        self::ensurePasswordResetTable();

        $stmt = Database::connection()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $resetId]);
    }

    private static function ensurePasswordResetTable(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS password_resets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at TIMESTAMP NULL,
                INDEX idx_password_resets_token (token_hash),
                CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $done = true;
    }
}
