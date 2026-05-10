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
        ]);
    }

    public function store(): void
    {
        Middleware::permission('news.create');
        $this->validateRequest('/admin/news/create');

        $data = $this->validatedData();
        $data['author_id'] = $this->authorIdFromRequest((int) current_user()['id']);
        $data['slug'] = News::uniqueSlug($data['title']);
        $data['status'] = $this->requestedStatus();
        $data['published_at'] = $this->publishedAtFromRequest();
        $data['cover_image'] = $this->uploadCover();
        $contentMedia = $this->uploadContentMedia();
        $data['content'] .= $contentMedia['html'];
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
        ]);
    }

    public function update(): void
    {
        Middleware::auth();
        $newsItem = $this->newsFromQuery();
        $this->authorizeEdit($newsItem);
        $this->validateRequest('/admin/news/edit?id=' . $newsItem['id']);

        $data = $this->validatedData();
        $data['author_id'] = $this->authorIdFromRequest((int) $newsItem['author_id']);
        $data['slug'] = News::uniqueSlug($data['title'], (int) $newsItem['id']);
        $data['status'] = $this->nextStatusAfterEdit($newsItem);
        $data['published_at'] = $this->publishedAtFromRequest() ?: $newsItem['published_at'];
        $data['cover_image'] = $this->coverImageFromRequest($newsItem['cover_image']);
        $contentMedia = $this->uploadContentMedia();
        $data['content'] .= $contentMedia['html'];
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

        if (!Auth::can('news.manage')) {
            $isOwner = (int) $newsItem['author_id'] === (int) current_user()['id'];
            $canArchiveOwnDraft = $isOwner && !in_array($newsItem['status'], ['published', 'archived'], true);

            if (!$canArchiveOwnDraft) {
                Middleware::permission('news.manage');
            }
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

    private function validateRequest(string $fallback): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($fallback);
        }
    }

    private function validatedData(): array
    {
        $title = trim($_POST['title'] ?? '');
        $content = clean_article_html($_POST['content'] ?? '');

        $hasMedia = (bool) preg_match('/<(img|iframe|video|audio)\b/i', $content);
        $hasUploadedMedia = !empty($_FILES['content_media']['name'][0]);
        if ($title === '' || (trim(strip_tags($content)) === '' && !$hasMedia && !$hasUploadedMedia)) {
            Session::flash('error', 'Título e conteúdo são obrigatórios.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        return [
            'title' => $title,
            'summary' => $_POST['summary'] ?? '',
            'content' => $content,
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
            'type' => in_array($_POST['type'] ?? '', ['noticia', 'reportagem', 'artigo', 'coluna'], true) ? $_POST['type'] : 'noticia',
            'featured' => Auth::can('news.manage') && isset($_POST['featured']),
            'urgent' => Auth::can('news.manage') && isset($_POST['urgent']),
            'is_archive' => isset($_POST['is_archive']),
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

    private function uploadCover(): ?string
    {
        $externalCover = $this->externalMediaUrl($_POST['cover_image_url'] ?? '', 'image');
        if ($externalCover) {
            return $externalCover;
        }

        if (empty($_FILES['cover_image']['name']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($_FILES['cover_image']['tmp_name']);

        if (!isset($allowed[$mime]) || $_FILES['cover_image']['size'] > 3 * 1024 * 1024 || !getimagesize($_FILES['cover_image']['tmp_name'])) {
            Session::flash('error', 'Imagem invalida. Use JPG, PNG ou WEBP com ate 3MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/news';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target)) {
            Session::flash('error', 'Não foi possível salvar a imagem.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/news');
        }

        return '/public/uploads/news/' . $filename;
    }

    private function coverImageFromRequest(?string $existing): ?string
    {
        return $this->uploadCover() ?: $existing;
    }

    private function uploadContentMedia(): array
    {
        if (empty($_FILES['content_media']['name']) || !is_array($_FILES['content_media']['name'])) {
            return ['html' => '', 'first_image' => null];
        }

        $allowed = [
            'image/jpeg' => ['jpg', 'image'],
            'image/png' => ['png', 'image'],
            'image/webp' => ['webp', 'image'],
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
                $html[] = '<p><img src="' . e($path) . '" alt=""></p>';
            } elseif ($type === 'video') {
                $html[] = '<p><video controls src="' . e($path) . '"></video></p>';
            } elseif ($type === 'audio') {
                $html[] = '<p><audio controls src="' . e($path) . '"></audio></p>';
            }
        }

        return [
            'html' => $html ? "\n" . implode("\n", $html) : '',
            'first_image' => $firstImage,
        ];
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
