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
                image_authorized TINYINT(1) NOT NULL DEFAULT 0,
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
                event_cep VARCHAR(12) NULL,
                event_address VARCHAR(255) NULL,
                cover_image VARCHAR(255) NULL,
                related_links TEXT NULL,
                capacity INT UNSIGNED NULL,
                registration_enabled TINYINT(1) NOT NULL DEFAULT 0,
                public_show_location TINYINT(1) NOT NULL DEFAULT 1,
                public_show_address TINYINT(1) NOT NULL DEFAULT 1,
                public_show_capacity TINYINT(1) NOT NULL DEFAULT 1,
                public_show_responsible TINYINT(1) NOT NULL DEFAULT 1,
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
        if (!in_array('event_cep', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN event_cep VARCHAR(12) NULL AFTER location');
        }
        if (!in_array('event_address', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN event_address VARCHAR(255) NULL AFTER event_cep');
        }
        if (!in_array('registration_enabled', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN registration_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER capacity');
        }
        foreach ([
            'public_show_location' => 'ALTER TABLE library_events ADD COLUMN public_show_location TINYINT(1) NOT NULL DEFAULT 1 AFTER registration_enabled',
            'public_show_address' => 'ALTER TABLE library_events ADD COLUMN public_show_address TINYINT(1) NOT NULL DEFAULT 1 AFTER public_show_location',
            'public_show_capacity' => 'ALTER TABLE library_events ADD COLUMN public_show_capacity TINYINT(1) NOT NULL DEFAULT 1 AFTER public_show_address',
            'public_show_responsible' => 'ALTER TABLE library_events ADD COLUMN public_show_responsible TINYINT(1) NOT NULL DEFAULT 1 AFTER public_show_capacity',
        ] as $column => $sql) {
            if (!in_array($column, $eventColumns, true)) {
                $db->exec($sql);
            }
        }
        if (!in_array('related_links', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN related_links TEXT NULL AFTER cover_image');
        }

        $personColumns = $db->query('SHOW COLUMNS FROM people')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('image_authorized', $personColumns, true)) {
            $db->exec('ALTER TABLE people ADD COLUMN image_authorized TINYINT(1) NOT NULL DEFAULT 0 AFTER contact_authorized');
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS library_event_participants (
                event_id BIGINT UNSIGNED NOT NULL,
                person_id BIGINT UNSIGNED NOT NULL,
                status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente',
                notes TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (event_id, person_id),
                CONSTRAINT fk_event_participants_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_participants_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
                CONSTRAINT fk_event_participants_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );

        $participantStatus = $db->query("SHOW COLUMNS FROM library_event_participants LIKE 'status'")->fetch();
        if ($participantStatus && !str_contains((string) ($participantStatus['Type'] ?? ''), "'pendente'")) {
            $db->exec("ALTER TABLE library_event_participants MODIFY status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente'");
        }

        $done = true;
    }

    public static function all(?int $createdBy = null): array
    {
        self::ensureSchema();

        $sql = "SELECT library_events.*,
                       responsible.name AS responsible_name,
                       COALESCE(participant_counts.total, 0) AS participant_count
                FROM library_events
                LEFT JOIN users responsible ON responsible.id = library_events.responsible_user_id
                LEFT JOIN (
                   SELECT event_id, COUNT(*) AS total
                   FROM library_event_participants
                   WHERE status <> 'cancelado'
                   GROUP BY event_id
                ) participant_counts ON participant_counts.event_id = library_events.id
                WHERE library_events.active = 1";
        $params = [];

        if ($createdBy !== null) {
            $sql .= ' AND (library_events.created_by = :created_by OR library_events.responsible_user_id = :created_by)';
            $params['created_by'] = $createdBy;
        }

        $sql .= ' ORDER BY COALESCE(library_events.starts_at, library_events.created_at) DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function publicUpcoming(int $limit = 6): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT library_events.id, title, description, starts_at, ends_at, location, event_cep, event_address, cover_image, capacity, registration_enabled, public_show_location, public_show_address, public_show_capacity, public_show_responsible, status,
                    COALESCE(participant_counts.total, 0) AS participant_count
             FROM library_events
             LEFT JOIN (
                SELECT event_id, COUNT(*) AS total
                FROM library_event_participants
                WHERE status <> 'cancelado'
                GROUP BY event_id
             ) participant_counts ON participant_counts.event_id = library_events.id
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
                "SELECT library_events.id, title, description, starts_at, ends_at, location, event_cep, event_address, cover_image, capacity, registration_enabled, public_show_location, public_show_address, public_show_capacity, public_show_responsible, status, created_at, updated_at,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    WHERE status <> 'cancelado'
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
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
                "SELECT library_events.id, title, description, starts_at, ends_at, location, event_cep, event_address, cover_image, capacity, registration_enabled, public_show_location, public_show_address, public_show_capacity, public_show_responsible, status, created_at, updated_at,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    WHERE status <> 'cancelado'
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
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
                "SELECT library_events.id, title, description, starts_at, ends_at, location, event_cep, event_address, cover_image, capacity, registration_enabled, public_show_location, public_show_address, public_show_capacity, public_show_responsible, status, created_at, updated_at,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    WHERE status <> 'cancelado'
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
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
                    responsible.name AS responsible_name,
                    COALESCE(participant_counts.total, 0) AS participant_count
             FROM library_events
             LEFT JOIN users responsible ON responsible.id = library_events.responsible_user_id
             LEFT JOIN (
                SELECT event_id, COUNT(*) AS total
                FROM library_event_participants
                WHERE status <> 'cancelado'
                GROUP BY event_id
             ) participant_counts ON participant_counts.event_id = library_events.id
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
                    people.cpf,
                    people.birth_date,
                    people.email,
                    people.cep,
                    people.address,
                    people.address_number,
                    people.address_complement,
                    people.city,
                    people.state,
                    people.phone,
                    people.whatsapp,
                    people.district,
                    people.is_minor,
                    people.guardian_name,
                    people.guardian_relation,
                    people.guardian_cpf,
                    people.guardian_phone,
                    people.guardian_email,
                    people.contact_authorized,
                    people.image_authorized
             FROM library_event_participants
             INNER JOIN people ON people.id = library_event_participants.person_id
             WHERE library_event_participants.event_id = :event_id
             ORDER BY people.full_name ASC'
        );
        $stmt->execute(['event_id' => $eventId]);

        return $stmt->fetchAll();
    }

    public static function participantStats(int $eventId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT status, COUNT(*) AS total
             FROM library_event_participants
             WHERE event_id = :event_id
             GROUP BY status"
        );
        $stmt->execute(['event_id' => $eventId]);

        $stats = ['pendente' => 0, 'inscrito' => 0, 'presente' => 0, 'ausente' => 0, 'cancelado' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $stats[(string) $row['status']] = (int) $row['total'];
        }

        return $stats;
    }

    public static function activeParticipantCount(int $eventId): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS total
             FROM library_event_participants
             WHERE event_id = :event_id
               AND status <> 'cancelado'"
        );
        $stmt->execute(['event_id' => $eventId]);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public static function participant(int $eventId, int $personId): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT * FROM library_event_participants WHERE event_id = :event_id AND person_id = :person_id LIMIT 1'
        );
        $stmt->execute(['event_id' => $eventId, 'person_id' => $personId]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO library_events
                (title, description, starts_at, ends_at, location, event_cep, event_address, cover_image, related_links, capacity, registration_enabled, public_show_location, public_show_address, public_show_capacity, public_show_responsible, responsible_user_id, status, notes, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:title, :description, :starts_at, :ends_at, :location, :event_cep, :event_address, :cover_image, :related_links, :capacity, :registration_enabled, :public_show_location, :public_show_address, :public_show_capacity, :public_show_responsible, :responsible_user_id, :status, :notes, 1, :created_by, :updated_by, NOW(), NOW())'
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
                 event_cep = :event_cep,
                 event_address = :event_address,
                 cover_image = :cover_image,
                 related_links = :related_links,
                 capacity = :capacity,
                 registration_enabled = :registration_enabled,
                 public_show_location = :public_show_location,
                 public_show_address = :public_show_address,
                 public_show_capacity = :public_show_capacity,
                 public_show_responsible = :public_show_responsible,
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
            'status' => in_array($status, ['pendente', 'inscrito', 'presente', 'ausente', 'cancelado'], true) ? $status : 'pendente',
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
            'event_cep' => self::nullable($data['event_cep'] ?? null),
            'event_address' => self::nullable($data['event_address'] ?? null),
            'cover_image' => self::nullable($data['cover_image'] ?? null),
            'related_links' => self::nullable($data['related_links'] ?? null),
            'capacity' => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'registration_enabled' => (int) !empty($data['registration_enabled']),
            'public_show_location' => (int) !empty($data['public_show_location']),
            'public_show_address' => (int) !empty($data['public_show_address']),
            'public_show_capacity' => (int) !empty($data['public_show_capacity']),
            'public_show_responsible' => (int) !empty($data['public_show_responsible']),
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
