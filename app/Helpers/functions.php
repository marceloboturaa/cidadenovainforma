<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $config = require dirname(__DIR__, 2) . '/config/app.php';
    $baseUrl = $config['base_url'];

    if (!$baseUrl) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $trustedHosts = $config['trusted_hosts'] ?? [];

        if ($trustedHosts && !in_array(strtolower(preg_replace('/:\d+$/', '', $host) ?: ''), array_map('strtolower', $trustedHosts), true)) {
            $host = $trustedHosts[0] ?? 'localhost';
        }

        if (!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
            $host = 'localhost';
        }

        $basePath = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $baseUrl = $scheme . '://' . $host . ($basePath ? '/' . $basePath : '');
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

function media_url(string $path = ''): string
{
    $path = trim($path);

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '//')) {
        return 'https:' . $path;
    }

    return url($path);
}

function media_available(?string $path): bool
{
    $path = normalize_media_path($path) ?? '';

    if ($path === '') {
        return false;
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return true;
    }

    $localPath = parse_url($path, PHP_URL_PATH);

    if (!is_string($localPath) || $localPath === '') {
        return false;
    }

    $localPath = str_replace('\\', '/', rawurldecode($localPath));

    if (str_contains($localPath, '../')) {
        return false;
    }

    $root = dirname(__DIR__, 2);
    $relativePath = ltrim($localPath, '/');
    $candidates = [
        $root . '/' . $relativePath,
        $root . '/public/' . $relativePath,
    ];

    foreach (array_unique($candidates) as $candidate) {
        if (is_file($candidate)) {
            return true;
        }
    }

    return false;
}

function first_article_image(?string $content): ?string
{
    $content = (string) $content;

    if ($content === '') {
        return null;
    }

    if (!preg_match_all('/<img\b[^>]*\bsrc\s*=\s*("|\')([^"\']+)\1/i', $content, $matches)) {
        return null;
    }

    foreach ($matches[2] as $src) {
        $src = normalize_media_path($src);

        if ($src !== null && media_available($src)) {
            return $src;
        }
    }

    return null;
}

function news_public_image(array $news): ?string
{
    $cover = normalize_media_path($news['cover_image'] ?? null);

    if ($cover !== null && media_available($cover)) {
        return $cover;
    }

    return first_article_image($news['content'] ?? '');
}

function normalize_media_path(?string $path): ?string
{
    $path = trim((string) $path);

    if ($path === '') {
        return null;
    }

    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, 'public/uploads/')) {
        return '/' . $path;
    }

    if (str_starts_with($path, 'uploads/')) {
        return '/public/' . $path;
    }

    return $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_field(): string
{
    return Csrf::field();
}

function flash(string $key): ?string
{
    return Session::flash($key);
}

function current_user(): ?array
{
    return Auth::user();
}

