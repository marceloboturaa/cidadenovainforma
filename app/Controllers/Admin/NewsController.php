<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\News;
use App\Models\Tag;
use App\Models\User;

class NewsController
{
    public function index(): void
    {
        Middleware::auth();
        $user = current_user();
        $canManage = Auth::can('news.manage') || Auth::can('news.approve');

        if (!$canManage && !Auth::can('news.create')) {
            Middleware::permission('news.manage');
        }

        $filters = [
            'status' => $_GET['status'] ?? '',
            'is_archive' => $_GET['is_archive'] ?? '',
            'q' => trim($_GET['q'] ?? ''),
        ];

        if (!$canManage) {
            $filters['author_id'] = (int) $user['id'];
        }

        View::render('admin/news/index', [
            'news' => News::all($filters),
            'statuses' => News::STATUS_LABELS,
            'visibilityLabels' => News::VISIBILITY_LABELS,
            'filters' => $filters,
            'canApprove' => Auth::can('news.approve'),
        ]);
    }

    public function create(): void
    {
        Middleware::permission('news.create');

        View::render('admin/news/form', [
            'newsItem' => null,
            'categories' => Category::active(),
            'users' => $this->authorOptions(),
            'tags' => '',
            'action' => url('/admin/news'),
            'title' => 'Nova notícia',
            'visibilityLabels' => News::VISIBILITY_LABELS,
        ]);
    }

    public function store(): void
    {
        Middleware::permission('news.create');
        $this->validateRequest('/admin/news/create');

        $contentMedia = $this->uploadContentMedia();
        $data = $this->validatedData($contentMedia);
        $data['author_id'] = $this->authorIdFromRequest((int) current_user()['id']);
        $data['slug'] = News::uniqueSlug($data['title']);
        $data['status'] = $this->requestedStatus();
        $data['published_at'] = $this->publishedAtFromRequest();
        $data['cover_image'] = $this->uploadCover();
        $data['cover_image'] = $data['cover_image'] ?: $contentMedia['first_image'];

        $newsId = News::create($data);
        Tag::syncForNews($newsId, $_POST['tags'] ?? '');
        Logger::info('news.created', 'Notícia criada: ' . $data['title'], current_user()['id']);

        Session::flash('success', $data['status'] === 'pending' ? 'Matéria enviada para aprovação.' : 'Rascunho salvo.');
        redirect('/admin/news');
    }

    public function edit(): void
    {
        Middleware::auth();
        $newsItem = $this->newsFromQuery();
        $this->authorizeEdit($newsItem);

        View::render('admin/news/form', [
            'newsItem' => $newsItem,
            'categories' => Category::active(),
            'users' => $this->authorOptions(),
            'tags' => Tag::namesForNews((int) $newsItem['id']),
            'action' => url('/admin/news/update?id=' . $newsItem['id']),
            'title' => 'Editar notícia',
            'visibilityLabels' => News::VISIBILITY_LABELS,
        ]);
    }

    public function update(): void
    {
        Middleware::auth();
        $newsItem = $this->newsFromQuery();
        $this->authorizeEdit($newsItem);
        $this->validateRequest('/admin/news/edit?id=' . $newsItem['id']);

        $contentMedia = $this->uploadContentMedia();
        $data = $this->validatedData($contentMedia);
        if (!Auth::can('news.manage') && !Auth::can('news.approve')) {
            $data['public_visibility'] = $newsItem['public_visibility'] ?? News::VISIBILITY_LISTED;
        }
        $data['author_id'] = $this->authorIdFromRequest((int) $newsItem['author_id']);
        $data['slug'] = News::uniqueSlug($data['title'], (int) $newsItem['id']);
        $data['status'] = $this->nextStatusAfterEdit($newsItem);
        $data['published_at'] = $this->publishedAtFromRequest() ?: $newsItem['published_at'];
        $data['cover_image'] = $this->coverImageFromRequest($newsItem['cover_image']);
        $data['cover_image'] = $data['cover_image'] ?: $contentMedia['first_image'];

        News::update((int) $newsItem['id'], $data);
        Tag::syncForNews((int) $newsItem['id'], $_POST['tags'] ?? '');
        Logger::info('news.updated', 'Notícia atualizada: ' . $data['title'], current_user()['id']);

        Session::flash('success', $data['status'] === 'pending' ? 'Matéria atualizada e enviada para aprovação.' : 'Matéria atualizada.');
        redirect('/admin/news');
    }

