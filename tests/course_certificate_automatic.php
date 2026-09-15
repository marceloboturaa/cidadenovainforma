<?php
namespace App\Core {
    class Database { public static $db; public static function connection() { return self::$db; } }
    class CertificateTestDatabase {
        public function __construct(public \PDO $pdo) {}
        public function exec($sql) { return 0; }
        public function quote($value) { return $this->pdo->quote($value); }
        public function query($sql) { return $this->pdo->query(str_starts_with($sql, 'SHOW ') ? 'SELECT 1' : $sql); }
        public function prepare($sql) { return $this->pdo->prepare(str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $sql)); }
    }
}
namespace App\Models {
    class CertificateNotification {
        public static array $calls = [];
        public static function notify(int $id): bool { self::$calls[] = $id; return true; }
    }
    class User {
        public static function find($id) { return ['name' => 'Estudante Teste']; }
    }
}
namespace {
require dirname(__DIR__) . '/app/Core/CourseCertificateData.php';
require dirname(__DIR__) . '/app/Models/Education.php';
require dirname(__DIR__) . '/app/Controllers/Admin/EducationController.php';
use App\Models\Education;
use App\Core\CourseCertificateData;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->sqliteCreateFunction('NOW', fn () => '2026-09-14 12:00:00');
\App\Core\Database::$db = new \App\Core\CertificateTestDatabase($pdo);
$pdo->exec('CREATE TABLE users (id INTEGER, name TEXT, email TEXT);
CREATE TABLE certificate_institutions (id INTEGER, name TEXT, cnpj TEXT, city TEXT, state TEXT, site TEXT);
CREATE TABLE education_courses (id INTEGER, title TEXT, active INTEGER, teacher_user_id INTEGER, certificate_institution_id INTEGER, certificate_enabled INTEGER, certificate_auto_release INTEGER, certificate_min_frequency INTEGER);
CREATE TABLE education_enrollments (course_id INTEGER, user_id INTEGER, status TEXT);
CREATE TABLE education_lessons (id INTEGER, course_id INTEGER, module_id INTEGER, active INTEGER, attendance_mode TEXT);
CREATE TABLE education_modules (id INTEGER, required INTEGER);
CREATE TABLE education_lesson_progress (lesson_id INTEGER, user_id INTEGER, completed_at TEXT);
CREATE TABLE education_certificates (id INTEGER PRIMARY KEY AUTOINCREMENT, course_id INTEGER, user_id INTEGER, verification_code TEXT, validation_hash TEXT, status TEXT, student_name TEXT, authorized_by INTEGER, authorized_at TEXT, issued_by INTEGER, issued_at TEXT, created_at TEXT, updated_at TEXT, UNIQUE(course_id, user_id));
CREATE TABLE certificate_audit_logs (certificate_id INTEGER, institution_id INTEGER, user_id INTEGER, action TEXT, old_values_json TEXT, new_values_json TEXT, ip_address TEXT, user_agent TEXT, created_at TEXT);
INSERT INTO users VALUES (1, "Estudante Teste", "test@example.test");
INSERT INTO education_courses VALUES (1, "Curso", 1, NULL, NULL, 1, 1, 75);
INSERT INTO education_enrollments VALUES (1, 1, "approved");
INSERT INTO education_lessons VALUES (1, 1, NULL, 1, "video"), (2, 1, NULL, 1, "video");
INSERT INTO education_lesson_progress VALUES (1, 1, "2026-09-01");');
check(!Education::certificateStatusForCourseUser(1, 1)['certificate'], 'Incomplete course must not issue');
$pdo->exec('INSERT INTO education_lesson_progress VALUES (2, 1, "2026-09-02"); UPDATE education_enrollments SET status = "pending"');
check(!Education::certificateStatusForCourseUser(1, 1)['certificate'], 'Pending enrollment must not issue');
$pdo->exec('UPDATE education_enrollments SET status = "approved"; UPDATE education_courses SET certificate_auto_release = 0');
check(!Education::certificateStatusForCourseUser(1, 1)['certificate'], 'Manual mode must not auto issue');
$pdo->exec('UPDATE education_courses SET certificate_auto_release = 1, certificate_enabled = 0');
check(!Education::certificateStatusForCourseUser(1, 1)['certificate'], 'Disabled certificates must not issue');
$pdo->exec('UPDATE education_courses SET certificate_enabled = 1');
$first = Education::certificateStatusForCourseUser(1, 1)['certificate'];
check($first['status'] === 'issued', 'Eligible course must issue');
check(count(\App\Models\CertificateNotification::$calls) === 1, 'Automatic issue must notify');
check(Education::certificateStatusForCourseUser(1, 1)['certificate']['verification_code'] === $first['verification_code'], 'Repeated access must be idempotent');
$pdo->exec('UPDATE education_certificates SET status = "pending"');
check(Education::certificateStatusForCourseUser(1, 1)['certificate']['status'] === 'issued', 'Eligible pending request must release');
check(count(\App\Models\CertificateNotification::$calls) === 2, 'Automatic approval must notify');
$pdo->exec('UPDATE education_certificates SET status = "revoked"');
check(Education::certificateStatusForCourseUser(1, 1)['certificate']['status'] === 'revoked', 'Revoked certificate must stay revoked');
$pdo->exec('UPDATE education_certificates SET status = "deleted"');
check(!Education::certificateStatusForCourseUser(1, 1)['certificate'], 'Deleted certificate must not be recreated');
$method = new ReflectionMethod(\App\Controllers\Admin\EducationController::class, 'certificateText');
$method->setAccessible(true);
$controller = new \App\Controllers\Admin\EducationController();
$course = ['title' => 'Curso A', 'starts_at' => '2026-01-10', 'ends_at' => '2026-02-20', 'workload_hours' => '40.50',
    'certificate_text' => '{student_name}|{course_title}|{course_start_date}|{course_end_date}|{course_hours}|{frequency}|{period_start}|{period_end}'];
$text = $method->invoke($controller, $course, ['student_name' => 'Ana', 'issued_at' => '2026-09-14'], ['frequency' => 90], ['start' => '2020-01-01', 'end' => '2020-02-01']);
check($text === 'Ana|Curso A|10/01/2026|20/02/2026|40,5|90%|10/01/2026|20/02/2026', 'Course data must override enrollment and preview dates');
CourseCertificateData::validate($course);
foreach ([['starts_at' => '2026-02-30'], ['starts_at' => '2026-09-10', 'ends_at' => '2026-09-01'], ['workload_hours' => -1], ['workload_hours' => 10000]] as $invalid) {
    try { CourseCertificateData::validate($invalid); throw new RuntimeException('Invalid value accepted'); } catch (InvalidArgumentException $e) {}
}
check(CourseCertificateData::hours(['workload_hours' => '40.00']) === '40', 'Whole hours formatting');
echo "Automação, matrícula, conclusão, revogação, variáveis e datas aprovadas.\n";
}
