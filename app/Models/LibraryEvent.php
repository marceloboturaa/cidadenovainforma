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
        Education::ensureSchema();

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
                event_course_id BIGINT UNSIGNED NULL,
                capacity INT UNSIGNED NULL,
                public_enabled TINYINT(1) NOT NULL DEFAULT 1,
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
        if (!in_array('public_enabled', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN public_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER capacity');
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
        if (!in_array('event_course_id', $eventColumns, true)) {
            $db->exec('ALTER TABLE library_events ADD COLUMN event_course_id BIGINT UNSIGNED NULL AFTER related_links');
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS library_event_courses (
                event_id BIGINT UNSIGNED NOT NULL,
                course_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (event_id, course_id),
                CONSTRAINT fk_library_event_courses_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
                CONSTRAINT fk_library_event_courses_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE
            ) ENGINE=InnoDB"
        );
        $db->exec(
            "INSERT IGNORE INTO library_event_courses (event_id, course_id, created_at)
             SELECT id, event_course_id, NOW()
             FROM library_events
             WHERE event_course_id IS NOT NULL"
        );

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

        return self::hydrateEventsWithCourses($stmt->fetchAll());
    }

    public static function publicUpcoming(int $limit = 6): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT library_events.id, library_events.title, library_events.description, library_events.starts_at, library_events.ends_at, library_events.location, library_events.event_cep, library_events.event_address, library_events.cover_image, library_events.event_course_id, education_courses.title AS course_title, education_courses.summary AS course_summary, education_courses.cover_image AS course_cover_image, library_events.capacity, library_events.registration_enabled, library_events.public_show_location, library_events.public_show_address, library_events.public_show_capacity, library_events.public_show_responsible, library_events.status,
                    COALESCE(participant_counts.total, 0) AS participant_count
             FROM library_events
             LEFT JOIN education_courses ON education_courses.id = library_events.event_course_id
             LEFT JOIN (
                SELECT event_id, COUNT(*) AS total
                FROM library_event_participants
                WHERE status <> 'cancelado'
                GROUP BY event_id
             ) participant_counts ON participant_counts.event_id = library_events.id
             WHERE library_events.active = 1
               AND library_events.public_enabled = 1
               AND library_events.status = 'aberto'
               AND (library_events.starts_at IS NULL OR COALESCE(library_events.ends_at, library_events.starts_at) >= NOW())
             ORDER BY COALESCE(library_events.starts_at, library_events.created_at) ASC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return self::hydrateEventsWithCourses($stmt->fetchAll());
    }

    public static function publicUpcomingAll(): array
    {
        self::ensureSchema();

        $events = Database::connection()
            ->query(
                "SELECT library_events.id, library_events.title, library_events.description, library_events.starts_at, library_events.ends_at, library_events.location, library_events.event_cep, library_events.event_address, library_events.cover_image, library_events.event_course_id, education_courses.title AS course_title, education_courses.summary AS course_summary, education_courses.cover_image AS course_cover_image, library_events.capacity, library_events.registration_enabled, library_events.public_show_location, library_events.public_show_address, library_events.public_show_capacity, library_events.public_show_responsible, library_events.status, library_events.created_at, library_events.updated_at,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN education_courses ON education_courses.id = library_events.event_course_id
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    WHERE status <> 'cancelado'
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
                 WHERE library_events.active = 1
                   AND library_events.public_enabled = 1
                   AND library_events.status = 'aberto'
                   AND (library_events.starts_at IS NULL OR COALESCE(library_events.ends_at, library_events.starts_at) >= NOW())
                 ORDER BY COALESCE(library_events.starts_at, library_events.created_at) ASC"
            )
            ->fetchAll();
        return self::hydrateEventsWithCourses($events);
    }

    public static function publicPastAll(): array
    {
        self::ensureSchema();

        $events = Database::connection()
            ->query(
                "SELECT library_events.id, library_events.title, library_events.description, library_events.starts_at, library_events.ends_at, library_events.location, library_events.event_cep, library_events.event_address, library_events.cover_image, library_events.event_course_id, education_courses.title AS course_title, education_courses.summary AS course_summary, education_courses.cover_image AS course_cover_image, library_events.capacity, library_events.registration_enabled, library_events.public_show_location, library_events.public_show_address, library_events.public_show_capacity, library_events.public_show_responsible, library_events.status, library_events.created_at, library_events.updated_at,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN education_courses ON education_courses.id = library_events.event_course_id
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    WHERE status <> 'cancelado'
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
                 WHERE library_events.active = 1
                   AND library_events.public_enabled = 1
                   AND library_events.status <> 'cancelado'
                   AND (
                        library_events.status = 'encerrado'
                        OR (library_events.starts_at IS NOT NULL AND COALESCE(library_events.ends_at, library_events.starts_at) < NOW())
                   )
                 ORDER BY COALESCE(library_events.starts_at, library_events.created_at) DESC"
            )
            ->fetchAll();
        return self::hydrateEventsWithCourses($events);
    }

    public static function publicAll(): array
    {
        self::ensureSchema();

        $events = Database::connection()
            ->query(
                "SELECT library_events.id, library_events.title, library_events.description, library_events.starts_at, library_events.ends_at, library_events.location, library_events.event_cep, library_events.event_address, library_events.cover_image, library_events.event_course_id, education_courses.title AS course_title, education_courses.summary AS course_summary, education_courses.cover_image AS course_cover_image, library_events.capacity, library_events.registration_enabled, library_events.public_show_location, library_events.public_show_address, library_events.public_show_capacity, library_events.public_show_responsible, library_events.status, library_events.created_at, library_events.updated_at,
                        COALESCE(participant_counts.total, 0) AS participant_count
                 FROM library_events
                 LEFT JOIN education_courses ON education_courses.id = library_events.event_course_id
                 LEFT JOIN (
                    SELECT event_id, COUNT(*) AS total
                    FROM library_event_participants
                    WHERE status <> 'cancelado'
                    GROUP BY event_id
                 ) participant_counts ON participant_counts.event_id = library_events.id
                 WHERE library_events.active = 1
                   AND library_events.public_enabled = 1
                   AND library_events.status <> 'cancelado'
                 ORDER BY COALESCE(library_events.starts_at, library_events.created_at) DESC"
            )
            ->fetchAll();
        return self::hydrateEventsWithCourses($events);
    }

    public static function findPublic(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            "SELECT library_events.*,
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
                WHERE status <> 'cancelado'
                GROUP BY event_id
             ) participant_counts ON participant_counts.event_id = library_events.id
             WHERE library_events.id = :id
               AND library_events.active = 1
               AND library_events.public_enabled = 1
               AND library_events.status <> 'cancelado'
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);

        $event = $stmt->fetch() ?: null;
        return $event ? self::hydrateEventWithCourses($event) : null;
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT * FROM library_events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $event = $stmt->fetch() ?: null;
        return $event ? self::hydrateEventWithCourses($event) : null;
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

    public static function updateParticipantStatuses(int $eventId, array $personIds, string $status): int
    {
        self::ensureSchema();

        $personIds = array_values(array_unique(array_filter(array_map('intval', $personIds))));
        if (!$personIds) {
            return 0;
        }

        $status = in_array($status, ['pendente', 'inscrito', 'presente', 'ausente', 'cancelado'], true) ? $status : 'inscrito';
        $placeholders = implode(',', array_fill(0, count($personIds), '?'));
        $stmt = Database::connection()->prepare(
            "UPDATE library_event_participants
             SET status = ?
             WHERE event_id = ?
               AND person_id IN ({$placeholders})"
        );
        $stmt->execute(array_merge([$status, $eventId], $personIds));

        return $stmt->rowCount();
    }

    public static function updatePendingParticipantStatuses(int $eventId, string $status): int
    {
        self::ensureSchema();

        $status = in_array($status, ['inscrito', 'presente', 'ausente', 'cancelado'], true) ? $status : 'inscrito';
        $stmt = Database::connection()->prepare(
            "UPDATE library_event_participants
             SET status = :status
             WHERE event_id = :event_id
               AND status = 'pendente'"
        );
        $stmt->execute([
            'event_id' => $eventId,
            'status' => $status,
        ]);

        return $stmt->rowCount();
    }

    public static function confirmPendingParticipantsByEmail(string $email): int
    {
        self::ensureSchema();

        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            "UPDATE library_event_participants
             INNER JOIN people ON people.id = library_event_participants.person_id
             SET library_event_participants.status = 'inscrito',
                 library_event_participants.notes = CONCAT(
                    COALESCE(NULLIF(library_event_participants.notes, ''), ''),
                    CASE
                        WHEN COALESCE(NULLIF(library_event_participants.notes, ''), '') = '' THEN ''
                        ELSE '\n'
                    END,
                    'Login negado no painel; inscricao mantida no evento.'
                 )
             WHERE LOWER(people.email) = :email
               AND library_event_participants.status = 'pendente'"
        );
        $stmt->execute(['email' => $email]);

        return $stmt->rowCount();
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO library_events
                (title, description, starts_at, ends_at, location, event_cep, event_address, cover_image, related_links, event_course_id, capacity, public_enabled, registration_enabled, public_show_location, public_show_address, public_show_capacity, public_show_responsible, responsible_user_id, status, notes, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:title, :description, :starts_at, :ends_at, :location, :event_cep, :event_address, :cover_image, :related_links, :event_course_id, :capacity, :public_enabled, :registration_enabled, :public_show_location, :public_show_address, :public_show_capacity, :public_show_responsible, :responsible_user_id, :status, :notes, 1, :created_by, :updated_by, NOW(), NOW())'
        );
        $stmt->execute(self::payload($data));

        $id = (int) Database::connection()->lastInsertId();
        self::syncCourses($id, self::courseIdsFromData($data));

        return $id;
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
                 event_course_id = :event_course_id,
                 capacity = :capacity,
                 public_enabled = :public_enabled,
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
        self::syncCourses($id, self::courseIdsFromData($data));
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

    public static function detachAllParticipants(int $eventId): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'DELETE FROM library_event_participants WHERE event_id = :event_id'
        );
        $stmt->execute(['event_id' => $eventId]);

        return $stmt->rowCount();
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
            'event_course_id' => self::primaryCourseId($data),
            'capacity' => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'public_enabled' => array_key_exists('public_enabled', $data) ? (int) !empty($data['public_enabled']) : 1,
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

    private static function primaryCourseId(array $data): ?int
    {
        $ids = self::courseIdsFromData($data);
        return $ids[0] ?? null;
    }

    private static function courseIdsFromData(array $data): array
    {
        $raw = $data['event_course_ids'] ?? $data['event_course_id'] ?? [];
        $values = is_array($raw) ? $raw : [$raw];

        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }

    private static function syncCourses(int $eventId, array $courseIds): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM library_event_courses WHERE event_id = :event_id')->execute(['event_id' => $eventId]);

        if (!$courseIds) {
            return;
        }

        $stmt = $db->prepare(
            'INSERT IGNORE INTO library_event_courses (event_id, course_id, created_at)
             VALUES (:event_id, :course_id, NOW())'
        );
        foreach ($courseIds as $courseId) {
            $stmt->execute([
                'event_id' => $eventId,
                'course_id' => $courseId,
            ]);
        }
    }

    private static function hydrateEventsWithCourses(array $events): array
    {
        if (!$events) {
            return [];
        }

        $coursesByEvent = self::coursesForEvents(array_column($events, 'id'));
        foreach ($events as &$event) {
            $event = self::applyCourseData($event, $coursesByEvent[(int) $event['id']] ?? []);
        }
        unset($event);

        return $events;
    }

    private static function hydrateEventWithCourses(array $event): array
    {
        $coursesByEvent = self::coursesForEvents([(int) $event['id']]);
        return self::applyCourseData($event, $coursesByEvent[(int) $event['id']] ?? []);
    }

    private static function coursesForEvents(array $eventIds): array
    {
        $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
        if (!$eventIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT library_event_courses.event_id,
                    education_courses.id,
                    education_courses.title,
                    education_courses.summary,
                    education_courses.cover_image
             FROM library_event_courses
             INNER JOIN education_courses ON education_courses.id = library_event_courses.course_id
             WHERE library_event_courses.event_id IN ({$placeholders})
               AND education_courses.active = 1
             ORDER BY education_courses.title ASC"
        );
        $stmt->execute($eventIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $course) {
            $grouped[(int) $course['event_id']][] = $course;
        }

        return $grouped;
    }

    private static function applyCourseData(array $event, array $courses): array
    {
        if (!$courses && !empty($event['event_course_id']) && !empty($event['course_title'])) {
            $courses[] = [
                'id' => (int) $event['event_course_id'],
                'title' => $event['course_title'],
                'summary' => $event['course_summary'] ?? null,
                'cover_image' => $event['course_cover_image'] ?? null,
            ];
        }

        $event['linked_courses'] = $courses;
        $event['event_course_ids'] = array_map(fn (array $course): int => (int) $course['id'], $courses);
        $event['course_title'] = implode(', ', array_map(fn (array $course): string => (string) $course['title'], $courses));

        if ($courses) {
            $event['event_course_id'] = (int) $courses[0]['id'];
            $event['course_summary'] = $courses[0]['summary'] ?? null;
            $event['course_cover_image'] = $courses[0]['cover_image'] ?? null;
        }

        return $event;
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
