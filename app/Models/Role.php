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
}
