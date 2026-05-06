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
        $data['author_id'] = current_user()['id'];
        $data['slug'] = News::uniqueSlug($data['title']);
        $data['status'] = $this->requestedStatus();
        $data['cover_image'] = $this->uploadCover();

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
        $data['slug'] = News::uniqueSlug($data['title'], (int) $newsItem['id']);
        $data['status'] = $this->nextStatusAfterEdit($newsItem);
        $data['published_at'] = $newsItem['published_at'];
        $data['cover_image'] = $this->uploadCover() ?: $newsItem['cover_image'];

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

        if ($title === '' || trim(strip_tags($content)) === '') {
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

        return 'draft';
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

        if (in_array($newsItem['status'], ['published', 'archived'], true)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function uploadCover(): ?string
    {
        if (empty($_FILES['cover_image']['name']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($_FILES['cover_image']['tmp_name']);

        if (!isset($allowed[$mime]) || $_FILES['cover_image']['size'] > 3 * 1024 * 1024) {
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
}
