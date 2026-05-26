<?php

namespace App\Models;

use App\Core\Database;

class LibraryEvent
{
    public static function ensureSchema(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $db = Database::connection();

        $db->exec(
            "CREATE TABLE IF NOT EXISTS people (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(160) NOT NULL,
                cpf VARCHAR(20) NULL,
                birth_date DATE NULL,
                phone VARCHAR(30) NULL,
                whatsapp VARCHAR(30) NULL,
                email VARCHAR(190) NULL,
                cep VARCHAR(12) NULL,
                address VARCHAR(255) NULL,
                address_number VARCHAR(30) NULL,
                address_complement VARCHAR(120) NULL,
                district VARCHAR(120) NULL,
                city VARCHAR(120) NULL,
                state VARCHAR(2) NULL,
                is_minor TINYINT(1) NOT NULL DEFAULT 0,
                guardian_name VARCHAR(160) NULL,
                guardian_relation VARCHAR(80) NULL,
                guardian_cpf VARCHAR(20) NULL,
                guardian_phone VARCHAR(30) NULL,
                guardian_email VARCHAR(190) NULL,
                contact_authorized TINYINT(1) NOT NULL DEFAULT 0,
                notes TEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_people_name (full_name),
                INDEX idx_people_contact (email, whatsapp),
                CONSTRAINT fk_people_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_people_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS library_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                location VARCHAR(160) NULL,
                cover_image VARCHAR(255) NULL,
                related_links TEXT NULL,
                capacity INT UNSIGNED NULL,
                responsible_user_id BIGINT UNSIGNED NULL,
                status ENUM('aberto','encerrado','cancelado') NOT NULL DEFAULT 'aberto',
                notes TEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_library_events_starts_at (starts_at),
                CONSTRAINT fk_library_events_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_library_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_library_events_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $eventColumns = $db->query('SHOW COLUMNS FROM library_events')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('cover_image', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN cover_image VARCHAR(255) NULL AFTER location');
        }
        if (!in_array('related_links', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN related_links TEXT NULL AFTER cover_image');
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS library_event_participants (
                event_id BIGINT UNSIGNED NOT NULL,
                person_id BIGINT UNSIGNED NOT NULL,
                status ENUM('inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'inscrito',
                notes TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (event_id, person_id),
                CONSTRAINT fk_event_participants_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_participants_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_participants_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $done = true;
    }

    public static function all(): array
    {
        self::ensureSchema();

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

    public static function publicUpcoming(int $limit = 6): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT id, title, description, starts_at, ends_at, location, cover_image, capacity, status
             FROM library_events
             WHERE active = 1
               AND status = 'aberto'
               AND (starts_at IS NULL OR COALESCE(ends_at, starts_at) >= NOW())
             ORDER BY COALESCE(starts_at, created_at) ASC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function publicUpcomingAll(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                "SELECT id, title, description, starts_at, ends_at, location, cover_image, capacity, status, created_at, updated_at
                 FROM library_events
                 WHERE active = 1
                   AND status = 'aberto'
                   AND (starts_at IS NULL OR COALESCE(ends_at, starts_at) >= NOW())
                 ORDER BY COALESCE(starts_at, created_at) ASC"
            )
            ->fetchAll();
    }

    public static function publicPastAll(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                "SELECT id, title, description, starts_at, ends_at, location, cover_image, capacity, status, created_at, updated_at
                 FROM library_events
                 WHERE active = 1
                   AND status <> 'cancelado'
                   AND (
                        status = 'encerrado'
                        OR (starts_at IS NOT NULL AND COALESCE(ends_at, starts_at) < NOW())
                   )
                 ORDER BY COALESCE(starts_at, created_at) DESC"
            )
            ->fetchAll();
    }

    public static function publicAll(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                "SELECT id, title, description, starts_at, ends_at, location, cover_image, capacity, status, created_at, updated_at
                 FROM library_events
                 WHERE active = 1
                   AND status <> 'cancelado'
                 ORDER BY COALESCE(starts_at, created_at) DESC"
            )
            ->fetchAll();
    }

    public static function findPublic(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT library_events.*,
                    responsible.name AS responsible_name
             FROM library_events
             LEFT JOIN users responsible ON responsible.id = library_events.responsible_user_id
             WHERE library_events.id = :id
               AND library_events.active = 1
               AND library_events.status <> 'cancelado'
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT * FROM library_events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function participants(int $eventId): array
    {
        self::ensureSchema();

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
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO library_events
                (title, description, starts_at, ends_at, location, cover_image, related_links, capacity, responsible_user_id, status, notes, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:title, :description, :starts_at, :ends_at, :location, :cover_image, :related_links, :capacity, :responsible_user_id, :status, :notes, 1, :created_by, :updated_by, NOW(), NOW())'
        );
        $stmt->execute(self::payload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::ensureSchema();

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
                 cover_image = :cover_image,
                 related_links = :related_links,
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
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE library_events SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function attachParticipant(int $eventId, int $personId, string $status, ?string $notes, ?int $createdBy): void
    {
        self::ensureSchema();

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
        self::ensureSchema();

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
            'cover_image' => self::nullable($data['cover_image'] ?? null),
            'related_links' => self::nullable($data['related_links'] ?? null),
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
