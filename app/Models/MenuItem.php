<?php

namespace App\Models;

use App\Core\Database;

class MenuItem
{
    public static function all(): array
    {
        return Database::connection()
            ->query(
                'SELECT menu_items.*, categories.name AS category_name, categories.slug AS category_slug
                 FROM menu_items
                 LEFT JOIN categories ON categories.id = menu_items.category_id
                 ORDER BY menu_items.sort_order ASC, menu_items.label ASC'
            )
            ->fetchAll();
    }

    public static function visible(): array
    {
        return Database::connection()
            ->query(
                'SELECT label, url
                 FROM menu_items
                 WHERE visible = 1
                 ORDER BY sort_order ASC, label ASC'
            )
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM menu_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO menu_items (category_id, label, url, sort_order, visible, created_at, updated_at)
             VALUES (:category_id, :label, :url, :sort_order, :visible, NOW(), NOW())'
        );
        $stmt->execute(self::payload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload['id'] = $id;

        $stmt = Database::connection()->prepare(
            'UPDATE menu_items
             SET category_id = :category_id, label = :label, url = :url, sort_order = :sort_order, visible = :visible, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($payload);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM menu_items WHERE id = :id')->execute(['id' => $id]);
    }

    private static function payload(array $data): array
    {
        return [
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'label' => trim($data['label']),
            'url' => trim($data['url']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'visible' => (int) !empty($data['visible']),
        ];
    }
}
