<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Person;

class PersonController
{
    public function index(): void
    {
        Middleware::permission('people.manage');
        $query = trim((string) ($_GET['q'] ?? ''));

        View::render('admin/people/index', [
            'people' => Person::all($query),
            'editing' => $this->editing(),
            'query' => $query,
            'canDeactivate' => $this->currentUserIsMaster(),
        ]);
    }

    public function store(): void
    {
        Middleware::permission('people.manage');
        $this->validateCsrf();
        $name = trim((string) ($_POST['full_name'] ?? ''));

        if ($name === '') {
            Session::flash('error', 'Informe o nome completo.');
            redirect('/admin/people');
        }

        $userId = (int) (current_user()['id'] ?? 0);
        $id = Person::create(array_merge($_POST, [
            'created_by' => $userId ?: null,
            'updated_by' => $userId ?: null,
        ]));

        Logger::info('people.created', 'Pessoa cadastrada: ' . $name, $userId ?: null);
        Session::flash('success', 'Pessoa cadastrada. ID: ' . $id);
        redirect('/admin/people');
    }

    public function update(): void
    {
        Middleware::permission('people.manage');
        $this->validateCsrf();
        $person = $this->personFromQuery();
        $name = trim((string) ($_POST['full_name'] ?? ''));

        if ($name === '') {
            Session::flash('error', 'Informe o nome completo.');
            redirect('/admin/people/edit?id=' . $person['id']);
        }

        $userId = (int) (current_user()['id'] ?? 0);
        Person::update((int) $person['id'], array_merge($_POST, [
            'updated_by' => $userId ?: null,
        ]));

        Logger::info('people.updated', 'Pessoa atualizada: ' . $name, $userId ?: null);
        Session::flash('success', 'Cadastro atualizado.');
        redirect('/admin/people');
    }

    public function delete(): void
    {
        Middleware::permission('people.manage');
        $this->masterOnly();
        $this->validateCsrf();
        $person = $this->personFromQuery();

        Person::deactivate((int) $person['id']);
        Logger::info('people.deactivated', 'Pessoa desativada: ' . $person['full_name'], current_user()['id'] ?? null);
        Session::flash('success', 'Cadastro desativado.');
        redirect('/admin/people');
    }

    private function editing(): ?array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        return $id ? Person::find($id) : null;
    }

    private function personFromQuery(): array
    {
        $person = $this->editing();

        if (!$person) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $person;
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/people');
        }
    }

    private function masterOnly(): void
    {
        if (!$this->currentUserIsMaster()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function currentUserIsMaster(): bool
    {
        $user = Auth::user();
        return $user && ($user['role_slug'] ?? '') === 'master';
    }
}
