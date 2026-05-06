<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\MenuItem;

class MenuController
{
    public function index(): void
    {
        $this->masterOnly();

        View::render('admin/menu/index', [
            'items' => MenuItem::all(),
            'categories' => Category::active(),
            'editing' => $this->editing(),
        ]);
    }

    public function store(): void
    {
        $this->masterOnly();
        $this->validateCsrf();
        $data = $this->validatedData();

        MenuItem::create($data);
        Logger::info('menu.created', 'Item de menu criado: ' . $data['label'], current_user()['id']);
        Session::flash('success', 'Item de menu criado.');
        redirect('/admin/menu');
    }

    public function update(): void
    {
        $this->masterOnly();
        $this->validateCsrf();
        $item = $this->itemFromQuery();
        $data = $this->validatedData();

        MenuItem::update((int) $item['id'], $data);
        Logger::info('menu.updated', 'Item de menu atualizado: ' . $data['label'], current_user()['id']);
        Session::flash('success', 'Item de menu atualizado.');
        redirect('/admin/menu');
    }

    public function delete(): void
    {
        $this->masterOnly();
        $this->validateCsrf();
        $item = $this->itemFromQuery();

        MenuItem::delete((int) $item['id']);
        Logger::info('menu.deleted', 'Item de menu removido: ' . $item['label'], current_user()['id']);
        Session::flash('success', 'Item de menu removido.');
        redirect('/admin/menu');
    }

    private function validatedData(): array
    {
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if ($label === '' || $url === '') {
            Session::flash('error', 'Informe o nome e o link do menu.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/menu');
        }

        if (!str_starts_with($url, '/') && !preg_match('#^https?://#i', $url)) {
            Session::flash('error', 'Use um link interno iniciado por / ou uma URL completa.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/menu');
        }

        return [
            'category_id' => filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
            'label' => $label,
            'url' => $url,
            'sort_order' => filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT) ?: 0,
            'visible' => isset($_POST['visible']),
        ];
    }

    private function editing(): ?array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        return $id ? MenuItem::find($id) : null;
    }

    private function itemFromQuery(): array
    {
        $item = $this->editing();

        if (!$item) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $item;
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/menu');
        }
    }

    private function masterOnly(): void
    {
        $user = Auth::user();

        if (!$user || $user['role_slug'] !== 'master') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }
}