function slugify(string $value): string
{
    $value = trim($value);
    $value = strtr($value, [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ç' => 'C', 'ç' => 'c', 'Ñ' => 'N', 'ñ' => 'n',
    ]);
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = strtolower($converted ?: $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    $value = trim($value, '-');

    return $value !== '' ? $value : bin2hex(random_bytes(4));
}

function selected(string $current, string $expected): string
{
    return $current === $expected ? 'selected' : '';
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

function link_line(string $value): array
{
    $value = trim($value);

    if ($value === '') {
        return ['label' => '', 'url' => null];
    }

    if (preg_match('/^(.+?)\s*\|\s*(https?:\/\/\S+)$/i', $value, $match)) {
        return ['label' => trim($match[1]), 'url' => trim($match[2])];
    }

    if (preg_match('/^(https?:\/\/\S+)$/i', $value, $match)) {
        return ['label' => preg_replace('#^https?://#i', '', rtrim($match[1], '/')), 'url' => $match[1]];
    }

    if (preg_match('/^(https?:\/\/\S+)\s+(.+)$/i', $value, $match)) {
        return ['label' => trim($match[2]), 'url' => trim($match[1])];
    }

    return ['label' => $value, 'url' => null];
}

function clean_article_html(string $html): string
{
    $html = trim($html);
    $html = preg_replace('/<\/div>\s*<div([^>]*)>/i', '</p><p$1>', $html) ?? $html;
    $html = preg_replace('/<div([^>]*)>/i', '<p$1>', $html) ?? $html;
    $html = preg_replace('/<\/div>/i', '</p>', $html) ?? $html;

    $allowed = '<p><br><strong><b><em><i><u><h2><h3><blockquote><ul><ol><li><a><img><iframe><video><audio><span>';
    $html = strip_tags($html, $allowed);

    $html = preg_replace('/\son[a-z]+\s*=\s*("|\').*?\1/i', '', $html) ?? $html;
    $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;

    $html = preg_replace_callback('/<a\s+([^>]+)>/i', function (array $matches): string {
        $attrs = $matches[1];
        preg_match('/href\s*=\s*("|\')([^"\']+)\1/i', $attrs, $href);
        $url = $href[2] ?? '#';

        if (!preg_match('#^(https?://|mailto:|/)#i', $url)) {
            $url = '#';
        }

        return '<a href="' . e($url) . '" target="_blank" rel="noopener">';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<span\s+([^>]*)>/i', function (array $matches): string {
        preg_match('/class\s*=\s*("|\')([^"\']+)\1/i', $matches[1], $class);
        $classes = preg_split('/\s+/', trim($class[2] ?? '')) ?: [];
        $allowedClasses = ['text-color-ink', 'text-color-gray', 'text-color-red', 'text-color-orange', 'text-color-gold', 'text-color-green', 'text-color-teal', 'text-color-blue'];
        $safeClasses = array_values(array_intersect($classes, $allowedClasses));

        return $safeClasses ? '<span class="' . e(implode(' ', $safeClasses)) . '">' : '<span>';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<(p|h2|h3|blockquote|li)\s+([^>]*)>/i', function (array $matches): string {
        $tag = strtolower($matches[1]);
        preg_match('/class\s*=\s*("|\')([^"\']+)\1/i', $matches[2], $class);
        preg_match('/(?:text-align\s*:\s*|align\s*=\s*("|\')?)(left|center|right|justify)/i', $matches[2], $align);
        $classes = preg_split('/\s+/', trim($class[2] ?? '')) ?: [];
        $allowedClasses = ['text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify'];
        $safeClasses = array_values(array_intersect($classes, $allowedClasses));

        if (!empty($align[2])) {
            $safeClasses[] = 'text-align-' . strtolower($align[2]);
        }

        $safeClasses = array_values(array_unique($safeClasses));

        return $safeClasses ? '<' . $tag . ' class="' . e(implode(' ', $safeClasses)) . '">' : '<' . $tag . '>';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<img\s+([^>]+)>/i', function (array $matches): string {
        $attrs = $matches[1];
        preg_match('/src\s*=\s*("|\')([^"\']+)\1/i', $attrs, $src);
        preg_match('/alt\s*=\s*("|\')([^"\']*)\1/i', $attrs, $alt);
        $url = normalize_media_path($src[2] ?? '') ?? '';

        if (!preg_match('#^(https?://|/)#i', $url)) {
            return '';
        }

        if (!media_available($url)) {
            return '';
        }

        return '<img src="' . e($url) . '" alt="' . e($alt[2] ?? '') . '" loading="lazy" onerror="this.remove()">';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<iframe\s+([^>]*)>\s*<\/iframe>/i', function (array $matches): string {
        preg_match('/src\s*=\s*("|\')([^"\']+)\1/i', $matches[1], $src);
        $url = $src[2] ?? '';

        if (!preg_match('#^https://(www\.youtube\.com/embed/|player\.vimeo\.com/video/)#i', $url)) {
            return '';
        }

        return '<iframe src="' . e($url) . '" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<(video|audio)\s+([^>]*)>\s*(?:<\/\1>)?/i', function (array $matches): string {
        $tag = strtolower($matches[1]);
        preg_match('/src\s*=\s*("|\')([^"\']+)\1/i', $matches[2], $src);
        $url = $src[2] ?? '';
        $pattern = $tag === 'video'
            ? '#^(https?://|/).+\.(mp4|webm)(\?.*)?$#i'
            : '#^(https?://|/).+\.(mp3|ogg|wav)(\?.*)?$#i';

        if (!preg_match($pattern, $url)) {
            return '';
        }

        $playsInline = $tag === 'video' ? ' playsinline' : '';

        return '<' . $tag . ' controls' . $playsInline . ' src="' . e($url) . '"></' . $tag . '>';
    }, $html) ?? $html;

    $html = preg_replace('/<(br|strong|b|em|i|u|ul|ol)\s+[^>]*>/i', '<$1>', $html) ?? $html;
    $html = preg_replace('/<p>\s*(?:&nbsp;|\s|<br>)*<\/p>/i', '', $html) ?? $html;
    $html = preg_replace('/(?:<br>\s*){3,}/i', '<br><br>', $html) ?? $html;

    return $html;
}

function article_html(string $content): string
{
    if ($content === strip_tags($content)) {
        $paragraphs = preg_split('/\R{2,}/', trim($content)) ?: [];
        $html = array_map(function (string $paragraph): string {
            return '<p>' . nl2br(e(trim($paragraph))) . '</p>';
        }, array_filter($paragraphs, fn (string $paragraph): bool => trim($paragraph) !== ''));

        return implode('', $html);
    }

    return clean_article_html($content);
}
