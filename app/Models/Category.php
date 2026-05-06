<?php

namespace App\Models;

use App\Core\Database;

class Category
{
    public static function all(): array
    {
        return Database::connection()
            ->query(
                'SELECT categories.*, parent.name AS parent_name,
                        COALESCE(news_counts.total, 0) AS news_count
                 FROM categories
                 LEFT JOIN categories parent ON parent.id = categories.parent_id
                 LEFT JOIN (
                    SELECT category_id, COUNT(*) AS total
                    FROM news
                    GROUP BY category_id
                 ) news_counts ON news_counts.category_id = categories.id
                 ORDER BY categories.name ASC'
            )
            ->fetchAll();
    }

    public static function active(): array
    {
        return Database::connection()
            ->query('SELECT id, parent_id, name, slug FROM categories WHERE active = 1 ORDER BY name ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE slug = :slug AND active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO categories (parent_id, name, slug, description, active, created_at, updated_at)
             VALUES (:parent_id, :name, :slug, :description, :active, NOW(), NOW())'
        );
        $stmt->execute(self::payload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload['id'] = $id;

        $stmt = Database::connection()->prepare(
            'UPDATE categories
             SET parent_id = :parent_id, name = :name, slug = :slug, description = :description, active = :active, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($payload);
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $db->prepare('UPDATE categories SET parent_id = NULL WHERE parent_id = :id')->execute(['id' => $id]);
        $db->prepare('UPDATE news SET category_id = NULL WHERE category_id = :id')->execute(['id' => $id]);
        $db->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = slugify($name);
        $slug = $base;
        $counter = 2;
        $db = Database::connection();

        do {
            $sql = 'SELECT id FROM categories WHERE slug = :slug';
            $params = ['slug' => $slug];

            if ($ignoreId) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }

            $stmt = $db->prepare($sql . ' LIMIT 1');
            $stmt->execute($params);
            $exists = (bool) $stmt->fetchColumn();

            if ($exists) {
                $slug = $base . '-' . $counter++;
            }
        } while ($exists);

        return $slug;
    }

    public static function seedDefaults(): void
    {
        $defaults = [
            ['Geral', 'geral'],
            ['Cidade', 'cidade'],
            ['Política', 'politica'],
            ['Educação', 'educacao'],
            ['Saúde', 'saude'],
            ['Esporte', 'esporte'],
            ['Cultura', 'cultura'],
            ['Horta', 'horta'],
            ['Rádio', 'radio'],
        ];

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO categories (name, slug, active, created_at, updated_at)
             VALUES (:name, :slug, 1, NOW(), NOW())'
        );

        foreach ($defaults as [$name, $slug]) {
            $stmt->execute(['name' => $name, 'slug' => $slug]);
        }
    }

    private static function payload(array $data): array
    {
        return [
            'parent_id' => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'name' => trim($data['name']),
            'slug' => $data['slug'],
            'description' => trim($data['description'] ?? ''),
            'active' => (int) !empty($data['active']),
        ];
    }
}
