<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\SimplePdf;
use App\Core\View;
use App\Models\Person;

class PersonController
{
    public function index(): void
    {
        Middleware::permission('people.manage');
        $query = trim((string) ($_GET['q'] ?? ''));

        View::render('admin/people/index', [
            'people' => Person::all($query, $this->volunteerScopeUserId()),
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

    public function export(): void
    {
        Middleware::permission('people.manage');

        $format = strtolower((string) ($_GET['format'] ?? 'csv'));
        $people = Person::all(trim((string) ($_GET['q'] ?? '')), $this->volunteerScopeUserId());

        if ($format === 'pdf') {
            $lines = [];
            foreach ($people as $index => $person) {
                $lines[] = sprintf(
                    '%d. %s | CPF: %s | WhatsApp: %s | E-mail: %s | Cidade: %s/%s | Contato: %s',
                    $index + 1,
                    $person['full_name'] ?? '',
                    $person['cpf'] ?: '-',
                    $person['whatsapp'] ?: ($person['phone'] ?: '-'),
                    $person['email'] ?: '-',
                    $person['city'] ?: '-',
                    $person['state'] ?: '-',
                    !empty($person['contact_authorized']) ? 'autorizado' : 'nao autorizado'
                );
            }

            $this->downloadPdf('lista-pessoas.pdf', 'Lista de pessoas cadastradas', $lines);
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lista-pessoas.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nome', 'CPF', 'Nascimento', 'Telefone', 'WhatsApp', 'E-mail', 'CEP', 'Endereco', 'Numero', 'Complemento', 'Bairro', 'Cidade', 'UF', 'Menor', 'Responsavel', 'Parentesco', 'CPF responsavel', 'Telefone responsavel', 'E-mail responsavel', 'Contato autorizado', 'Observacoes'], ';');
        foreach ($people as $person) {
            fputcsv($output, [
                $person['full_name'] ?? '',
                $person['cpf'] ?? '',
                $person['birth_date'] ?? '',
                $person['phone'] ?? '',
                $person['whatsapp'] ?? '',
                $person['email'] ?? '',
                $person['cep'] ?? '',
                $person['address'] ?? '',
                $person['address_number'] ?? '',
                $person['address_complement'] ?? '',
                $person['district'] ?? '',
                $person['city'] ?? '',
                $person['state'] ?? '',
                !empty($person['is_minor']) ? 'Sim' : 'Nao',
                $person['guardian_name'] ?? '',
                $person['guardian_relation'] ?? '',
                $person['guardian_cpf'] ?? '',
                $person['guardian_phone'] ?? '',
                $person['guardian_email'] ?? '',
                !empty($person['contact_authorized']) ? 'Sim' : 'Nao',
                $person['notes'] ?? '',
            ], ';');
        }
        fclose($output);
        exit;
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

        $scopeUserId = $this->volunteerScopeUserId();
        if ($scopeUserId !== null && (int) ($person['created_by'] ?? 0) !== $scopeUserId) {
            http_response_code(403);
            View::render('errors/403');
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

    private function downloadPdf(string $filename, string $title, array $lines): void
    {
        $pdf = SimplePdf::fromLines($title, $lines ?: ['Nenhum registro encontrado.']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function volunteerScopeUserId(): ?int
    {
        $user = Auth::user();

        if (!$user || !Auth::hasRole(['voluntario', 'equipe']) || Auth::hasRole(['master', 'admin', 'admin-local', 'diretor'])) {
            return null;
        }

        return (int) $user['id'];
    }
}
