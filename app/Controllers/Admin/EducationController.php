<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Education;
use App\Models\User;

class EducationController
{
    private const MAX_LESSON_IMAGE_SIZE = 8 * 1024 * 1024;

    private const MAX_BLOCK_FILE_SIZE = 50 * 1024 * 1024;

    private const BLOCKED_BLOCK_EXTENSIONS = ['bat', 'cmd', 'com', 'exe', 'htaccess', 'html', 'htm', 'js', 'msi', 'phtml', 'phar', 'php', 'pl', 'ps1', 'py', 'sh', 'shtml', 'vbs'];

    public function index(): void
    {
        Middleware::auth();
        $user = current_user();
        $canManage = $this->canManageAll();
        $canViewAllCourses = $canManage || Auth::hasRole(['admin', 'admin-local']);
        $courses = $this->canTeach() && !$canViewAllCourses
            ? Education::coursesForManagement((int) $user['id'])
            : Education::coursesForUser((int) $user['id'], $canViewAllCourses);

        View::render('admin/education/index', [
            'courses' => $courses,
            'canManage' => $this->canManage(),
        ]);
    }

    public function manage(): void
    {
        Middleware::auth();
        $this->authorizeManage();

        $editing = $this->courseFromQuery(false);
        if ($editing && !$this->canManageCourse($editing)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $users = User::activeForAccessLists();

        View::render('admin/education/manage', [
            'courses' => Education::coursesForManagement($this->canManageAll() ? null : (int) (current_user()['id'] ?? 0)),
            'editing' => $editing,
            'users' => $users,
            'teacherOptions' => $this->teacherOptions($users),
            'studentOptions' => $this->studentOptions($users),
            'canManageAll' => $this->canManageAll(),
        ]);
    }

    public function storeCourse(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o título do curso.');
            redirect('/admin/education/manage');
        }

        $userId = (int) (current_user()['id'] ?? 0);
        $teacherUserId = $this->canManageAll() ? ($_POST['teacher_user_id'] ?? null) : $userId;
        $id = Education::createCourse(array_merge($_POST, [
            'teacher_user_id' => $teacherUserId,
            'created_by' => $userId ?: null,
            'updated_by' => $userId ?: null,
        ]));
        Education::syncEnrollments($id, $_POST['user_ids'] ?? []);

        Logger::info('education.course_created', 'Curso criado: ' . $title, $userId ?: null);
        Session::flash('success', 'Curso criado.');
        redirect('/admin/education/course?id=' . $id);
    }

    public function updateCourse(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');

        $course = $this->courseFromQuery();
        $this->authorizeCourseManage($course);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o título do curso.');
            redirect('/admin/education/manage?id=' . $course['id']);
        }

        $userId = (int) (current_user()['id'] ?? 0);
        $teacherUserId = $this->canManageAll() ? ($_POST['teacher_user_id'] ?? null) : ($course['teacher_user_id'] ?? $userId);
        Education::updateCourse((int) $course['id'], array_merge($_POST, [
            'teacher_user_id' => $teacherUserId,
            'updated_by' => $userId ?: null,
        ]));
        Education::syncEnrollments((int) $course['id'], $_POST['user_ids'] ?? []);

