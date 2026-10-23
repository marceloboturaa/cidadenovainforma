<?php
namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;

class NewsRedirect
{
    public static function ensureSchema(): void
    {
        Database::connection()->exec('CREATE TABLE IF NOT EXISTS news_redirects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_path VARCHAR(500) NOT NULL UNIQUE,
            news_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public static function sourcePath(string $input): string
    {
        $input = trim($input);
        $parts = parse_url($input);
        $base = parse_url(url('/'));
        if (!$parts || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user'])
            || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true))
            || (isset($parts['host']) && strcasecmp($parts['host'], $base['host'] ?? '') !== 0)) {
            throw new InvalidArgumentException('Use um link deste site, sem parâmetros ou fragmentos.');
        }
        $path = '/' . trim($parts['path'] ?? '', '/');
        $basePath = rtrim($base['path'] ?? '', '/');
        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }
        $decoded = rawurldecode($path);
        if ($path === '/' || strlen($path) > 500 || preg_match('/[\x00-\x20\x7f\\\\?#]/', $decoded)
            || preg_match('#(^|/)\.{1,2}(/|$)#', $decoded)
            || preg_match('#^/(admin|login|logout|register|api|public|uploads|assets|forgot-password|reset-password)(/|$)#i', $decoded)) {
            throw new InvalidArgumentException('Informe um caminho público válido para o link antigo.');
        }
        return $path;
    }

    public static function all(): array
    {
        self::ensureSchema();
        return Database::connection()->query('SELECT r.*, n.title, n.slug, n.status FROM news_redirects r LEFT JOIN news n ON n.id = r.news_id ORDER BY r.updated_at DESC')->fetchAll();
    }

    public static function save(string $path, int $newsId): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('INSERT INTO news_redirects (source_path, news_id, created_at, updated_at) VALUES (:path, :news, NOW(), NOW()) ON DUPLICATE KEY UPDATE news_id = VALUES(news_id), updated_at = NOW()');
        $stmt->execute(['path' => $path, 'news' => $newsId]);
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('DELETE FROM news_redirects WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function destination(string $path): ?string
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT n.slug FROM news_redirects r INNER JOIN news n ON n.id = r.news_id WHERE r.source_path = :path AND n.status = "published" LIMIT 1');
        $stmt->execute(['path' => $path]);
        $slug = $stmt->fetchColumn();
        $target = $slug ? '/noticia/' . $slug : null;
        return $target !== $path ? $target : null;
    }

    public static function redirectMissing(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { return false; }
        try {
            $path = self::sourcePath(explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0]);
        } catch (InvalidArgumentException $e) { return false; }
        $destination = self::destination($path);
        if (!$destination) { return false; }
        header('Location: ' . url($destination), true, 302);
        return true;
    }
}
