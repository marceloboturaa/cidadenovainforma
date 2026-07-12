<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Models\InstitutionLanding;
use App\Models\InstitutionPage;
use App\Models\Tag;

class InstitutionPageController
{
    public function index(): void
    {
        $pages = $this->manageablePages(false);
        $canManageLanding = $this->canManageLanding();

        if (!$pages && !$canManageLanding) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        View::render('admin/institution-pages/index', [
            'pages' => $pages,
            'canManageLanding' => $canManageLanding,
        ]);
    }

    public function landing(): void
    {
        $this->authorizeLanding();

        View::render('admin/institution-pages/landing', [
            'landing' => InstitutionLanding::get(),
            'action' => url('/admin/institution-pages/landing'),
        ]);
    }

    public function edit(): void
    {
        $page = $this->pageFromQuery();
        $this->authorize($page['slug']);

        View::render('admin/institution-pages/form', [
            'page' => $page,
            'tags' => Tag::all(),
            'action' => url('/admin/institution-pages/update?slug=' . $page['slug']),
        ]);
    }

    public function update(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/institution-pages');
        }

        $page = $this->pageFromQuery();
        $this->authorize($page['slug']);

        $name = trim($_POST['name'] ?? '');
        $kicker = trim($_POST['kicker'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '' || $kicker === '' || $summary === '' || $description === '') {
            Session::flash('error', 'Preencha nome, chamada, resumo e descrição.');
            redirect('/admin/institution-pages/edit?slug=' . $page['slug']);
        }

        $coverImage = $_POST['cover_image'] ?? '';

        try {
            $coverImage = $this->uploadedImagePath('cover_image_file') ?? $coverImage;
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/admin/institution-pages/edit?slug=' . $page['slug']);
        }

        InstitutionPage::update($page['slug'], [
            'name' => $name,
            'kicker' => $kicker,
            'summary' => $summary,
            'description' => $description,
            'team' => $_POST['team'] ?? '',
            'materials' => $_POST['materials'] ?? '',
            'photos' => $_POST['photos'] ?? '',
            'galleries' => $_POST['galleries'] ?? [],
            'cover_image' => $coverImage,
            'cta_label' => $_POST['cta_label'] ?? '',
            'cta_url' => $_POST['cta_url'] ?? '',
            'show_on_landing' => $_POST['show_on_landing'] ?? '',
            'search_terms' => $_POST['search_terms'] ?? '',
            'related_tags' => $_POST['related_tags'] ?? [],
        ]);

        Logger::info('institution_pages.updated', 'Página institucional atualizada: ' . $name, current_user()['id'] ?? null);
        Session::flash('success', 'Página institucional atualizada.');
        redirect('/admin/institution-pages');
    }

    public function updateLanding(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/institution-pages');
        }

        $this->authorizeLanding();

        try {
            $payload = $this->landingPayload();
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/admin/institution-pages/landing');
        }

        InstitutionLanding::update($payload);