        Logger::info('education.course_updated', 'Curso atualizado: ' . $title, $userId ?: null);
        Session::flash('success', 'Curso atualizado.');
        redirect('/admin/education/course?id=' . $course['id']);
    }

    public function deleteCourse(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $course = $this->courseFromQuery();
        $this->authorizeCourseManage($course);

        Education::deactivateCourse((int) $course['id']);
        Logger::info('education.course_deleted', 'Curso desativado: ' . $course['title'], current_user()['id'] ?? null);
        Session::flash('success', 'Curso removido da lista.');
        redirect('/admin/education/manage');
    }

    public function course(): void
    {
        Middleware::auth();
        $user = current_user();
        $course = $this->courseFromQuery();
        $canManage = $this->canManageCourse($course);
        $canTakeAttendance = $this->canTakeAttendance($course);

        if (!Education::userCanAccessCourse((int) $course['id'], (int) $user['id'], $canManage || $canTakeAttendance)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        View::render('admin/education/course', [
            'course' => $course,
            'lessons' => Education::lessonsForCourse((int) $course['id'], (int) $user['id']),
            'modules' => Education::modulesForCourse((int) $course['id']),
            'canManage' => $canManage,
            'canTakeAttendance' => $canTakeAttendance,
            'editingLesson' => $this->lessonFromQuery(false),
            'editingModule' => $this->moduleFromQuery(false),
        ]);
    }

    public function storeModule(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $course = $this->courseFromQuery();
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o título do módulo.');
            redirect('/admin/education/course?id=' . $course['id']);
        }

        Education::createModule(array_merge($_POST, ['course_id' => $course['id']]));
        Session::flash('success', 'Módulo criado.');
        redirect('/admin/education/course?id=' . $course['id']);
    }

    public function updateModule(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $module = $this->moduleFromQuery();
        $course = Education::findCourse((int) $module['course_id']);
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o título do módulo.');
            redirect('/admin/education/course?id=' . $module['course_id'] . '&module_id=' . $module['id']);
        }

        Education::updateModule((int) $module['id'], array_merge($_POST, ['course_id' => $module['course_id']]));
        Session::flash('success', 'Módulo atualizado.');
        redirect('/admin/education/course?id=' . $module['course_id']);
    }

    public function deleteModule(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $module = $this->moduleFromQuery();
        $course = Education::findCourse((int) $module['course_id']);
        $this->authorizeCourseManage($course);

        Education::deactivateModule((int) $module['id']);
        Session::flash('success', 'Módulo removido.');
        redirect('/admin/education/course?id=' . $module['course_id']);
    }

    public function storeLesson(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $course = $this->courseFromQuery();
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o título da aula.');
            redirect('/admin/education/course?id=' . $course['id']);
        }

        Education::createLesson(array_merge($_POST, [
            'course_id' => $course['id'],
            'image_url' => $this->lessonImageFromRequest(),
        ]));
        Session::flash('success', 'Aula criada.');
        redirect('/admin/education/course?id=' . $course['id']);
    }

    public function updateLesson(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $lesson = $this->lessonFromQuery();
        $course = Education::findCourse((int) $lesson['course_id']);
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o título da aula.');
            redirect('/admin/education/course?id=' . $lesson['course_id'] . '&lesson_id=' . $lesson['id']);
        }

        Education::updateLesson((int) $lesson['id'], array_merge($_POST, [
            'course_id' => $lesson['course_id'],
            'image_url' => $this->lessonImageFromRequest(),
        ]));
        Session::flash('success', 'Aula atualizada.');
        redirect('/admin/education/course?id=' . $lesson['course_id']);
    }

    public function deleteLesson(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education/manage');
        $lesson = $this->lessonFromQuery();
        $course = Education::findCourse((int) $lesson['course_id']);
        $this->authorizeCourseManage($course);

        Education::deactivateLesson((int) $lesson['id']);
        Session::flash('success', 'Aula removida.');
        redirect('/admin/education/course?id=' . $lesson['course_id']);
    }

    public function lesson(): void
    {
        Middleware::auth();
        $user = current_user();
        $lesson = $this->lessonFromQuery();
        $course = Education::findCourse((int) $lesson['course_id']);
        $canManage = $this->canManageCourse($course);

        if (!Education::userCanAccessCourse((int) $lesson['course_id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        View::render('admin/education/lesson', [
            'lesson' => $lesson,
            'course' => $course,
            'videoEmbedUrl' => $this->videoEmbedUrl((string) ($lesson['video_url'] ?? '')),
            'blocks' => Education::blocksForLesson((int) $lesson['id']),
            'editingBlock' => $this->blockFromQuery(false),
            'canManage' => $canManage,
            'modules' => Education::modulesForCourse((int) $lesson['course_id']),
            'playlist' => Education::lessonsForCourse((int) $lesson['course_id'], (int) $user['id']),
        ]);
    }

    public function storeBlock(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');
        $lesson = $this->lessonFromQuery();
        $course = Education::findCourse((int) $lesson['course_id']);
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $mediaUrl = trim((string) ($_POST['media_url'] ?? ''));
        $filePath = $this->storeBlockFile();
        $type = strtolower(trim((string) ($_POST['type'] ?? 'text')));

        if ($title === '' && $content === '' && $mediaUrl === '' && $filePath === null) {
            Session::flash('error', 'Informe conteúdo, vídeo ou arquivo para adicionar ao material.');
            redirect('/admin/education/lesson?id=' . $lesson['id']);
        }

        Education::createLessonBlock(array_merge($_POST, [
            'lesson_id' => $lesson['id'],
            'type' => $filePath && !in_array($type, ['image', 'assignment', 'certificate'], true) ? 'file' : $type,
            'file_path' => $filePath,
        ]));

        Session::flash('success', 'Material adicionado à aula.');
        redirect('/admin/education/lesson?id=' . $lesson['id']);
    }

    public function updateBlock(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');
        $block = $this->blockFromQuery();
        $lesson = Education::findLesson((int) $block['lesson_id']);
        $course = Education::findCourse((int) $block['course_id']);
        $this->authorizeCourseManage($course);

        $filePath = $this->storeBlockFile();
        Education::updateLessonBlock((int) $block['id'], array_merge($_POST, [
            'lesson_id' => $lesson['id'],
            'file_path' => $filePath ?: ($block['file_path'] ?? null),
        ]));

        Session::flash('success', 'Material atualizado.');
        redirect('/admin/education/lesson?id=' . $lesson['id']);
    }

    public function deleteBlock(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');
        $block = $this->blockFromQuery();
        $course = Education::findCourse((int) $block['course_id']);
        $this->authorizeCourseManage($course);

        Education::deactivateLessonBlock((int) $block['id']);
        Session::flash('success', 'Material removido.');
        redirect('/admin/education/lesson?id=' . $block['lesson_id']);
    }

    public function downloadBlock(): void
    {
        Middleware::auth();
        $user = current_user();
        $block = $this->blockFromQuery();
        $course = Education::findCourse((int) $block['course_id']);
        $canManage = $this->canManageCourse($course);

        if (!Education::userCanAccessCourse((int) $block['course_id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $relativePath = (string) ($block['file_path'] ?? '');
        $path = dirname(__DIR__, 3) . '/' . ltrim($relativePath, '/');

        if ($relativePath === '' || !is_file($path)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $downloadName = basename($path);
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $downloadName) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function progress(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');
        $user = current_user();
        $lesson = $this->lessonFromQuery();
        $course = Education::findCourse((int) $lesson['course_id']);
        $canManage = $this->canManageCourse($course);

        if (!Education::userCanAccessCourse((int) $lesson['course_id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        Education::markLesson((int) $lesson['id'], (int) $user['id'], ($_POST['completed'] ?? '') === '1');
        Session::flash('success', 'Progresso atualizado.');
        redirect('/admin/education/lesson?id=' . $lesson['id']);
    }

    public function attendance(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->authorizeAttendance($course);

        $date = $this->attendanceDate();
        View::render('admin/education/attendance', [
            'course' => $course,
            'date' => $date,
            'students' => Education::enrolledStudentsForCourse((int) $course['id']),
            'records' => Education::attendanceForCourseDate((int) $course['id'], $date),
        ]);
    }

    public function saveAttendance(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->authorizeAttendance($course);
        $this->validateCsrf('/admin/education/attendance?id=' . $course['id']);

        $date = $this->attendanceDate();
        Education::saveAttendance(
            (int) $course['id'],
            $date,
            is_array($_POST['attendance'] ?? null) ? $_POST['attendance'] : [],
            (int) (current_user()['id'] ?? 0) ?: null
        );

        Session::flash('success', 'Chamada salva.');
        redirect('/admin/education/attendance?id=' . $course['id'] . '&date=' . $date);
    }

    public function attendanceReport(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->authorizeAttendance($course);

        [$startDate, $endDate] = $this->attendanceRange();
        View::render('admin/education/attendance-report', [
            'course' => $course,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rows' => Education::attendanceReportForCourse((int) $course['id'], $startDate, $endDate),
            'dates' => Education::attendanceDatesForCourse((int) $course['id'], $startDate, $endDate),
        ]);
    }

    private function courseFromQuery(bool $required = true): ?array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $course = $id ? Education::findCourse($id) : null;

        if (!$course && $required) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $course;
    }

    private function lessonFromQuery(bool $required = true): ?array
    {
        $id = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT)
            ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lesson = $id ? Education::findLesson($id) : null;

        if (!$lesson && $required) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $lesson;
    }

    private function moduleFromQuery(bool $required = true): ?array
    {
        $id = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT);
        $module = $id ? Education::findModule($id) : null;

        if (!$module && $required) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $module;
    }

    private function blockFromQuery(bool $required = true): ?array
    {
        $id = filter_input(INPUT_GET, 'block_id', FILTER_VALIDATE_INT);
        if (!$id && $required) {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        }
        $block = $id ? Education::findLessonBlock($id) : null;

        if (!$block && $required) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $block;
    }

    private function canManage(): bool
    {
        return $this->canManageAll() || $this->canTeach();
    }

    private function canManageAll(): bool
    {
        return Auth::hasRole(['master', 'diretor']);
    }

    private function canTeach(): bool
    {
        return Auth::hasRole('professor') || Auth::can('education.teach');
    }

    private function canTakeAttendance(?array $course): bool
    {
        if (!$course) {
            return false;
        }

        if (Auth::hasRole(['master', 'admin', 'admin-local', 'diretor'])) {
            return true;
        }

        return $this->canTeach() && (int) ($course['teacher_user_id'] ?? 0) === (int) (current_user()['id'] ?? 0);
    }

    private function teacherOptions(array $users): array
    {
        return array_values(array_filter($users, function (array $user): bool {
            $slugs = $this->roleSlugs($user);
            return array_intersect($slugs, ['professor', 'master', 'diretor']) !== [];
        }));
    }

    private function studentOptions(array $users): array
    {
        return array_values(array_filter($users, function (array $user): bool {
            return in_array('estudante', $this->roleSlugs($user), true);
        }));
    }

    private function roleSlugs(array $user): array
    {
        return array_values(array_filter(explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? ''))));
    }

    private function authorizeManage(): void
    {
        if (!$this->canManage()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function canManageCourse(?array $course): bool
    {
        if (!$course) {
            return false;
        }

        if ($this->canManageAll()) {
            return true;
        }

        return $this->canTeach() && (int) ($course['teacher_user_id'] ?? 0) === (int) (current_user()['id'] ?? 0);
    }

    private function authorizeCourseManage(?array $course): void
    {
        if (!$this->canManageCourse($course)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeAttendance(?array $course): void
    {
        if (!$this->canTakeAttendance($course)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function validateCsrf(string $redirectTo): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($redirectTo);
        }
    }

    private function attendanceDate(): string
    {
        $date = trim((string) ($_POST['attendance_date'] ?? $_GET['date'] ?? date('Y-m-d')));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
    }

    private function attendanceRange(): array
    {
        $startDate = trim((string) ($_GET['start_date'] ?? date('Y-m-01')));
        $endDate = trim((string) ($_GET['end_date'] ?? date('Y-m-d')));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-01');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function videoEmbedUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $match)) {
            return 'https://www.youtube.com/embed/' . $match[1];
        }

        return $url;
    }

    private function storeBlockFile(): ?string
    {
        if (empty($_FILES['block_file']['name']) || ($_FILES['block_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        if ((int) $_FILES['block_file']['size'] > self::MAX_BLOCK_FILE_SIZE) {
            Session::flash('error', 'O arquivo deve ter no máximo 50MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $extension = strtolower(pathinfo((string) $_FILES['block_file']['name'], PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, self::BLOCKED_BLOCK_EXTENSIONS, true)) {
            Session::flash('error', 'Tipo de arquivo não permitido.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $directory = dirname(__DIR__, 3) . '/storage/documents/education';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $safeBase = slugify(pathinfo((string) $_FILES['block_file']['name'], PATHINFO_FILENAME));
        $filename = $safeBase . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($_FILES['block_file']['tmp_name'], $target)) {
            Session::flash('error', 'Não foi possível salvar o arquivo.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        return '/storage/documents/education/' . $filename;
    }

    private function lessonImageFromRequest(): ?string
    {
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));

        if (empty($_FILES['lesson_image']['name']) || ($_FILES['lesson_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $imageUrl !== '' ? $imageUrl : null;
        }

        if (($_FILES['lesson_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Não foi possível enviar a imagem da aula.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $tmpName = (string) ($_FILES['lesson_image']['tmp_name'] ?? '');
        $size = (int) ($_FILES['lesson_image']['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        if (!$imageInfo || !isset($allowedTypes[$imageInfo[2] ?? 0]) || $size <= 0 || $size > self::MAX_LESSON_IMAGE_SIZE) {
            Session::flash('error', 'Use uma imagem JPG, PNG, WEBP ou GIF com até 8MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/education';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            Session::flash('error', 'A pasta de imagens da aula não está gravável.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $filename = 'lesson-' . bin2hex(random_bytes(12)) . '.' . $allowedTypes[$imageInfo[2]];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            Session::flash('error', 'Não foi possível salvar a imagem da aula.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        return '/public/uploads/education/' . $filename;
    }
}
