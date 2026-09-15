<?php

namespace App\Models;

use App\Core\Database;

class EventCertificate
{
    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) return;
        LibraryEvent::ensureSchema();
        $db = Database::connection();
        if (!$db->query("SHOW COLUMNS FROM library_event_participants LIKE 'is_coordinator'")->fetch()) {
            $db->exec('ALTER TABLE library_event_participants ADD COLUMN is_coordinator TINYINT(1) NOT NULL DEFAULT 0');
        }
        $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS library_event_certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    certificate_type ENUM('participacao','coordenacao') NOT NULL,
    student_name VARCHAR(180) NOT NULL,
    event_title VARCHAR(180) NOT NULL,
    event_starts_at DATETIME NULL,
    event_ends_at DATETIME NULL,
    verification_code VARCHAR(48) NOT NULL,
    issued_by BIGINT UNSIGNED NULL,
    issued_at DATETIME NOT NULL,
    UNIQUE KEY uq_event_certificate_recipient_type (event_id, person_id, certificate_type),
    UNIQUE KEY uq_event_certificate_code (verification_code),
    INDEX idx_event_certificate_user (user_id),
    CONSTRAINT fk_event_certificate_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_certificate_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_certificate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_certificate_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
SQL
        );
        $done = true;
    }

    public static function types(): array
    {
        return ['participacao' => 'Certificado de participação', 'coordenacao' => 'Certificado de coordenação'];
    }

    public static function setCoordinator(int $eventId, int $personId, bool $enabled): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('UPDATE library_event_participants SET is_coordinator = :enabled WHERE event_id = :event AND person_id = :person');
        $stmt->execute(['enabled' => (int) $enabled, 'event' => $eventId, 'person' => $personId]);
    }

    public static function issue(int $eventId, int $personId, array $types, int $issuerId): void
    {
        self::ensureSchema();
        $types = array_values(array_unique($types));
        if (!$types || array_diff($types, array_keys(self::types()))) {
            throw new \InvalidArgumentException('Selecione participação, coordenação ou ambos.');
        }
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT p.*, people.full_name, people.email, e.title, e.starts_at, e.ends_at, e.status AS event_status
                FROM library_event_participants p INNER JOIN people ON people.id = p.person_id
                INNER JOIN library_events e ON e.id = p.event_id
                WHERE p.event_id = :event AND p.person_id = :person FOR UPDATE');
            $stmt->execute(['event' => $eventId, 'person' => $personId]);
            $participant = $stmt->fetch();
            if (!$participant || !in_array($participant['status'], ['inscrito', 'presente'], true) || $participant['event_status'] === 'cancelado') {
                throw new \InvalidArgumentException('A emissão exige inscrição aprovada e evento não cancelado.');
            }
            if (in_array('coordenacao', $types, true) && !(int) $participant['is_coordinator']) {
                throw new \InvalidArgumentException('Atribua a coordenação ao inscrito antes de emitir este certificado.');
            }
            // Match the existing event registration/account relationship. Ambiguous emails stay person-only.
            $users = $db->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 2');
            $users->execute(['email' => $participant['email'] ?? '']);
            $ids = $users->fetchAll(\PDO::FETCH_COLUMN);
            $userId = count($ids) === 1 ? (int) $ids[0] : null;
            foreach ($types as $type) {
                $insert = $db->prepare('INSERT INTO library_event_certificates
                    (event_id, person_id, user_id, certificate_type, student_name, event_title, event_starts_at, event_ends_at, verification_code, issued_by, issued_at)
                    VALUES (:event, :person, :user, :type, :name, :title, :start, :end, :code, :issuer, NOW())
                    ON DUPLICATE KEY UPDATE id = id');
                $insert->execute(['event' => $eventId, 'person' => $personId, 'user' => $userId, 'type' => $type,
                    'name' => $participant['full_name'], 'title' => $participant['title'],
                    'start' => $participant['starts_at'], 'end' => $participant['ends_at'],
                    'code' => 'EVT' . strtoupper(bin2hex(random_bytes(16))), 'issuer' => $issuerId]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private static function decorate(array $row): array
    {
        return array_merge($row, ['status' => 'issued', 'course_title' => $row['event_title'],
            'certificate_title' => self::types()[$row['certificate_type']],
            'certificate_activity_type' => 'evento', 'teacher_name' => null]);
    }

    public static function forEvent(int $eventId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM library_event_certificates WHERE event_id = :event ORDER BY student_name, certificate_type');
        $stmt->execute(['event' => $eventId]);
        return array_map([self::class, 'decorate'], $stmt->fetchAll());
    }

    public static function forUser(int $userId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM library_event_certificates WHERE user_id = :user ORDER BY issued_at DESC, id DESC');
        $stmt->execute(['user' => $userId]);
        return array_map([self::class, 'decorate'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM library_event_certificates WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::decorate($row) : null;
    }

    public static function verify(string $code): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM library_event_certificates WHERE verification_code = :code');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();
        return $row ? self::decorate($row) : null;
    }
}
