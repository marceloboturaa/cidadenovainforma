<?php
require __DIR__ . '/course_certificate_automatic.php';

$pdo->exec('ALTER TABLE education_courses ADD closed_at TEXT;
ALTER TABLE education_courses ADD closed_by INTEGER;
ALTER TABLE education_courses ADD updated_at TEXT;
ALTER TABLE users ADD active INTEGER DEFAULT 1;
DELETE FROM education_certificates;
DELETE FROM education_lesson_progress;
INSERT INTO education_lessons VALUES (3, 1, NULL, 1, "video"), (4, 1, NULL, 1, "video"), (5, 1, NULL, 1, "video");
INSERT INTO users VALUES (2, "Aluno 2", "two@example.test", 1), (3, "Aluno 3", "three@example.test", 1), (4, "Aluno 4", "four@example.test", 1);
INSERT INTO education_enrollments VALUES (1, 2, "approved"), (1, 3, "pending"), (1, 4, "approved");
UPDATE education_courses SET certificate_auto_release = 0;
INSERT INTO education_lesson_progress VALUES (1,1,"done"),(2,1,"done"),(3,1,"done"),(4,1,"done"),
(1,2,"done"),(2,2,"done"),(3,2,"done"),
(1,3,"done"),(2,3,"done"),(3,3,"done"),(4,3,"done"),
(1,4,"done"),(2,4,"done"),(3,4,"done"),(4,4,"done");');
$revoked = \App\Models\Education::issueCertificate(1, 4, true);
$pdo->exec('UPDATE education_certificates SET status = "revoked"');
\App\Models\CertificateNotification::$calls = [];
$result = \App\Models\Education::closeCourse(1, 99);
check($result === ['already_closed' => false, 'issued' => 1], 'Only approved enrollment above 75% must issue');
check(count(\App\Models\CertificateNotification::$calls) === 1, 'Closure must notify issued certificate');
check($pdo->query('SELECT closed_by FROM education_courses')->fetchColumn() == 99, 'Closure records teacher');
check($pdo->query('SELECT authorized_by FROM education_certificates WHERE user_id=1')->fetchColumn() == 99, 'Certificate records authorizing teacher');
check(\App\Models\Education::certificateForCourseUser(1,4)['status'] === 'revoked', 'Closure preserves revocation');
check(\App\Models\Education::closeCourse(1,99)['already_closed'], 'Repeated closure is idempotent');
check(count(\App\Models\CertificateNotification::$calls) === 1, 'Repeated closure does not notify again');

// Three of four mandatory lessons is exactly 75%; optional lessons do not count.
$pdo->exec('UPDATE education_courses SET closed_at=NULL; UPDATE education_lessons SET active=0 WHERE id=5;
INSERT INTO education_modules VALUES (1,0);
INSERT INTO education_lessons VALUES (6,1,1,1,"video");
INSERT INTO education_lesson_progress VALUES (6,2,"done");');
check(\App\Models\Education::closeCourse(1,99)['issued'] === 0, 'Exactly 75% and optional completion must not qualify');
check(!\App\Models\Education::certificateForCourseUser(1,2), '75% student has no certificate');
$pdo->exec('UPDATE education_courses SET closed_at=NULL, certificate_enabled=0');
try {
    \App\Models\Education::closeCourse(1,99);
    throw new RuntimeException('Disabled certificate accepted');
} catch (InvalidArgumentException $e) {
    check(!$pdo->inTransaction(), 'Failed closure rolls back');
    check(!$pdo->query('SELECT closed_at FROM education_courses')->fetchColumn(), 'Failed closure leaves course open');
}
echo "Course closure: threshold, enrollment, teacher, notification, revocation and idempotency passed.\n";
