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
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
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
    $html = preg_replace('/<\/div>\s*<div[^>]*>/i', '</p><p>', $html) ?? $html;
    $html = preg_replace('/<div[^>]*>/i', '<p>', $html) ?? $html;
    $html = preg_replace('/<\/div>/i', '</p>', $html) ?? $html;

    $allowed = '<p><br><strong><b><em><i><u><h2><h3><blockquote><ul><ol><li><a><img>';
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

    $html = preg_replace_callback('/<img\s+([^>]+)>/i', function (array $matches): string {
        $attrs = $matches[1];
        preg_match('/src\s*=\s*("|\')([^"\']+)\1/i', $attrs, $src);
        preg_match('/alt\s*=\s*("|\')([^"\']*)\1/i', $attrs, $alt);
        $url = $src[2] ?? '';

        if (!preg_match('#^(https?://|/)#i', $url)) {
            return '';
        }

        return '<img src="' . e($url) . '" alt="' . e($alt[2] ?? '') . '" loading="lazy">';
    }, $html) ?? $html;

    $html = preg_replace('/<(p|br|strong|b|em|i|u|h2|h3|blockquote|ul|ol|li)\s+[^>]*>/i', '<$1>', $html) ?? $html;
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
