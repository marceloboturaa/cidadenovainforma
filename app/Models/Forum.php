<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class Forum
{
    public static function ensureSchema(): void
    {
        $db = Database::connection();

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_areas (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(80) NOT NULL UNIQUE,
                description TEXT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_area_roles (
                area_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                can_view TINYINT(1) NOT NULL DEFAULT 1,
                can_post TINYINT(1) NOT NULL DEFAULT 1,
                can_moderate TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (area_id, role_id),
                CONSTRAINT fk_forum_area_roles_area FOREIGN KEY (area_id) REFERENCES forum_areas(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_area_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                area_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                description TEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_forum_category_area_slug (area_id, slug),
                CONSTRAINT fk_forum_categories_area FOREIGN KEY (area_id) REFERENCES forum_areas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                area_id BIGINT UNSIGNED NOT NULL,
                category_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                body TEXT NOT NULL,
                status ENUM("open","closed","hidden") NOT NULL DEFAULT "open",
                pinned TINYINT(1) NOT NULL DEFAULT 0,
                is_public TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_forum_topics_area (area_id),
                INDEX idx_forum_topics_category (category_id),
                CONSTRAINT fk_forum_topics_area FOREIGN KEY (area_id) REFERENCES forum_areas(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_topics_category FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE SET NULL,
                CONSTRAINT fk_forum_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_replies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_forum_replies_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_attachments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NULL,
                reply_id BIGINT UNSIGNED NULL,
                uploaded_by BIGINT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                original_name VARCHAR(190) NOT NULL,
                mime_type VARCHAR(120) NOT NULL,
                size_bytes BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                CONSTRAINT fk_forum_attachments_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_attachments_reply FOREIGN KEY (reply_id) REFERENCES forum_replies(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS forum_notifications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                topic_id BIGINT UNSIGNED NOT NULL,
                reply_id BIGINT UNSIGNED NULL,
                message VARCHAR(190) NOT NULL,
                read_at DATETIME NULL,
                created_at TIMESTAMP NULL,
                INDEX idx_forum_notifications_user (user_id, read_at),
                CONSTRAINT fk_forum_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_notifications_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
                CONSTRAINT fk_forum_notifications_reply FOREIGN KEY (reply_id) REFERENCES forum_replies(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        self::seedDefaults();
    }

    public static function areasForUser(int $userId): array
    {
        self::ensureSchema();
        $user = User::findWithRole($userId);

        if (!$user) {
            return [];
        }

        if (in_array('master', self::roleSlugs($user), true)) {
            return Database::connection()
                ->query('SELECT * FROM forum_areas WHERE active = 1 ORDER BY sort_order ASC, name ASC')
                ->fetchAll();
        }

        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT forum_areas.*
             FROM forum_areas
             LEFT JOIN forum_area_roles ON forum_area_roles.area_id = forum_areas.id AND forum_area_roles.can_view = 1
             LEFT JOIN user_roles ON user_roles.role_id = forum_area_roles.role_id AND user_roles.user_id = :user_roles_id
             WHERE forum_areas.active = 1
               AND (forum_areas.is_public = 1 OR user_roles.user_id IS NOT NULL OR forum_area_roles.role_id = (SELECT role_id FROM users WHERE id = :user_id))
             ORDER BY forum_areas.sort_order ASC, forum_areas.name ASC'
        );
        $stmt->execute(['user_roles_id' => $userId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function findArea(string $slug): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM forum_areas WHERE slug = :slug AND active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    public static function categoriesForArea(int $areaId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT * FROM forum_categories WHERE area_id = :area_id AND active = 1 ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute(['area_id' => $areaId]);

        return $stmt->fetchAll();
    }

    public static function topicsForArea(int $areaId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT forum_topics.*,
                    forum_categories.name AS category_name,
                    users.name AS user_name,
                    COUNT(DISTINCT forum_replies.id) AS reply_count,
                    COUNT(DISTINCT forum_attachments.id) AS attachment_count
             FROM forum_topics
             LEFT JOIN forum_categories ON forum_categories.id = forum_topics.category_id
             INNER JOIN users ON users.id = forum_topics.user_id
             LEFT JOIN forum_replies ON forum_replies.topic_id = forum_topics.id AND forum_replies.active = 1
             LEFT JOIN forum_attachments ON forum_attachments.topic_id = forum_topics.id
             WHERE forum_topics.area_id = :area_id
               AND forum_topics.status <> "hidden"
             GROUP BY forum_topics.id
             ORDER BY forum_topics.pinned DESC, forum_topics.updated_at DESC, forum_topics.created_at DESC'
        );
        $stmt->execute(['area_id' => $areaId]);

        return $stmt->fetchAll();
    }

    public static function findTopic(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT forum_topics.*, forum_areas.slug AS area_slug, forum_areas.name AS area_name, users.name AS user_name
             FROM forum_topics
             INNER JOIN forum_areas ON forum_areas.id = forum_topics.area_id
             INNER JOIN users ON users.id = forum_topics.user_id
             WHERE forum_topics.id = :id
               AND forum_areas.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function repliesForTopic(int $topicId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT forum_replies.*, users.name AS user_name
             FROM forum_replies
             INNER JOIN users ON users.id = forum_replies.user_id
             WHERE forum_replies.topic_id = :topic_id
               AND forum_replies.active = 1
             ORDER BY forum_replies.created_at ASC, forum_replies.id ASC'
        );
        $stmt->execute(['topic_id' => $topicId]);

        return $stmt->fetchAll();
    }

    public static function attachmentsForTopic(int $topicId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT * FROM forum_attachments WHERE topic_id = :topic_id OR reply_id IN (SELECT id FROM forum_replies WHERE topic_id = :reply_topic_id)'
        );
        $stmt->execute(['topic_id' => $topicId, 'reply_topic_id' => $topicId]);

        return $stmt->fetchAll();
    }

    public static function findAttachment(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT forum_attachments.*, forum_topics.area_id
             FROM forum_attachments
             LEFT JOIN forum_topics ON forum_topics.id = forum_attachments.topic_id
             LEFT JOIN forum_replies ON forum_replies.id = forum_attachments.reply_id
             LEFT JOIN forum_topics reply_topic ON reply_topic.id = forum_replies.topic_id
             WHERE forum_attachments.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $attachment = $stmt->fetch() ?: null;

        if ($attachment && empty($attachment['area_id'])) {
            $stmt = Database::connection()->prepare(
                'SELECT reply_topic.area_id
                 FROM forum_attachments
                 INNER JOIN forum_replies ON forum_replies.id = forum_attachments.reply_id
                 INNER JOIN forum_topics reply_topic ON reply_topic.id = forum_replies.topic_id
                 WHERE forum_attachments.id = :id'
            );
            $stmt->execute(['id' => $id]);
            $attachment['area_id'] = $stmt->fetchColumn() ?: null;
        }

        return $attachment;
    }

    public static function createTopic(array $data): int
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'INSERT INTO forum_topics (area_id, category_id, user_id, title, body, status, pinned, is_public, created_at, updated_at)
             VALUES (:area_id, :category_id, :user_id, :title, :body, "open", 0, :is_public, NOW(), NOW())'
        );
        $stmt->execute([
            'area_id' => (int) $data['area_id'],
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'user_id' => (int) $data['user_id'],
            'title' => trim((string) $data['title']),
            'body' => trim((string) $data['body']),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
        ]);

        $topicId = (int) Database::connection()->lastInsertId();
        self::notifyArea($topicId, null, (int) $data['area_id'], (int) $data['user_id'], 'Novo tópico no fórum.');

        return $topicId;
    }

    public static function updateTopic(int $topicId, string $title, string $body, ?int $userId = null): void
    {
        self::ensureSchema();

        $userSql = $userId ? ', user_id = :user_id' : '';
        $params = [
            'title' => trim($title),
            'body' => trim($body),
            'id' => $topicId,
        ];
        if ($userId) {
            $params['user_id'] = $userId;
        }

        Database::connection()->prepare(
            'UPDATE forum_topics
             SET title = :title,
                 body = :body,
                 ' . ltrim($userSql, ', ') . ($userSql ? ',' : '') . '
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($params);
    }

    public static function createReply(array $data): int
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'INSERT INTO forum_replies (topic_id, user_id, body, active, created_at, updated_at)
             VALUES (:topic_id, :user_id, :body, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'topic_id' => (int) $data['topic_id'],
            'user_id' => (int) $data['user_id'],
            'body' => trim((string) $data['body']),
        ]);
        $replyId = (int) Database::connection()->lastInsertId();

        Database::connection()
            ->prepare('UPDATE forum_topics SET updated_at = NOW() WHERE id = :id')
            ->execute(['id' => (int) $data['topic_id']]);

        $topic = self::findTopic((int) $data['topic_id']);
        if ($topic) {
            self::notifyArea((int) $topic['id'], $replyId, (int) $topic['area_id'], (int) $data['user_id'], 'Nova resposta no fórum.');
        }

        return $replyId;
    }

    public static function addAttachment(array $data): void
    {
        self::ensureSchema();
        Database::connection()->prepare(
            'INSERT INTO forum_attachments (topic_id, reply_id, uploaded_by, path, original_name, mime_type, size_bytes, created_at)
             VALUES (:topic_id, :reply_id, :uploaded_by, :path, :original_name, :mime_type, :size_bytes, NOW())'
        )->execute([
            'topic_id' => $data['topic_id'] ?? null,
            'reply_id' => $data['reply_id'] ?? null,
            'uploaded_by' => (int) $data['uploaded_by'],
            'path' => $data['path'],
            'original_name' => $data['original_name'],
            'mime_type' => $data['mime_type'],
            'size_bytes' => (int) $data['size_bytes'],
        ]);
    }

    public static function setTopicStatus(int $topicId, string $status): void
    {
        self::ensureSchema();
        $status = in_array($status, ['open', 'closed', 'hidden'], true) ? $status : 'open';

        Database::connection()
            ->prepare('UPDATE forum_topics SET status = :status, updated_at = NOW() WHERE id = :id')
            ->execute(['status' => $status, 'id' => $topicId]);
    }

    public static function deactivateReply(int $replyId): void
    {
        self::ensureSchema();
        Database::connection()
            ->prepare('UPDATE forum_replies SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $replyId]);
    }

    public static function createCategory(int $areaId, string $name): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO forum_categories (area_id, name, slug, active, sort_order, created_at, updated_at)
             VALUES (:area_id, :name, :slug, 1, 0, NOW(), NOW())'
        );
        $stmt->execute([
            'area_id' => $areaId,
            'name' => trim($name),
            'slug' => slugify($name),
        ]);
    }

    public static function canViewArea(array $area, int $userId): bool
    {
        if (Auth::hasRole('master') || !empty($area['is_public'])) {
            return true;
        }

        return self::areaAccess($area, $userId, 'can_view');
    }

    public static function canPostArea(array $area, int $userId): bool
    {
        return Auth::can('forum.create') && self::areaAccess($area, $userId, 'can_post');
    }

    public static function canModerateArea(array $area, int $userId): bool
    {
        return Auth::can('forum.moderate') || self::areaAccess($area, $userId, 'can_moderate');
    }

    public static function unreadCount(int $userId): int
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM forum_notifications WHERE user_id = :user_id AND read_at IS NULL');
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function markTopicNotificationsRead(int $topicId, int $userId): void
    {
        self::ensureSchema();
        Database::connection()
            ->prepare('UPDATE forum_notifications SET read_at = NOW() WHERE topic_id = :topic_id AND user_id = :user_id AND read_at IS NULL')
            ->execute(['topic_id' => $topicId, 'user_id' => $userId]);
    }

    private static function areaAccess(array $area, int $userId, string $field): bool
    {
        if (Auth::hasRole('master')) {
            return true;
        }

        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM forum_area_roles
             LEFT JOIN user_roles ON user_roles.role_id = forum_area_roles.role_id AND user_roles.user_id = :user_roles_id
             WHERE forum_area_roles.area_id = :area_id
               AND forum_area_roles.' . $field . ' = 1
               AND (user_roles.user_id IS NOT NULL OR forum_area_roles.role_id = (SELECT role_id FROM users WHERE id = :user_id))
             LIMIT 1'
        );
        $stmt->execute([
            'user_roles_id' => $userId,
            'area_id' => (int) $area['id'],
            'user_id' => $userId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private static function notifyArea(int $topicId, ?int $replyId, int $areaId, int $authorId, string $message): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO forum_notifications (user_id, topic_id, reply_id, message, created_at)
             SELECT DISTINCT users.id, :topic_id, :reply_id, :message, NOW()
             FROM users
             INNER JOIN user_roles ON user_roles.user_id = users.id
             INNER JOIN forum_area_roles ON forum_area_roles.role_id = user_roles.role_id
             WHERE forum_area_roles.area_id = :area_id
               AND forum_area_roles.can_view = 1
               AND users.active = 1
               AND users.id <> :author_id'
        );
        $stmt->execute([
            'topic_id' => $topicId,
            'reply_id' => $replyId,
            'message' => $message,
            'area_id' => $areaId,
            'author_id' => $authorId,
        ]);
    }

    private static function seedDefaults(): void
    {
        $areas = [
            ['Fórum da direção', 'direcao', 'Discussões privadas da direção.', 0, 10],
            ['Fórum dos professores', 'professores', 'Planejamento pedagógico e suporte docente.', 0, 20],
            ['Fórum dos estudantes', 'estudantes', 'Dúvidas e conversas dos estudantes.', 0, 30],
            ['Fórum institucional interno', 'institucional-interno', 'Assuntos internos autorizados da instituição.', 0, 40],
        ];

        $stmt = Database::connection()->prepare(
            'INSERT INTO forum_areas (name, slug, description, is_public, active, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order), updated_at = NOW()'
        );

        foreach ($areas as $area) {
            $stmt->execute($area);
        }

        $permissions = [
            'direcao' => [
                'master' => [1, 1, 1],
                'diretor' => [1, 1, 1],
            ],
            'professores' => [
                'master' => [1, 1, 1],
                'diretor' => [1, 1, 1],
                'professor' => [1, 1, 0],
            ],
            'estudantes' => [
                'master' => [1, 1, 1],
                'diretor' => [1, 1, 1],
                'professor' => [1, 1, 1],
                'estudante' => [1, 1, 0],
            ],
            'institucional-interno' => [
                'master' => [1, 1, 1],
                'admin' => [1, 1, 1],
                'admin-local' => [1, 1, 1],
                'diretor' => [1, 1, 1],
                'professor' => [1, 1, 0],
                'voluntario' => [1, 1, 0],
            ],
        ];

        $areaRows = Database::connection()->query('SELECT id, slug FROM forum_areas')->fetchAll();
        $roleRows = Database::connection()->query('SELECT id, slug FROM roles')->fetchAll();
        $areaMap = array_column($areaRows, 'id', 'slug');
        $roleMap = array_column($roleRows, 'id', 'slug');
        $stmt = Database::connection()->prepare(
            'INSERT INTO forum_area_roles (area_id, role_id, can_view, can_post, can_moderate, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_post = VALUES(can_post), can_moderate = VALUES(can_moderate)'
        );

        foreach ($permissions as $areaSlug => $rolePermissions) {
            foreach ($rolePermissions as $roleSlug => $flags) {
                if (isset($areaMap[$areaSlug], $roleMap[$roleSlug])) {
                    $stmt->execute([$areaMap[$areaSlug], $roleMap[$roleSlug], $flags[0], $flags[1], $flags[2]]);
                }
            }
        }

        $categoryStmt = Database::connection()->prepare(
            'INSERT IGNORE INTO forum_categories (area_id, name, slug, description, active, sort_order, created_at, updated_at)
             VALUES (:area_id, :name, :slug, NULL, 1, :sort_order, NOW(), NOW())'
        );

        foreach ($areaMap as $areaId) {
            $categoryStmt->execute(['area_id' => $areaId, 'name' => 'Geral', 'slug' => 'geral', 'sort_order' => 10]);
        }
    }

    private static function roleSlugs(array $user): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? '')))));
    }
}
