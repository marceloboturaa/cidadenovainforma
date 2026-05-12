<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Mailer;
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
        Session::flash('success', 'Obrigado pelo cadastro. Sua solicitação está sendo averiguada e em breve retornaremos.');
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

            $resetLink = url('/reset-password?token=' . urlencode($token));
            $emailSent = $this->sendPasswordResetEmail($user, $resetLink);

            $config = require dirname(__DIR__, 2) . '/config/app.php';
            $mailConfig = require dirname(__DIR__, 2) . '/config/mail.php';
            $mailer = strtolower((string) ($mailConfig['mailer'] ?? 'mail'));
            $smtpIncomplete = $mailer === 'smtp' && (
                empty($mailConfig['host'])
                || empty($mailConfig['username'])
                || empty($mailConfig['password'])
            );

            if (!$emailSent || $smtpIncomplete || ($config['env'] ?? 'production') !== 'production' || !empty($config['debug'])) {
                Session::flash('reset_link', $resetLink);
            }
        }

        Session::flash('success', 'Se o e-mail existir, enviaremos instruções de recuperação.');
        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        View::render('auth/reset', ['token' => trim($_GET['token'] ?? '')], 'auth');
    }

    public function reset(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/reset-password?token=' . urlencode($_POST['token'] ?? ''));
        }

        $token = trim($_POST['token'] ?? '');
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

    private function sendPasswordResetEmail(array $user, string $resetLink): bool
    {
        $name = trim((string) ($user['name'] ?? ''));
        $greeting = $name !== '' ? 'Olá, ' . $name . '.' : 'Olá.';
        $safeLink = e($resetLink);
        $html = '<div style="margin:0;padding:0;background:#f3f6fa;font-family:Arial,Helvetica,sans-serif;color:#1d171b;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#f3f6fa;">'
            . '<tr><td align="center" style="padding:34px 16px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;border-collapse:collapse;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 18px 46px rgba(17,24,39,.12);">'
            . '<tr><td style="background:#1d171b;padding:28px 32px;text-align:center;">'
            . '<div style="font-size:42px;line-height:1;font-weight:900;letter-spacing:2px;color:#ffffff;">CNI</div>'
            . '<div style="margin-top:10px;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#f0c8ca;">Cidade Nova Informa</div>'
            . '</td></tr>'
            . '<tr><td style="padding:34px 32px 12px;">'
            . '<div style="font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:#c5161d;">Redefinição de senha</div>'
            . '<h1 style="margin:8px 0 0;font-size:25px;line-height:1.25;color:#1d171b;">Atualize seu acesso</h1>'
            . '<p style="margin:22px 0 0;font-size:15px;line-height:1.7;color:#4b5563;">' . e($greeting) . '</p>'
            . '<p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:#4b5563;">Recebemos uma solicitação para alterar a senha do seu acesso ao painel do Cidade Nova Informa.</p>'
            . '<p style="margin:26px 0 28px;text-align:center;"><a href="' . $safeLink . '" style="display:inline-block;padding:13px 22px;border-radius:8px;background:#c5161d;color:#ffffff;font-size:14px;font-weight:800;text-decoration:none;">Criar nova senha</a></p>'
            . '<p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">Este link expira em 1 hora. Se você não solicitou a alteração, ignore este e-mail.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:20px 32px 32px;">'
            . '<div style="padding:14px 16px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;">'
            . '<p style="margin:0 0 8px;font-size:12px;line-height:1.5;color:#6b7280;">Se o botão não funcionar, copie e cole este link no navegador:</p>'
            . '<a href="' . $safeLink . '" style="font-size:12px;line-height:1.5;color:#c5161d;word-break:break-all;text-decoration:none;">' . $safeLink . '</a>'
            . '</div>'
            . '</td></tr>'
            . '</table>'
            . '<p style="margin:18px 0 0;font-size:12px;color:#9ca3af;">Mensagem automática do Cidade Nova Informa.</p>'
            . '</td></tr></table>'
            . '</div>';
        $text = $greeting . "\n\n"
            . "Recebemos uma solicitação para alterar a senha do seu acesso ao Cidade Nova Informa.\n\n"
            . "Acesse o link abaixo para criar uma nova senha:\n"
            . $resetLink . "\n\n"
            . "Este link expira em 1 hora. Se você não solicitou a alteração, ignore este e-mail.";

        try {
            if (Mailer::send((string) $user['email'], 'Alteração de senha - Cidade Nova Informa', $html, $text)) {
                Logger::info('auth.password_reset_email_sent', 'E-mail de recuperação enviado.', (int) $user['id']);
                return true;
            }
        } catch (\Throwable $exception) {
            Logger::info('auth.password_reset_email_failed', 'Falha ao enviar e-mail de recuperação: ' . $exception->getMessage(), (int) $user['id']);
        }

        return false;
    }
}
