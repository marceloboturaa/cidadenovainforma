<?php

namespace App\Models;

use App\Core\Database;

class Education
{
    public static function ensureSchema(): void
    {
        $db = Database::connection();

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_courses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                summary TEXT NULL,
                cover_image VARCHAR(255) NULL,
                teacher_user_id BIGINT UNSIGNED NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_courses_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_modules (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                summary TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_modules_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lessons (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                module_id BIGINT UNSIGNED NULL,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                video_url VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_lessons_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_lessons_module FOREIGN KEY (module_id) REFERENCES education_modules(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $lessonColumns = $db->query('SHOW COLUMNS FROM education_lessons')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('module_id', $lessonColumns, true)) {
            $db->exec('ALTER TABLE education_lessons ADD COLUMN module_id BIGINT UNSIGNED NULL AFTER course_id');
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lesson_blocks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lesson_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(40) NOT NULL DEFAULT "text",
                title VARCHAR(180) NULL,
                content LONGTEXT NULL,
                media_url VARCHAR(255) NULL,
                file_path VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_blocks_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_enrollments (
                course_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (course_id, user_id),
                CONSTRAINT fk_education_enrollments_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lesson_progress (
                lesson_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                completed_at DATETIME NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (lesson_id, user_id),
                CONSTRAINT fk_education_progress_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_forum_topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                body TEXT NOT NULL,
                status ENUM("open","closed","hidden") NOT NULL DEFAULT "open",
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_education_forum_topics_course (course_id),
                CONSTRAINT fk_education_topics_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_forum_replies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_replies_topic FOREIGN KEY (topic_id) REFERENCES education_forum_topics(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
    }

    public static function coursesForManagement(?int $teacherUserId = null): array
    {
        self::ensureSchema();

        $where = 'education_courses.active = 1';
        $params = [];
        if ($teacherUserId) {
            $where .= ' AND education_courses.teacher_user_id = :teacher_user_id';
            $params['teacher_user_id'] = $teacherUserId;
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*,
                    teacher.name AS teacher_name,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count,
                    COUNT(DISTINCT education_enrollments.user_id) AS student_count
             FROM education_courses
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             LEFT JOIN education_lessons ON education_lessons.course_id = education_courses.id AND education_lessons.active = 1
             LEFT JOIN education_enrollments ON education_enrollments.course_id = education_courses.id
             WHERE ' . $where . '
             GROUP BY education_courses.id
             ORDER BY education_courses.created_at DESC, education_courses.id DESC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function coursesForUser(int $userId, bool $canManage = false): array
    {
        self::ensureSchema();

        if ($canManage) {
            return self::coursesForManagement();
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*,
                    teacher.name AS teacher_name,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count,
                    COUNT(DISTINCT completed.lesson_id) AS completed_count
             FROM education_courses
             INNER JOIN education_enrollments ON education_enrollments.course_id = education_courses.id
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             LEFT JOIN education_lessons ON education_lessons.course_id = education_courses.id AND education_lessons.active = 1
             LEFT JOIN education_lesson_progress completed
                ON completed.lesson_id = education_lessons.id
               AND completed.user_id = :progress_user_id
               AND completed.completed_at IS NOT NULL
             WHERE education_courses.active = 1
               AND education_enrollments.user_id = :enrolled_user_id
             GROUP BY education_courses.id
             ORDER BY education_courses.created_at DESC, education_courses.id DESC'
        );
        $stmt->execute([
            'progress_user_id' => $userId,
            'enrolled_user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public static function findCourse(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*, teacher.name AS teacher_name
             FROM education_courses
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             WHERE education_courses.id = :id AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function modulesForCourse(int $courseId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_modules.*,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count
             FROM education_modules
             LEFT JOIN education_lessons ON education_lessons.module_id = education_modules.id AND education_lessons.active = 1
             WHERE education_modules.course_id = :course_id
               AND education_modules.active = 1
             GROUP BY education_modules.id
             ORDER BY education_modules.sort_order ASC, education_modules.id ASC'
        );
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    public static function createModule(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_modules (course_id, title, summary, sort_order, active, created_at, updated_at)
             VALUES (:course_id, :title, :summary, :sort_order, 1, NOW(), NOW())'
        );
        $stmt->execute(self::modulePayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateModule(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::modulePayload($data);
        $payload['id'] = $id;

        Database::connection()->prepare(
            'UPDATE education_modules
             SET course_id = :course_id,
                 title = :title,
                 summary = :summary,
                 sort_order = :sort_order,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateModule(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_modules SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function userCanAccessCourse(int $courseId, int $userId, bool $canManage = false): bool
    {
        if ($canManage) {
            return self::findCourse($courseId) !== null;
        }

        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM education_enrollments
             INNER JOIN education_courses ON education_courses.id = education_enrollments.course_id
             WHERE education_enrollments.course_id = :course_id
               AND education_enrollments.user_id = :user_id
               AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function lessonsForCourse(int $courseId, int $userId = 0): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_lessons.*,
                    progress.completed_at,
                    education_modules.title AS module_title
             FROM education_lessons
             LEFT JOIN education_modules ON education_modules.id = education_lessons.module_id
             LEFT JOIN education_lesson_progress progress
                ON progress.lesson_id = education_lessons.id
               AND progress.user_id = :user_id
             WHERE education_lessons.course_id = :course_id
               AND education_lessons.active = 1
             ORDER BY COALESCE(education_modules.sort_order, 0) ASC,
                      education_lessons.sort_order ASC,
                      education_lessons.id ASC'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function findLesson(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_lessons.*, education_courses.title AS course_title
             FROM education_lessons
             INNER JOIN education_courses ON education_courses.id = education_lessons.course_id
             WHERE education_lessons.id = :id
               AND education_lessons.active = 1
               AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function blocksForLesson(int $lessonId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT *
             FROM education_lesson_blocks
             WHERE lesson_id = :lesson_id
               AND active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['lesson_id' => $lessonId]);

        return $stmt->fetchAll();
    }

    public static function findLessonBlock(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_lesson_blocks.*,
                    education_lessons.course_id,
                    education_lessons.title AS lesson_title
             FROM education_lesson_blocks
             INNER JOIN education_lessons ON education_lessons.id = education_lesson_blocks.lesson_id
             INNER JOIN education_courses ON education_courses.id = education_lessons.course_id
             WHERE education_lesson_blocks.id = :id
               AND education_lesson_blocks.active = 1
               AND education_lessons.active = 1
               AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function createLessonBlock(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_lesson_blocks
                (lesson_id, type, title, content, media_url, file_path, sort_order, active, created_at, updated_at)
             VALUES
                (:lesson_id, :type, :title, :content, :media_url, :file_path, :sort_order, 1, NOW(), NOW())'
        );
        $stmt->execute(self::blockPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateLessonBlock(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::blockPayload($data);
        $payload['id'] = $id;

        Database::connection()->prepare(
            'UPDATE education_lesson_blocks
             SET lesson_id = :lesson_id,
                 type = :type,
                 title = :title,
                 content = :content,
                 media_url = :media_url,
                 file_path = :file_path,
                 sort_order = :sort_order,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateLessonBlock(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_lesson_blocks SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function createCourse(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_courses
                (title, summary, cover_image, teacher_user_id, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:title, :summary, :cover_image, :teacher_user_id, 1, :created_by, :updated_by, NOW(), NOW())'
        );
        $stmt->execute(self::coursePayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateCourse(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::coursePayload($data);
        $payload['id'] = $id;
        unset($payload['created_by']);

        Database::connection()->prepare(
            'UPDATE education_courses
             SET title = :title,
                 summary = :summary,
                 cover_image = :cover_image,
                 teacher_user_id = :teacher_user_id,
                 updated_by = :updated_by,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateCourse(int $id): void
    {
        self::ensureSchema();
        Database::connection()
            ->prepare('UPDATE education_courses SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function createLesson(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_lessons
                (course_id, module_id, title, description, video_url, sort_order, active, created_at, updated_at)
             VALUES
                (:course_id, :module_id, :title, :description, :video_url, :sort_order, 1, NOW(), NOW())'
        );
        $stmt->execute(self::lessonPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateLesson(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::lessonPayload($data);
        $payload['id'] = $id;

        Database::connection()->prepare(
            'UPDATE education_lessons
             SET course_id = :course_id,
                 module_id = :module_id,
                 title = :title,
                 description = :description,
                 video_url = :video_url,
                 sort_order = :sort_order,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateLesson(int $id): void
    {
        self::ensureSchema();
        Database::connection()
            ->prepare('UPDATE education_lessons SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function syncEnrollments(int $courseId, array $userIds): void
    {
        self::ensureSchema();

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('DELETE FROM education_enrollments WHERE course_id = :course_id')->execute(['course_id' => $courseId]);
            $stmt = $db->prepare('INSERT IGNORE INTO education_enrollments (course_id, user_id, created_at) VALUES (:course_id, :user_id, NOW())');

            foreach ($userIds as $userId) {
                $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function enrollmentUserIds(int $courseId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT user_id FROM education_enrollments WHERE course_id = :course_id');
        $stmt->execute(['course_id' => $courseId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
    }

    public static function markLesson(int $lessonId, int $userId, bool $completed): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'INSERT INTO education_lesson_progress (lesson_id, user_id, completed_at, updated_at)
             VALUES (:lesson_id, :user_id, :completed_at, NOW())
             ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at), updated_at = NOW()'
        )->execute([
            'lesson_id' => $lessonId,
            'user_id' => $userId,
            'completed_at' => $completed ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public static function forumTopics(?int $courseId = null): array
    {
        self::ensureSchema();

        $scope = $courseId ? 'education_forum_topics.course_id = :course_id' : 'education_forum_topics.course_id IS NULL';
        $stmt = Database::connection()->prepare(
            'SELECT education_forum_topics.*,
                    users.name AS user_name,
                    COUNT(education_forum_replies.id) AS reply_count
             FROM education_forum_topics
             INNER JOIN users ON users.id = education_forum_topics.user_id
             LEFT JOIN education_forum_replies
                ON education_forum_replies.topic_id = education_forum_topics.id
               AND education_forum_replies.active = 1
             WHERE ' . $scope . '
               AND education_forum_topics.status <> "hidden"
             GROUP BY education_forum_topics.id
             ORDER BY education_forum_topics.updated_at DESC, education_forum_topics.created_at DESC'
        );
        $stmt->execute($courseId ? ['course_id' => $courseId] : []);

        return $stmt->fetchAll();
    }

    public static function createForumTopic(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_forum_topics (course_id, user_id, title, body, status, created_at, updated_at)
             VALUES (:course_id, :user_id, :title, :body, "open", NOW(), NOW())'
        );
        $stmt->execute(self::topicPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function forumReplies(int $topicId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_forum_replies.*, users.name AS user_name
             FROM education_forum_replies
             INNER JOIN users ON users.id = education_forum_replies.user_id
             WHERE education_forum_replies.topic_id = :topic_id
               AND education_forum_replies.active = 1
             ORDER BY education_forum_replies.created_at ASC, education_forum_replies.id ASC'
        );
        $stmt->execute(['topic_id' => $topicId]);

        return $stmt->fetchAll();
    }

    public static function createForumReply(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_forum_replies (topic_id, user_id, body, active, created_at, updated_at)
             VALUES (:topic_id, :user_id, :body, 1, NOW(), NOW())'
        );
        $stmt->execute(self::replyPayload($data));

        Database::connection()
            ->prepare('UPDATE education_forum_topics SET updated_at = NOW() WHERE id = :id')
            ->execute(['id' => (int) $data['topic_id']]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function closeForumTopic(int $topicId): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forum_topics SET status = "closed", updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $topicId]);
    }

    private static function coursePayload(array $data): array
    {
        return [
            'title' => trim((string) ($data['title'] ?? '')),
            'summary' => self::nullable($data['summary'] ?? null),
            'cover_image' => self::nullable($data['cover_image'] ?? null),
            'teacher_user_id' => !empty($data['teacher_user_id']) ? (int) $data['teacher_user_id'] : null,
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    private static function modulePayload(array $data): array
    {
        return [
            'course_id' => (int) ($data['course_id'] ?? 0),
            'title' => trim((string) ($data['title'] ?? '')),
            'summary' => self::nullable($data['summary'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private static function lessonPayload(array $data): array
    {
        return [
            'course_id' => (int) ($data['course_id'] ?? 0),
            'module_id' => !empty($data['module_id']) ? (int) $data['module_id'] : null,
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => self::nullable($data['description'] ?? null),
            'video_url' => self::nullable($data['video_url'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private static function blockPayload(array $data): array
    {
        $allowedTypes = ['video', 'text', 'image', 'article', 'file', 'embed', 'quiz', 'assignment'];
        $type = strtolower(trim((string) ($data['type'] ?? 'text')));

        return [
            'lesson_id' => (int) ($data['lesson_id'] ?? 0),
            'type' => in_array($type, $allowedTypes, true) ? $type : 'text',
            'title' => self::nullable($data['title'] ?? null),
            'content' => self::nullable($data['content'] ?? null),
            'media_url' => self::nullable($data['media_url'] ?? null),
            'file_path' => self::nullable($data['file_path'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private static function topicPayload(array $data): array
    {
        return [
            'course_id' => !empty($data['course_id']) ? (int) $data['course_id'] : null,
            'user_id' => (int) ($data['user_id'] ?? 0),
            'title' => trim((string) ($data['title'] ?? '')),
            'body' => trim((string) ($data['body'] ?? '')),
        ];
    }

    private static function replyPayload(array $data): array
    {
        return [
            'topic_id' => (int) ($data['topic_id'] ?? 0),
            'user_id' => (int) ($data['user_id'] ?? 0),
            'body' => trim((string) ($data['body'] ?? '')),
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }
}
