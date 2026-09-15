<?php
namespace App\Core {
    class Database { public static $db; public static function connection() { return self::$db; } }
    class Mailer {
        public static array $messages = [];
        public static bool $success = true;
        public static function send(...$args): bool { self::$messages[] = $args; return self::$success; }
    }
    class NotificationTestDatabase {
        public function __construct(public \PDO $pdo) {}
        public function exec($sql) { return 0; }
        private function sql($sql) { return str_replace(['INSERT IGNORE', ' FOR UPDATE', 'DATE_SUB(NOW(), INTERVAL 15 MINUTE)'], ['INSERT OR IGNORE', '', "'2026-09-15 11:45:00'"], $sql); }
        public function prepare($sql) { return $this->pdo->prepare($this->sql($sql)); }
        public function query($sql) { return $this->pdo->query($this->sql($sql)); }
        public function beginTransaction() { return $this->pdo->beginTransaction(); }
        public function inTransaction() { return $this->pdo->inTransaction(); }
        public function commit() { return $this->pdo->commit(); }
        public function rollBack() { return $this->pdo->rollBack(); }
        public function lastInsertId() { return $this->pdo->lastInsertId(); }
    }
}
namespace App\Models { class Education { public static function ensureSchema(): void {} } }
namespace {
    require dirname(__DIR__) . '/app/Models/Announcement.php';
    require dirname(__DIR__) . '/app/Models/CertificateNotification.php';
    function e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    function url($path): string { return 'https://example.test' . $path; }
    function check($ok, $message): void { if (!$ok) throw new RuntimeException($message); }
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->sqliteCreateFunction('NOW', fn () => '2026-09-15 12:00:00');
    \App\Core\Database::$db = new \App\Core\NotificationTestDatabase($pdo);
    $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, active INTEGER);
        CREATE TABLE education_courses (id INTEGER PRIMARY KEY, title TEXT);
        CREATE TABLE education_certificates (id INTEGER PRIMARY KEY, course_id INTEGER, user_id INTEGER, status TEXT);
        CREATE TABLE certificate_notifications (certificate_id INTEGER PRIMARY KEY, announcement_id INTEGER, email_sent_at TEXT, email_attempted_at TEXT, email_attempts INTEGER DEFAULT 0, last_error TEXT, created_at TEXT);
        CREATE TABLE announcements (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, body TEXT, url TEXT, button_label TEXT, active INTEGER, created_by INTEGER, created_at TEXT, updated_at TEXT);
        CREATE TABLE announcement_recipients (announcement_id INTEGER, user_id INTEGER, created_at TEXT);
        CREATE TABLE announcement_reads (announcement_id INTEGER, user_id INTEGER, read_at TEXT);
        INSERT INTO users VALUES (1, "Ana <Teste>", "ana@example.test", 1), (2, "Outro", "outro@example.test", 1);
        INSERT INTO education_courses VALUES (1, "Curso <Teste>");
        INSERT INTO education_certificates VALUES (1, 1, 1, "pending"), (2, 1, 1, "issued"), (3, 1, 1, "revoked"), (4, 1, NULL, "issued");');
    use App\Models\CertificateNotification as Notice;
    use App\Models\Announcement;
    use App\Core\Mailer;
    check(!Notice::notify(1) && !Notice::notify(3) && !Notice::notify(4), 'Only issued certificates with an account are notified');
    check(count(Mailer::$messages) === 0, 'No premature emails');
    check(Notice::notify(2), 'Issued certificate notifies');
    check(Notice::notify(2), 'Repeated notification is safe');
    check(count(Mailer::$messages) === 1, 'No duplicate email');
    check($pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn() == 1, 'No duplicate announcement');
    check(count(Announcement::unreadForUser(1)) === 1, 'Owner sees notice');
    check(Announcement::unreadForUser(2) === [], 'Other users never see notice');
    check(str_contains(Mailer::$messages[0][2], '&lt;Teste&gt;'), 'Email escapes user data');
    check(str_contains(Mailer::$messages[0][3], 'certificate_id=2'), 'Email links to certificate');
    $pdo->exec('INSERT INTO education_certificates VALUES (5, 1, 1, "issued")');
    Mailer::$success = false;
    check(!Notice::notify(5), 'Failed email is recorded');
    check($pdo->query('SELECT email_sent_at FROM certificate_notifications WHERE certificate_id = 5')->fetchColumn() === null, 'Failure is not marked sent');
    Notice::notify(5);
    check(count(Mailer::$messages) === 2, 'Cooldown prevents immediate resend');
    $pdo->exec('UPDATE certificate_notifications SET email_attempted_at = "2026-09-15 11:00:00" WHERE certificate_id = 5');
    Mailer::$success = true;
    $result = Notice::processPending();
    check($result === ['processed' => 1, 'failed' => 0], 'Worker retries pending email');
    check($pdo->query('SELECT COUNT(*) FROM announcements')->fetchColumn() == 2, 'Retry preserves single notice');
    check(count(Mailer::$messages) === 3, 'Worker sends one retry');
    $pdo->exec('UPDATE education_certificates SET status = "revoked" WHERE id = 2');
    check(!Notice::notify(2), 'Revocation must not send');
    check(count(Announcement::unreadForUser(1)) === 1, 'Revoked certificate notice is hidden');
    $pdo->exec('UPDATE education_certificates SET status = "issued" WHERE id = 2');
    check(Notice::notify(2), 'Reissue restores notice');
    check(count(Announcement::unreadForUser(1)) === 2, 'Restored notice is visible');
    check(count(Mailer::$messages) === 3, 'Reissue does not repeat a sent email');
    echo "Certificate notices: eligibility, privacy, deduplication and email retry passed.\n";
}
