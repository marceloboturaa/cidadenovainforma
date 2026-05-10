<?php

namespace App\Models;

use App\Core\Database;

class Tag
{
    public static function all(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                'SELECT tags.id, tags.name, COALESCE(NULLIF(tags.display_name, ""), tags.name) AS display_name, tags.slug, tags.created_at, COALESCE(news_counts.total, 0) AS news_count
                 FROM tags
                 LEFT JOIN (
                    SELECT tag_id, COUNT(*) AS total
                    FROM news_tags
                    GROUP BY tag_id
                 ) news_counts ON news_counts.tag_id = tags.id
                 ORDER BY display_name ASC'
            )
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT tags.id, tags.name, COALESCE(NULLIF(tags.display_name, ""), tags.name) AS display_name, tags.slug, tags.created_at FROM tags WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT tags.id, tags.name, COALESCE(NULLIF(tags.display_name, ""), tags.name) AS display_name, tags.slug, tags.created_at FROM tags WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $slug = $data['slug'];
        $stmt = Database::connection()->prepare('INSERT INTO tags (name, display_name, slug, created_at) VALUES (:name, :display_name, :slug, NOW())');
        $stmt->execute([
            'name' => $slug,
            'display_name' => self::displayName($data['display_name'] ?? $data['name']),
            'slug' => $slug,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::ensureSchema();

        $slug = $data['slug'];
        $stmt = Database::connection()->prepare('UPDATE tags SET name = :name, display_name = :display_name, slug = :slug WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $slug,
            'display_name' => self::displayName($data['display_name'] ?? $data['name']),
            'slug' => $slug,
        ]);
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();

        Database::connection()->prepare('DELETE FROM tags WHERE id = :id')->execute(['id' => $id]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        self::ensureSchema();

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
        self::ensureSchema();

        $db = Database::connection();
        $db->prepare('DELETE FROM news_tags WHERE news_id = :news_id')->execute(['news_id' => $newsId]);

        $names = array_filter(array_unique(array_map('trim', explode(',', $rawTags))));
        if (!$names) {
            return;
        }

        $insertTag = $db->prepare('INSERT IGNORE INTO tags (name, display_name, slug, created_at) VALUES (:name, :display_name, :slug, NOW())');
        $findTag = $db->prepare('SELECT id FROM tags WHERE slug = :slug LIMIT 1');
        $attach = $db->prepare('INSERT IGNORE INTO news_tags (news_id, tag_id) VALUES (:news_id, :tag_id)');

        foreach ($names as $name) {
            $slug = slugify($name);
            if ($slug === '') {
                continue;
            }

            $insertTag->execute([
                'name' => $slug,
                'display_name' => self::displayName($name),
                'slug' => $slug,
            ]);
            $findTag->execute(['slug' => $slug]);
            $tagId = (int) $findTag->fetchColumn();
            $attach->execute(['news_id' => $newsId, 'tag_id' => $tagId]);
        }
    }

    public static function namesForNews(int $newsId): string
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(NULLIF(tags.display_name, ""), tags.name) AS display_name
             FROM tags
             INNER JOIN news_tags ON news_tags.tag_id = tags.id
             WHERE news_tags.news_id = :news_id
             ORDER BY display_name ASC'
        );
        $stmt->execute(['news_id' => $newsId]);

        return implode(', ', array_column($stmt->fetchAll(), 'display_name'));
    }

    public static function publicForNews(int $newsId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(NULLIF(tags.display_name, ""), tags.name) AS display_name, tags.slug
             FROM tags
             INNER JOIN news_tags ON news_tags.tag_id = tags.id
             WHERE news_tags.news_id = :news_id
             ORDER BY display_name ASC'
        );
        $stmt->execute(['news_id' => $newsId]);

        return $stmt->fetchAll();
    }

    private static function displayName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($name));
    }

    public static function ensureSchema(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $db = Database::connection();
        $columns = $db->query('SHOW COLUMNS FROM tags')->fetchAll(\PDO::FETCH_COLUMN);

        if (!in_array('display_name', $columns, true)) {
            $db->exec('ALTER TABLE tags ADD COLUMN display_name VARCHAR(120) NULL AFTER name');
        }

        $db->exec('UPDATE tags SET display_name = name WHERE display_name IS NULL OR display_name = ""');
        $db->exec('UPDATE tags SET name = slug WHERE slug IS NOT NULL AND slug <> "" AND name <> slug');
        self::repairExistingSlugs();

        $done = true;
    }

    private static function repairExistingSlugs(): void
    {
        $db = Database::connection();
        $tags = $db->query('SELECT id, display_name, slug FROM tags ORDER BY id ASC')->fetchAll();
        $update = $db->prepare('UPDATE tags SET name = :name, slug = :slug WHERE id = :id');

        foreach ($tags as $tag) {
            $displayName = trim((string) ($tag['display_name'] ?? ''));
            $currentSlug = trim((string) ($tag['slug'] ?? ''));

            if ($displayName === '') {
                continue;
            }

            $fixedSlug = self::uniqueSlugForRepair(slugify($displayName), (int) $tag['id']);
            if ($fixedSlug === '' || $fixedSlug === $currentSlug) {
                continue;
            }

            $update->execute([
                'id' => (int) $tag['id'],
                'name' => $fixedSlug,
                'slug' => $fixedSlug,
            ]);
        }
    }

    private static function uniqueSlugForRepair(string $base, int $ignoreId): string
    {
        if ($base === '') {
            return '';
        }

        $slug = $base;
        $counter = 2;
        $db = Database::connection();

        do {
            $stmt = $db->prepare('SELECT id FROM tags WHERE slug = :slug AND id <> :id LIMIT 1');
            $stmt->execute([
                'slug' => $slug,
                'id' => $ignoreId,
            ]);
            $exists = (bool) $stmt->fetchColumn();

            if ($exists) {
                $slug = $base . '-' . $counter++;
            }
        } while ($exists);

        return $slug;
    }
}
