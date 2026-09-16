<?php
// Run in isolation: php tests/certificate_report.php
namespace App\Core {
    // Exercise report SQL on isolated fixtures. Ignore the model's MySQL schema maintenance.
    class Database {
        public static $db;
        public static function connection() { return self::$db; }
    }
    class ReportDatabase {
        public function __construct(public \PDO $pdo) {}
        public function exec($sql) { return 0; }
        public function quote($value) { return $this->pdo->quote($value); }
        public function query($sql) {
            if (str_starts_with($sql, 'SHOW ')) return $this->pdo->query('SELECT 1');
            return $this->pdo->query($sql);
        }
        public function prepare($sql) { return $this->pdo->prepare($sql); }
    }
}
namespace {
    require dirname(__DIR__) . '/app/Models/Education.php';
    require dirname(__DIR__) . '/app/Models/Announcement.php';
    require dirname(__DIR__) . '/app/Models/CertificateNotification.php';
    use App\Models\Education;
    if (in_array('--mysql', $argv ?? [], true)) {
        $config = require dirname(__DIR__) . '/config/database.php';
        $pdo = new PDO('mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';charset=utf8mb4', $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $testDatabase = 'cni_report_test_' . bin2hex(random_bytes(6));
        $pdo->exec('CREATE DATABASE `' . $testDatabase . '`');
        register_shutdown_function(static function () use ($pdo, $testDatabase) { $pdo->exec('DROP DATABASE `' . $testDatabase . '`'); });
        $pdo->exec('USE `' . $testDatabase . '`');
    } else {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->sqliteCreateFunction('CONCAT', fn (...$args) => implode('', $args));
    }
    \App\Core\Database::$db = new \App\Core\ReportDatabase($pdo);
    $pdo->exec('CREATE TABLE education_courses (id INTEGER, title TEXT, teacher_user_id INTEGER, certificate_activity_type TEXT);
        CREATE TABLE users (id INTEGER, name TEXT);
        CREATE TABLE people (id INTEGER, full_name TEXT);
        CREATE TABLE education_certificates (id INTEGER, course_id INTEGER, user_id INTEGER, person_id INTEGER, student_name TEXT, status TEXT, verification_code TEXT, issued_at TEXT, authorized_at TEXT);
        INSERT INTO users VALUES (10, "Professor A"), (20, "Professor B"), (30, "Aluno A");
        INSERT INTO people VALUES (40, "Pessoa B");
        INSERT INTO education_courses VALUES (1, "Curso A", 10, "curso_livre"), (2, "Curso B", 20, "curso_livre"), (3, "Homenagem", 10, "reconhecimento");');
    $insert = $pdo->prepare('INSERT INTO education_certificates VALUES (?, ?, ?, ?, ?, ?, ?, "2026-09-14 12:00:00", NULL)');
    for ($id = 1; $id <= 26; $id++) $insert->execute([$id, 1, 30, null, '', 'issued', 'A-' . $id]);
    $insert->execute([27, 2, null, 40, '', 'issued', 'B-27']);
    foreach (['pending', 'revoked', 'deleted', 'draft'] as $i => $status) $insert->execute([28 + $i, 1, 30, null, '', $status, 'HIDDEN-' . $i]);
    $insert->execute([32, 3, 30, null, '', 'issued', 'RECOGNITION']);
    $pdo->exec('ALTER TABLE education_courses ADD COLUMN active INTEGER DEFAULT 1; ALTER TABLE education_courses ADD COLUMN certificate_enabled INTEGER DEFAULT 1; ALTER TABLE education_courses ADD COLUMN certificate_auto_release INTEGER DEFAULT 0');
    function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
    $pdo->exec('ALTER TABLE users ADD email TEXT; ALTER TABLE users ADD active INTEGER DEFAULT 1;
        CREATE TABLE certificate_notifications (certificate_id INTEGER PRIMARY KEY, email_sent_at TEXT, email_attempted_at TEXT, email_attempts INTEGER, last_error TEXT);
        INSERT INTO certificate_notifications VALUES (1,"2026-09-15 12:00:00","2026-09-15 12:00:00",1,NULL), (2,NULL,"2026-09-15 12:00:00",2,"Falha de envio");');
    $all = Education::certificateReport(null, '', 0, 1);
    foreach (['all', 'issued', 'pending', 'revoked'] as $certificateState) {
        foreach (['', 'sent', 'pending', 'failed', 'unavailable', 'not_released'] as $mailState) {
            $combination = Education::certificateReport(null, '', 0, 1, $certificateState, $mailState);
            check(is_array($combination['rows']), 'Every filter combination must execute');
        }
    }
    check((int) Education::certificateReport(null, '', 0, 1, 'issued', 'not_released')['totals']['certificates'] === 0, 'Issued plus not released returns empty results');
    check((int) Education::certificateReport(null, '', 0, 1, 'all', 'not_released')['totals']['certificates'] === 2, 'All statuses finds pending and revoked without deleted or drafts');
    check((int) $all['totals']['certificates'] === 27 && (int) $all['totals']['courses'] === 2 && (int) $all['totals']['recipients'] === 2, 'Global totals and status exclusions');
    $own = Education::certificateReport(10, '', 0, 1);
    check((int) $all['totals']['email_sent'] === 1 && (int) $all['totals']['email_failed'] === 1 && (int) $all['totals']['email_pending'] === 24 && (int) $all['totals']['email_unavailable'] === 1, 'Email totals cover all pages and distinguish unavailable recipients');
    $sent = Education::certificateReport(10, '', 0, 1, 'issued', 'sent');
    check(count($sent['rows']) === 1 && $sent['rows'][0]['email_sent_at'] === '2026-09-15 12:00:00', 'Sent filter and timestamp');
    check((int) Education::certificateReport(20, '', 1, 1, 'issued', 'sent')['totals']['certificates'] === 0, 'Email filter cannot bypass teacher scope');
    check((int) Education::certificateReport(10, '', 0, 1, 'pending')['totals']['email_not_released'] === 1, 'Pending certificates are not mistaken for pending emails');
    $hub = Education::certificateHub(10);
    check(count($hub) === 1 && (int) $hub[0]['issued'] === 26 && (int) $hub[0]['pending'] === 1, 'Central scoped course counts');
    check(count(Education::certificateHub(99)) === 0, 'Central hides unrelated courses');
    check((int) Education::certificateReport(10, '', 0, 1, 'pending')['totals']['certificates'] === 1, 'Teacher pending requests');
    check((int) Education::certificateReport(20, '', 1, 1, 'pending')['totals']['certificates'] === 0, 'Foreign pending requests hidden');
    check((int) $own['totals']['certificates'] === 26 && count($own['rows']) === 25 && count($own['courses']) === 1, 'Teacher scope includes totals, rows and options');
    $last = Education::certificateReport(10, '', 0, 999);
    check($last['page'] === 2 && count($last['rows']) === 1, 'Pagination bounds');
    check((int) Education::certificateReport(10, '', 2, 1)['totals']['certificates'] === 0, 'Foreign course filter must not escape teacher scope');
    check((int) Education::certificateReport(99, '', 0, 1)['totals']['certificates'] === 0, 'Unassigned teacher sees nothing');
    check((int) Education::certificateReport(null, 'Pessoa B', 0, 1)['totals']['certificates'] === 1, 'Person recipient search');
    check((int) Education::certificateReport(null, 'B-27', 0, 1)['totals']['certificates'] === 1, 'Code search');
    check((int) Education::certificateReport(null, "' OR 1=1 --", 0, 1)['totals']['certificates'] === 0, 'Search is parameterized');
    function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    function url($value) { return $value; }
    function csrf_field() { return '<input type="hidden" name="_token" value="test">'; }
    $search = '<script>alert(1)</script>'; $courseId = 0; $ownCoursesOnly = true;
    foreach ([$own, Education::certificateReport(99, '', 0, 1)] as $report) {
        ob_start(); require dirname(__DIR__) . '/app/Views/admin/education/certificate-report.php'; $html = ob_get_clean();
        check(!str_contains($html, $search), 'Search output escaped');
        check(str_contains($html, $report['rows'] ? 'Aluno A' : 'Nenhum certificado encontrado'), 'Populated and empty view');
    }
    $reportError = 'Falha de consulta: referência test';
    ob_start(); require dirname(__DIR__) . '/app/Views/admin/education/certificate-report.php'; $html = ob_get_clean();
    check(str_contains($html, $reportError) && !str_contains($html, 'Nenhum certificado encontrado') && !str_contains($html, 'Certificados encontrados'), 'Failure must not masquerade as empty data');
    echo "Painel: escopo, totais, filtros, paginação e renderização aprovados.\n";
}
