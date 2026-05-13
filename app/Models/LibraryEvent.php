<?php

namespace App\Models;

use App\Core\Database;

class LibraryEvent
{
    public static function all(): array
    {
        return Database::connection()
            ->query(
                'SELECT library_events.*,
                        responsible.name AS responsible_name,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN users responsible ON responsible.id = library_events.responsible_user_id
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
                 WHERE library_events.active = 1
                 ORDER BY COALESCE(library_events.starts_at, library_events.created_at) DESC'
            )
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM library_events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function participants(int $eventId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT library_event_participants.*,
                    people.full_name,
                    people.email,
                    people.phone,
                    people.whatsapp,
                    people.district
             FROM library_event_participants
             INNER JOIN people ON people.id = library_event_participants.person_id
             WHERE library_event_participants.event_id = :event_id
             ORDER BY people.full_name ASC'
        );
        $stmt->execute(['event_id' => $eventId]);

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO library_events
                (title, description, starts_at, ends_at, location, capacity, responsible_user_id, status, notes, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:title, :description, :starts_at, :ends_at, :location, :capacity, :responsible_user_id, :status, :notes, 1, :created_by, :updated_by, NOW(), NOW())'
        );
        $stmt->execute(self::payload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload['id'] = $id;
        unset($payload['created_by']);

        $stmt = Database::connection()->prepare(
            'UPDATE library_events
             SET title = :title,
                 description = :description,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 location = :location,
                 capacity = :capacity,
                 responsible_user_id = :responsible_user_id,
                 status = :status,
                 notes = :notes,
                 updated_by = :updated_by,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($payload);
    }

    public static function deactivate(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE library_events SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function attachParticipant(int $eventId, int $personId, string $status, ?string $notes, ?int $createdBy): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO library_event_participants (event_id, person_id, status, notes, created_by, created_at)
             VALUES (:event_id, :person_id, :status, :notes, :created_by, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)'
        );
        $stmt->execute([
            'event_id' => $eventId,
            'person_id' => $personId,
            'status' => in_array($status, ['inscrito', 'presente', 'ausente', 'cancelado'], true) ? $status : 'inscrito',
            'notes' => self::nullable($notes),
            'created_by' => $createdBy,
        ]);
    }

    public static function detachParticipant(int $eventId, int $personId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM library_event_participants WHERE event_id = :event_id AND person_id = :person_id'
        );
        $stmt->execute(['event_id' => $eventId, 'person_id' => $personId]);
    }

    private static function payload(array $data): array
    {
        $startsAt = self::dateTime($data['starts_at'] ?? null);
        $endsAt = self::dateTime($data['ends_at'] ?? null);
        $status = (string) ($data['status'] ?? 'aberto');

        return [
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => self::nullable($data['description'] ?? null),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'location' => self::nullable($data['location'] ?? null),
            'capacity' => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'responsible_user_id' => !empty($data['responsible_user_id']) ? (int) $data['responsible_user_id'] : null,
            'status' => in_array($status, ['aberto', 'encerrado', 'cancelado'], true) ? $status : 'aberto',
            'notes' => self::nullable($data['notes'] ?? null),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }

    private static function dateTime(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        return str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
    }
}