    public function approve(): void
    {
        Middleware::permission('news.approve');
        $this->validateRequest('/admin/news');
        $newsItem = $this->newsFromQuery();

        News::changeStatus((int) $newsItem['id'], 'published', current_user()['id']);
        Logger::info('news.approved', 'Matéria aprovada: ' . $newsItem['title'], current_user()['id']);
        Session::flash('success', 'Matéria publicada.');
        redirect('/admin/news');
    }

    public function reject(): void
    {
        Middleware::permission('news.approve');
        $this->validateRequest('/admin/news');
        $newsItem = $this->newsFromQuery();

        News::changeStatus((int) $newsItem['id'], 'rejected', current_user()['id']);
        Logger::info('news.rejected', 'Matéria rejeitada: ' . $newsItem['title'], current_user()['id']);
        Session::flash('success', 'Matéria rejeitada.');
        redirect('/admin/news');
    }

    public function archive(): void
    {
        Middleware::auth();
        $this->validateRequest('/admin/news');
        $newsItem = $this->newsFromQuery();

        if (!$this->canArchive($newsItem)) {
            Middleware::permission('news.manage');
        }

        News::changeStatus((int) $newsItem['id'], 'archived');
        Logger::info('news.archived', 'Matéria arquivada: ' . $newsItem['title'], current_user()['id']);
        Session::flash('success', 'Matéria arquivada.');
        redirect('/admin/news');
    }

    public function delete(): void
    {
        Middleware::auth();
        $this->validateRequest('/admin/news');
        $newsItem = $this->newsFromQuery();

        if ((current_user()['role_slug'] ?? '') !== 'master') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        News::delete((int) $newsItem['id']);
        Logger::info('news.deleted', 'Matéria excluída: ' . $newsItem['title'], current_user()['id']);
        Session::flash('success', 'Matéria excluída permanentemente.');
        redirect('/admin/news');
    }

    public function bulk(): void
    {
        Middleware::auth();
        $this->validateRequest('/admin/news');

        $action = $_POST['bulk_action'] ?? '';
        $ids = $this->selectedNewsIdsFromRequest();

        if (!$ids) {
            Session::flash('error', 'Selecione pelo menos uma notícia.');
            redirect('/admin/news');
        }

        if ($action === 'delete') {
            if ((current_user()['role_slug'] ?? '') !== 'master') {
                http_response_code(403);
                View::render('errors/403');
                exit;
            }

            $deleted = 0;
            foreach ($ids as $id) {
                $newsItem = News::find($id);
                if (!$newsItem) {
                    continue;
                }

                News::delete($id);
                Logger::info('news.deleted', 'Matéria excluída em lote: ' . $newsItem['title'], current_user()['id']);
                $deleted++;
            }

            Session::flash('success', $deleted . ' matéria(s) excluída(s) permanentemente.');
            redirect('/admin/news');
        }

        if ($action === 'archive') {
            $archived = 0;
            $skipped = 0;

            foreach ($ids as $id) {
                $newsItem = News::find($id);
                if (!$newsItem) {
                    $skipped++;
                    continue;
                }

                if (!$this->canArchive($newsItem)) {
                    $skipped++;
                    continue;
                }

                News::changeStatus($id, 'archived');
                Logger::info('news.archived', 'Matéria arquivada em lote: ' . $newsItem['title'], current_user()['id']);
                $archived++;
            }

            $message = $archived . ' matéria(s) arquivada(s).';
            if ($skipped > 0) {
                $message .= ' ' . $skipped . ' ignorada(s) por permissão ou status.';
            }
            Session::flash('success', $message);
            redirect('/admin/news');
        }

        Session::flash('error', 'Ação em lote inválida.');
        redirect('/admin/news');
    }

