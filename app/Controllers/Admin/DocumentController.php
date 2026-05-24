<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Document;
use App\Models\SiteSetting;
use App\Models\User;

class DocumentController
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    private const DOCUMENT_FORMATS_SETTING = 'document_allowed_extensions';

    private const DEFAULT_ALLOWED_EXTENSIONS = [
        'pdf',
        'ai',
        'cdr',
        'eps',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'odt',
        'ods',
        'odp',
        'txt',
        'csv',
        'rtf',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'mp4',
        'mov',
        'avi',
        'mkv',
        'mp3',
        'wav',
        'zip',
        'rar',
        '7z',
    ];

    private const BLOCKED_EXTENSIONS = [
        'bat',
        'cmd',
        'com',
        'exe',
        'htaccess',
        'html',
        'htm',
        'js',
        'msi',
        'phtml',
        'phar',
        'php',
        'php3',
        'php4',
        'php5',
        'php7',
        'php8',
        'pl',
        'ps1',
        'py',
        'sh',
        'shtml',
        'vbs',
    ];

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
            'documents' => $this->canManageDocuments() ? Document::all() : Document::visibleForUser((int) current_user()['id']),
            'users' => User::activeForAccessLists(),
            'canManage' => $this->canManageDocuments(),
            'canUpload' => $this->canUploadDocuments(),
            'documentUploadUserIds' => Document::uploadUserIds(),
            'canManageFormats' => $this->currentUserIsMaster(),
            'allowedExtensions' => $this->allowedExtensions(),
            'allowedExtensionsText' => implode(', ', $this->allowedExtensions()),
            'allowedAccept' => $this->allowedAccept(),
        ]);
    }

    public function store(): void
    {
        $this->authorizeUpload();
        $this->validateCsrf();

        $linkUrl = $this->normalizeDocumentLink((string) ($_POST['document_url'] ?? ''));
        $hasFile = !empty($_FILES['document']['name']) && ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if (!$hasFile && $linkUrl === '') {
            Session::flash('error', 'Envie um arquivo ou informe um link valido.');
            redirect('/admin/documents');
        }

        $payload = $hasFile
            ? $this->storeUploadedDocument('document')
            : $this->documentLinkPayload($linkUrl, (string) ($_POST['title'] ?? ''));

        $documentId = Document::create([
            'uploaded_by' => current_user()['id'],
            'title' => trim($_POST['title'] ?? '') ?: pathinfo($payload['original_name'], PATHINFO_FILENAME),
            'path' => $payload['path'],
            'mime_type' => $payload['mime_type'],
            'original_name' => $payload['original_name'],
            'size_bytes' => $payload['size_bytes'],
            'is_public' => $this->canManageDocuments() && isset($_POST['is_public']),
            'allow_download' => !$this->canManageDocuments() || isset($_POST['allow_download']),
        ]);
        Document::updateAccess(
            $documentId,
            $this->canManageDocuments() && isset($_POST['is_public']),
            $this->canManageDocuments() ? ($_POST['user_ids'] ?? []) : [(int) current_user()['id']],
            !$this->canManageDocuments() || isset($_POST['allow_download'])
        );

        Session::flash('success', $hasFile ? 'Documento enviado.' : 'Link cadastrado.');
        redirect('/admin/documents');
    }

    public function update(): void
    {
        $this->validateCsrf();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document || !$this->canEditDocument($document)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'Informe o titulo do documento.');
            redirect('/admin/documents');
        }

        $data = ['title' => $title];
        if (!empty($_FILES['document']['name']) && ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $data = array_merge($data, $this->storeUploadedDocument('document'));
        } elseif (($linkUrl = $this->normalizeDocumentLink((string) ($_POST['document_url'] ?? ''))) !== '') {
            $data = array_merge($data, $this->documentLinkPayload($linkUrl, $title));
        }

        Document::update((int) $document['id'], $data);

        if ($this->canManageDocuments()) {
            Document::updateAccess((int) $document['id'], isset($_POST['is_public']), $_POST['user_ids'] ?? [], isset($_POST['allow_download']));
        }

        Session::flash('success', 'Documento atualizado.');
        redirect('/admin/documents');
    }

    public function formats(): void
    {
        $this->authorizeMaster();
        $this->validateCsrf();

        $extensions = $this->normalizeExtensions((string) ($_POST['allowed_extensions'] ?? ''));

        if (!$extensions) {
            Session::flash('error', 'Informe pelo menos um formato permitido.');
            redirect('/admin/documents');
        }

        SiteSetting::set(self::DOCUMENT_FORMATS_SETTING, implode(',', $extensions));
        Session::flash('success', 'Formatos de documentos atualizados.');
        redirect('/admin/documents');
    }

    public function download(): void
    {
        $this->authorizeView();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (
            !$document
            || (!$this->canManageDocuments() && !Document::userCanAccess((int) $document['id'], (int) current_user()['id']))
            || (!$this->canManageDocuments() && empty($document['allow_download']))
        ) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        if (Document::isExternalLink($document)) {
            header('Location: ' . $document['path']);
            exit;
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

    public function view(): void
    {
        $this->authorizeView();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document || (!$this->canManageDocuments() && !Document::userCanAccess((int) $document['id'], (int) current_user()['id']))) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        if (Document::isExternalLink($document)) {
            View::render('admin/documents/view-link', [
                'document' => $document,
                'canDownload' => $this->canManageDocuments() || !empty($document['allow_download']),
            ]);
            return;
        }

        $path = Document::absolutePath($document);
        if (!$path || !is_file($path)) {
            Session::flash('error', 'Arquivo não encontrado no servidor.');
            redirect('/admin/documents');
        }

        if (!isset($_GET['inline'])) {
            $canDownload = $this->canManageDocuments() || !empty($document['allow_download']);
            $viewer = $this->documentViewerData($document, $path, $canDownload);
            View::render('admin/documents/view', [
                'document' => $document,
                'canDownload' => $canDownload,
                'viewerType' => $viewer['type'],
                'documentSrc' => $viewer['src'],
                'documentText' => $viewer['text'],
            ]);
            return;
        }

        $filename = str_replace(['"', "\r", "\n"], '', basename($document['original_name']));

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $this->safeMimeType((string) $document['mime_type']));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=0, must-revalidate');

        readfile($path);
        exit;
    }

    private function documentExtensionIsAllowed(string $extension, string $mime): bool
    {
        if (!in_array($extension, $this->allowedExtensions(), true)) {
            return false;
        }

        if (($expectedExtension = self::ALLOWED_MIME_TYPES[$mime] ?? null) && $expectedExtension === $extension) {
            return true;
        }

        $zipBasedDocuments = ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'zip'];
        if (
            in_array($extension, $zipBasedDocuments, true)
            && in_array($mime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'], true)
        ) {
            return true;
        }

        return !isset(self::ALLOWED_MIME_TYPES[$mime]);
    }

    private function safeMimeType(string $mime): string
    {
        return preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $mime)
            ? $mime
            : 'application/octet-stream';
    }

    private function documentViewerData(array $document, string $path, bool $canDownload): array
    {
        $mime = strtolower((string) ($document['mime_type'] ?? ''));
        $extension = strtolower(pathinfo((string) ($document['original_name'] ?? ''), PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            return [
                'type' => 'image',
                'src' => url('/admin/documents/visualizar?id=' . $document['id'] . '&inline=1'),
                'text' => '',
            ];
        }

        if (in_array($mime, ['text/plain', 'text/csv'], true) || in_array($extension, ['txt', 'csv'], true)) {
            return [
                'type' => 'text',
                'src' => '',
                'text' => (string) file_get_contents($path),
            ];
        }

        if (($mime === 'application/pdf' || $extension === 'pdf') && $canDownload) {
            return [
                'type' => 'pdf',
                'src' => url('/admin/documents/visualizar?id=' . $document['id'] . '&inline=1'),
                'text' => '',
            ];
        }

        return [
            'type' => 'unavailable',
            'src' => '',
            'text' => '',
        ];
    }

    private function storeUploadedDocument(string $field): array
    {
        $file = $_FILES[$field] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Envie um documento valido.');
            redirect('/admin/documents');
        }

        if ((int) $file['size'] > self::MAX_FILE_SIZE) {
            Session::flash('error', 'O documento deve ter no maximo 10MB.');
            redirect('/admin/documents');
        }

        $mime = mime_content_type((string) $file['tmp_name']) ?: '';
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!$this->documentExtensionIsAllowed($extension, $mime)) {
            Session::flash('error', 'Tipo de documento nao permitido. Formatos liberados: ' . implode(', ', $this->allowedExtensions()) . '.');
            redirect('/admin/documents');
        }

        $directory = dirname(__DIR__, 3) . '/storage/documents';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_writable($directory)) {
            Session::flash('error', 'A pasta de documentos nao esta gravavel no servidor.');
            redirect('/admin/documents');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            Session::flash('error', 'Nao foi possivel salvar o documento.');
            redirect('/admin/documents');
        }

        return [
            'path' => '/storage/documents/' . $filename,
            'mime_type' => $mime ?: 'application/octet-stream',
            'original_name' => (string) $file['name'],
            'size_bytes' => (int) $file['size'],
        ];
    }

    private function normalizeDocumentLink(string $value): string
    {
        $value = trim($value);

        if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        if (strlen($value) > 255) {
            Session::flash('error', 'O link deve ter no maximo 255 caracteres.');
            redirect('/admin/documents');
        }

        return preg_match('#^https?://#i', $value) ? $value : '';
    }

    private function documentLinkPayload(string $url, string $title): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'link';
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $basename = basename($path);
        $name = $basename !== '' && $basename !== '/' ? $basename : ($title ?: $host);

        return [
            'path' => $url,
            'mime_type' => 'text/uri-list',
            'original_name' => $name,
            'size_bytes' => 0,
        ];
    }

    private function allowedExtensions(): array
    {
        $saved = SiteSetting::get(self::DOCUMENT_FORMATS_SETTING, implode(',', self::DEFAULT_ALLOWED_EXTENSIONS));
        return $this->normalizeExtensions($saved) ?: self::DEFAULT_ALLOWED_EXTENSIONS;
    }

    private function allowedAccept(): string
    {
        return implode(',', array_map(
            fn (string $extension): string => '.' . $extension,
            $this->allowedExtensions()
        ));
    }

    private function normalizeExtensions(string $value): array
    {
        $parts = preg_split('/[\s,;]+/', strtolower($value)) ?: [];
        $extensions = [];

        foreach ($parts as $part) {
            $extension = trim($part, ". \t\n\r\0\x0B");

            if (
                $extension === ''
                || !preg_match('/^[a-z0-9]{1,12}$/', $extension)
                || in_array($extension, self::BLOCKED_EXTENSIONS, true)
            ) {
                continue;
            }

            $extensions[] = $extension;
        }

        return array_values(array_unique($extensions));
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

        Document::updateAccess((int) $document['id'], isset($_POST['is_public']), $_POST['user_ids'] ?? [], isset($_POST['allow_download']));
        Session::flash('success', 'Acesso do documento atualizado.');
        redirect('/admin/documents');
    }

    public function uploaders(): void
    {
        $this->authorizeManage();
        $this->validateCsrf();

        Document::syncUploadUsers($_POST['user_ids'] ?? []);
        Session::flash('success', 'Usuarios autorizados a enviar documentos foram atualizados.');
        redirect('/admin/documents');
    }

    private function authorizeView(): void
    {
        $user = current_user();

        if (
            !$user
            || (!Auth::can('documents.view') && !$this->canManageDocuments() && !$this->canUploadDocuments() && !Document::userHasAnyAccess((int) $user['id']))
        ) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeManage(): void
    {
        if (!$this->canManageDocuments()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeUpload(): void
    {
        if (!$this->canUploadDocuments()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeMaster(): void
    {
        if (!$this->currentUserIsMaster()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function currentUserIsMaster(): bool
    {
        $user = current_user();
        return $user && Auth::hasRole('master');
    }

    private function canManageDocuments(): bool
    {
        return Auth::can('documents.manage') && !Auth::hasRole('diretor');
    }

    private function canUploadDocuments(): bool
    {
        $user = current_user();

        return $this->canManageDocuments()
            || ($user && Document::userCanUpload((int) $user['id']));
    }

    private function canEditDocument(array $document): bool
    {
        $user = current_user();

        return $this->canManageDocuments()
            || ($user && $this->canUploadDocuments() && (int) $document['uploaded_by'] === (int) $user['id']);
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/documents');
        }
    }
}
