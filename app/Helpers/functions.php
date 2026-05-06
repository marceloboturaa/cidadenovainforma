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
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
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

function clean_article_html(string $html): string
{
    $html = trim($html);
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

    return $html;
}

function article_html(string $content): string
{
    if ($content === strip_tags($content)) {
        return nl2br(e($content));
    }

    return clean_article_html($content);
}
