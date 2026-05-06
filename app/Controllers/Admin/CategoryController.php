<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;

class CategoryController
{
    public function index(): void
    {
        Middleware::permission('categories.manage');

        View::render('admin/categories/index', [
            'categories' => Category::all(),
            'parents' => Category::active(),
            'editing' => $this->editing(),
        ]);
    }

    public function store(): void
    {
        Middleware::permission('categories.manage');
        $this->validateCsrf();
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            Session::flash('error', 'Informe o nome da categoria.');
            redirect('/admin/categories');
        }

        Category::create([
            'parent_id' => $_POST['parent_id'] ?? null,
            'name' => $name,
            'slug' => Category::uniqueSlug($name),
            'description' => $_POST['description'] ?? '',
            'active' => isset($_POST['active']),
        ]);

        Logger::info('categories.created', 'Categoria criada: ' . $name, current_user()['id']);
        Session::flash('success', 'Categoria criada.');
        redirect('/admin/categories');
    }

    public function update(): void
    {
        Middleware::permission('categories.manage');
        $this->validateCsrf();
        $category = $this->categoryFromQuery();
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            Session::flash('error', 'Informe o nome da categoria.');
            redirect('/admin/categories/edit?id=' . $category['id']);
        }

        $parentId = (int) ($_POST['parent_id'] ?? 0);
        if ($parentId === (int) $category['id']) {
            $parentId = 0;
        }

        Category::update((int) $category['id'], [
            'parent_id' => $parentId ?: null,
            'name' => $name,
            'slug' => Category::uniqueSlug($name, (int) $category['id']),
            'description' => $_POST['description'] ?? '',
            'active' => isset($_POST['active']),
        ]);

        Logger::info('categories.updated', 'Categoria atualizada: ' . $name, current_user()['id']);
        Session::flash('success', 'Categoria atualizada.');
        redirect('/admin/categories');
    }

    public function delete(): void
    {
        Middleware::permission('categories.manage');
        $this->validateCsrf();
        $category = $this->categoryFromQuery();

        Category::delete((int) $category['id']);
        Logger::info('categories.deleted', 'Categoria removida: ' . $category['name'], current_user()['id']);
        Session::flash('success', 'Categoria removida. Notícias vinculadas ficaram sem categoria.');
        redirect('/admin/categories');
    }

    private function editing(): ?array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        return $id ? Category::find($id) : null;
    }

    private function categoryFromQuery(): array
    {
        $category = $this->editing();

        if (!$category) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $category;
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/categories');
        }
    }
}
