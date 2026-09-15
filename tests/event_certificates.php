<?php
namespace App\Core {
    class Database { public static $db; public static function connection() { return self::$db; } }
    class EventTestDatabase {
        public function __construct(public \PDO $pdo) {}
        public function exec($sql) { return 0; }
        public function query($sql) { return $this->pdo->query(str_starts_with($sql, 'SHOW ') ? 'SELECT 1' : $sql); }
        public function prepare($sql) { return $this->pdo->prepare(str_replace([' FOR UPDATE', 'ON DUPLICATE KEY UPDATE id = id'], ['', 'ON CONFLICT(event_id, person_id, certificate_type) DO NOTHING'], $sql)); }
        public function beginTransaction() { return $this->pdo->beginTransaction(); }
        public function commit() { return $this->pdo->commit(); }
        public function rollBack() { return $this->pdo->rollBack(); }
        public function inTransaction() { return $this->pdo->inTransaction(); }
    }
}
namespace App\Models { class LibraryEvent { public static function ensureSchema(): void {} } }
namespace {
require dirname(__DIR__) . '/app/Models/EventCertificate.php';
use App\Models\EventCertificate;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->sqliteCreateFunction('NOW', fn () => '2026-09-14 12:00:00');
\App\Core\Database::$db = new \App\Core\EventTestDatabase($pdo);
$pdo->exec('CREATE TABLE library_events (id INTEGER, title TEXT, starts_at TEXT, ends_at TEXT, status TEXT);
CREATE TABLE people (id INTEGER, full_name TEXT, email TEXT);
CREATE TABLE users (id INTEGER, email TEXT);
CREATE TABLE library_event_participants (event_id INTEGER, person_id INTEGER, status TEXT, is_coordinator INTEGER);
CREATE TABLE library_event_certificates (id INTEGER PRIMARY KEY AUTOINCREMENT, event_id INTEGER, person_id INTEGER, user_id INTEGER, certificate_type TEXT, student_name TEXT, event_title TEXT, event_starts_at TEXT, event_ends_at TEXT, verification_code TEXT UNIQUE, issued_by INTEGER, issued_at TEXT, UNIQUE(event_id, person_id, certificate_type));
INSERT INTO library_events VALUES (1, "Evento", "2026-09-01", "2026-09-02", "encerrado");
INSERT INTO people VALUES (1, "Ana", "ana@example.test");
INSERT INTO users VALUES (10, "ana@example.test");
INSERT INTO library_event_participants VALUES (1, 1, "inscrito", 0);');
try { EventCertificate::issue(1, 1, ['participacao', 'coordenacao'], 99); throw new RuntimeException('Coordination without assignment'); } catch (InvalidArgumentException $e) {}
check(count(EventCertificate::forEvent(1)) === 0, 'Invalid request must not partially issue');
EventCertificate::setCoordinator(1, 1, true);
EventCertificate::issue(1, 1, ['participacao', 'coordenacao'], 99);
$rows = EventCertificate::forEvent(1);
check(count($rows) === 2 && $rows[0]['verification_code'] !== $rows[1]['verification_code'], 'Two independent certificates');
EventCertificate::issue(1, 1, ['participacao', 'coordenacao'], 99);
check(count(EventCertificate::forEvent(1)) === 2, 'Repeated issuance does not duplicate');
check(count(EventCertificate::forUser(10)) === 2 && count(EventCertificate::forUser(11)) === 0, 'Owner list scoped');
check(EventCertificate::verify($rows[0]['verification_code'])['student_name'] === 'Ana', 'Public verification');
check(EventCertificate::verify('EVTUNKNOWN') === null, 'Unknown verification');
$pdo->exec('UPDATE library_event_participants SET status = "pendente"');
try { EventCertificate::issue(1, 1, ['participacao'], 99); throw new RuntimeException('Pending enrollment accepted'); } catch (InvalidArgumentException $e) {}
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function url($value) { return $value; }
$isOwner = true;
foreach ($rows as $certificate) {
    ob_start(); require dirname(__DIR__) . '/app/Views/admin/library-events/certificate.php'; $html = ob_get_clean();
    check(str_contains($html, $certificate['certificate_title']) && str_contains($html, $certificate['verification_code']), 'Rendered type and independent code');
}
echo "Eventos: dois certificados, função, matrícula, duplicação, titular e validação aprovados.\n";
}