        Logger::info('institution_landing.updated', 'Página institucional principal atualizada.', current_user()['id'] ?? null);
        Session::flash('success', 'Página institucional principal atualizada.');
        redirect('/admin/institution-pages');
    }

    private function manageablePages(bool $abort = true): array
    {
        $user = Auth::user();
        $pages = $user ? InstitutionPage::manageableForUser((int) $user['id'], ($user['role_slug'] ?? '') === 'master') : [];

        if (!$pages && $abort) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        return $pages;
    }

    private function pageFromQuery(): array
    {
        $slug = trim($_GET['slug'] ?? '');
        $page = $slug !== '' ? InstitutionPage::find($slug) : null;

        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $page;
    }

    private function authorize(string $slug): void
    {
        $user = Auth::user();

        if (!$user || !InstitutionPage::canManage($slug, (int) $user['id'], ($user['role_slug'] ?? '') === 'master')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function canManageLanding(): bool
    {
        return Auth::hasRole(['master', 'admin']);
    }

    private function authorizeLanding(): void
    {
        if (!$this->canManageLanding()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function landingPayload(): array
    {
        $galleryItems = $this->indexedItems($_POST['gallery'] ?? [], ['type', 'title', 'description', 'url', 'cover']);

        foreach ($galleryItems as $index => $item) {
            $galleryItems[$index]['cover'] = $this->uploadedImagePath('gallery_cover_file', $index) ?? ($item['cover'] ?? '');
        }

        $heroImage = $this->uploadedImagePath('hero_image_file') ?? ($_POST['hero']['image'] ?? '');

        return [
            'hero' => [
                'eyebrow' => $_POST['hero']['eyebrow'] ?? '',
                'title' => $_POST['hero']['title'] ?? '',
                'subtitle' => $_POST['hero']['subtitle'] ?? '',
                'button_label' => $_POST['hero']['button_label'] ?? '',
                'button_url' => $_POST['hero']['button_url'] ?? '',
                'image' => $heroImage,
            ],
            'about' => [
                'eyebrow' => $_POST['about']['eyebrow'] ?? '',
                'title' => $_POST['about']['title'] ?? '',
                'body' => $_POST['about']['body'] ?? '',
            ],
            'projects' => [
                'visible' => !empty($_POST['projects']['visible']),
            ],
            'gallery' => [
                'eyebrow' => $_POST['gallery_meta']['eyebrow'] ?? '',
                'title' => $_POST['gallery_meta']['title'] ?? '',
                'intro' => $_POST['gallery_meta']['intro'] ?? '',
                'items' => $galleryItems,
            ],
            'impact' => [
                'eyebrow' => $_POST['impact_meta']['eyebrow'] ?? '',
                'title' => $_POST['impact_meta']['title'] ?? '',
                'stats' => $this->indexedItems($_POST['impact'] ?? [], ['value', 'label', 'description']),
            ],
            'support' => [
                'eyebrow' => $_POST['support_meta']['eyebrow'] ?? '',
                'title' => $_POST['support_meta']['title'] ?? '',
                'body' => $_POST['support_meta']['body'] ?? '',
                'items' => $this->indexedItems($_POST['support'] ?? [], ['title', 'description', 'button_label', 'url']),
            ],
            'seo' => [
                'title' => $_POST['seo']['title'] ?? '',
                'description' => $_POST['seo']['description'] ?? '',
            ],
        ];
    }

    private function indexedItems(array $source, array $fields): array
    {
        $count = 0;

        foreach ($fields as $field) {
            $values = $source[$field] ?? [];
            $count = max($count, is_array($values) ? count($values) : 0);
        }

        $items = [];

        for ($index = 0; $index < $count; $index++) {
            $item = [];

            foreach ($fields as $field) {
                $values = $source[$field] ?? [];
                $item[$field] = is_array($values) ? (string) ($values[$index] ?? '') : '';
            }

            $items[] = $item;
        }

        return $items;
    }

    private function uploadedImagePath(string $field, ?int $index = null): ?string
    {
        if (empty($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];

        if ($index !== null) {
            $error = $file['error'][$index] ?? UPLOAD_ERR_NO_FILE;

            if ($error === UPLOAD_ERR_NO_FILE) {
                return null;
            }

            $file = [
                'name' => $file['name'][$index] ?? '',
                'tmp_name' => $file['tmp_name'][$index] ?? '',
                'size' => $file['size'][$index] ?? 0,
                'error' => $error,
            ];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Não foi possível receber uma das imagens. Tente novamente.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        if (!$imageInfo || !isset($allowedTypes[$imageInfo[2] ?? 0]) || $size <= 0 || $size > 8 * 1024 * 1024) {
            throw new \RuntimeException('Use imagens JPG, PNG, WEBP ou GIF com até 8MB.');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/institution';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            throw new \RuntimeException('A pasta de uploads institucionais não está gravável.');
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$imageInfo[2]];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            throw new \RuntimeException('Não foi possível salvar uma das imagens.');
        }

        return '/public/uploads/institution/' . $filename;
    }
}
