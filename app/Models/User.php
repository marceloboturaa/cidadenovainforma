<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function ensureRoleSchema(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS user_roles (
                user_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (user_id, role_id),
                CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        self::ensureRegistrationColumns();

        $done = true;
    }

    private static function ensureRegistrationColumns(): void
    {
        $db = Database::connection();
        $columns = $db->query('SHOW COLUMNS FROM users')->fetchAll(\PDO::FETCH_COLUMN);

        if (!in_array('registration_origin', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN registration_origin VARCHAR(40) NOT NULL DEFAULT "manual" AFTER active');
        }
        if (!in_array('registration_event_id', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN registration_event_id BIGINT UNSIGNED NULL AFTER registration_origin');
        }
        if (!in_array('registration_person_id', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN registration_person_id BIGINT UNSIGNED NULL AFTER registration_event_id');
        }
        if (!in_array('registration_course_id', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN registration_course_id BIGINT UNSIGNED NULL AFTER registration_person_id');
        }
        if (!in_array('profile_update_required', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN profile_update_required TINYINT(1) NOT NULL DEFAULT 0 AFTER registration_course_id');
        }
        if (!in_array('profile_update_fields', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN profile_update_fields TEXT NULL AFTER profile_update_required');
        }
        if (!in_array('profile_update_requested_by', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN profile_update_requested_by BIGINT UNSIGNED NULL AFTER profile_update_fields');
        }
        if (!in_array('profile_update_requested_at', $columns, true)) {
            $db->exec('ALTER TABLE users ADD COLUMN profile_update_requested_at DATETIME NULL AFTER profile_update_requested_by');
        }
    }

    public static function findByEmail(string $email): ?array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findWithRole(int $id): ?array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare(
            'SELECT users.*,
                    primary_role.name AS primary_role_name,
                    primary_role.slug AS primary_role_slug,
                    (
                        SELECT roles_inner.name
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                        ORDER BY roles_inner.level DESC, roles_inner.name ASC
                        LIMIT 1
                    ) AS role_name,
                    (
                        SELECT roles_inner.slug
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                        ORDER BY roles_inner.level DESC, roles_inner.name ASC
                        LIMIT 1
                    ) AS role_slug,
                    (
                        SELECT GROUP_CONCAT(DISTINCT roles_inner.name ORDER BY roles_inner.level DESC, roles_inner.name ASC SEPARATOR ", ")
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                    ) AS role_names,
                    (
                        SELECT GROUP_CONCAT(DISTINCT roles_inner.slug ORDER BY roles_inner.level DESC, roles_inner.name ASC SEPARATOR ",")
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                    ) AS role_slugs
             FROM users
             INNER JOIN roles primary_role ON primary_role.id = users.role_id
             WHERE users.id = :id AND users.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->query(
            'SELECT users.id,
                    users.role_id,
                    users.name,
                    users.email,
                    users.active,
                    users.profile_update_required,
                    users.profile_update_fields,
                    users.profile_update_requested_at,
                    users.created_at,
                    primary_role.name AS primary_role_name,
                    primary_role.slug AS primary_role_slug,
                    (
                        SELECT roles_inner.name
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                        ORDER BY roles_inner.level DESC, roles_inner.name ASC
                        LIMIT 1
                    ) AS role_name,
                    (
                        SELECT roles_inner.slug
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                        ORDER BY roles_inner.level DESC, roles_inner.name ASC
                        LIMIT 1
                    ) AS role_slug,
                    (
                        SELECT GROUP_CONCAT(DISTINCT roles_inner.name ORDER BY roles_inner.level DESC, roles_inner.name ASC SEPARATOR ", ")
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                    ) AS role_names,
                    (
                        SELECT GROUP_CONCAT(DISTINCT roles_inner.slug ORDER BY roles_inner.level DESC, roles_inner.name ASC SEPARATOR ",")
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                    ) AS role_slugs
             FROM users
             INNER JOIN roles primary_role ON primary_role.id = users.role_id
             ORDER BY users.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function activeForAccessLists(): array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->query(
            'SELECT users.id,
                    users.name,
                    users.email,
                    (
                        SELECT roles_inner.name
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                        ORDER BY roles_inner.level DESC, roles_inner.name ASC
                        LIMIT 1
                    ) AS role_name,
                    (
                        SELECT roles_inner.slug
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                        ORDER BY roles_inner.level DESC, roles_inner.name ASC
                        LIMIT 1
                    ) AS role_slug,
                    (
                        SELECT GROUP_CONCAT(DISTINCT roles_inner.name ORDER BY roles_inner.level DESC, roles_inner.name ASC SEPARATOR ", ")
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                    ) AS role_names,
                    (
                        SELECT GROUP_CONCAT(DISTINCT roles_inner.slug ORDER BY roles_inner.level DESC, roles_inner.name ASC SEPARATOR ",")
                        FROM roles roles_inner
                        WHERE roles_inner.id = users.role_id
                           OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
                    ) AS role_slugs
             FROM users
             WHERE users.active = 1
             ORDER BY (
                SELECT MAX(roles_inner.level)
                FROM roles roles_inner
                WHERE roles_inner.id = users.role_id
                   OR roles_inner.id IN (SELECT user_roles_inner.role_id FROM user_roles user_roles_inner WHERE user_roles_inner.user_id = users.id)
             ) DESC, users.name ASC'
        );

        return $stmt->fetchAll();
    }

    public static function pending(): array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->query(
            'SELECT users.id,
                    users.name,
                    users.email,
                    users.created_at,
                    users.registration_origin,
                    users.registration_event_id,
                    users.registration_person_id,
                    users.registration_course_id,
                    roles.name AS role_name,
                    library_events.title AS registration_event_title,
                    education_courses.title AS registration_course_title
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             LEFT JOIN library_events ON library_events.id = users.registration_event_id
             LEFT JOIN education_courses ON education_courses.id = users.registration_course_id
             WHERE users.active = 0
             ORDER BY users.created_at ASC'
        );

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO users (role_id, region_id, name, email, password_hash, active, registration_origin, registration_event_id, registration_person_id, registration_course_id, created_at, updated_at)
             VALUES (:role_id, :region_id, :name, :email, :password_hash, :active, :registration_origin, :registration_event_id, :registration_person_id, :registration_course_id, NOW(), NOW())'
        );

        $stmt->execute([
            'role_id' => $data['role_id'],
            'region_id' => $data['region_id'] ?? null,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'active' => (int) ($data['active'] ?? 1),
            'registration_origin' => self::registrationOrigin($data['registration_origin'] ?? (!empty($data['active']) ? 'manual' : 'login')),
            'registration_event_id' => !empty($data['registration_event_id']) ? (int) $data['registration_event_id'] : null,
            'registration_person_id' => !empty($data['registration_person_id']) ? (int) $data['registration_person_id'] : null,
            'registration_course_id' => !empty($data['registration_course_id']) ? (int) $data['registration_course_id'] : null,
        ]);

        $userId = (int) Database::connection()->lastInsertId();
        self::syncRoles($userId, array_merge([(int) $data['role_id']], $data['role_ids'] ?? []), (int) $data['role_id']);

        return $userId;
    }

    public static function touchLastLogin(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function permissions(int $userId): array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT permissions.slug
             FROM users
             LEFT JOIN user_roles ON user_roles.user_id = users.id
             INNER JOIN role_permissions ON role_permissions.role_id = users.role_id OR role_permissions.role_id = user_roles.role_id
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

    public static function updateProfile(int $userId, string $name, string $email): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id' => $userId,
            'name' => trim($name),
            'email' => strtolower(trim($email)),
        ]);
    }

    public static function updateOwnProfile(int $userId, array $data, array $fields): void
    {
        self::ensureRoleSchema();

        $fields = self::profileUpdateFields($fields);
        $set = ['profile_update_required = 0', 'profile_update_fields = NULL', 'profile_update_requested_by = NULL', 'profile_update_requested_at = NULL', 'updated_at = NOW()'];
        $params = ['id' => $userId];

        if (in_array('name', $fields, true)) {
            $set[] = 'name = :name';
            $params['name'] = trim((string) ($data['name'] ?? ''));
        }
        if (in_array('email', $fields, true)) {
            $set[] = 'email = :email';
            $params['email'] = strtolower(trim((string) ($data['email'] ?? '')));
        }
        if (in_array('password', $fields, true) && !empty($data['password'])) {
            $set[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        $stmt = Database::connection()->prepare(
            'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public static function requestProfileUpdate(int $userId, int $requestedBy, array $fields): void
    {
        self::ensureRoleSchema();
        $fields = self::profileUpdateFields($fields);

        $stmt = Database::connection()->prepare(
            'UPDATE users
             SET profile_update_required = 1,
                 profile_update_fields = :fields,
                 profile_update_requested_by = :requested_by,
                 profile_update_requested_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $userId,
            'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
            'requested_by' => $requestedBy,
        ]);
    }

    public static function profileUpdateFields(array|string|null $fields): array
    {
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            $fields = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $fields)));
        }

        $fields = array_values(array_unique(array_map('strval', $fields ?? [])));
        if (!$fields) {
            return ['name', 'email'];
        }
        if (in_array('all', $fields, true)) {
            return ['name', 'email', 'address', 'password'];
        }

        $allowed = ['name', 'email', 'address', 'password'];
        $fields = array_values(array_intersect($allowed, $fields));

        return $fields ?: ['name', 'email'];
    }

    public static function updateRole(int $userId, int $roleId): void
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare(
            'UPDATE users SET role_id = :role_id, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id' => $userId,
            'role_id' => $roleId,
        ]);
        self::syncRoles($userId, [$roleId], $roleId);
    }

    public static function syncRoles(int $userId, array $roleIds, int $primaryRoleId): void
    {
        self::ensureRoleSchema();

        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
        if (!in_array($primaryRoleId, $roleIds, true)) {
            $roleIds[] = $primaryRoleId;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('UPDATE users SET role_id = :role_id, updated_at = NOW() WHERE id = :id')
                ->execute(['role_id' => $primaryRoleId, 'id' => $userId]);
            $db->prepare('DELETE FROM user_roles WHERE user_id = :user_id')->execute(['user_id' => $userId]);
            $stmt = $db->prepare('INSERT IGNORE INTO user_roles (user_id, role_id, created_at) VALUES (:user_id, :role_id, NOW())');

            foreach ($roleIds as $roleId) {
                $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function roleIds(int $userId): array
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare(
            'SELECT role_id FROM user_roles WHERE user_id = :user_id
             UNION
             SELECT role_id FROM users WHERE id = :user_id_primary'
        );
        $stmt->execute([
            'user_id' => $userId,
            'user_id_primary' => $userId,
        ]);

        return array_map('intval', array_column($stmt->fetchAll(), 'role_id'));
    }

    public static function activate(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET active = 1, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    private static function registrationOrigin(mixed $origin): string
    {
        $origin = strtolower(trim((string) $origin));
        return in_array($origin, ['manual', 'login', 'event', 'course'], true) ? $origin : 'manual';
    }

    public static function deletePending(int $id): bool
    {
        self::ensureRoleSchema();

        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id AND active = 0');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::connection()
            ->prepare('UPDATE users SET active = :active, updated_at = NOW() WHERE id = :id')
            ->execute([
                'id' => $id,
                'active' => $active ? 1 : 0,
            ]);
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
