<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Models\Permission;
use App\Models\Role;

class AuthorizationController
{
    public function index(): void
    {
        $this->masterOnly();
        $roles = Role::all();
        $selectedRole = $this->selectedRole($roles);

        View::render('admin/authorizations/index', [
            'roles' => $roles,
            'permissions' => Permission::all(),
            'selectedRole' => $selectedRole,
            'selectedPermissionIds' => $selectedRole ? Role::permissionIds((int) $selectedRole['id']) : [],
        ]);
    }

    public function update(): void
    {
        $this->masterOnly();

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin/authorizations');
        }

        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $role = $roleId ? Role::find($roleId) : null;

        if (!$role || ($role['slug'] ?? '') === 'master') {
            Session::flash('error', 'Este cargo nao pode ser alterado por aqui.');
            redirect('/admin/authorizations');
        }

        Role::syncPermissions((int) $role['id'], $_POST['permission_ids'] ?? []);
        Logger::info('authorizations.updated', 'Permissoes atualizadas para o cargo: ' . $role['slug'], current_user()['id'] ?? null);
        Session::flash('success', 'Permissoes atualizadas para ' . $role['name'] . '.');
        redirect('/admin/authorizations?role_id=' . $role['id']);
    }

    private function selectedRole(array $roles): ?array
    {
        $roleId = filter_input(INPUT_GET, 'role_id', FILTER_VALIDATE_INT);

        if ($roleId) {
            foreach ($roles as $role) {
                if ((int) $role['id'] === $roleId) {
                    return $role;
                }
            }
        }

        foreach ($roles as $role) {
            if (($role['slug'] ?? '') !== 'master') {
                return $role;
            }
        }

        return null;
    }

    private function masterOnly(): void
    {
        if (!Auth::hasRole('master')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }
}
