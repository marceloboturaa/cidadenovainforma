<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Core\WhatsAppNotifier;
use App\Models\Announcement;
use App\Models\Education;
use App\Models\SiteSetting;
use App\Models\Stats;
use App\Models\UserPresence;

class DashboardController
{
    public function index(): void
    {
        Middleware::auth();
        $user = current_user();
        $roleSlugs = array_values(array_filter(explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? ''))));
        $isStudent = in_array('estudante', $roleSlugs, true);
        $isTeacher = !$isStudent && (in_array('professor', $roleSlugs, true) || Auth::can('education.teach'));
        $canViewSensitiveDashboard = Stats::canViewSensitiveInfo($user);
        $canViewEditorialDashboard = $canViewSensitiveDashboard || Stats::canViewEditorialInfo($user);
        $canManageHomeNotice = Auth::hasRole(['master', 'admin']) || Auth::can('home_notice.manage');
        $canManageAnnouncements = Announcement::canManage($user);

        View::render('admin/dashboard', [
            'stats' => Stats::dashboard($user),
            'showsAllLogs' => $canViewSensitiveDashboard,
            'canViewSensitiveDashboard' => $canViewSensitiveDashboard,
            'canViewEditorialDashboard' => $canViewEditorialDashboard,
            'canManageHomeNotice' => $canManageHomeNotice,
            'canManageAnnouncements' => $canManageAnnouncements,
            'isStudent' => $isStudent,
            'isTeacher' => $isTeacher,
            'teacherCourses' => $isTeacher ? Education::coursesForManagement((int) ($user['id'] ?? 0)) : [],
            'studentCourses' => $isStudent ? Education::coursesForUser((int) ($user['id'] ?? 0)) : [],
            'studentResponses' => $isStudent ? Education::studentResponsesForDashboard((int) ($user['id'] ?? 0)) : [],
            'studentAnnouncements' => $isStudent ? Announcement::unreadForUser((int) ($user['id'] ?? 0), 8) : [],
            'homeNotice' => $canManageHomeNotice ? [
                'enabled' => SiteSetting::get('home_notice_enabled', '0'),
                'courses_enabled' => SiteSetting::get('home_courses_enabled', '1'),
                'course_highlights_enabled' => SiteSetting::get('home_course_highlights_enabled', '1'),
                'courses_show_images' => SiteSetting::get('home_courses_show_images', '1'),
                'courses_show_lesson_count' => SiteSetting::get('home_courses_show_lesson_count', '1'),
                'courses_show_summary' => SiteSetting::get('home_courses_show_summary', '1'),
                'courses_show_teacher' => SiteSetting::get('home_courses_show_teacher', '1'),
                'courses_show_button' => SiteSetting::get('home_courses_show_button', '1'),
                'courses_position' => SiteSetting::get('home_courses_position', 'after_news'),
                'title' => SiteSetting::get('home_notice_title', ''),
                'text' => SiteSetting::get('home_notice_text', ''),
                'url' => SiteSetting::get('home_notice_url', ''),
                'label' => SiteSetting::get('home_notice_label', ''),
            ] : [],
        ]);
    }

    public function homeNotice(): void
    {
        Middleware::auth();
        if (!Auth::hasRole(['master', 'admin']) && !Auth::can('home_notice.manage')) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin');
        }

        $enabled = !empty($_POST['home_notice_enabled']) ? '1' : '0';
        $coursesEnabled = !empty($_POST['home_courses_enabled']) ? '1' : '0';
        $courseHighlightsEnabled = !empty($_POST['home_course_highlights_enabled']) ? '1' : '0';
        $coursesShowImages = !empty($_POST['home_courses_show_images']) ? '1' : '0';
        $coursesShowLessonCount = !empty($_POST['home_courses_show_lesson_count']) ? '1' : '0';
        $coursesShowSummary = !empty($_POST['home_courses_show_summary']) ? '1' : '0';
        $coursesShowTeacher = !empty($_POST['home_courses_show_teacher']) ? '1' : '0';
        $coursesShowButton = !empty($_POST['home_courses_show_button']) ? '1' : '0';
        $coursesPosition = in_array($_POST['home_courses_position'] ?? '', ['before_news', 'after_news'], true)
            ? $_POST['home_courses_position']
            : 'after_news';
        $title = trim((string) ($_POST['home_notice_title'] ?? ''));
        $text = trim((string) ($_POST['home_notice_text'] ?? ''));
        $url = trim((string) ($_POST['home_notice_url'] ?? ''));
        $label = trim((string) ($_POST['home_notice_label'] ?? ''));
        $limit = static function (string $value, int $length): string {
            return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
        };

        SiteSetting::set('home_notice_enabled', $enabled);
        SiteSetting::set('home_courses_enabled', $coursesEnabled);
        SiteSetting::set('home_course_highlights_enabled', $courseHighlightsEnabled);
        SiteSetting::set('home_courses_show_images', $coursesShowImages);
        SiteSetting::set('home_courses_show_lesson_count', $coursesShowLessonCount);
        SiteSetting::set('home_courses_show_summary', $coursesShowSummary);
        SiteSetting::set('home_courses_show_teacher', $coursesShowTeacher);
        SiteSetting::set('home_courses_show_button', $coursesShowButton);
        SiteSetting::set('home_courses_position', $coursesPosition);
        SiteSetting::set('home_notice_title', $limit($title, 120));
        SiteSetting::set('home_notice_text', $limit($text, 260));
        SiteSetting::set('home_notice_url', $limit($url, 255));
        SiteSetting::set('home_notice_label', $limit($label, 60));

        Session::flash('success', 'Aviso do topo do site atualizado.');
        redirect('/admin');
    }

    public function announcement(): void
    {
        Middleware::auth();
        $user = current_user();
        if (!Announcement::canManage($user)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin');
        }

        $title = trim((string) ($_POST['announcement_title'] ?? ''));
        $body = trim((string) ($_POST['announcement_body'] ?? ''));

        if ($title === '' || $body === '') {
            Session::flash('error', 'Informe titulo e mensagem do aviso.');
            redirect('/admin');
        }

        Announcement::create([
            'title' => $title,
            'body' => $body,
            'url' => $_POST['announcement_url'] ?? null,
            'button_label' => $_POST['announcement_button_label'] ?? null,
            'created_by' => $user['id'] ?? null,
        ]);

        if (!empty($_POST['send_whatsapp'])) {
            $result = $this->sendAnnouncementToWhatsApp($title, $body, (string) ($_POST['announcement_url'] ?? ''), $user);

            Session::flash(
                'success',
                'Aviso enviado no painel. WhatsApp: ' . $result['sent'] . ' enviado(s), ' . $result['failed'] . ' falha(s), ' . $result['total'] . ' contato(s) encontrado(s).'
            );
            redirect('/admin');
        }

        Session::flash('success', 'Aviso enviado para todos os usuarios do painel.');
        redirect('/admin');
    }

    public function readAnnouncement(): void
    {
        Middleware::auth();
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin');
        }

        $id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
        $user = current_user();
        if ($id && $user) {
            Announcement::markRead($id, (int) $user['id']);
        }

        $returnTo = (string) ($_POST['return_to'] ?? '/admin');
        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            $returnTo = '/admin';
        }

        redirect($returnTo);
    }

    public function presencePing(): void
    {
        Middleware::auth();
        $user = current_user();
        if ($user) {
            UserPresence::touch((int) $user['id']);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    private function sendAnnouncementToWhatsApp(string $title, string $body, string $url, ?array $user): array
    {
        $recipients = Announcement::whatsappRecipients();
        $message = "Cidade Nova Informa - Aviso\n\n" . $title . "\n\n" . $body;
        $url = trim($url);

        if ($url !== '') {
            $href = preg_match('#^https?://#i', $url) ? $url : url($url);
            $message .= "\n\nAcesse: " . $href;
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            if (WhatsAppNotifier::sendText($recipient['phone'] ?? '', $message)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        Logger::info(
            'announcement.whatsapp_sent',
            'Aviso enviado por WhatsApp. Destinatarios: ' . count($recipients) . '. Enviados: ' . $sent . '. Falhas: ' . $failed . '.',
            isset($user['id']) ? (int) $user['id'] : null
        );

        return [
            'total' => count($recipients),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }
}
