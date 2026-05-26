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
    $requestHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $configuredHost = is_string($baseUrl) && $baseUrl !== '' ? (parse_url($baseUrl, PHP_URL_HOST) ?: '') : '';
    $configuredIsLocal = in_array(strtolower($configuredHost), ['localhost', '127.0.0.1'], true);
    $requestIsLocal = in_array(strtolower(preg_replace('/:\d+$/', '', $requestHost) ?: ''), ['localhost', '127.0.0.1'], true);

    if ($baseUrl && $configuredIsLocal && $requestHost !== '' && !$requestIsLocal) {
        $baseUrl = '';
    }

    if (!$baseUrl) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $requestHost ?: 'localhost';
        $trustedHosts = $config['trusted_hosts'] ?? [];
        $normalizedTrustedHosts = array_map('strtolower', $trustedHosts);
        $trustedHostsAreLocalOnly = $normalizedTrustedHosts
            && !array_diff($normalizedTrustedHosts, ['localhost', '127.0.0.1']);

        if ($trustedHosts && !$trustedHostsAreLocalOnly && !in_array(strtolower(preg_replace('/:\d+$/', '', $host) ?: ''), $normalizedTrustedHosts, true)) {
            $host = $trustedHosts[0] ?? 'localhost';
        }

        if (!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
            $host = 'localhost';
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = in_array($scriptDir, ['.', '/'], true) ? '' : trim($scriptDir, '/');
        $baseUrl = $scheme . '://' . $host . ($basePath ? '/' . $basePath : '');
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

function versioned_asset_url(string $path): string
{
    $path = '/' . ltrim($path, '/');
    $filePath = dirname(__DIR__, 2) . $path;
    $version = is_file($filePath) ? filemtime($filePath) : time();

    return url($path) . '?v=' . $version;
}

function media_url(string $path = ''): string
{
    $path = normalize_media_path($path) ?? '';

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

    $localPath = normalize_media_path(str_replace('\\', '/', rawurldecode($localPath))) ?? '';

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
    $path = trim(html_entity_decode((string) $path, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    if ($path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        $parts = parse_url($path);
        $urlHost = strtolower((string) ($parts['host'] ?? ''));
        $urlPath = (string) ($parts['path'] ?? '');
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $knownHosts = array_filter([
            parse_url($config['base_url'] ?? '', PHP_URL_HOST) ?: null,
            $_SERVER['HTTP_HOST'] ?? null,
            $_SERVER['SERVER_NAME'] ?? null,
            ...($config['trusted_hosts'] ?? []),
            'localhost',
            '127.0.0.1',
        ]);
        $knownHosts = array_map(
            fn (string $host): string => strtolower(preg_replace('/:\d+$/', '', $host) ?: $host),
            $knownHosts
        );
        $normalizedUrlHost = preg_replace('/:\d+$/', '', $urlHost) ?: $urlHost;

        if ($urlPath !== '' && in_array($normalizedUrlHost, array_unique($knownHosts), true)) {
            $path = $urlPath;
        } else {
            return $path;
        }
    } elseif (str_starts_with($path, '//')) {
        return $path;
    }

    $config = require dirname(__DIR__, 2) . '/config/app.php';
    $basePaths = [
        parse_url($config['base_url'] ?? '', PHP_URL_PATH),
        str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')),
    ];

    foreach (array_unique(array_filter($basePaths, 'is_string')) as $basePath) {
        if ($basePath === '' || $basePath === '/' || $basePath === '.') {
            continue;
        }

        $basePath = '/' . trim($basePath, '/');
        if ($path === $basePath) {
            return '/';
        }

        if (str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }
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

function text_excerpt(?string $value, int $limit = 160): string
{
    $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

    if ($text === '' || $limit <= 0) {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        $excerpt = rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8'));
    } else {
        if (strlen($text) <= $limit) {
            return $text;
        }

        $excerpt = rtrim(substr($text, 0, $limit - 1));
    }

    $excerpt = preg_replace('/\s+\S*$/', '', $excerpt) ?: $excerpt;

    return rtrim($excerpt, ' .,;:') . '...';
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

function clean_inline_style(string $style): string
{
    $safe = [];
    $allowed = [
        'background-color',
        'color',
        'font-size',
        'letter-spacing',
        'line-height',
        'text-align',
    ];

    foreach (explode(';', $style) as $declaration) {
        if (!str_contains($declaration, ':')) {
            continue;
        }

        [$property, $value] = array_map('trim', explode(':', $declaration, 2));
        $property = strtolower($property);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        if (!in_array($property, $allowed, true) || preg_match('/(?:expression|javascript|url\s*\()/i', $value)) {
            continue;
        }

        $isSafeValue = match ($property) {
            'color', 'background-color' => (bool) preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,\s%]+\)|[a-z]+)$/i', $value),
            'font-size', 'letter-spacing' => (bool) preg_match('/^-?\d+(\.\d+)?(px|em|rem|%)$/i', $value),
            'line-height' => (bool) preg_match('/^\d+(\.\d+)?(px|em|rem|%)?$/i', $value),
            'text-align' => in_array(strtolower($value), ['left', 'center', 'right', 'justify'], true),
            default => false,
        };

        if ($isSafeValue) {
            $safe[] = $property . ':' . $value;
        }
    }

    return implode(';', $safe);
}

function clean_article_html(string $html): string
{
    $html = trim($html);
    $html = preg_replace('/<\/div>\s*<div([^>]*)>/i', '</p><p$1>', $html) ?? $html;
    $html = preg_replace('/<div([^>]*)>/i', '<p$1>', $html) ?? $html;
    $html = preg_replace('/<\/div>/i', '</p>', $html) ?? $html;

    $allowed = '<p><br><strong><b><em><i><u><h2><h3><h4><blockquote><ul><ol><li><a><img><iframe><video><audio><span><table><thead><tbody><tr><th><td><hr><pre><code>';
    $html = strip_tags($html, $allowed);

    $html = preg_replace('/\son[a-z]+\s*=\s*("|\').*?\1/i', '', $html) ?? $html;
    $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;

    $html = preg_replace_callback('/<a\s+([^>]+)>/i', function (array $matches): string {
        $attrs = $matches[1];
        preg_match('/href\s*=\s*("|\')([^"\']+)\1/i', $attrs, $href);
        preg_match('/title\s*=\s*("|\')([^"\']*)\1/i', $attrs, $title);
        $url = $href[2] ?? '#';

        if (!preg_match('#^(https?://|mailto:|/)#i', $url)) {
            $url = '#';
        }

        $titleAttribute = trim($title[2] ?? '') !== '' ? ' title="' . e(trim($title[2])) . '"' : '';

        return '<a href="' . e($url) . '"' . $titleAttribute . ' target="_blank" rel="noopener">';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<span\s+([^>]*)>/i', function (array $matches): string {
        preg_match('/class\s*=\s*("|\')([^"\']+)\1/i', $matches[1], $class);
        preg_match('/style\s*=\s*("|\')([^"\']+)\1/i', $matches[1], $style);
        $classes = preg_split('/\s+/', trim($class[2] ?? '')) ?: [];
        $allowedClasses = ['text-color-ink', 'text-color-gray', 'text-color-red', 'text-color-orange', 'text-color-gold', 'text-color-green', 'text-color-teal', 'text-color-blue'];
        $safeClasses = array_values(array_intersect($classes, $allowedClasses));
        $safeStyle = clean_inline_style($style[2] ?? '');
        $styleAttribute = $safeStyle !== '' ? ' style="' . e($safeStyle) . '"' : '';

        return '<span' . ($safeClasses ? ' class="' . e(implode(' ', $safeClasses)) . '"' : '') . $styleAttribute . '>';
    }, $html) ?? $html;

    $html = preg_replace_callback('/<(p|h2|h3|h4|blockquote|li|td|th)\s+([^>]*)>/i', function (array $matches): string {
        $tag = strtolower($matches[1]);
        preg_match('/class\s*=\s*("|\')([^"\']+)\1/i', $matches[2], $class);
        preg_match('/style\s*=\s*("|\')([^"\']+)\1/i', $matches[2], $style);
        preg_match('/(?:text-align\s*:\s*|align\s*=\s*("|\')?)(left|center|right|justify)/i', $matches[2], $align);
        $classes = preg_split('/\s+/', trim($class[2] ?? '')) ?: [];
        $allowedClasses = ['text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify'];
        $safeClasses = array_values(array_intersect($classes, $allowedClasses));

        if (!empty($align[2])) {
            $safeClasses[] = 'text-align-' . strtolower($align[2]);
        }

        $safeClasses = array_values(array_unique($safeClasses));
        $safeStyle = clean_inline_style($style[2] ?? '');

        return '<' . $tag
            . ($safeClasses ? ' class="' . e(implode(' ', $safeClasses)) . '"' : '')
            . ($safeStyle !== '' ? ' style="' . e($safeStyle) . '"' : '')
            . '>';
    }, $html) ?? $html;

    $html = preg_replace('/<(pre|code)\s+[^>]*>/i', '<$1>', $html) ?? $html;

    $html = preg_replace_callback('/<img\s+([^>]+)>/i', function (array $matches): string {
        $attrs = $matches[1];
        preg_match('/src\s*=\s*("|\')([^"\']+)\1/i', $attrs, $src);
        preg_match('/alt\s*=\s*("|\')([^"\']*)\1/i', $attrs, $alt);
        preg_match('/class\s*=\s*("|\')([^"\']+)\1/i', $attrs, $class);
        $url = normalize_media_path($src[2] ?? '') ?? '';

        if (!preg_match('#^(https?://|/)#i', $url)) {
            return '';
        }

        $classes = preg_split('/\s+/', trim($class[2] ?? '')) ?: [];
        $allowedClasses = [
            'image-size-small',
            'image-size-medium',
            'image-size-large',
            'image-size-full',
            'image-align-left',
            'image-align-center',
            'image-align-right',
            'image-align-justify',
        ];
        $safeClasses = array_values(array_intersect($classes, $allowedClasses));
        $classAttribute = $safeClasses ? ' class="' . e(implode(' ', $safeClasses)) . '"' : '';

        return '<img src="' . e(media_url($url)) . '" alt="' . e($alt[2] ?? '') . '"' . $classAttribute . ' loading="lazy" onerror="this.remove()">';
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

        return '<' . $tag . ' controls' . $playsInline . ' src="' . e(media_url($url)) . '"></' . $tag . '>';
    }, $html) ?? $html;

    $html = preg_replace('/<(br|strong|b|em|i|u|ul|ol|table|thead|tbody|tr|hr)\s+[^>]*>/i', '<$1>', $html) ?? $html;
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
