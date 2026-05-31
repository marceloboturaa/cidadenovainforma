<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\RegistrationNotifier;
use App\Core\Session;
use App\Core\View;
use App\Models\Document;
use App\Models\InstitutionPage;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserPresence;
use Throwable;

class UserController
{
    public function index(): void
    {
        Middleware::permission('users.manage');
        $users = User::all();
        $onlineUserIds = (current_user()['role_slug'] ?? '') === 'master' ? UserPresence::onlineUserIds() : [];

        View::render('admin/users/index', [
            'users' => $users,
            'onlineUserIds' => $onlineUserIds,
            'pendingUsers' => User::pending(),
            'registrationEnabled' => SiteSetting::registrationEnabled(),
            'institutionPages' => InstitutionPage::all(),
            'userResponsibilities' => $this->userResponsibilities($users),
            'documentUploadUserIds' => Document::uploadUserIds(),
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function password(): void
    {
        View::render('admin/users/password');
    }

    public function updatePassword(): void
    {
        $this->validateCsrf('/admin/password');

        $user = current_user();
        $currentPassword = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        if (!$user) {
            redirect('/login');
        }

        if (!password_verify($currentPassword, $user['password_hash'] ?? '')) {
            Session::flash('error', 'Senha atual incorreta.');
            redirect('/admin/password');
        }

        if (strlen($password) < 8 || $password !== $confirmation) {
            Session::flash('error', 'A nova senha precisa ter no mínimo 8 caracteres e confirmação igual.');
            redirect('/admin/password');
        }

        User::updatePassword((int) $user['id'], $password);
        Logger::info('users.password_changed', 'Senha alterada pelo próprio usuário.', (int) $user['id']);
        Session::flash('success', 'Senha alterada com sucesso.');
        redirect('/admin/password');
    }

    public function store(): void
    {
        Middleware::permission('users.manage');

        $this->validateCsrf();

        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $roleIds = $this->assignableRoleIds($_POST['role_ids'] ?? []);
        if ($roleId && !in_array($roleId, $roleIds, true)) {
            $roleIds[] = $roleId;
        }
        $role = $roleId ? Role::find($roleId) : null;

        if ($name === '' || !$email || strlen($password) < 8 || !$role) {
            Session::flash('error', 'Preencha nome, e-mail, cargo e senha com no minimo 8 caracteres.');
            redirect('/admin/users');
        }

        if (!$this->canAssignRole($role)) {
            Session::flash('error', 'Você não pode criar usuário com este cargo.');
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
            'role_ids' => $roleIds,
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
        RegistrationNotifier::userApproved($user);
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

        try {
            InstitutionPage::syncUserResponsibilities((int) $user['id'], $_POST['pages'] ?? []);
            Logger::info('users.institution_responsibilities', 'Responsabilidades institucionais atualizadas: ' . $user['email'], current_user()['id'] ?? null);
            Session::flash('success', 'Responsabilidades atualizadas para ' . $user['name'] . '.');
        } catch (Throwable $exception) {
            Logger::info('users.institution_responsibilities_error', 'Falha ao atualizar responsabilidades: ' . $exception->getMessage(), current_user()['id'] ?? null);
            Session::flash('error', 'Não foi possível atualizar os responsáveis. Verifique o banco e tente novamente.');
        }

        redirect('/admin/users');
    }

    public function documentUploads(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        Document::syncUploadUsers($_POST['user_ids'] ?? []);
        Logger::info('users.document_uploads', 'PermissÃµes de envio de documentos atualizadas.', current_user()['id'] ?? null);
        Session::flash('success', 'UsuÃ¡rios autorizados a enviar documentos foram atualizados.');
        redirect('/admin/users');
    }

    public function updateRole(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $roleIds = $this->assignableRoleIds($_POST['role_ids'] ?? []);
        $user = $id ? User::find($id) : null;
        $role = $roleId ? Role::find($roleId) : null;
        if ($roleId && !in_array($roleId, $roleIds, true)) {
            $roleIds[] = $roleId;
        }

        if (!$user || !$role) {
            Session::flash('error', 'Usuário ou cargo não encontrado.');
            redirect('/admin/users');
        }

        if ((int) $user['id'] === (int) (current_user()['id'] ?? 0) || !$this->canAssignRole($role)) {
            Session::flash('error', 'Você não pode alterar este cargo.');
            redirect('/admin/users');
        }

        $currentRoleSlugs = $this->roleSlugs(User::roleIds((int) $user['id']));
        if (in_array('master', $currentRoleSlugs, true)) {
            Session::flash('error', 'O cargo MASTER não pode ser alterado por esta tela.');
            redirect('/admin/users');
        }

        User::syncRoles((int) $user['id'], $roleIds, (int) $role['id']);
        Logger::info('users.role_updated', 'Cargos atualizados para: ' . $user['email'], current_user()['id'] ?? null);
        Session::flash('success', 'Cargos atualizados para ' . $user['name'] . '.');
        redirect('/admin/users');
    }

    public function update(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $user = $id ? User::find($id) : null;

        if (!$user || $name === '' || !$email) {
            Session::flash('error', 'Informe nome e e-mail válidos.');
            redirect('/admin/users');
        }

        $existing = User::findByEmail($email);
        if ($existing && (int) $existing['id'] !== (int) $user['id']) {
            Session::flash('error', 'Este e-mail já está em uso por outro usuário.');
            redirect('/admin/users');
        }

        User::updateProfile((int) $user['id'], $name, $email);
        Logger::info('users.updated', 'Dados do usuário atualizados: ' . $email, current_user()['id'] ?? null);
        Session::flash('success', 'Dados atualizados para ' . $name . '.');
        redirect('/admin/users');
    }

    public function status(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $active = ($_POST['active'] ?? '') === '1';
        $user = $id ? User::find($id) : null;

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            redirect('/admin/users');
        }

        if ((int) $user['id'] === (int) (current_user()['id'] ?? 0)) {
            Session::flash('error', 'Você não pode alterar o status do próprio usuário.');
            redirect('/admin/users');
        }

        $roleSlugs = $this->roleSlugs(User::roleIds((int) $user['id']));
        if (in_array('master', $roleSlugs, true)) {
            Session::flash('error', 'O usuário MASTER não pode ser inativado por esta tela.');
            redirect('/admin/users');
        }

        User::setActive((int) $user['id'], $active);
        Logger::info('users.status_updated', ($active ? 'Usuário ativado: ' : 'Usuário inativado: ') . $user['email'], current_user()['id'] ?? null);
        Session::flash('success', $active ? 'Usuário ativado.' : 'Usuário inativado.');
        redirect('/admin/users');
    }

    public function resetPassword(): void
    {
        Middleware::permission('users.manage');
        $this->masterOnly();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        $user = $id ? User::find($id) : null;

        if (!$user) {
            Session::flash('error', 'Usuário não encontrado.');
            redirect('/admin/users');
        }

        if (strlen($password) < 8 || $password !== $confirmation) {
            Session::flash('error', 'A nova senha precisa ter no mínimo 8 caracteres e confirmação igual.');
            redirect('/admin/users');
        }

        User::updatePassword((int) $user['id'], $password);
        Logger::info('users.password_reset', 'Senha redefinida para: ' . $user['email'], current_user()['id'] ?? null);
        Session::flash('success', 'Senha redefinida para ' . $user['name'] . '.');
        redirect('/admin/users');
    }

    private function validateCsrf(string $redirectTo = '/admin/users'): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($redirectTo);
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

    private function assignableRoles(): array
    {
        return array_values(array_filter(Role::all(), fn (array $role): bool => $this->canAssignRole($role)));
    }

    private function canAssignRole(array $role): bool
    {
        $current = current_user();

        if (($current['role_slug'] ?? '') !== 'master') {
            return false;
        }

        return ($role['slug'] ?? '') !== 'master';
    }

    private function assignableRoleIds(mixed $value): array
    {
        $ids = is_array($value) ? $value : [];
        $assignable = $this->assignableRoles();
        $assignableIds = array_map(fn (array $role): int => (int) $role['id'], $assignable);

        return array_values(array_intersect(array_unique(array_map('intval', $ids)), $assignableIds));
    }

    private function roleSlugs(array $roleIds): array
    {
        $roles = array_filter(array_map(fn (int $roleId): ?array => Role::find($roleId), $roleIds));

        return array_values(array_filter(array_map(fn (array $role): string => (string) ($role['slug'] ?? ''), $roles)));
    }
}
