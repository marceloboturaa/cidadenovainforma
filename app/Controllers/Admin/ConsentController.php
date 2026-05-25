<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\Consent;

class ConsentController
{
    public function index(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.view');

        View::render('admin/consent/index', [
            'settings' => Consent::settings(),
            'categories' => Consent::categories(),
            'scripts' => Consent::scripts(),
            'records' => Consent::records(80),
            'audits' => Consent::auditLogs(80),
            'stats' => Consent::stats(),
            'canEditTexts' => Auth::can('consent.texts') || Auth::can('consent.manage'),
            'canManage' => Auth::can('consent.manage'),
        ]);
    }

    public function settings(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.texts');
        $this->validateCsrf();

        Consent::updateSettings($_POST, current_user()['id'] ?? null);
        Session::flash('success', 'Configurações de consentimento atualizadas.');
        redirect('/admin/consent');
    }

    public function category(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.manage');
        $this->validateCsrf();

        try {
            Consent::saveCategory($_POST, current_user()['id'] ?? null);
            Session::flash('success', 'Categoria salva.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('/admin/consent#categorias');
    }

    public function deleteCategory(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.manage');
        $this->validateCsrf();

        try {
            Consent::deleteCategory((int) ($_POST['id'] ?? 0), current_user()['id'] ?? null);
            Session::flash('success', 'Categoria removida.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('/admin/consent#categorias');
    }

    public function script(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.manage');
        $this->validateCsrf();

        try {
            Consent::saveScript($_POST, current_user()['id'] ?? null);
            Session::flash('success', 'Script salvo.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('/admin/consent#scripts');
    }

    public function deleteScript(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.manage');
        $this->validateCsrf();

        Consent::deleteScript((int) ($_POST['id'] ?? 0), current_user()['id'] ?? null);
        Session::flash('success', 'Script removido.');
        redirect('/admin/consent#scripts');
    }

    public function export(): void
    {
        Consent::bootstrap();
        Middleware::permission('consent.view');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio-consentimentos-lgpd.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Visitante', 'Usuário', 'IP anonimizado', 'Navegador', 'Versão', 'Preferências', 'Origem', 'Data']);
        foreach (Consent::exportRows() as $row) {
            fputcsv($out, [
                $row['id'],
                $row['visitor_id'],
                $row['user_name'] ?? '',
                $row['ip_anonymized'],
                $row['user_agent'],
                $row['policy_version'],
                $row['preferences_json'],
                $row['source'],
                $row['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Token inválido. Tente novamente.');
            redirect('/admin/consent');
        }
    }
}
