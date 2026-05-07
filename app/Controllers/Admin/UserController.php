<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\InstitutionPage;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;

class UserController
{
    public function index(): void
    {
        Middleware::permission('users.manage');
        $users = User::all();

        View::render('admin/users/index', [
            'users' => $users,
            'pendingUsers' => User::pending(),
            'registrationEnabled' => SiteSetting::registrationEnabled(),
            'institutionPages' => InstitutionPage::all(),
            'userResponsibilities' => $this->userResponsibilities($users),
            'roles' => Role::all(),
        ]);
    }

    public function store(): void
    {
        Middleware::permission('users.manage');

        $this->validateCsrf();

        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

        if ($name === '' || !$email || strlen($password) < 8 || !$roleId) {
            Session::flash('error', 'Preencha nome, e-mail, cargo e senha com no minimo 8 caracteres.');
            redirect('/admin/users');
        }

        if (User::findByEmail($email)) {
            Session::flash('error', 'Este e-mail ja esta cadastrado.');
            redirect('/admin/users');
        }

        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => $roleId,
        ]);

        Logger::info('users.created', 'Usuário criado.', current_user()['id'] ?? null);
        Session::flash('success', 'Usuário criado com sucesso. ID: ' . $userId);
        redirect('/admin/users');
    }

    public function toggleRegistrations(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $enabled = ($_POST['enabled'] ?? '') === '1';
        SiteSetting::setRegistrationEnabled($enabled);

        Logger::info(
            'users.registration_toggle',
            $enabled ? 'Novos cadastros autorizados.' : 'Novos cadastros bloqueados.',
            current_user()['id'] ?? null
        );
        Session::flash('success', $enabled ? 'Novos cadastros foram autorizados.' : 'Novos cadastros foram bloqueados.');
        redirect('/admin/users');
    }

    public function approve(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $user = $id ? User::find($id) : null;

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            redirect('/admin/users');
        }

        User::activate((int) $user['id']);
        Logger::info('users.approved', 'Cadastro aprovado: ' . $user['email'], current_user()['id'] ?? null);
        Session::flash('success', 'Cadastro aprovado para ' . $user['name'] . '.');
        redirect('/admin/users');
    }

    public function responsibilities(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $user = $id ? User::find($id) : null;

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            redirect('/admin/users');
        }

        InstitutionPage::syncUserResponsibilities((int) $user['id'], $_POST['pages'] ?? []);
        Logger::info('users.institution_responsibilities', 'Responsabilidades institucionais atualizadas: ' . $user['email'], current_user()['id'] ?? null);
        Session::flash('success', 'Responsabilidades atualizadas para ' . $user['name'] . '.');
        redirect('/admin/users');
    }

    public function resetPassword(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';
        $user = $id ? User::find($id) : null;

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            redirect('/admin/users');
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'A nova senha precisa ter no mínimo 8 caracteres.');
            redirect('/admin/users');
        }

        User::updatePassword((int) $user['id'], $password);
        Logger::info('users.password_reset', 'Senha redefinida para: ' . $user['email'], current_user()['id'] ?? null);
        Session::flash('success', 'Senha redefinida para ' . $user['name'] . '.');
        redirect('/admin/users');
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/users');
        }
    }

    private function masterOnly(): void
    {
        if ((current_user()['role_slug'] ?? '') !== 'master') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function userResponsibilities(array $users): array
    {
        $result = [];

        foreach ($users as $user) {
            $result[(int) $user['id']] = InstitutionPage::userResponsibilities((int) $user['id']);
        }

        return $result;
    }
}
