<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Education;
use App\Models\Forum;
use App\Models\User;

class EducationController
{
    private const MAX_LESSON_IMAGE_SIZE = 8 * 1024 * 1024;

    private const MAX_COURSE_COVER_SIZE = 8 * 1024 * 1024;

    private const MAX_CERTIFICATE_BACKGROUND_SIZE = 12 * 1024 * 1024;

    private const MAX_BLOCK_FILE_SIZE = 50 * 1024 * 1024;

    private const MAX_ASSIGNMENT_FILE_SIZE = 25 * 1024 * 1024;

    private const BLOCKED_BLOCK_EXTENSIONS = ['bat', 'cmd', 'com', 'exe', 'htaccess', 'html', 'htm', 'js', 'msi', 'phtml', 'phar', 'php', 'pl', 'ps1', 'py', 'sh', 'shtml', 'vbs'];

    private const ALLOWED_ASSIGNMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'odt', 'txt', 'rtf', 'xls', 'xlsx', 'ods', 'ppt', 'pptx', 'odp', 'jpg', 'jpeg', 'png', 'webp', 'zip', 'rar', '7z'];

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

    public function certificates(): void
    {
        Middleware::auth();
        $user = current_user();

        View::render('admin/education/certificates', [
            'certificates' => Education::certificatesForUser((int) $user['id']),
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
            'canAssignTeacher' => $this->canAssignTeacher(),
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
        $teacherUserId = $this->canAssignTeacher()
            ? ($_POST['teacher_user_id'] ?? null)
            : ($this->canTeach() ? $userId : null);
        $id = Education::createCourse(array_merge($_POST, [
            'cover_image' => $this->courseCoverFromRequest(null),
            'public_enabled' => $this->canManageAll() && !empty($_POST['public_enabled']) ? 1 : 0,
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
        $teacherUserId = $this->canAssignTeacher() ? ($_POST['teacher_user_id'] ?? null) : ($course['teacher_user_id'] ?? null);
        Education::updateCourse((int) $course['id'], array_merge($this->certificateFieldsFromCourse($course), $_POST, [
            'cover_image' => $this->courseCoverFromRequest($course['cover_image'] ?? null),
            'public_enabled' => $this->canManageAll() ? (!empty($_POST['public_enabled']) ? 1 : 0) : ($course['public_enabled'] ?? 0),
            'teacher_user_id' => $teacherUserId,
            'updated_by' => $userId ?: null,
        ]));
        if (($_POST['enrollment_sync'] ?? '') === '1') {
            Education::syncEnrollments((int) $course['id'], $_POST['user_ids'] ?? []);
        }

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

        $lessons = Education::lessonsWithSequenceAccess(
            Education::lessonsForCourse((int) $course['id'], (int) $user['id']),
            $canManage
        );
        $forumTopics = Education::forumTopics((int) $course['id']);
        $courseForms = Education::formsForCourse((int) $course['id']);
        $certificateStatus = Education::certificateStatusForCourseUser((int) $course['id'], (int) $user['id']);

        View::render('admin/education/course', [
            'course' => $course,
            'lessons' => $lessons,
            'modules' => Education::modulesForCourse((int) $course['id']),
            'canManage' => $canManage,
            'canAssignTeacher' => $this->canAssignTeacher(),
            'teacherOptions' => $this->teacherOptions(User::activeForAccessLists()),
            'forumAuthorOptions' => User::activeForAccessLists(),
            'canTakeAttendance' => $canTakeAttendance,
            'editingLesson' => $this->lessonFromQuery(false, false),
            'editingModule' => $this->moduleFromQuery(false),
            'forumTopics' => $forumTopics,
            'forumRepliesByTopic' => Education::forumRepliesForTopics(array_column($forumTopics, 'id'), $canManage),
            'courseForms' => $courseForms,
            'certificateStatus' => $certificateStatus,
            'certificateNameRequests' => $canManage ? Education::certificateNameRequestsForCourse((int) $course['id']) : [],
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
        $isScheduleLocked = !$canManage && !$this->lessonIsAvailable($lesson);
        $isLocked = (!empty($lesson['locked']) || $isScheduleLocked) && !$canManage;

        if (!Education::userCanAccessCourse((int) $lesson['course_id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }
        if (!Education::userCanAccessLessonInSequence((int) $lesson['id'], (int) $user['id'], $canManage)) {
            Session::flash('error', $isScheduleLocked ? 'Esta aula ainda não foi liberada no agendamento.' : 'Conclua a aula anterior antes de assistir esta aula.');
            redirect('/admin/education/course?id=' . $lesson['course_id']);
        }

        $playlist = Education::lessonsWithSequenceAccess(
            Education::lessonsForCourse((int) $lesson['course_id'], (int) $user['id']),
            $canManage
        );
        $hasVideo = trim((string) ($lesson['video_url'] ?? '')) !== '';
        $videoWatched = !$hasVideo || $canManage || Education::userWatchedLessonVideo((int) $lesson['id'], (int) $user['id']);
        $lessonForumTopics = Education::forumTopics((int) $lesson['course_id'], (int) $lesson['id']);
        $blocks = $isLocked ? [] : Education::blocksForLesson((int) $lesson['id']);
        $assignmentBlocks = array_values(array_filter($blocks, fn (array $block): bool => ($block['type'] ?? '') === 'assignment'));
        $lessonForms = $isLocked ? [] : Education::formsForCourse((int) $lesson['course_id'], (int) $lesson['id']);

        View::render('admin/education/lesson', [
            'lesson' => $lesson,
            'course' => $course,
            'videoEmbedUrl' => $isLocked ? null : $this->videoEmbedUrl((string) ($lesson['video_url'] ?? '')),
            'blocks' => $blocks,
            'editingBlock' => $this->blockFromQuery(false),
            'canManage' => $canManage,
            'isLocked' => $isLocked,
            'isScheduleLocked' => $isScheduleLocked,
            'hasVideo' => $hasVideo,
            'videoWatched' => $videoWatched,
            'modules' => Education::modulesForCourse((int) $lesson['course_id']),
            'playlist' => $playlist,
            'lessonForumTopics' => $lessonForumTopics,
            'lessonForumRepliesByTopic' => Education::forumRepliesForTopics(array_column($lessonForumTopics, 'id'), $canManage),
            'canAssignForumAuthor' => $this->canAssignTeacher(),
            'forumAuthorOptions' => User::activeForAccessLists(),
            'lessonForms' => $lessonForms,
            'assignmentSubmissionsByBlock' => $canManage ? Education::assignmentSubmissionsForBlocks(array_column($assignmentBlocks, 'id')) : [],
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
        if (!empty($lesson['locked']) && !$canManage) {
            Session::flash('error', 'Esta aula está bloqueada pelo professor.');
            redirect('/admin/education/lesson?id=' . $lesson['id']);
        }
        if (!$this->lessonIsAvailable($lesson) && !$canManage) {
            Session::flash('error', 'Esta aula ainda não foi liberada no agendamento.');
            redirect('/admin/education/course?id=' . $lesson['course_id']);
        }
        if (!Education::userCanAccessLessonInSequence((int) $lesson['id'], (int) $user['id'], $canManage)) {
            Session::flash('error', 'Conclua a aula anterior antes de marcar esta aula.');
            redirect('/admin/education/course?id=' . $lesson['course_id']);
        }
        if (($_POST['completed'] ?? '') === '1' && !$canManage && ($lesson['attendance_mode'] ?? 'video') === 'manual') {
            Session::flash('error', 'Esta aula ao vivo precisa da validação de presença pelo professor.');
            redirect('/admin/education/lesson?id=' . $lesson['id']);
        }
        if (($_POST['completed'] ?? '') === '1' && !$canManage && trim((string) ($lesson['video_url'] ?? '')) !== '' && !Education::userWatchedLessonVideo((int) $lesson['id'], (int) $user['id'])) {
            Session::flash('error', 'Assista ao vídeo completo antes de concluir a aula.');
            redirect('/admin/education/lesson?id=' . $lesson['id']);
        }

        Education::markLesson((int) $lesson['id'], (int) $user['id'], ($_POST['completed'] ?? '') === '1');
        Session::flash('success', 'Progresso atualizado.');
        redirect('/admin/education/lesson?id=' . $lesson['id']);
    }

    public function watchVideo(): void
    {
        Middleware::auth();
        header('Content-Type: application/json; charset=utf-8');

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Sessão expirada.']);
            return;
        }

        $user = current_user();
        $lesson = $this->lessonFromQuery();
        $course = Education::findCourse((int) $lesson['course_id']);
        $canManage = $this->canManageCourse($course);

        if (!Education::userCanAccessCourse((int) $lesson['course_id'], (int) $user['id'], $canManage)
            || !Education::userCanAccessLessonInSequence((int) $lesson['id'], (int) $user['id'], $canManage)
            || (!empty($lesson['locked']) && !$canManage)
            || (!$this->lessonIsAvailable($lesson) && !$canManage)
        ) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acesso negado.']);
            return;
        }

        if (trim((string) ($lesson['video_url'] ?? '')) !== '') {
            Education::markLessonVideoWatched((int) $lesson['id'], (int) $user['id']);
        }

        echo json_encode(['ok' => true]);
    }

    public function storeForumTopic(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');
        $user = current_user();
        $lessonId = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT);
        $lesson = $lessonId ? Education::findLesson($lessonId) : null;
        $course = $lesson ? Education::findCourse((int) $lesson['course_id']) : $this->courseFromQuery();
        $canManage = $this->canManageCourse($course);

        if (!$course || !$canManage || !Education::userCanAccessCourse((int) $course['id'], (int) $user['id'], true)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($title === '' || $body === '') {
            Session::flash('error', 'Informe título e mensagem para publicar no fórum do curso.');
            redirect('/admin/education/course?id=' . $course['id']);
        }

        $centralTopicId = $this->createCentralForumCopy($course, $lesson, $user, $title, $body);
        $topicId = Education::createForumTopic([
            'course_id' => $course['id'],
            'lesson_id' => $lesson['id'] ?? null,
            'central_topic_id' => $centralTopicId,
            'user_id' => $user['id'],
            'title' => $title,
            'body' => $body,
        ]);

        Session::flash('success', $lesson ? 'Tópico publicado no fórum desta aula.' : 'Tópico publicado no fórum do curso.');
        redirect($lesson ? '/admin/education/lesson?id=' . $lesson['id'] . '#lesson-forum' : '/admin/education/course?id=' . $course['id'] . '#course-forum');
    }

    public function deleteForumTopic(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');

        $topicId = filter_input(INPUT_GET, 'topic_id', FILTER_VALIDATE_INT);
        $topic = $topicId ? Education::findForumTopic($topicId) : null;
        if (!$topic || empty($topic['course_id'])) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $topic['course_id']);
        $this->authorizeCourseManage($course);

        Education::hideForumTopic((int) $topic['id']);
        if (!empty($topic['central_topic_id'])) {
            Forum::setTopicStatus((int) $topic['central_topic_id'], 'hidden');
        }

        Session::flash('success', 'Fórum removido.');
        redirect(!empty($topic['lesson_id']) ? '/admin/education/lesson?id=' . $topic['lesson_id'] . '#lesson-forum' : '/admin/education/course?id=' . $topic['course_id'] . '#course-forum');
    }

    public function updateForumTopic(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');

        $topicId = filter_input(INPUT_GET, 'topic_id', FILTER_VALIDATE_INT);
        $topic = $topicId ? Education::findForumTopic($topicId) : null;
        if (!$topic || empty($topic['course_id'])) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $topic['course_id']);
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($title === '' || $body === '') {
            Session::flash('error', 'Informe título e mensagem para editar o fórum.');
            redirect(!empty($topic['lesson_id']) ? '/admin/education/lesson?id=' . $topic['lesson_id'] . '#lesson-forum' : '/admin/education/course?id=' . $topic['course_id'] . '#course-forum');
        }

        $topicUserId = (int) $topic['user_id'];
        if ($this->canAssignTeacher()) {
            $requestedUserId = (int) ($_POST['user_id'] ?? 0);
            $topicUserId = $requestedUserId > 0 ? $requestedUserId : $topicUserId;
        }

        Education::updateForumTopic((int) $topic['id'], [
            'title' => $title,
            'body' => $body,
            'user_id' => $topicUserId,
        ]);

        if (!empty($topic['central_topic_id'])) {
            $lesson = !empty($topic['lesson_id']) ? Education::findLesson((int) $topic['lesson_id']) : null;
            $this->updateCentralForumCopy($course, $lesson, (int) $topic['central_topic_id'], $title, $body, $topicUserId);
        }

        Session::flash('success', 'Fórum atualizado.');
        redirect(!empty($topic['lesson_id']) ? '/admin/education/lesson?id=' . $topic['lesson_id'] . '#lesson-forum' : '/admin/education/course?id=' . $topic['course_id'] . '#course-forum');
    }

    public function deleteForumReply(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');

        $replyId = filter_input(INPUT_GET, 'reply_id', FILTER_VALIDATE_INT);
        $reply = $replyId ? Education::findForumReply($replyId, true) : null;
        if (!$reply || empty($reply['course_id'])) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $reply['course_id']);
        $this->authorizeCourseManage($course);

        Education::hideForumReply((int) $reply['id']);

        Session::flash('success', 'Comentário ocultado.');
        redirect(!empty($reply['lesson_id']) ? '/admin/education/lesson?id=' . $reply['lesson_id'] . '#lesson-forum' : '/admin/education/course?id=' . $reply['course_id'] . '#course-forum');
    }

    public function restoreForumReply(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');

        $replyId = filter_input(INPUT_GET, 'reply_id', FILTER_VALIDATE_INT);
        $reply = $replyId ? Education::findForumReply($replyId, true) : null;
        if (!$reply || empty($reply['course_id'])) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $reply['course_id']);
        $this->authorizeCourseManage($course);

        Education::restoreForumReply((int) $reply['id']);

        Session::flash('success', 'Comentário restaurado para estudantes.');
        redirect(!empty($reply['lesson_id']) ? '/admin/education/lesson?id=' . $reply['lesson_id'] . '#lesson-forum' : '/admin/education/course?id=' . $reply['course_id'] . '#course-forum');
    }

    public function storeForumReply(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');
        $user = current_user();
        $topicId = filter_input(INPUT_GET, 'topic_id', FILTER_VALIDATE_INT);
        $topic = $topicId ? Education::findForumTopic($topicId) : null;

        if (!$topic || empty($topic['course_id'])) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $topic['course_id']);
        $canManage = $this->canManageCourse($course);
        if (!$course || !Education::userCanAccessCourse((int) $course['id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            Session::flash('error', 'Escreva a resposta antes de enviar.');
            redirect('/admin/education/course?id=' . $course['id'] . '#course-forum');
        }

        Education::createForumReply([
            'topic_id' => $topic['id'],
            'user_id' => $user['id'],
            'body' => $body,
        ]);

        Session::flash('success', 'Resposta publicada.');
        redirect(!empty($topic['lesson_id']) ? '/admin/education/lesson?id=' . $topic['lesson_id'] . '#lesson-forum' : '/admin/education/course?id=' . $course['id'] . '#course-forum');
    }

    public function storeForm(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');
        [$course, $lesson] = $this->formScopeFromRequest();
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        $questions = $this->formQuestionsFromRequest();
        if ($title === '' || !$questions) {
            Session::flash('error', 'Informe o titulo e pelo menos uma pergunta.');
            redirect($lesson ? '/admin/education/lesson?id=' . $lesson['id'] . '#lesson-forms' : '/admin/education/course?id=' . $course['id'] . '#course-forms');
        }

        Education::createForm([
            'course_id' => $course['id'],
            'lesson_id' => $lesson['id'] ?? null,
            'created_by' => current_user()['id'] ?? 0,
            'title' => $title,
            'description' => $_POST['description'] ?? '',
        ], $questions);

        Session::flash('success', 'Formulario criado.');
        redirect($lesson ? '/admin/education/lesson?id=' . $lesson['id'] . '#lesson-forms' : '/admin/education/course?id=' . $course['id'] . '#course-forms');
    }

    public function updateForm(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');
        $form = $this->formFromQuery();
        $course = Education::findCourse((int) $form['course_id']);
        $this->authorizeCourseManage($course);

        $title = trim((string) ($_POST['title'] ?? ''));
        $questions = $this->formQuestionsFromRequest();
        if ($title === '' || !$questions) {
            Session::flash('error', 'Informe o titulo e pelo menos uma pergunta.');
            redirect($this->formRedirect($form));
        }

        Education::updateForm((int) $form['id'], [
            'title' => $title,
            'description' => $_POST['description'] ?? '',
        ], $questions);

        Session::flash('success', 'Formulario atualizado.');
        redirect($this->formRedirect($form));
    }

    public function deleteForm(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');
        $form = $this->formFromQuery();
        $course = Education::findCourse((int) $form['course_id']);
        $this->authorizeCourseManage($course);

        Education::deactivateForm((int) $form['id']);
        Session::flash('success', 'Formulario removido.');
        redirect($this->formRedirect($form));
    }

    public function submitForm(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');
        $user = current_user();
        $form = $this->formFromQuery();
        $course = Education::findCourse((int) $form['course_id']);
        $canManage = $this->canManageCourse($course);

        if (!$course || !Education::userCanAccessCourse((int) $course['id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }
        if (!empty($form['lesson_id'])) {
            $lesson = Education::findLesson((int) $form['lesson_id']);
            if (!$lesson || (!empty($lesson['locked']) && !$canManage) || !Education::userCanAccessLessonInSequence((int) $lesson['id'], (int) $user['id'], $canManage)) {
                http_response_code(403);
                View::render('errors/403');
                return;
            }
        }

        $questions = Education::formQuestions((int) $form['id']);
        $answers = [];
        foreach ($questions as $question) {
            $answers[(int) $question['id']] = trim((string) ($_POST['answers'][(int) $question['id']] ?? ''));
        }

        Education::saveFormResponse((int) $form['id'], (int) $user['id'], $answers);
        Session::flash('success', 'Formulario enviado.');
        redirect($this->formRedirect($form));
    }

    public function gradeFormResponse(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');

        $id = filter_input(INPUT_GET, 'response_id', FILTER_VALIDATE_INT);
        $response = $id ? Education::findFormResponse($id) : null;
        if (!$response) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $response['course_id']);
        $this->authorizeCourseManage($course);

        Education::gradeFormResponse(
            (int) $response['id'],
            (string) ($_POST['correction_status'] ?? 'pending'),
            (string) ($_POST['grade'] ?? ''),
            (string) ($_POST['feedback'] ?? ''),
            (int) (current_user()['id'] ?? 0)
        );

        Session::flash('success', 'Correcao do formulario salva.');
        redirect($this->formRedirect($response));
    }

    public function submitAssignment(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');
        $user = current_user();
        $block = $this->blockFromQuery();
        $course = Education::findCourse((int) $block['course_id']);
        $canManage = $this->canManageCourse($course);

        if (($block['type'] ?? '') !== 'assignment' || !$course || !Education::userCanAccessCourse((int) $course['id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }
        $lesson = Education::findLesson((int) $block['lesson_id']);
        if (!$lesson || (!empty($lesson['locked']) && !$canManage) || !Education::userCanAccessLessonInSequence((int) $lesson['id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $textAnswer = trim((string) ($_POST['text_answer'] ?? ''));
        $file = $this->storeAssignmentFile((int) $block['id'], (int) $user['id']);
        if ($textAnswer === '' && !$file && !Education::assignmentSubmission((int) $block['id'], (int) $user['id'])) {
            Session::flash('error', 'Envie um arquivo ou escreva uma resposta.');
            redirect('/admin/education/lesson?id=' . $block['lesson_id']);
        }

        Education::saveAssignmentSubmission((int) $block['id'], (int) $user['id'], $textAnswer, $file);
        Session::flash('success', 'Tarefa enviada.');
        redirect('/admin/education/lesson?id=' . $block['lesson_id']);
    }

    public function gradeAssignment(): void
    {
        Middleware::auth();
        $this->authorizeManage();
        $this->validateCsrf('/admin/education');

        $id = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);
        $submission = $id ? $this->assignmentSubmissionById($id) : null;
        if (!$submission) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $block = Education::findLessonBlock((int) $submission['block_id']);
        $course = $block ? Education::findCourse((int) $block['course_id']) : null;
        $this->authorizeCourseManage($course);

        Education::gradeAssignmentSubmission(
            (int) $submission['id'],
            (string) ($_POST['correction_status'] ?? 'pending'),
            (string) ($_POST['grade'] ?? ''),
            (string) ($_POST['feedback'] ?? ''),
            (int) (current_user()['id'] ?? 0)
        );

        Session::flash('success', 'Correcao da tarefa salva.');
        redirect('/admin/education/lesson?id=' . ($block['lesson_id'] ?? ''));
    }

    public function downloadSubmission(): void
    {
        Middleware::auth();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $submission = $id ? $this->assignmentSubmissionById($id) : null;
        if (!$submission) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $block = Education::findLessonBlock((int) $submission['block_id']);
        if (!$block) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $block['course_id']);
        $user = current_user();
        if (!$this->canManageCourse($course) && (int) $submission['user_id'] !== (int) $user['id']) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $path = dirname(__DIR__, 3) . '/' . ltrim((string) $submission['file_path'], '/');
        if (!is_file($path)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $downloadName = str_replace(['"', "\r", "\n"], '', basename((string) ($submission['original_name'] ?: $path)));
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function attendance(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->authorizeAttendance($course);

        $lesson = $this->lessonFromQuery(false, false);
        if ($lesson && (int) $lesson['course_id'] !== (int) $course['id']) {
            $lesson = null;
        }
        $date = $this->attendanceDate();
        if ($lesson && !empty($lesson['available_at'])) {
            $date = substr((string) $lesson['available_at'], 0, 10);
        }
        View::render('admin/education/attendance', [
            'course' => $course,
            'lesson' => $lesson,
            'date' => $date,
            'students' => Education::enrolledStudentsForCourse((int) $course['id']),
            'records' => Education::attendanceForCourseDate((int) $course['id'], $date, $lesson ? (int) $lesson['id'] : 0),
            'lessons' => Education::lessonsForCourse((int) $course['id']),
        ]);
    }

    public function saveAttendance(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->authorizeAttendance($course);
        $this->validateCsrf('/admin/education/attendance?id=' . $course['id']);

        $lessonId = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT) ?: 0;
        $lesson = $lessonId ? Education::findLesson($lessonId) : null;
        if ($lesson && (int) $lesson['course_id'] !== (int) $course['id']) {
            $lesson = null;
            $lessonId = 0;
        }
        $date = $this->attendanceDate();
        Education::saveAttendance(
            (int) $course['id'],
            $date,
            is_array($_POST['attendance'] ?? null) ? $_POST['attendance'] : [],
            (int) (current_user()['id'] ?? 0) ?: null,
            $lessonId
        );

        Session::flash('success', $lesson ? 'Presença da aula validada.' : 'Chamada salva.');
        redirect('/admin/education/attendance?id=' . $course['id'] . '&date=' . $date . ($lessonId ? '&lesson_id=' . $lessonId : ''));
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

    public function updateCertificate(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->authorizeCourseManage($course);
        $this->validateCsrf('/admin/education/course?id=' . $course['id']);

        $title = trim((string) ($_POST['certificate_title'] ?? ''));
        $text = trim((string) ($_POST['certificate_text'] ?? ''));
        if (!empty($_POST['certificate_enabled']) && ($title === '' || $text === '')) {
            Session::flash('error', 'Informe o título e o texto do certificado para liberar a emissão.');
            redirect('/admin/education/course?id=' . $course['id'] . '#course-certificate');
        }

        Education::updateCourse((int) $course['id'], array_merge($course, [
            'certificate_enabled' => !empty($_POST['certificate_enabled']) ? 1 : 0,
            'certificate_title' => $title,
            'certificate_text' => $text,
            'certificate_background' => $this->certificateBackgroundFromRequest($course['certificate_background'] ?? null, 'certificate_background', 'certificate_background_upload'),
            'certificate_min_frequency' => $_POST['certificate_min_frequency'] ?? 0,
            'certificate_course_nature' => $_POST['certificate_course_nature'] ?? null,
            'certificate_modality' => $_POST['certificate_modality'] ?? null,
            'certificate_approval_criteria' => $_POST['certificate_approval_criteria'] ?? null,
            'certificate_legal_text' => $_POST['certificate_legal_text'] ?? null,
            'certificate_institution_name' => $_POST['certificate_institution_name'] ?? null,
            'certificate_institution_city' => $_POST['certificate_institution_city'] ?? null,
            'certificate_institution_cnpj' => $_POST['certificate_institution_cnpj'] ?? null,
            'certificate_institution_site' => $_POST['certificate_institution_site'] ?? null,
            'certificate_objectives' => $_POST['certificate_objectives'] ?? null,
            'certificate_competencies' => $_POST['certificate_competencies'] ?? null,
            'certificate_responsible_name' => $_POST['certificate_responsible_name'] ?? null,
            'certificate_responsible_credential' => $_POST['certificate_responsible_credential'] ?? null,
            'certificate_program_enabled' => !empty($_POST['certificate_program_enabled']) ? 1 : 0,
            'certificate_program_background' => $this->certificateBackgroundFromRequest($course['certificate_program_background'] ?? null, 'certificate_program_background', 'certificate_program_background_upload'),
            'certificate_program_extra' => $_POST['certificate_program_extra'] ?? null,
            'certificate_program_columns' => $_POST['certificate_program_columns'] ?? 2,
            'updated_by' => (int) (current_user()['id'] ?? 0) ?: null,
        ]));

        Session::flash('success', 'Certificado do curso atualizado.');
        redirect('/admin/education/course?id=' . $course['id'] . '#course-certificate');
    }

    public function requestCertificate(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->validateCsrf('/admin/education/course?id=' . $course['id']);
        $userId = (int) (current_user()['id'] ?? 0);

        if (!Education::userCanAccessCourse((int) $course['id'], $userId, false)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $status = Education::certificateStatusForCourseUser((int) $course['id'], $userId);
        if (empty($status['eligible'])) {
            Session::flash('error', 'O certificado ainda não foi liberado. Conclua o curso e confira a frequência exigida.');
            redirect('/admin/education/course?id=' . $course['id'] . '#course-certificate');
        }

        Education::issueCertificate((int) $course['id'], $userId);
        Logger::info('education.certificate_issued', 'Certificado emitido para o curso: ' . ($course['title'] ?? ''), $userId ?: null);
        redirect('/admin/education/certificate?id=' . $course['id']);
    }

    public function certificate(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $userId = (int) (current_user()['id'] ?? 0);

        if (!Education::userCanAccessCourse((int) $course['id'], $userId, false)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $certificate = Education::certificateForCourseUser((int) $course['id'], $userId);
        if (!$certificate) {
            Session::flash('error', 'Solicite o certificado quando o curso estiver concluido.');
            redirect('/admin/education/course?id=' . $course['id'] . '#course-certificate');
        }

        $status = Education::certificateStatusForCourseUser((int) $course['id'], $userId);
        $lessons = Education::lessonsForCourse((int) $course['id'], $userId);
        View::render('admin/education/certificate', [
            'course' => $course,
            'certificate' => $certificate,
            'certificateStatus' => $status,
            'certificateText' => $this->certificateText($course, $certificate, $status),
            'certificateProgram' => $this->certificateProgram(Education::modulesForCourse((int) $course['id']), $lessons),
            'certificatePeriod' => Education::certificatePeriodForCourseUser((int) $course['id'], $userId),
        ]);
    }

    public function requestCertificateNameChange(): void
    {
        Middleware::auth();
        $course = $this->courseFromQuery();
        $this->validateCsrf('/admin/education/certificate?id=' . $course['id']);
        $userId = (int) (current_user()['id'] ?? 0);

        if (!Education::userCanAccessCourse((int) $course['id'], $userId, false)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $certificate = Education::certificateForCourseUser((int) $course['id'], $userId);
        if (!$certificate) {
            Session::flash('error', 'Solicite o certificado antes de pedir alteração de nome.');
            redirect('/admin/education/course?id=' . $course['id'] . '#course-certificate');
        }

        $requestedName = trim((string) ($_POST['requested_student_name'] ?? ''));
        if ($requestedName === '' || mb_strlen($requestedName, 'UTF-8') < 5 || mb_strlen($requestedName, 'UTF-8') > 180) {
            Session::flash('error', 'Informe o nome completo para o certificado.');
            redirect('/admin/education/certificate?id=' . $course['id']);
        }

        Education::requestCertificateNameChange((int) $course['id'], $userId, $requestedName);
        Logger::info('education.certificate_name_change_requested', 'Alteração de nome solicitada no certificado: ' . ($course['title'] ?? ''), $userId ?: null);
        Session::flash('success', 'Solicitação enviada. O nome será atualizado no certificado após autorização.');
        redirect('/admin/education/certificate?id=' . $course['id']);
    }

    public function reviewCertificateNameChange(): void
    {
        Middleware::auth();
        $certificateId = filter_input(INPUT_GET, 'certificate_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $certificate = $certificateId ? Education::certificateById($certificateId) : null;
        if (!$certificate) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $course = Education::findCourse((int) $certificate['course_id']);
        $this->authorizeCourseManage($course);
        $this->validateCsrf('/admin/education/course?id=' . ($certificate['course_id'] ?? '') . '#course-certificate');

        $decision = (string) ($_POST['decision'] ?? '');
        if (!in_array($decision, ['approve', 'reject'], true)) {
            Session::flash('error', 'Informe se a alteração será aprovada ou recusada.');
            redirect('/admin/education/course?id=' . ($certificate['course_id'] ?? '') . '#course-certificate');
        }

        Education::reviewCertificateNameChange($certificateId, $decision === 'approve', (int) (current_user()['id'] ?? 0));
        Logger::info('education.certificate_name_change_reviewed', 'Solicitação de nome do certificado revisada: ' . $decision, current_user()['id'] ?? null);
        Session::flash('success', $decision === 'approve' ? 'Nome atualizado no certificado.' : 'Alteração de nome recusada.');
        redirect('/admin/education/course?id=' . ($certificate['course_id'] ?? '') . '#course-certificate');
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

    private function formFromQuery(): array
    {
        $id = filter_input(INPUT_GET, 'form_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $form = $id ? Education::findForm($id) : null;

        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $form;
    }

    private function formScopeFromRequest(): array
    {
        $lessonId = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT);
        if ($lessonId) {
            $lesson = Education::findLesson($lessonId);
            if (!$lesson) {
                http_response_code(404);
                View::render('errors/404');
                exit;
            }

            return [Education::findCourse((int) $lesson['course_id']), $lesson];
        }

        $course = $this->courseFromQuery();
        return [$course, null];
    }

    private function formQuestionsFromRequest(): array
    {
        $questions = is_array($_POST['questions'] ?? null) ? $_POST['questions'] : [];

        return array_values(array_filter(array_map(
            fn (mixed $question): string => trim((string) $question),
            $questions
        )));
    }

    private function formRedirect(array $form): string
    {
        return !empty($form['lesson_id'])
            ? '/admin/education/lesson?id=' . $form['lesson_id'] . '#lesson-forms'
            : '/admin/education/course?id=' . $form['course_id'] . '#course-forms';
    }

    private function storeAssignmentFile(int $blockId, int $userId): ?array
    {
        if (empty($_FILES['assignment_file']['name']) || ($_FILES['assignment_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($_FILES['assignment_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) $_FILES['assignment_file']['size'] > self::MAX_ASSIGNMENT_FILE_SIZE) {
            Session::flash('error', 'O arquivo da tarefa deve ter no maximo 25MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $extension = strtolower(pathinfo((string) $_FILES['assignment_file']['name'], PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::ALLOWED_ASSIGNMENT_EXTENSIONS, true)) {
            Session::flash('error', 'Tipo de arquivo nao permitido para tarefa.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $directory = dirname(__DIR__, 3) . '/storage/documents/education/submissions';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            Session::flash('error', 'A pasta de entregas nao esta gravavel.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $base = slugify(pathinfo((string) $_FILES['assignment_file']['name'], PATHINFO_FILENAME));
        $filename = 'tarefa-' . $blockId . '-' . $userId . '-' . $base . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target)) {
            Session::flash('error', 'Nao foi possivel salvar a entrega.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        return [
            'file_path' => '/storage/documents/education/submissions/' . $filename,
            'original_name' => (string) $_FILES['assignment_file']['name'],
            'size_bytes' => (int) $_FILES['assignment_file']['size'],
        ];
    }

    private function assignmentSubmissionById(int $id): ?array
    {
        return Education::findAssignmentSubmission($id);
    }

    private function lessonFromQuery(bool $required = true, bool $allowIdParam = true): ?array
    {
        $id = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT);
        if (!$id && $allowIdParam) {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        }
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
        return Auth::hasRole(['master', 'admin', 'admin-local', 'diretor']);
    }

    private function canAssignTeacher(): bool
    {
        return Auth::hasRole('master');
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

    private function lessonIsAvailable(array $lesson): bool
    {
        $availableAt = trim((string) ($lesson['available_at'] ?? ''));
        return $availableAt === '' || strtotime($availableAt) <= time();
    }

    private function certificateFieldsFromCourse(array $course): array
    {
        return [
            'certificate_enabled' => $course['certificate_enabled'] ?? 0,
            'certificate_title' => $course['certificate_title'] ?? null,
            'certificate_text' => $course['certificate_text'] ?? null,
            'certificate_background' => $course['certificate_background'] ?? null,
            'certificate_min_frequency' => $course['certificate_min_frequency'] ?? 0,
            'certificate_course_nature' => $course['certificate_course_nature'] ?? null,
            'certificate_modality' => $course['certificate_modality'] ?? null,
            'certificate_approval_criteria' => $course['certificate_approval_criteria'] ?? null,
            'certificate_legal_text' => $course['certificate_legal_text'] ?? null,
            'certificate_institution_name' => $course['certificate_institution_name'] ?? null,
            'certificate_institution_city' => $course['certificate_institution_city'] ?? null,
            'certificate_institution_cnpj' => $course['certificate_institution_cnpj'] ?? null,
            'certificate_institution_site' => $course['certificate_institution_site'] ?? null,
            'certificate_objectives' => $course['certificate_objectives'] ?? null,
            'certificate_competencies' => $course['certificate_competencies'] ?? null,
            'certificate_responsible_name' => $course['certificate_responsible_name'] ?? null,
            'certificate_responsible_credential' => $course['certificate_responsible_credential'] ?? null,
            'certificate_program_enabled' => $course['certificate_program_enabled'] ?? 1,
            'certificate_program_background' => $course['certificate_program_background'] ?? null,
            'certificate_program_extra' => $course['certificate_program_extra'] ?? null,
            'certificate_program_columns' => $course['certificate_program_columns'] ?? 2,
        ];
    }

    private function certificateProgram(array $modules, array $lessons): array
    {
        $program = [];
        $knownModuleIds = [];

        foreach ($modules as $module) {
            $moduleId = (int) ($module['id'] ?? 0);
            $knownModuleIds[$moduleId] = true;
            $program[$moduleId] = [
                'title' => $this->certificateProgramLine($module['title'] ?? 'Módulo', 90),
                'summary' => $this->certificateProgramLine($module['summary'] ?? '', 130),
                'lessons' => [],
            ];
        }

        foreach ($lessons as $lesson) {
            $moduleId = (int) ($lesson['module_id'] ?? 0);
            if ($moduleId <= 0 || !isset($knownModuleIds[$moduleId])) {
                $moduleId = 0;
                if (!isset($program[$moduleId])) {
                    $program[$moduleId] = [
                        'title' => 'Aulas complementares',
                        'summary' => '',
                        'lessons' => [],
                    ];
                }
            }

            $program[$moduleId]['lessons'][] = [
                'title' => $this->certificateProgramLine($lesson['title'] ?? 'Aula', 120),
            ];
        }

        return array_values(array_filter($program, static fn (array $module): bool => !empty($module['lessons']) || $module['summary'] !== ''));
    }

    private function certificateProgramLine(?string $value, int $limit): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?: '';
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
            return rtrim(mb_substr($text, 0, max(0, $limit - 3), 'UTF-8'), ' .,;:') . '...';
        }

        if (!function_exists('mb_strlen') && strlen($text) > $limit) {
            return rtrim(substr($text, 0, max(0, $limit - 3)), ' .,;:') . '...';
        }

        return $text;
    }

    private function certificateText(array $course, array $certificate, array $status): string
    {
        $issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : date('d/m/Y');
        $text = trim((string) ($course['certificate_text'] ?? ''));
        if ($text === '') {
            $text = 'Certificamos que {student_name} concluiu o curso {course_title}.';
        }

        return strtr($text, [
            '{student_name}' => (string) ($certificate['student_name'] ?? ''),
            '{course_title}' => (string) ($course['title'] ?? ''),
            '{teacher_name}' => (string) ($course['teacher_name'] ?? ''),
            '{frequency}' => (string) ((int) ($status['frequency'] ?? 0)) . '%',
            '{issued_at}' => $issuedAt,
            '{verification_code}' => (string) ($certificate['verification_code'] ?? ''),
        ]);
    }

    private function videoEmbedUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $match)) {
            return 'https://www.youtube.com/embed/' . $match[1] . '?enablejsapi=1';
        }

        return $url;
    }

    private function createCentralForumCopy(array $course, ?array $lesson, array $user, string $title, string $body): ?int
    {
        $area = Forum::findArea('estudantes') ?: Forum::findArea('professores');
        if (!$area) {
            return null;
        }

        [$centralTitle, $centralBody] = $this->centralForumPayload($course, $lesson, $title, $body);

        try {
            return Forum::createTopic([
                'area_id' => $area['id'],
                'category_id' => null,
                'user_id' => (int) ($user['id'] ?? 0),
                'title' => $centralTitle,
                'body' => $centralBody,
                'is_public' => false,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function updateCentralForumCopy(array $course, ?array $lesson, int $centralTopicId, string $title, string $body, int $userId): void
    {
        [$centralTitle, $centralBody] = $this->centralForumPayload($course, $lesson, $title, $body);

        try {
            Forum::updateTopic($centralTopicId, $centralTitle, $centralBody, $userId);
        } catch (\Throwable) {
            return;
        }
    }

    private function centralForumPayload(array $course, ?array $lesson, string $title, string $body): array
    {
        $prefix = $lesson ? 'Aula: ' . ($lesson['title'] ?? '') : 'Curso: ' . ($course['title'] ?? '');
        $centralTitle = trim($prefix . ' - ' . $title);
        $centralBody = '<p><strong>Fórum vinculado ao ensino.</strong></p>'
            . '<p><strong>Curso:</strong> ' . e($course['title'] ?? '') . '</p>'
            . ($lesson ? '<p><strong>Aula:</strong> ' . e($lesson['title'] ?? '') . '</p>' : '')
            . '<hr>'
            . $body;

        return [$centralTitle, $centralBody];
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

    private function courseCoverFromRequest(?string $existing): ?string
    {
        $coverUrl = trim((string) ($_POST['cover_image'] ?? ''));

        if (empty($_FILES['course_cover']['name']) || ($_FILES['course_cover']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $coverUrl !== '' ? $coverUrl : null;
        }

        if (($_FILES['course_cover']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Não foi possível enviar a capa do curso.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education/manage');
        }

        $tmpName = (string) ($_FILES['course_cover']['tmp_name'] ?? '');
        $size = (int) ($_FILES['course_cover']['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        if (!$imageInfo || !isset($allowedTypes[$imageInfo[2] ?? 0]) || $size <= 0 || $size > self::MAX_COURSE_COVER_SIZE) {
            Session::flash('error', 'Use uma capa JPG, PNG, WEBP ou GIF com até 8MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education/manage');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/education';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            Session::flash('error', 'A pasta de imagens do curso não está gravável.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education/manage');
        }

        $filename = 'course-' . bin2hex(random_bytes(12)) . '.' . $allowedTypes[$imageInfo[2]];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            Session::flash('error', 'Não foi possível salvar a capa do curso.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education/manage');
        }

        return '/public/uploads/education/' . $filename;
    }

    private function certificateBackgroundFromRequest(?string $existing, string $fieldName = 'certificate_background', string $uploadFieldName = 'certificate_background_upload'): ?string
    {
        $background = trim((string) ($_POST[$fieldName] ?? ''));

        if (empty($_FILES[$uploadFieldName]['name']) || ($_FILES[$uploadFieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $background !== '' ? $background : $existing;
        }

        if (($_FILES[$uploadFieldName]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Nao foi possivel enviar o fundo do certificado.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $tmpName = (string) ($_FILES[$uploadFieldName]['tmp_name'] ?? '');
        $size = (int) ($_FILES[$uploadFieldName]['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];

        if (!$imageInfo || !isset($allowedTypes[$imageInfo[2] ?? 0]) || $size <= 0 || $size > self::MAX_CERTIFICATE_BACKGROUND_SIZE) {
            Session::flash('error', 'Use um fundo JPG, PNG ou WEBP com ate 12MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/education';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            Session::flash('error', 'A pasta de imagens do certificado nao esta gravavel.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        $filename = 'certificate-' . bin2hex(random_bytes(12)) . '.' . $allowedTypes[$imageInfo[2]];
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($tmpName, $target)) {
            Session::flash('error', 'Nao foi possivel salvar o fundo do certificado.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/education');
        }

        return '/public/uploads/education/' . $filename;
    }
}
