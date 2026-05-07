<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Models\InstitutionPage;
use App\Models\Tag;

class InstitutionPageController
{
    public function index(): void
    {
        $pages = $this->manageablePages();

        View::render('admin/institution-pages/index', [
            'pages' => $pages,
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

        InstitutionPage::update($page['slug'], [
            'name' => $name,
            'kicker' => $kicker,
            'summary' => $summary,
            'description' => $description,
            'team' => $_POST['team'] ?? '',
            'materials' => $_POST['materials'] ?? '',
            'photos' => $_POST['photos'] ?? '',
            'galleries' => $_POST['galleries'] ?? [],
            'search_terms' => $_POST['search_terms'] ?? '',
            'related_tags' => $_POST['related_tags'] ?? [],
        ]);

        Logger::info('institution_pages.updated', 'Página institucional atualizada: ' . $name, current_user()['id'] ?? null);
        Session::flash('success', 'Página institucional atualizada.');
        redirect('/admin/institution-pages');
    }

    private function manageablePages(): array
    {
        $user = Auth::user();
        $pages = $user ? InstitutionPage::manageableForUser((int) $user['id'], ($user['role_slug'] ?? '') === 'master') : [];

        if (!$pages) {
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
}
