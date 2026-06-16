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
                INDEX idx_event_messages_conversation (conversation_id, created_at),
                INDEX idx_event_messages_sender (sender_user_id),
                CONSTRAINT fk_event_messages_conversation FOREIGN KEY (conversation_id) REFERENCES event_conversations(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB"
        );

        $done = true;
    }

    public static function conversationsForUser(array $user): array
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

    public static function canModerate(?array $user = null): bool
    {
        $user ??= Auth::user();

        return $user
            && (Auth::hasRole(['master', 'admin', 'admin-local']) || Auth::can('events.manage') || Auth::can('event_participants.manage'));
    }

    private static function canAccessEvent(array $event, array $user): bool
    {
        if (self::canModerate($user)) {
            return true;
        }

        $availableIds = array_map('intval', array_column(self::availableEventsForUser($user), 'id'));

        return in_array((int) $event['id'], $availableIds, true);
    }

    private static function conversationSelect(): string
    {
        return 'SELECT event_conversations.*,
                       library_events.title AS event_title,
                       library_events.starts_at AS event_starts_at,
                       participant.name AS participant_name,
                       participant.email AS participant_email,
                       responsible.name AS responsible_name,
                       last_message.body AS last_message_body,
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
}
