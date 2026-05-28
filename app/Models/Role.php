<?php

namespace App\Models;

use App\Core\Database;

class Role
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, slug, level FROM roles ORDER BY level DESC, name ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, slug, level FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, slug, level FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function permissionIds(int $roleId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT permission_id FROM role_permissions WHERE role_id = :role_id'
        );
        $stmt->execute(['role_id' => $roleId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'permission_id'));
    }

    public static function syncPermissions(int $roleId, array $permissionIds): void
    {
        $permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds))));
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);
            $stmt = $db->prepare(
                'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
            );

            foreach ($permissionIds as $permissionId) {
                $stmt->execute([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }
}
