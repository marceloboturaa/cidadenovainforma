<?php

namespace App\Models;

use App\Core\Database;

class News
{
    public const STATUS_LABELS = [
        'draft' => 'Rascunho',
        'pending' => 'Pendente',
        'rejected' => 'Rejeitada',
        'published' => 'Publicada',
        'archived' => 'Arquivada',
    ];

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT news.*, users.name AS author_name, categories.name AS category_name
                FROM news
                INNER JOIN users ON users.id = news.author_id
                LEFT JOIN categories ON categories.id = news.category_id
                WHERE 1 = 1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND news.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['author_id'])) {
            $sql .= ' AND news.author_id = :author_id';
            $params['author_id'] = $filters['author_id'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (news.title LIKE :q_title OR news.summary LIKE :q_summary OR news.content LIKE :q_content)';
            $term = '%' . $filters['q'] . '%';
            $params['q_title'] = $term;
            $params['q_summary'] = $term;
            $params['q_content'] = $term;
        }

        if (array_key_exists('is_archive', $filters) && $filters['is_archive'] !== '') {
            $sql .= ' AND news.is_archive = :is_archive';
            $params['is_archive'] = (int) $filters['is_archive'];
        }

        $sql .= ' ORDER BY news.updated_at DESC, news.created_at DESC LIMIT 100';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT news.*, users.name AS author_name, categories.name AS category_name
             FROM news
             INNER JOIN users ON users.id = news.author_id
             LEFT JOIN categories ON categories.id = news.category_id
             WHERE news.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT news.*, users.name AS author_name, categories.name AS category_name, categories.slug AS category_slug
             FROM news
             INNER JOIN users ON users.id = news.author_id
             LEFT JOIN categories ON categories.id = news.category_id
             WHERE news.slug = :slug AND news.status = "published"
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function publicList(array $filters = [], int $limit = 12): array
    {
        $sql = 'SELECT DISTINCT news.*, users.name AS author_name, categories.name AS category_name, categories.slug AS category_slug
                FROM news
                INNER JOIN users ON users.id = news.author_id
                LEFT JOIN categories ON categories.id = news.category_id
                LEFT JOIN news_tags ON news_tags.news_id = news.id
                LEFT JOIN tags ON tags.id = news_tags.tag_id
                WHERE news.status = "published"';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND news.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['tag_id'])) {
            $sql .= ' AND tags.id = :tag_id';
            $params['tag_id'] = $filters['tag_id'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                news.title LIKE :q_title
                OR news.summary LIKE :q_summary
                OR news.content LIKE :q_content
                OR categories.name LIKE :q_category
                OR tags.name LIKE :q_tag
                OR users.name LIKE :q_author
            )';
            $term = '%' . $filters['q'] . '%';
            $params['q_title'] = $term;
            $params['q_summary'] = $term;
            $params['q_content'] = $term;
            $params['q_category'] = $term;
            $params['q_tag'] = $term;
            $params['q_author'] = $term;
        }

        if (!empty($filters['featured'])) {
            $sql .= ' AND news.featured = 1';
        }

        if (!empty($filters['urgent'])) {
            $sql .= ' AND news.urgent = 1';
        }

        if (array_key_exists('is_archive', $filters) && $filters['is_archive'] !== '') {
            $sql .= ' AND news.is_archive = :is_archive';
            $params['is_archive'] = (int) $filters['is_archive'];
        }

        $order = !empty($filters['archive_order'])
            ? ' ORDER BY news.original_published_at DESC, news.published_at DESC, news.created_at DESC'
            : ' ORDER BY news.published_at DESC, news.created_at DESC';

        $sql .= $order . ' LIMIT ' . max(1, min(50, $limit));

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function popular(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT news.*, categories.name AS category_name
             FROM news
             LEFT JOIN categories ON categories.id = news.category_id
             WHERE news.status = "published"
             ORDER BY news.views DESC, news.published_at DESC
             LIMIT ' . max(1, min(20, $limit))
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function incrementViews(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE news SET views = views + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO news
                (author_id, category_id, title, slug, summary, content, cover_image, type, status, featured, urgent, is_archive, original_published_at, original_author, original_source, original_url, archive_note, published_at, created_at, updated_at)
             VALUES
                (:author_id, :category_id, :title, :slug, :summary, :content, :cover_image, :type, :status, :featured, :urgent, :is_archive, :original_published_at, :original_author, :original_source, :original_url, :archive_note, :published_at, NOW(), NOW())'
        );

        $stmt->execute(self::payload($data));
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload['id'] = $id;

        $stmt = Database::connection()->prepare(
            'UPDATE news SET
                category_id = :category_id,
                title = :title,
                slug = :slug,
                summary = :summary,
                content = :content,
                cover_image = :cover_image,
                type = :type,
                status = :status,
                featured = :featured,
                urgent = :urgent,
                is_archive = :is_archive,
                original_published_at = :original_published_at,
                original_author = :original_author,
                original_source = :original_source,
                original_url = :original_url,
                archive_note = :archive_note,
                published_at = :published_at,
                updated_at = NOW()
             WHERE id = :id'
        );

        unset($payload['author_id']);
        $stmt->execute($payload);
    }

    public static function changeStatus(int $id, string $status, ?int $approvedBy = null): void
    {
        $publishedAt = $status === 'published' ? 'NOW()' : 'published_at';
        $stmt = Database::connection()->prepare(
            "UPDATE news
             SET status = :status,
                 approved_by = COALESCE(:approved_by, approved_by),
                 published_at = {$publishedAt},
                 updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'approved_by' => $approvedBy,
        ]);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = slugify($title);
        $slug = $base;
        $counter = 2;
        $db = Database::connection();

        do {
            $sql = 'SELECT id FROM news WHERE slug = :slug';
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

    public static function statusCounts(): array
    {
        $rows = Database::connection()
            ->query('SELECT status, COUNT(*) AS total FROM news GROUP BY status')
            ->fetchAll();

        return array_column($rows, 'total', 'status');
    }

    private static function payload(array $data): array
    {
        return [
            'author_id' => $data['author_id'] ?? null,
            'category_id' => $data['category_id'] ?: null,
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            'summary' => trim($data['summary'] ?? ''),
            'content' => trim($data['content']),
            'cover_image' => $data['cover_image'] ?? null,
            'type' => $data['type'] ?? 'noticia',
            'status' => $data['status'] ?? 'draft',
            'featured' => (int) !empty($data['featured']),
            'urgent' => (int) !empty($data['urgent']),
            'is_archive' => (int) !empty($data['is_archive']),
            'original_published_at' => !empty($data['original_published_at']) ? $data['original_published_at'] : null,
            'original_author' => trim($data['original_author'] ?? ''),
            'original_source' => trim($data['original_source'] ?? ''),
            'original_url' => trim($data['original_url'] ?? ''),
            'archive_note' => trim($data['archive_note'] ?? ''),
            'published_at' => ($data['status'] ?? '') === 'published' ? ($data['published_at'] ?? date('Y-m-d H:i:s')) : ($data['published_at'] ?? null),
        ];
    }
}
