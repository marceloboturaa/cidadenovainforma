<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/admin');
        }

        View::render('auth/login', [], 'auth');
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/login');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password || !Auth::attempt($email, $password)) {
            Session::flash('error', 'E-mail ou senha invalidos.');
            redirect('/login');
        }

        redirect('/admin');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/admin');
        }

        View::render('auth/register', [
            'registrationEnabled' => SiteSetting::registrationEnabled(),
        ], 'auth');
    }

    public function register(): void
    {
        if (!SiteSetting::registrationEnabled()) {
            Session::flash('error', 'Novos cadastros estão bloqueados no momento.');
            redirect('/register');
        }

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/register');
        }

        $name = trim($_POST['name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        if ($name === '' || !$email || strlen($password) < 8 || $password !== $confirmation) {
            Session::flash('error', 'Preencha nome, e-mail e senha com confirmação igual.');
            redirect('/register');
        }

        if (User::findByEmail($email)) {
            Session::flash('error', 'Este e-mail já possui cadastro.');
            redirect('/register');
        }

        $role = Role::findBySlug('jornalista');
        if (!$role) {
            Session::flash('error', 'Não foi possível criar o cadastro agora.');
            redirect('/register');
        }

        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => $role['id'],
            'active' => 0,
        ]);

        Logger::info('users.registration_requested', 'Novo cadastro aguardando aprovação: ' . $email, $userId);
        Session::flash('success', 'Cadastro enviado. Aguarde aprovação do administrador master para acessar o painel.');
        redirect('/login');
    }

    public function logout(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/login');
        }

        Auth::logout();
        redirect('/login');
    }

    public function showForgot(): void
    {
        View::render('auth/forgot', [], 'auth');
    }

    public function forgot(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/forgot-password');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $user = $email ? User::findByEmail($email) : null;

        if ($user) {
            $token = bin2hex(random_bytes(32));
            User::storeResetToken((int) $user['id'], hash('sha256', $token), date('Y-m-d H:i:s', strtotime('+1 hour')));
            Logger::info('auth.password_reset_requested', 'Token de recuperação gerado.', (int) $user['id']);

            // Em produção, envie este link por e-mail. Em ambiente local, ele fica visível para teste.
            Session::flash('reset_link', url('/reset-password?token=' . $token));
        }

        Session::flash('success', 'Se o e-mail existir, enviaremos instruções de recuperação.');
        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        View::render('auth/reset', ['token' => $_GET['token'] ?? ''], 'auth');
    }

    public function reset(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/reset-password?token=' . urlencode($_POST['token'] ?? ''));
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        $reset = User::findValidReset($token);

        if (!$reset || strlen($password) < 8 || $password !== $confirmation) {
            Session::flash('error', 'Token inválido ou senha fora do padrão.');
            redirect('/reset-password?token=' . urlencode($token));
        }

        User::updatePassword((int) $reset['user_id'], $password);
        User::markResetUsed((int) $reset['id']);
        Logger::info('auth.password_reset_completed', 'Senha redefinida.', (int) $reset['user_id']);

        Session::flash('success', 'Senha alterada. Faça login para continuar.');
        redirect('/login');
    }
}
