<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Category;
use App\Models\Document;
use App\Models\InstitutionPage;
use App\Models\MenuItem;
use App\Models\News;
use App\Models\Tag;

class PublicController
{
    public function home(): void
    {
        $this->logAccess();

        View::render('public/home', [
            'featured' => News::publicList(['featured' => true], 5),
            'urgent' => News::publicList(['urgent' => true], 4),
            'latest' => News::publicList([], 12),
            'popular' => News::popular(5),
            'menuItems' => MenuItem::visible(),
            'pageTitle' => 'Cidade Nova Informa',
            'canonicalUrl' => url('/'),
        ], 'public');
    }

    public function search(): void
    {
        $q = trim($_GET['q'] ?? '');
        $this->logAccess();

        View::render('public/list', [
            'heading' => $q ? 'Busca por "' . $q . '"' : 'Busca',
            'news' => $q ? News::publicList(['q' => $q], 20) : [],
            'menuItems' => MenuItem::visible(),
            'query' => $q,
            'pageTitle' => 'Busca - Cidade Nova Informa',
            'metaDescription' => 'Resultados de busca no Cidade Nova Informa.',
            'canonicalUrl' => url('/buscar') . ($q ? '?q=' . urlencode($q) : ''),
        ], 'public');
    }

    public function institution(): void
    {
        $this->logAccess();

        View::render('public/institution', [
            'areas' => $this->institutionAreas(),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Instituição - Cidade Nova Informa',
            'metaDescription' => 'Conheça a missão, a atuação e o compromisso editorial do Cidade Nova Informa.',
            'canonicalUrl' => url('/instituicao'),
        ], 'public');
    }

    public function institutionArea(): void
    {
        $areas = $this->institutionAreas();
        $slug = $_GET['slug'] ?? '';
        $area = $areas[$slug] ?? null;
        $this->logAccess();

        if (!$area) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        View::render('public/institution-area', [
            'area' => $area,
            'areas' => $areas,
            'photos' => $area['photos'],
            'relatedNews' => News::relatedToInstitutionPage($area, 6),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $area['name'] . ' - Instituição - Cidade Nova Informa',
            'metaDescription' => $area['summary'],
            'canonicalUrl' => url('/instituicao/' . $area['slug']),
        ], 'public');
    }

    public function documents(): void
    {
        $this->logAccess();

        View::render('public/documents', [
            'documents' => Document::publicAll(),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Documentos - Cidade Nova Informa',
            'metaDescription' => 'Documentos públicos disponibilizados pelo Cidade Nova Informa.',
            'canonicalUrl' => url('/documentos'),
        ], 'public');
    }

    public function downloadDocument(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document || empty($document['is_public'])) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $path = Document::absolutePath($document);
        if (!$path || !is_file($path)) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $downloadName = str_replace(['"', "\r", "\n"], '', basename($document['original_name']));

        header('X-Content-Type-Options: nosniff');
        header('X-Download-Options: noopen');
        header('Content-Type: ' . $this->safeMimeType((string) $document['mime_type']));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');

