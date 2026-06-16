<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class Communication
{
    public static function ensureSchema(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        LibraryEvent::ensureSchema();
        Education::ensureSchema();

        $db = Database::connection();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS event_conversations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_id BIGINT UNSIGNED NOT NULL,
                participant_user_id BIGINT UNSIGNED NOT NULL,
                responsible_user_id BIGINT UNSIGNED NULL,
                status ENUM('aberta','encerrada') NOT NULL DEFAULT 'aberta',
                last_message_at DATETIME NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uniq_event_conversation_user (event_id, participant_user_id),
                INDEX idx_event_conversations_responsible (responsible_user_id),
                INDEX idx_event_conversations_last_message (last_message_at),
                CONSTRAINT fk_event_conversations_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_conversations_participant FOREIGN KEY (participant_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_conversations_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS event_conversation_messages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                conversation_id BIGINT UNSIGNED NOT NULL,
                sender_user_id BIGINT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMP NULL,
                read_at DATETIME NULL,
                deleted_at DATETIME NULL,
                deleted_by BIGINT UNSIGNED NULL,
                INDEX idx_event_messages_conversation (conversation_id, created_at),
                INDEX idx_event_messages_sender (sender_user_id),
                CONSTRAINT fk_event_messages_conversation FOREIGN KEY (conversation_id) REFERENCES event_conversations(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_messages_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );
        self::ensureColumn('event_conversation_messages', 'deleted_at', 'DATETIME NULL AFTER read_at');
        self::ensureColumn('event_conversation_messages', 'deleted_by', 'BIGINT UNSIGNED NULL AFTER deleted_at');

        $db->exec(
            "CREATE TABLE IF NOT EXISTS education_conversations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                student_user_id BIGINT UNSIGNED NOT NULL,
                teacher_user_id BIGINT UNSIGNED NULL,
                status ENUM('aberta','encerrada') NOT NULL DEFAULT 'aberta',
                last_message_at DATETIME NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uniq_education_conversation_user (course_id, student_user_id),
                INDEX idx_education_conversations_teacher (teacher_user_id),
                INDEX idx_education_conversations_last_message (last_message_at),
                CONSTRAINT fk_education_conversations_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_conversations_student FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_conversations_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS education_conversation_messages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                conversation_id BIGINT UNSIGNED NOT NULL,
                sender_user_id BIGINT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMP NULL,
                read_at DATETIME NULL,
                deleted_at DATETIME NULL,
                deleted_by BIGINT UNSIGNED NULL,
                INDEX idx_education_messages_conversation (conversation_id, created_at),
                INDEX idx_education_messages_sender (sender_user_id),
                CONSTRAINT fk_education_messages_conversation FOREIGN KEY (conversation_id) REFERENCES education_conversations(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_messages_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );
        self::ensureColumn('education_conversation_messages', 'deleted_at', 'DATETIME NULL AFTER read_at');
        self::ensureColumn('education_conversation_messages', 'deleted_by', 'BIGINT UNSIGNED NULL AFTER deleted_at');

        $done = true;
    }

    public static function conversationsForUser(array $user): array
    {
        return self::sortConversations(array_merge(
            self::eventConversationsForUser($user),
            self::educationConversationsForUser($user)
        ));
    }

    public static function eventConversationsForUser(array $user): array
    {
        self::ensureSchema();

        $userId = (int) $user['id'];
        $canModerate = self::canModerate($user);
        $params = [];
        $where = [];

        if (!$canModerate) {
            $where[] = '(event_conversations.participant_user_id = :participant_user_id OR event_conversations.responsible_user_id = :responsible_user_id)';
            $params['participant_user_id'] = $userId;
            $params['responsible_user_id'] = $userId;
        }

        $sql = self::conversationSelect()
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY COALESCE(event_conversations.last_message_at, event_conversations.created_at) DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function educationConversationsForUser(array $user): array
    {
        self::ensureSchema();

        $userId = (int) $user['id'];
        $canManage = self::canModerateEducation($user);
        $params = [];
        $where = [];

        if (!$canManage) {
            $where[] = '(education_conversations.student_user_id = :student_user_id OR education_conversations.teacher_user_id = :teacher_user_id)';
            $params['student_user_id'] = $userId;
            $params['teacher_user_id'] = $userId;
        }

        $sql = self::educationConversationSelect()
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY COALESCE(education_conversations.last_message_at, education_conversations.created_at) DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function availableEventsForUser(array $user): array
    {
        self::ensureSchema();

        if (self::canModerate($user)) {
            return LibraryEvent::all();
        }

        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT library_events.*,
                    responsible.name AS responsible_name,
                    education_courses.title AS course_title,
                    education_courses.summary AS course_summary,
                    education_courses.cover_image AS course_cover_image,
                    COALESCE(participant_counts.total, 0) AS participant_count
             FROM library_events
             LEFT JOIN users responsible ON responsible.id = library_events.responsible_user_id
             LEFT JOIN education_courses ON education_courses.id = library_events.event_course_id
             LEFT JOIN (
                SELECT event_id, COUNT(*) AS total
                FROM library_event_participants
                WHERE status <> "cancelado"
                GROUP BY event_id
             ) participant_counts ON participant_counts.event_id = library_events.id
             LEFT JOIN library_event_participants ON library_event_participants.event_id = library_events.id
             LEFT JOIN people ON people.id = library_event_participants.person_id
             WHERE library_events.active = 1
               AND (
                    library_events.responsible_user_id = :responsible_user_id
                    OR library_events.created_by = :created_by
                    OR library_events.id = (
                        SELECT users.registration_event_id FROM users WHERE users.id = :registration_user_id LIMIT 1
                    )
                    OR LOWER(people.email) = LOWER(:email)
               )
             ORDER BY COALESCE(library_events.starts_at, library_events.created_at) DESC'
        );
        $stmt->execute([
            'responsible_user_id' => (int) $user['id'],
            'created_by' => (int) $user['id'],
            'registration_user_id' => (int) $user['id'],
            'email' => (string) ($user['email'] ?? ''),
        ]);

        return $stmt->fetchAll();
    }

    public static function availableCoursesForUser(array $user): array
    {
        self::ensureSchema();

        $userId = (int) $user['id'];

        if (self::canModerateEducation($user)) {
            return Education::coursesForManagement();
        }

        if (self::isTeacher($user)) {
            return Education::coursesForManagement($userId);
        }

        return Education::coursesForUser($userId);
    }

    public static function availableCourseContactsForUser(array $user): array
    {
        self::ensureSchema();

        if (!self::canModerateEducation($user) && !self::isTeacher($user)) {
            return [];
        }

        $params = [];
        $where = [
            'education_courses.active = 1',
            'education_courses.certificate_activity_type <> "reconhecimento"',
            'education_enrollments.status = "approved"',
        ];

        if (!self::canModerateEducation($user)) {
            $where[] = 'education_courses.teacher_user_id = :teacher_user_id';
            $params['teacher_user_id'] = (int) $user['id'];
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.id AS course_id,
                    education_courses.title AS course_title,
                    education_courses.teacher_user_id,
                    users.id AS student_user_id,
                    users.name AS student_name,
                    users.email AS student_email
             FROM education_enrollments
             INNER JOIN education_courses ON education_courses.id = education_enrollments.course_id
             INNER JOIN users ON users.id = education_enrollments.user_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY education_courses.title ASC, users.name ASC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function startConversation(int $eventId, array $user, ?int $targetUserId = null): int
    {
        self::ensureSchema();

        $event = LibraryEvent::find($eventId);
        if (!$event || !self::canAccessEvent($event, $user)) {
            return 0;
        }

        $participantUserId = (int) $user['id'];
        $responsibleUserId = !empty($event['responsible_user_id']) ? (int) $event['responsible_user_id'] : null;

        if (self::canModerate($user) && $targetUserId) {
            $participantUserId = $targetUserId;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO event_conversations
                (event_id, participant_user_id, responsible_user_id, status, created_at, updated_at)
             VALUES
                (:event_id, :participant_user_id, :responsible_user_id, "aberta", NOW(), NOW())
             ON DUPLICATE KEY UPDATE responsible_user_id = VALUES(responsible_user_id), updated_at = NOW()'
        );
        $stmt->execute([
            'event_id' => $eventId,
            'participant_user_id' => $participantUserId,
            'responsible_user_id' => $responsibleUserId,
        ]);

        $existing = Database::connection()->prepare(
            'SELECT id FROM event_conversations WHERE event_id = :event_id AND participant_user_id = :participant_user_id LIMIT 1'
        );
        $existing->execute([
            'event_id' => $eventId,
            'participant_user_id' => $participantUserId,
        ]);

        return (int) $existing->fetchColumn();
    }

    public static function startEducationConversation(int $courseId, array $user, ?int $studentUserId = null): int
    {
        self::ensureSchema();

        $course = Education::findCourse($courseId);
        if (!$course || !self::canAccessCourse($course, $user, $studentUserId)) {
            return 0;
        }

        $studentUserId = (self::canModerateEducation($user) || self::isTeacherForCourse($course, $user))
            ? (int) ($studentUserId ?: 0)
            : (int) $user['id'];

        if ($studentUserId <= 0 || !Education::userCanAccessCourse($courseId, $studentUserId)) {
            return 0;
        }

        $teacherUserId = !empty($course['teacher_user_id']) ? (int) $course['teacher_user_id'] : null;

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_conversations
                (course_id, student_user_id, teacher_user_id, status, created_at, updated_at)
             VALUES
                (:course_id, :student_user_id, :teacher_user_id, "aberta", NOW(), NOW())
             ON DUPLICATE KEY UPDATE teacher_user_id = VALUES(teacher_user_id), updated_at = NOW()'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'student_user_id' => $studentUserId,
            'teacher_user_id' => $teacherUserId,
        ]);

        $existing = Database::connection()->prepare(
            'SELECT id FROM education_conversations WHERE course_id = :course_id AND student_user_id = :student_user_id LIMIT 1'
        );
        $existing->execute([
            'course_id' => $courseId,
            'student_user_id' => $studentUserId,
        ]);

        return (int) $existing->fetchColumn();
    }

    public static function findConversationForUser(int $id, array $user): ?array
    {
        self::ensureSchema();

        $params = ['id' => $id];
        $where = ['event_conversations.id = :id'];

        if (!self::canModerate($user)) {
            $where[] = '(event_conversations.participant_user_id = :participant_user_id OR event_conversations.responsible_user_id = :responsible_user_id)';
            $params['participant_user_id'] = (int) $user['id'];
            $params['responsible_user_id'] = (int) $user['id'];
        }

        $stmt = Database::connection()->prepare(self::conversationSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public static function findEducationConversationForUser(int $id, array $user): ?array
    {
        self::ensureSchema();

        $params = ['id' => $id];
        $where = ['education_conversations.id = :id'];

        if (!self::canModerateEducation($user)) {
            $where[] = '(education_conversations.student_user_id = :student_user_id OR education_conversations.teacher_user_id = :teacher_user_id)';
            $params['student_user_id'] = (int) $user['id'];
            $params['teacher_user_id'] = (int) $user['id'];
        }

        $stmt = Database::connection()->prepare(self::educationConversationSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    public static function messages(int $conversationId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT event_conversation_messages.*,
                    users.name AS sender_name,
                    roles.name AS sender_role_name
             FROM event_conversation_messages
             INNER JOIN users ON users.id = event_conversation_messages.sender_user_id
             INNER JOIN roles ON roles.id = users.role_id
             WHERE event_conversation_messages.conversation_id = :conversation_id
             ORDER BY event_conversation_messages.created_at ASC, event_conversation_messages.id ASC'
        );
        $stmt->execute(['conversation_id' => $conversationId]);

        return $stmt->fetchAll();
    }

    public static function educationMessages(int $conversationId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_conversation_messages.*,
                    users.name AS sender_name,
                    roles.name AS sender_role_name
             FROM education_conversation_messages
             INNER JOIN users ON users.id = education_conversation_messages.sender_user_id
             INNER JOIN roles ON roles.id = users.role_id
             WHERE education_conversation_messages.conversation_id = :conversation_id
             ORDER BY education_conversation_messages.created_at ASC, education_conversation_messages.id ASC'
        );
        $stmt->execute(['conversation_id' => $conversationId]);

        return $stmt->fetchAll();
    }

    public static function addMessage(int $conversationId, int $senderUserId, string $body): bool
    {
        self::ensureSchema();

        $body = trim($body);
        if ($body === '') {
            return false;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'INSERT INTO event_conversation_messages (conversation_id, sender_user_id, body, created_at)
                 VALUES (:conversation_id, :sender_user_id, :body, NOW())'
            );
            $stmt->execute([
                'conversation_id' => $conversationId,
                'sender_user_id' => $senderUserId,
                'body' => $body,
            ]);

            $db->prepare(
                'UPDATE event_conversations
                 SET last_message_at = NOW(), updated_at = NOW()
                 WHERE id = :id'
            )->execute(['id' => $conversationId]);

            $db->commit();
            return true;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function addEducationMessage(int $conversationId, int $senderUserId, string $body): bool
    {
        self::ensureSchema();

        $body = trim($body);
        if ($body === '') {
            return false;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'INSERT INTO education_conversation_messages (conversation_id, sender_user_id, body, created_at)
                 VALUES (:conversation_id, :sender_user_id, :body, NOW())'
            );
            $stmt->execute([
                'conversation_id' => $conversationId,
                'sender_user_id' => $senderUserId,
                'body' => $body,
            ]);

            $db->prepare(
                'UPDATE education_conversations
                 SET last_message_at = NOW(), updated_at = NOW()
                 WHERE id = :id'
            )->execute(['id' => $conversationId]);

            $db->commit();
            return true;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function deleteMessage(int $messageId, int $userId, string $type): bool
    {
        self::ensureSchema();

        $table = $type === 'education' ? 'education_conversation_messages' : 'event_conversation_messages';
        $conversationTable = $type === 'education' ? 'education_conversations' : 'event_conversations';

        $stmt = Database::connection()->prepare(
            'UPDATE ' . $table . '
             INNER JOIN ' . $conversationTable . ' ON ' . $conversationTable . '.id = ' . $table . '.conversation_id
             SET ' . $table . '.deleted_at = NOW(),
                 ' . $table . '.deleted_by = :deleted_by,
                 ' . $conversationTable . '.updated_at = NOW()
             WHERE ' . $table . '.id = :id
               AND ' . $table . '.sender_user_id = :sender_user_id
               AND ' . $table . '.deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $messageId,
            'sender_user_id' => $userId,
            'deleted_by' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function canModerate(?array $user = null): bool
    {
        $user ??= Auth::user();

        return $user
            && (Auth::hasRole(['master', 'admin', 'admin-local']) || Auth::can('events.manage') || Auth::can('event_participants.manage'));
    }

    public static function canModerateEducation(?array $user = null): bool
    {
        $user ??= Auth::user();

        return $user
            && (Auth::hasRole(['master', 'admin', 'admin-local']) || Auth::can('education.manage'));
    }

    private static function canAccessEvent(array $event, array $user): bool
    {
        if (self::canModerate($user)) {
            return true;
        }

        $availableIds = array_map('intval', array_column(self::availableEventsForUser($user), 'id'));

        return in_array((int) $event['id'], $availableIds, true);
    }

    private static function canAccessCourse(array $course, array $user, ?int $studentUserId = null): bool
    {
        if (self::canModerateEducation($user)) {
            return true;
        }

        if (self::isTeacherForCourse($course, $user)) {
            return $studentUserId ? Education::userCanAccessCourse((int) $course['id'], $studentUserId) : true;
        }

        return Education::userCanAccessCourse((int) $course['id'], (int) $user['id']);
    }

    private static function isTeacher(?array $user): bool
    {
        return $user
            && (Auth::hasRole('professor') || Auth::can('education.teach'));
    }

    private static function isTeacherForCourse(array $course, array $user): bool
    {
        return self::isTeacher($user) && (int) ($course['teacher_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
    }

    private static function sortConversations(array $conversations): array
    {
        usort($conversations, static function (array $a, array $b): int {
            $aDate = strtotime((string) ($a['last_message_created_at'] ?? $a['last_message_at'] ?? $a['created_at'] ?? '')) ?: 0;
            $bDate = strtotime((string) ($b['last_message_created_at'] ?? $b['last_message_at'] ?? $b['created_at'] ?? '')) ?: 0;

            return $bDate <=> $aDate;
        });

        return $conversations;
    }

    private static function conversationSelect(): string
    {
        return 'SELECT event_conversations.*,
                       "event" AS conversation_type,
                       CONCAT("event:", event_conversations.id) AS conversation_key,
                       library_events.title AS event_title,
                       library_events.title AS context_title,
                       library_events.starts_at AS event_starts_at,
                       library_events.starts_at AS context_at,
                       participant.name AS participant_name,
                       participant.email AS participant_email,
                       responsible.name AS responsible_name,
                       responsible.name AS counterpart_name,
                       last_message.body AS last_message_body,
                       last_message.deleted_at AS last_message_deleted_at,
                       last_message.created_at AS last_message_created_at
                FROM event_conversations
                INNER JOIN library_events ON library_events.id = event_conversations.event_id
                INNER JOIN users participant ON participant.id = event_conversations.participant_user_id
                LEFT JOIN users responsible ON responsible.id = event_conversations.responsible_user_id
                LEFT JOIN event_conversation_messages last_message ON last_message.id = (
                    SELECT event_conversation_messages_inner.id
                    FROM event_conversation_messages event_conversation_messages_inner
                    WHERE event_conversation_messages_inner.conversation_id = event_conversations.id
                    ORDER BY event_conversation_messages_inner.created_at DESC, event_conversation_messages_inner.id DESC
                    LIMIT 1
                )';
    }

    private static function educationConversationSelect(): string
    {
        return 'SELECT education_conversations.*,
                       "education" AS conversation_type,
                       CONCAT("education:", education_conversations.id) AS conversation_key,
                       education_courses.title AS course_title,
                       education_courses.title AS context_title,
                       education_courses.starts_at AS context_at,
                       student.name AS participant_name,
                       student.email AS participant_email,
                       teacher.name AS responsible_name,
                       teacher.name AS counterpart_name,
                       last_message.body AS last_message_body,
                       last_message.deleted_at AS last_message_deleted_at,
                       last_message.created_at AS last_message_created_at
                FROM education_conversations
                INNER JOIN education_courses ON education_courses.id = education_conversations.course_id
                INNER JOIN users student ON student.id = education_conversations.student_user_id
                LEFT JOIN users teacher ON teacher.id = education_conversations.teacher_user_id
                LEFT JOIN education_conversation_messages last_message ON last_message.id = (
                    SELECT education_conversation_messages_inner.id
                    FROM education_conversation_messages education_conversation_messages_inner
                    WHERE education_conversation_messages_inner.conversation_id = education_conversations.id
                    ORDER BY education_conversation_messages_inner.created_at DESC, education_conversation_messages_inner.id DESC
                    LIMIT 1
                )';
    }

    private static function ensureColumn(string $table, string $column, string $definition): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $db->quote($column));
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}
