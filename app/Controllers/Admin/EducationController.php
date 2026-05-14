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
    public function index(): void
    {
        Middleware::auth();
        $user = current_user();
        $canManage = $this->canManageAll();
        $courses = $this->canTeach() && !$canManage
            ? Education::coursesForManagement((int) $user['id'])
            : Education::coursesForUser((int) $user['id'], $canManage);

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

        View::render('admin/education/manage', [
            'courses' => Education::coursesForManagement($this->canManageAll() ? null : (int) (current_user()['id'] ?? 0)),
            'editing' => $editing,
            'users' => User::activeForAccessLists(),
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

        if (!Education::userCanAccessCourse((int) $course['id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        View::render('admin/education/course', [
            'course' => $course,
            'lessons' => Education::lessonsForCourse((int) $course['id'], (int) $user['id']),
            'canManage' => $canManage,
            'editingLesson' => $this->lessonFromQuery(false),
        ]);
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

        Education::createLesson(array_merge($_POST, ['course_id' => $course['id']]));
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

        Education::updateLesson((int) $lesson['id'], array_merge($_POST, ['course_id' => $lesson['course_id']]));
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
        $canManage = $this->canManage();

        if (!Education::userCanAccessCourse((int) $lesson['course_id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        View::render('admin/education/lesson', [
            'lesson' => $lesson,
            'course' => Education::findCourse((int) $lesson['course_id']),
            'videoEmbedUrl' => $this->videoEmbedUrl((string) ($lesson['video_url'] ?? '')),
        ]);
    }

    public function progress(): void
    {
        Middleware::auth();
        $this->validateCsrf('/admin/education');
        $user = current_user();
        $lesson = $this->lessonFromQuery();
        $canManage = $this->canManage();

        if (!Education::userCanAccessCourse((int) $lesson['course_id'], (int) $user['id'], $canManage)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        Education::markLesson((int) $lesson['id'], (int) $user['id'], ($_POST['completed'] ?? '') === '1');
        Session::flash('success', 'Progresso atualizado.');
        redirect('/admin/education/lesson?id=' . $lesson['id']);
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

    private function canManage(): bool
    {
        return $this->canManageAll() || $this->canTeach();
    }

    private function canManageAll(): bool
    {
        $user = Auth::user();
        $role = $user['role_slug'] ?? '';

        return Auth::can('education.manage') || in_array($role, ['master', 'admin', 'equipe'], true);
    }

    private function canTeach(): bool
    {
        return Auth::can('education.teach') || (Auth::user()['role_slug'] ?? '') === 'professor';
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

    private function validateCsrf(string $redirectTo): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($redirectTo);
        }
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
}
