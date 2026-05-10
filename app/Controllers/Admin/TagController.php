<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Tag;

class TagController
{
    public function index(): void
    {
        Middleware::permission('tags.manage');

        View::render('admin/tags/index', [
            'tags' => Tag::all(),
            'editing' => $this->editing(),
        ]);
    }

    public function store(): void
    {
        Middleware::permission('tags.manage');
        $this->validateCsrf();
        $displayName = trim($_POST['name'] ?? '');
        $slug = slugify($displayName);

        if ($displayName === '' || $slug === '') {
            Session::flash('error', 'Informe o nome da tag.');
            redirect('/admin/tags');
        }

        Tag::create([
            'name' => $slug,
            'display_name' => $displayName,
            'slug' => Tag::uniqueSlug($displayName),
        ]);

        Logger::info('tags.created', 'Tag criada: ' . $displayName, current_user()['id']);
        Session::flash('success', 'Tag criada.');
        redirect('/admin/tags');
    }

    public function update(): void
    {
        Middleware::permission('tags.manage');
        $this->validateCsrf();
        $tag = $this->tagFromQuery();
        $displayName = trim($_POST['name'] ?? '');
        $slug = slugify($displayName);

        if ($displayName === '' || $slug === '') {
            Session::flash('error', 'Informe o nome da tag.');
            redirect('/admin/tags/edit?id=' . $tag['id']);
        }

        Tag::update((int) $tag['id'], [
            'name' => $slug,
            'display_name' => $displayName,
            'slug' => Tag::uniqueSlug($displayName, (int) $tag['id']),
        ]);

        Logger::info('tags.updated', 'Tag atualizada: ' . $displayName, current_user()['id']);
        Session::flash('success', 'Tag atualizada.');
        redirect('/admin/tags');
    }

    public function delete(): void
    {
        Middleware::permission('tags.manage');
        $this->validateCsrf();
        $tag = $this->tagFromQuery();

        Tag::delete((int) $tag['id']);
        Logger::info('tags.deleted', 'Tag removida: ' . ($tag['display_name'] ?? $tag['name']), current_user()['id']);
        Session::flash('success', 'Tag removida.');
        redirect('/admin/tags');
    }

    private function editing(): ?array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        return $id ? Tag::find($id) : null;
    }

    private function tagFromQuery(): array
    {
        $tag = $this->editing();

        if (!$tag) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $tag;
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/tags');
        }
    }
}