        readfile($path);
        exit;
    }

    public function archive(): void
    {
        $this->logAccess();

        View::render('public/list', [
            'heading' => 'Acervo Cidade Nova',
            'news' => News::publicList(['is_archive' => 1, 'archive_order' => true], 30),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Acervo Cidade Nova - Cidade Nova Informa',
            'metaDescription' => 'Reportagens antigas republicadas para preservação histórica do acervo.',
            'canonicalUrl' => url('/acervo'),
        ], 'public');
    }

    public function category(): void
    {
        $category = Category::findBySlug($_GET['slug'] ?? '');
        $this->logAccess();

        if (!$category) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        View::render('public/list', [
            'heading' => $category['name'],
            'news' => News::publicList(['category_id' => $category['id']], 20),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $category['name'] . ' - Cidade Nova Informa',
            'metaDescription' => $category['description'] ?: 'Notícias da categoria ' . $category['name'],
            'canonicalUrl' => url('/categoria/' . $category['slug']),
        ], 'public');
    }

    public function tag(): void
    {
        $tag = Tag::findBySlug($_GET['slug'] ?? '');
        $this->logAccess();

        if (!$tag) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $tagDisplayName = $tag['display_name'] ?? $tag['name'];

        View::render('public/list', [
            'heading' => '#' . $tagDisplayName,
            'news' => News::publicList(['tag_id' => $tag['id']], 20),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $tagDisplayName . ' - Cidade Nova Informa',
            'metaDescription' => 'Notícias marcadas com ' . $tagDisplayName,
            'canonicalUrl' => url('/tag/' . $tag['slug']),
        ], 'public');
    }

    public function show(): void
    {
        $news = News::findPublishedBySlug($_GET['slug'] ?? '');

        if (!$news) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        News::incrementViews((int) $news['id']);
        $this->logAccess((int) $news['id']);

        View::render('public/show', [
            'news' => $news,
            'tags' => Tag::publicForNews((int) $news['id']),
            'related' => News::publicList(['category_id' => $news['category_id']], 4),
            'menuItems' => MenuItem::visible(),
            'pageTitle' => $news['title'] . ' - Cidade Nova Informa',
            'metaDescription' => $news['summary'] ?: substr(strip_tags($news['content']), 0, 150),
            'canonicalUrl' => url('/noticia/' . $news['slug']),
            'ogType' => 'article',
            'ogImage' => ($publicImage = news_public_image($news)) ? media_url($publicImage) : null,
        ], 'public');
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/instituicao'), 'priority' => '0.6'],
            ['loc' => url('/buscar'), 'priority' => '0.4'],
            ['loc' => url('/acervo'), 'priority' => '0.7'],
        ];

        foreach ($this->institutionAreas() as $area) {
            $urls[] = ['loc' => url('/instituicao/' . $area['slug']), 'priority' => '0.6'];
        }

        foreach (Category::active() as $category) {
            $urls[] = ['loc' => url('/categoria/' . $category['slug']), 'priority' => '0.7'];
        }

        foreach (Tag::all() as $tag) {
            if ((int) $tag['news_count'] > 0) {
                $urls[] = ['loc' => url('/tag/' . $tag['slug']), 'priority' => '0.5'];
            }
        }

        foreach (News::publicList([], 50) as $item) {
            $urls[] = [
                'loc' => url('/noticia/' . $item['slug']),
                'priority' => '0.8',
                'lastmod' => date('Y-m-d', strtotime($item['updated_at'] ?? $item['published_at'] ?? 'now')),
            ];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $item) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . e($item['loc']) . '</loc>' . PHP_EOL;
            if (!empty($item['lastmod'])) {
                echo '    <lastmod>' . e($item['lastmod']) . '</lastmod>' . PHP_EOL;
            }
            echo '    <priority>' . e($item['priority']) . '</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /login\n";
        echo 'Sitemap: ' . url('/sitemap.xml') . "\n";
    }

    private function logAccess(?int $newsId = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO access_logs (news_id, ip_address, user_agent, path, created_at)
             VALUES (:news_id, :ip_address, :user_agent, :path, NOW())'
        );
        $stmt->execute([
            'news_id' => $newsId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'path' => substr($_SERVER['REQUEST_URI'] ?? '/', 0, 255),
        ]);
    }

    private function safeMimeType(string $mime): string
    {
        return preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $mime)
            ? $mime
            : 'application/octet-stream';
    }

    private function institutionAreas(): array
    {
        $areas = InstitutionPage::all();

        return array_column($areas, null, 'slug');
    }

    private function institutionPhotos(string $slug): array
    {
        $files = glob(dirname(__DIR__, 2) . '/public/uploads/news/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        $files = array_values(array_filter($files, 'is_file'));
        sort($files);

        $offsets = ['biblioteca' => 0, 'horta' => 2, 'radio' => 4];
        $offset = $offsets[$slug] ?? 0;
        $selected = array_slice(array_values(array_unique(array_merge(array_slice($files, $offset), $files))), 0, 4);

        return array_map(
            fn (string $file): string => '/public/uploads/news/' . basename($file),
            $selected
        );
    }
}
