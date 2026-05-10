<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Document;
use App\Models\User;

class DocumentController
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/vnd.oasis.opendocument.presentation' => 'odp',
        'text/plain' => 'txt',
        'application/zip' => 'zip',
    ];

    public function index(): void
    {
        $this->authorizeView();

        View::render('admin/documents/index', [
            'documents' => Auth::can('documents.manage') ? Document::all() : Document::visibleForUser((int) current_user()['id']),
            'users' => User::activeForAccessLists(),
            'canManage' => Auth::can('documents.manage'),
        ]);
    }

    public function store(): void
    {
        $this->authorizeManage();
        $this->validateCsrf();

        if (empty($_FILES['document']['name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Envie um documento válido.');
            redirect('/admin/documents');
        }

        if ($_FILES['document']['size'] > self::MAX_FILE_SIZE) {
            Session::flash('error', 'O documento deve ter no máximo 10MB.');
            redirect('/admin/documents');
        }

        $mime = mime_content_type($_FILES['document']['tmp_name']) ?: '';
        $extension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));

        if (!$this->documentExtensionIsAllowed($extension, $mime)) {
            Session::flash('error', 'Tipo de documento não permitido.');
            redirect('/admin/documents');
        }

        $directory = dirname(__DIR__, 3) . '/storage/documents';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
            Session::flash('error', 'Não foi possível salvar o documento.');
            redirect('/admin/documents');
        }

        $documentId = Document::create([
            'uploaded_by' => current_user()['id'],
            'title' => trim($_POST['title'] ?? '') ?: pathinfo($_FILES['document']['name'], PATHINFO_FILENAME),
            'path' => '/storage/documents/' . $filename,
            'mime_type' => $mime ?: 'application/octet-stream',
            'original_name' => $_FILES['document']['name'],
            'size_bytes' => (int) $_FILES['document']['size'],
            'is_public' => isset($_POST['is_public']),
        ]);
        Document::updateAccess($documentId, isset($_POST['is_public']), $_POST['user_ids'] ?? []);

        Session::flash('success', 'Documento enviado.');
        redirect('/admin/documents');
    }

    public function download(): void
    {
        $this->authorizeView();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document || (!Auth::can('documents.manage') && !Document::userCanAccess((int) $document['id'], (int) current_user()['id']))) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $path = Document::absolutePath($document);
        if (!$path || !is_file($path)) {
            Session::flash('error', 'Arquivo não encontrado no servidor.');
            redirect('/admin/documents');
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Download-Options: noopen');
        header('Content-Type: ' . $this->safeMimeType((string) $document['mime_type']));
        $downloadName = str_replace(['"', "\r", "\n"], '', basename($document['original_name']));

        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');

        readfile($path);
        exit;
    }

    private function documentExtensionIsAllowed(string $extension, string $mime): bool
    {
        if (!in_array($extension, self::ALLOWED_MIME_TYPES, true)) {
            return false;
        }

        if (($expectedExtension = self::ALLOWED_MIME_TYPES[$mime] ?? null) && $expectedExtension === $extension) {
            return true;
        }

        $zipBasedDocuments = ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'zip'];
        return in_array($extension, $zipBasedDocuments, true)
            && in_array($mime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'], true);
    }

    private function safeMimeType(string $mime): string
    {
        return preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $mime)
            ? $mime
            : 'application/octet-stream';
    }

    public function delete(): void
    {
        $this->authorizeManage();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            Document::deactivate($id);
        }

        Session::flash('success', 'Documento removido da lista.');
        redirect('/admin/documents');
    }

    public function access(): void
    {
        $this->authorizeManage();
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document) {
            Session::flash('error', 'Documento não encontrado.');
            redirect('/admin/documents');
        }

        Document::updateAccess((int) $document['id'], isset($_POST['is_public']), $_POST['user_ids'] ?? []);
        Session::flash('success', 'Acesso do documento atualizado.');
        redirect('/admin/documents');
    }

    private function authorizeView(): void
    {
        $user = current_user();

        if (
            !$user
            || (!Auth::can('documents.view') && !Auth::can('documents.manage') && !Document::userHasAnyAccess((int) $user['id']))
        ) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeManage(): void
    {
        if (!Auth::can('documents.manage')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/documents');
        }
    }
}
