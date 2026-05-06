<?php

namespace App\Models;

use App\Core\Database;
use App\Models\News;

class Stats
{
    public static function dashboard(): array
    {
        $db = Database::connection();

        return [
            'users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'news' => (int) $db->query('SELECT COUNT(*) FROM news')->fetchColumn(),
            'pending_news' => (int) $db->query("SELECT COUNT(*) FROM news WHERE status = 'pending'")->fetchColumn(),
            'comments' => (int) $db->query('SELECT COUNT(*) FROM comments')->fetchColumn(),
            'published_news' => (int) $db->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn(),
            'draft_news' => (int) $db->query("SELECT COUNT(*) FROM news WHERE status = 'draft'")->fetchColumn(),
            'status_counts' => News::statusCounts(),
            'recent_news' => $db->query(
                'SELECT news.id, news.title, news.status, news.updated_at, users.name AS author_name
                 FROM news
                 INNER JOIN users ON users.id = news.author_id
                 ORDER BY news.updated_at DESC
                 LIMIT 8'
            )->fetchAll(),
            'access_days' => $db->query(
                'SELECT DATE(created_at) AS day, COUNT(*) AS total
                 FROM access_logs
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY day ASC'
            )->fetchAll(),
            'logs' => $db->query(
                'SELECT logs.*, users.name AS user_name
                 FROM logs
                 LEFT JOIN users ON users.id = logs.user_id
                 ORDER BY logs.created_at DESC
                 LIMIT 8'
            )->fetchAll(),
        ];
    }
}
