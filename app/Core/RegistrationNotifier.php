<?php

namespace App\Core;

class RegistrationNotifier
{
    public static function eventStatus(array $event, array $person, string $status, bool $loginRequested = false): void
    {
        $email = filter_var($person['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return;
        }

        $name = trim((string) ($person['full_name'] ?? ''));
        $eventTitle = trim((string) ($event['title'] ?? 'evento'));
        $subject = match ($status) {
            'pendente' => 'Inscrição recebida - ' . $eventTitle,
            'inscrito' => 'Inscrição confirmada - ' . $eventTitle,
            'cancelado' => 'Inscrição não confirmada - ' . $eventTitle,
            'presente' => 'Presença registrada - ' . $eventTitle,
            'ausente' => 'Ausência registrada - ' . $eventTitle,
            default => 'Atualização da inscrição - ' . $eventTitle,
        };
        $statusText = match ($status) {
            'pendente' => 'recebida e está pendente de conferência pela equipe',
            'inscrito' => 'confirmada',
            'cancelado' => 'não foi confirmada ou foi cancelada',
            'presente' => 'marcada como presente',
            'ausente' => 'marcada como ausente',
            default => 'atualizada',
        };

        $loginText = $loginRequested
            ? '<p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:#4b5563;">Sua solicitação de login também foi registrada. O acesso ao painel só será liberado após aprovação do administrador.</p>'
            : '';

        $html = '<div style="margin:0;padding:0;background:#f3f6fa;font-family:Arial,Helvetica,sans-serif;color:#1d171b;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#f3f6fa;">'
            . '<tr><td align="center" style="padding:34px 16px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;border-collapse:collapse;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 18px 46px rgba(17,24,39,.12);">'
            . '<tr><td style="background:#1d171b;padding:24px 30px;text-align:center;color:#ffffff;"><strong style="font-size:28px;">Cidade Nova Informa</strong></td></tr>'
            . '<tr><td style="padding:30px 30px 12px;">'
            . '<div style="font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:#c5161d;">Inscrição de evento</div>'
            . '<h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;color:#1d171b;">' . e($subject) . '</h1>'
            . '<p style="margin:22px 0 0;font-size:15px;line-height:1.7;color:#4b5563;">Olá' . ($name !== '' ? ', ' . e($name) : '') . '.</p>'
            . '<p style="margin:12px 0 0;font-size:15px;line-height:1.7;color:#4b5563;">Sua inscrição para <strong>' . e($eventTitle) . '</strong> foi ' . e($statusText) . '.</p>'
            . $loginText
            . '<p style="margin:18px 0 0;font-size:13px;line-height:1.7;color:#6b7280;">Mensagem automática. Em caso de dúvidas, aguarde contato da equipe responsável.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:20px 30px 30px;"><div style="padding:14px 16px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Status atual: <strong>' . e(ucfirst($status)) . '</strong></div></td></tr>'
            . '</table>'
            . '</td></tr></table>'
            . '</div>';

        $text = "Olá" . ($name !== '' ? ', ' . $name : '') . ".\n\n"
            . 'Sua inscrição para "' . $eventTitle . '" foi ' . $statusText . ".\n"
            . ($loginRequested ? "\nSua solicitação de login também foi registrada e depende de aprovação do administrador.\n" : '')
            . "\nStatus atual: " . ucfirst($status);

        try {
            Mailer::send((string) $email, $subject, $html, $text);
        } catch (\Throwable $exception) {
            Logger::info('event_registration.email_failed', 'Falha ao enviar e-mail de inscrição: ' . $exception->getMessage(), null);
        }
    }

    public static function userApproved(array $user): void
    {
        $email = filter_var($user['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return;
        }

        $html = '<p>Olá, ' . e($user['name'] ?? '') . '.</p>'
            . '<p>Seu login no Cidade Nova Informa foi aprovado. Você já pode acessar o painel com o e-mail e a senha cadastrados.</p>'
            . '<p><a href="' . e(url('/login')) . '">Entrar no painel</a></p>';
        $text = "Olá, " . ($user['name'] ?? '') . ".\n\nSeu login no Cidade Nova Informa foi aprovado.\n\nAcesse: " . url('/login');

        try {
            Mailer::send((string) $email, 'Login aprovado - Cidade Nova Informa', $html, $text);
        } catch (\Throwable $exception) {
            Logger::info('users.approved_email_failed', 'Falha ao enviar e-mail de aprovação: ' . $exception->getMessage(), (int) ($user['id'] ?? 0));
        }
    }
}