    private function validateRequest(string $fallback): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($fallback);
        }
    }

    private function validatedData(array $contentMedia): array
    {
        $title = trim($_POST['title'] ?? '');
        $content = clean_article_html($this->contentWithUploadedMedia($_POST['content'] ?? '', $contentMedia));

        $hasMedia = (bool) preg_match('/<(img|iframe|video|audio)\b/i', $content);
        $hasUploadedMedia = $contentMedia['html'] !== '';
        if ($title === '' || (trim(strip_tags($content)) === '' && !$hasMedia && !$hasUploadedMedia)) {
            Session::flash('error', 'Título e conteúdo são obrigatórios.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        return [
            'title' => $title,
            'summary' => $_POST['summary'] ?? '',
            'content' => $content,
            'cover_caption' => $_POST['cover_caption'] ?? '',
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
            'type' => in_array($_POST['type'] ?? '', ['noticia', 'reportagem', 'artigo', 'coluna'], true) ? $_POST['type'] : 'noticia',
            'featured' => Auth::can('news.manage') && isset($_POST['featured']),
            'urgent' => Auth::can('news.manage') && isset($_POST['urgent']),
            'is_archive' => isset($_POST['is_archive']),
            'public_visibility' => $this->publicVisibilityFromRequest(),
            'original_published_at' => $_POST['original_published_at'] ?? null,
            'original_author' => $_POST['original_author'] ?? '',
            'original_source' => $_POST['original_source'] ?? '',
            'original_url' => $_POST['original_url'] ?? '',
            'archive_note' => $_POST['archive_note'] ?? '',
        ];
    }

    private function authorOptions(): array
    {
        return (current_user()['role_slug'] ?? '') === 'master' ? User::activeForAccessLists() : [];
    }

    private function authorIdFromRequest(int $fallback): int
    {
        if ((current_user()['role_slug'] ?? '') !== 'master') {
            return $fallback;
        }

        $authorId = filter_input(INPUT_POST, 'author_id', FILTER_VALIDATE_INT);
        $author = $authorId ? User::findWithRole($authorId) : null;

        return $author ? (int) $author['id'] : $fallback;
    }

    private function requestedStatus(): string
    {
        if (($_POST['intent'] ?? '') === 'submit') {
            return Auth::can('news.approve') ? 'published' : 'pending';
        }

        return 'draft';
    }

    private function publicVisibilityFromRequest(): string
    {
        if (!Auth::can('news.manage') && !Auth::can('news.approve')) {
            return News::VISIBILITY_LISTED;
        }

        $visibility = $_POST['public_visibility'] ?? News::VISIBILITY_LISTED;

        return array_key_exists($visibility, News::VISIBILITY_LABELS) ? $visibility : News::VISIBILITY_LISTED;
    }

    private function nextStatusAfterEdit(array $newsItem): string
    {
        if (($_POST['intent'] ?? '') === 'submit') {
            return Auth::can('news.approve') ? 'published' : 'pending';
        }

        if (Auth::can('news.manage')) {
            return $newsItem['status'];
        }

        if ($newsItem['status'] === 'published') {
            return 'pending';
        }

        return 'draft';
    }

    private function publishedAtFromRequest(): ?string
    {
        $value = trim($_POST['published_at'] ?? '');

        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d\TH:i', $value);

        return $date ? $date->format('Y-m-d H:i:s') : null;
    }

    private function newsFromQuery(): array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $newsItem = $id ? News::find($id) : null;

        if (!$newsItem) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $newsItem;
    }

    private function authorizeEdit(array $newsItem): void
    {
        if (Auth::can('news.manage')) {
            return;
        }

        if (!Auth::can('news.create') || (int) $newsItem['author_id'] !== (int) current_user()['id']) {
            Middleware::permission('news.manage');
        }

        if ($newsItem['status'] === 'archived') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function canArchive(array $newsItem): bool
    {
        if (Auth::can('news.manage')) {
            return true;
        }

        $isOwner = (int) $newsItem['author_id'] === (int) current_user()['id'];

        return $isOwner && !in_array($newsItem['status'], ['published', 'archived'], true);
    }

    private function selectedNewsIdsFromRequest(): array
    {
        $ids = $_POST['news_ids'] ?? [];

        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids, fn (int $id): bool => $id > 0)));

        return array_slice($ids, 0, 200);
    }

    private function uploadCover(): ?string
    {
        $externalCover = $this->externalMediaUrl($_POST['cover_image_url'] ?? '', 'image');
        if ($externalCover) {
            return $externalCover;
        }

        if (empty($_FILES['cover_image']['name']) || ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($_FILES['cover_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Session::flash('error', $this->uploadErrorMessage((int) $_FILES['cover_image']['error']));
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        $tmpName = $_FILES['cover_image']['tmp_name'] ?? '';
        $size = (int) ($_FILES['cover_image']['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowedImageTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        if (!$imageInfo || !isset($allowedImageTypes[$imageInfo[2] ?? 0]) || $size <= 0 || $size > 8 * 1024 * 1024) {
            Session::flash('error', 'Imagem invalida. Use JPG, PNG, WEBP ou GIF com ate 8MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/news';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            Session::flash('error', 'A pasta de uploads nao esta gravavel.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        $extension = $allowedImageTypes[$imageInfo[2]];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            Session::flash('error', 'Não foi possível salvar a imagem.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        return '/public/uploads/news/' . $filename;
    }

    private function coverImageFromRequest(?string $existing): ?string
    {
        return $this->uploadCover() ?: $existing;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'A imagem enviada e maior que o limite permitido.',
            UPLOAD_ERR_PARTIAL => 'A imagem foi enviada parcialmente. Tente novamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporaria de upload nao foi encontrada no servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Nao foi possivel gravar a imagem no servidor.',
            UPLOAD_ERR_EXTENSION => 'Uma extensao do PHP bloqueou o upload da imagem.',
            default => 'Nao foi possivel enviar a imagem. Tente novamente.',
        };
    }

    private function uploadContentMedia(): array
    {
        if (empty($_FILES['content_media']['name']) || !is_array($_FILES['content_media']['name'])) {
            return ['html' => '', 'first_image' => null, 'items' => []];
        }

        $allowed = [
            'image/jpeg' => ['jpg', 'image'],
            'image/png' => ['png', 'image'],
            'image/webp' => ['webp', 'image'],
            'image/gif' => ['gif', 'image'],
            'video/mp4' => ['mp4', 'video'],
            'video/webm' => ['webm', 'video'],
            'audio/mpeg' => ['mp3', 'audio'],
            'audio/mp3' => ['mp3', 'audio'],
            'audio/ogg' => ['ogg', 'audio'],
            'audio/wav' => ['wav', 'audio'],
        ];
        $maxSize = 25 * 1024 * 1024;
        $directory = dirname(__DIR__, 3) . '/public/uploads/news';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $html = [];
        $items = [];
        $firstImage = null;
        foreach ($_FILES['content_media']['name'] as $index => $name) {
            if (($_FILES['content_media']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $_FILES['content_media']['tmp_name'][$index] ?? '';
            $size = (int) ($_FILES['content_media']['size'][$index] ?? 0);
            $mime = $tmpName ? (mime_content_type($tmpName) ?: '') : '';

            if (!isset($allowed[$mime]) || $size <= 0 || $size > $maxSize) {
                continue;
            }

            [$extension, $type] = $allowed[$mime];
            if ($type === 'image' && !getimagesize($tmpName)) {
                continue;
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $extension;
            $target = $directory . '/' . $filename;
            if (!move_uploaded_file($tmpName, $target)) {
                continue;
            }

            $path = '/public/uploads/news/' . $filename;
            if ($type === 'image') {
                $firstImage ??= $path;
                $itemHtml = '<p><img src="' . e($path) . '" alt=""></p>';
            } elseif ($type === 'video') {
                $itemHtml = '<p><video controls src="' . e($path) . '"></video></p>';
            } elseif ($type === 'audio') {
                $itemHtml = '<p><audio controls src="' . e($path) . '"></audio></p>';
            } else {
                continue;
            }

            $html[] = $itemHtml;
            $items[$index] = [
                'html' => $itemHtml,
                'path' => $path,
                'type' => $type,
            ];
        }

        return [
            'html' => $html ? "\n" . implode("\n", $html) : '',
            'first_image' => $firstImage,
            'items' => $items,
        ];
    }

    private function contentWithUploadedMedia(string $content, array $contentMedia): string
    {
        $usedIndexes = [];

        foreach ($contentMedia['items'] ?? [] as $index => $item) {
            $html = $item['html'] ?? '';
            if ($html === '') {
                continue;
            }

            $pendingAttribute = '\bdata-pending-upload\s*=\s*["\']' . preg_quote((string) $index, '/') . '["\']';
            $wrappedPattern = '/<p\b[^>]*>\s*<(?P<tag>img|video|audio)\b(?=[^>]*' . $pendingAttribute . ')[^>]*(?:>\s*<\/(?P=tag)>|>)\s*<\/p>/i';
            $mediaPattern = '/<(?P<tag>img|video|audio)\b(?=[^>]*' . $pendingAttribute . ')[^>]*(?:>\s*<\/(?P=tag)>|>)/i';

            $content = preg_replace_callback($wrappedPattern, function () use ($html, $index, &$usedIndexes): string {
                $usedIndexes[$index] = true;
                return $html;
            }, $content) ?? $content;

            $content = preg_replace_callback($mediaPattern, function () use ($html, $index, &$usedIndexes): string {
                $usedIndexes[$index] = true;
                return $html;
            }, $content) ?? $content;
        }

        $missingHtml = [];
        foreach ($contentMedia['items'] ?? [] as $index => $item) {
            if (!isset($usedIndexes[$index]) && !empty($item['html'])) {
                $missingHtml[] = $item['html'];
            }
        }

        return $content . ($missingHtml ? "\n" . implode("\n", $missingHtml) : '');
    }

    private function externalMediaUrl(string $url, string $type): ?string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $patterns = [
            'image' => '#^.+$#',
            'video' => '#\.(mp4|webm)$#i',
            'audio' => '#\.(mp3|ogg|wav)$#i',
        ];

        return preg_match($patterns[$type] ?? '#a\A#', $path) ? $url : null;
    }
}
