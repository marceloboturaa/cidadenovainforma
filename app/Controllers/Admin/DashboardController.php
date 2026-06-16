<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Announcement;
use App\Models\Education;
use App\Models\SiteSetting;
use App\Models\Stats;

class DashboardController
{
    public function index(): void
    {
        Middleware::auth();
        $user = current_user();
        $roleSlugs = array_values(array_filter(explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? ''))));
        $isStudent = in_array('estudante', $roleSlugs, true);
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
            'studentResponses' => $isStudent ? Education::studentResponsesForDashboard((int) ($user['id'] ?? 0)) : [],
            'homeNotice' => $canManageHomeNotice ? [
                'enabled' => SiteSetting::get('home_notice_enabled', '0'),
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
        $title = trim((string) ($_POST['home_notice_title'] ?? ''));
        $text = trim((string) ($_POST['home_notice_text'] ?? ''));
        $url = trim((string) ($_POST['home_notice_url'] ?? ''));
        $label = trim((string) ($_POST['home_notice_label'] ?? ''));
        $limit = static function (string $value, int $length): string {
            return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
        };

        SiteSetting::set('home_notice_enabled', $enabled);
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
}
