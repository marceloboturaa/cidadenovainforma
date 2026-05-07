<?php

namespace App\Models;

use App\Core\Database;

class Tag
{
    public static function all(): array
    {
        return Database::connection()
            ->query(
                'SELECT tags.*, COALESCE(news_counts.total, 0) AS news_count
                 FROM tags
                 LEFT JOIN (
                    SELECT tag_id, COUNT(*) AS total
                    FROM news_tags
                    GROUP BY tag_id
                 ) news_counts ON news_counts.tag_id = tags.id
                 ORDER BY tags.name ASC'
            )
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tags WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tags WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO tags (name, slug, created_at) VALUES (:name, :slug, NOW())');
        $stmt->execute([
            'name' => trim($data['name']),
            'slug' => $data['slug'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE tags SET name = :name, slug = :slug WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => trim($data['name']),
            'slug' => $data['slug'],
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM tags WHERE id = :id')->execute(['id' => $id]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = slugify($name);
        $slug = $base;
        $counter = 2;
        $db = Database::connection();

        do {
            $sql = 'SELECT id FROM tags WHERE slug = :slug';
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

    public static function syncForNews(int $newsId, string $rawTags): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM news_tags WHERE news_id = :news_id')->execute(['news_id' => $newsId]);

        $names = array_filter(array_unique(array_map('trim', explode(',', $rawTags))));
        if (!$names) {
            return;
        }

        $insertTag = $db->prepare('INSERT IGNORE INTO tags (name, slug, created_at) VALUES (:name, :slug, NOW())');
        $findTag = $db->prepare('SELECT id FROM tags WHERE slug = :slug LIMIT 1');
        $attach = $db->prepare('INSERT IGNORE INTO news_tags (news_id, tag_id) VALUES (:news_id, :tag_id)');

        foreach ($names as $name) {
            $slug = slugify($name);
            $insertTag->execute(['name' => $name, 'slug' => $slug]);
            $findTag->execute(['slug' => $slug]);
            $tagId = (int) $findTag->fetchColumn();
            $attach->execute(['news_id' => $newsId, 'tag_id' => $tagId]);
        }
    }

    public static function namesForNews(int $newsId): string
    {
        $stmt = Database::connection()->prepare(
            'SELECT tags.name
             FROM tags
             INNER JOIN news_tags ON news_tags.tag_id = tags.id
             WHERE news_tags.news_id = :news_id
             ORDER BY tags.name ASC'
        );
        $stmt->execute(['news_id' => $newsId]);

        return implode(', ', array_column($stmt->fetchAll(), 'name'));
    }

    public static function publicForNews(int $newsId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT tags.name, tags.slug
             FROM tags
             INNER JOIN news_tags ON news_tags.tag_id = tags.id
             WHERE news_tags.news_id = :news_id
             ORDER BY tags.name ASC'
        );
        $stmt->execute(['news_id' => $newsId]);

        return $stmt->fetchAll();
    }
}
