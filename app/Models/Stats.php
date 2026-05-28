<?php

namespace App\Models;

use App\Core\Database;
use App\Models\News;
use App\Models\UserPresence;

class Stats
{
    private const FULL_DASHBOARD_ROLES = ['master', 'admin', 'admin_local'];
    private const EDITORIAL_DASHBOARD_ROLES = ['jornalista', 'colunista'];

    public static function dashboard(?array $user = null): array
    {
        $db = Database::connection();
        $canViewSensitiveInfo = self::canViewSensitiveInfo($user);
        $canViewEditorialInfo = $canViewSensitiveInfo || self::canViewEditorialInfo($user);
        $logs = $canViewSensitiveInfo ? self::recentLogs() : [];
        $onlineUsers = $canViewSensitiveInfo ? UserPresence::onlineUsers() : [];

        return [
            'users' => $canViewSensitiveInfo ? (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() : 0,
            'online_users_count' => count($onlineUsers),
            'online_users' => $onlineUsers,
            'online_window_minutes' => UserPresence::onlineWindowMinutes(),
            'news' => $canViewEditorialInfo ? (int) $db->query('SELECT COUNT(*) FROM news')->fetchColumn() : 0,
            'pending_news' => $canViewEditorialInfo ? (int) $db->query("SELECT COUNT(*) FROM news WHERE status = 'pending'")->fetchColumn() : 0,
            'comments' => $canViewSensitiveInfo ? (int) $db->query('SELECT COUNT(*) FROM comments')->fetchColumn() : 0,
            'published_news' => $canViewEditorialInfo ? (int) $db->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn() : 0,
            'draft_news' => $canViewEditorialInfo ? (int) $db->query("SELECT COUNT(*) FROM news WHERE status = 'draft'")->fetchColumn() : 0,
            'status_counts' => $canViewEditorialInfo ? News::statusCounts() : [],
            'recent_news' => $canViewEditorialInfo ? $db->query(
                'SELECT news.id, news.title, news.status, news.updated_at, users.name AS author_name
                 FROM news
                 INNER JOIN users ON users.id = news.author_id
                 ORDER BY news.updated_at DESC
                 LIMIT 8'
            )->fetchAll() : [],
            'access_days' => $canViewSensitiveInfo ? $db->query(
                'SELECT DATE(created_at) AS day, COUNT(*) AS total
                 FROM access_logs
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY day ASC'
            )->fetchAll() : [],
            'logs' => $logs,
        ];
    }

    public static function canViewSensitiveInfo(?array $user): bool
    {
        return (bool) array_intersect(self::roleSlugs($user), self::FULL_DASHBOARD_ROLES);
    }

    public static function canViewEditorialInfo(?array $user): bool
    {
        return (bool) array_intersect(self::roleSlugs($user), self::EDITORIAL_DASHBOARD_ROLES);
    }

    private static function roleSlugs(?array $user): array
    {
        if (!$user) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? '')))));
    }

    private static function recentLogs(): array
    {
        $db = Database::connection();

        return $db->query(
            'SELECT logs.*, users.name AS user_name
             FROM logs
             LEFT JOIN users ON users.id = logs.user_id
             ORDER BY logs.created_at DESC
             LIMIT 8'
        )->fetchAll();
    }
}
