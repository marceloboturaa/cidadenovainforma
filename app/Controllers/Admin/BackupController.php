<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;

class BackupController
{
    private const NEWS_EXPORT_FILE = 'cni-news-export.json';

    public function index(): void
    {
        $this->masterOnly();

        View::render('admin/backups/index', [
            'canZip' => class_exists('ZipArchive'),
            'canShell' => function_exists('shell_exec'),
        ]);
    }

    public function download(): void
    {
        $this->masterOnly();

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/backups');
        }

        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'A extensão ZipArchive do PHP não está habilitada.');
            redirect('/admin/backups');
        }

        $backupPath = $this->createBackup();
        $filename = basename($backupPath);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($backupPath));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');

        readfile($backupPath);
        unlink($backupPath);
        exit;
    }

    public function exportNews(): void
    {
        $this->masterOnly();
        $this->validateCsrf();

        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'A extensão ZipArchive do PHP não está habilitada.');
            redirect('/admin/backups');
        }

        $backupPath = $this->createNewsExport();
        $filename = basename($backupPath);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($backupPath));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');

        readfile($backupPath);
        unlink($backupPath);
        exit;
    }

    public function importNews(): void
    {
        $this->masterOnly();
        $this->validateCsrf();

        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'A extensão ZipArchive do PHP não está habilitada.');
            redirect('/admin/backups');
        }

        if (empty($_FILES['news_backup']['name']) || $_FILES['news_backup']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Envie um arquivo de backup de notícias.');
            redirect('/admin/backups');
        }

        $result = $this->restoreNewsExport(
            $_FILES['news_backup']['tmp_name'],
            ($_POST['mode'] ?? '') === 'update'
        );

        Session::flash(
            'success',
            'Importação concluída: ' . $result['created'] . ' criada(s), ' . $result['updated'] . ' atualizada(s), ' . $result['skipped'] . ' ignorada(s).'
        );
        redirect('/admin/backups');
    }

    private function createBackup(): string
    {
        $root = dirname(__DIR__, 3);
        $backupDir = $root . '/storage/backups';
        $tempDir = $root . '/storage/temp/backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $sqlPath = $tempDir . '/database.sql';
        $this->dumpDatabase($sqlPath);

        $manifest = [
            'app' => 'Cidade Nova Informa',
            'created_at' => date('c'),
            'contains' => ['database.sql', 'public/uploads'],
            'restore_order' => [
                '1. Envie os arquivos do projeto para a hospedagem.',
                '2. Importe database.sql no MySQL da hospedagem.',
                '3. Envie a pasta public/uploads para o mesmo caminho no servidor.',
                '4. Ajuste config/database.php com os dados do banco online.',
            ],
        ];

        file_put_contents($tempDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $zipPath = $backupDir . '/cni-backup-' . date('Ymd-His') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->removeDirectory($tempDir);
            Session::flash('error', 'Não foi possível criar o arquivo de backup.');
            redirect('/admin/backups');
        }

        $zip->addFile($sqlPath, 'database.sql');
        $zip->addFile($tempDir . '/manifest.json', 'manifest.json');
        $this->addDirectoryToZip($zip, $root . '/public/uploads', 'public/uploads');
        $zip->close();

        $this->removeDirectory($tempDir);

        return $zipPath;
    }

    private function createNewsExport(): string
    {
        $root = dirname(__DIR__, 3);
        $backupDir = $root . '/storage/backups';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $payload = $this->newsExportPayload();
        $zipPath = $backupDir . '/cni-noticias-' . date('Ymd-His') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Session::flash('error', 'Não foi possível criar o arquivo de exportação.');
            redirect('/admin/backups');
        }

        $zip->addFromString(
            self::NEWS_EXPORT_FILE,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        foreach ($payload['news'] as $news) {
            if (empty($news['cover_image'])) {
                continue;
            }

            $coverPath = $this->publicUploadPath($news['cover_image']);
            if ($coverPath && is_file($coverPath)) {
                $zip->addFile($coverPath, ltrim($news['cover_image'], '/'));
            }
        }

        $zip->close();

        return $zipPath;
    }

    private function newsExportPayload(): array
    {
        $db = Database::connection();
        $newsRows = $db->query(
            'SELECT news.*, categories.name AS category_name, categories.slug AS category_slug
             FROM news
             LEFT JOIN categories ON categories.id = news.category_id
             ORDER BY news.created_at ASC, news.id ASC'
        )->fetchAll();

        $tagsStmt = $db->prepare(
            'SELECT tags.name, tags.slug
             FROM tags
             INNER JOIN news_tags ON news_tags.tag_id = tags.id
             WHERE news_tags.news_id = :news_id
             ORDER BY tags.name ASC'
        );

        foreach ($newsRows as &$news) {
            $tagsStmt->execute(['news_id' => $news['id']]);
            $news['tags'] = $tagsStmt->fetchAll();
            unset($news['id'], $news['author_id'], $news['approved_by'], $news['category_id'], $news['region_id']);
        }
        unset($news);

        return [
            'app' => 'Cidade Nova Informa',
            'type' => 'news-export',
            'version' => 1,
            'created_at' => date('c'),
            'news_count' => count($newsRows),
            'news' => $newsRows,
        ];
    }

    private function restoreNewsExport(string $uploadedFile, bool $updateExisting): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($uploadedFile) !== true) {
            Session::flash('error', 'Não foi possível abrir o arquivo ZIP enviado.');
            redirect('/admin/backups');
        }

        $json = $zip->getFromName(self::NEWS_EXPORT_FILE);
        if ($json === false) {
            $zip->close();
            Session::flash('error', 'Este ZIP não parece ser uma exportação de notícias válida.');
            redirect('/admin/backups');
        }

        $payload = json_decode($json, true);
        if (!is_array($payload) || ($payload['type'] ?? '') !== 'news-export' || !isset($payload['news']) || !is_array($payload['news'])) {
            $zip->close();
            Session::flash('error', 'O arquivo de notícias está inválido ou corrompido.');
            redirect('/admin/backups');
        }

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $db = Database::connection();
        $db->beginTransaction();

        try {
            foreach ($payload['news'] as $news) {
                if (empty($news['title']) || empty($news['slug']) || empty($news['content'])) {
                    $result['skipped']++;
                    continue;
                }

                $existingId = $this->findNewsIdBySlug($news['slug']);

                if ($existingId && !$updateExisting) {
                    $result['skipped']++;
                    continue;
                }

                $this->copyCoverFromZip($zip, $news['cover_image'] ?? null);
                $categoryId = $this->categoryIdForImport($news);
                $newsId = $existingId
                    ? $this->updateImportedNews($existingId, $news, $categoryId)
                    : $this->createImportedNews($news, $categoryId);

                $this->syncImportedTags($newsId, $news['tags'] ?? []);
                $existingId ? $result['updated']++ : $result['created']++;
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            $zip->close();
            Session::flash('error', 'Falha ao importar notícias: ' . $exception->getMessage());
            redirect('/admin/backups');
        }

        $zip->close();

        return $result;
    }

    private function createImportedNews(array $news, ?int $categoryId): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO news
                (author_id, category_id, title, slug, summary, content, cover_image, type, status, featured, urgent, is_archive, original_published_at, original_author, original_source, original_url, archive_note, views, published_at, created_at, updated_at)
             VALUES
                (:author_id, :category_id, :title, :slug, :summary, :content, :cover_image, :type, :status, :featured, :urgent, :is_archive, :original_published_at, :original_author, :original_source, :original_url, :archive_note, :views, :published_at, :created_at, :updated_at)'
        );
        $stmt->execute($this->importNewsPayload($news, $categoryId));

        return (int) $db->lastInsertId();
    }

    private function updateImportedNews(int $id, array $news, ?int $categoryId): int
    {
        $payload = $this->importNewsPayload($news, $categoryId);
        $payload['id'] = $id;
        unset($payload['author_id'], $payload['created_at']);

        Database::connection()->prepare(
            'UPDATE news SET
                category_id = :category_id,
                title = :title,
                slug = :slug,
                summary = :summary,
                content = :content,
                cover_image = :cover_image,
                type = :type,
                status = :status,
                featured = :featured,
                urgent = :urgent,
                is_archive = :is_archive,
                original_published_at = :original_published_at,
                original_author = :original_author,
                original_source = :original_source,
                original_url = :original_url,
                archive_note = :archive_note,
                views = :views,
                published_at = :published_at,
                updated_at = :updated_at
             WHERE id = :id'
        )->execute($payload);

        return $id;
    }

    private function importNewsPayload(array $news, ?int $categoryId): array
    {
        return [
            'author_id' => current_user()['id'],
            'category_id' => $categoryId,
            'title' => trim((string) $news['title']),
            'slug' => trim((string) $news['slug']),
            'summary' => trim((string) ($news['summary'] ?? '')),
            'content' => (string) $news['content'],
            'cover_image' => $this->cleanImportedCover($news['cover_image'] ?? null),
            'type' => in_array($news['type'] ?? '', ['noticia', 'reportagem', 'artigo', 'coluna'], true) ? $news['type'] : 'noticia',
            'status' => in_array($news['status'] ?? '', ['draft', 'pending', 'rejected', 'published', 'archived'], true) ? $news['status'] : 'draft',
            'featured' => (int) !empty($news['featured']),
            'urgent' => (int) !empty($news['urgent']),
            'is_archive' => (int) !empty($news['is_archive']),
            'original_published_at' => !empty($news['original_published_at']) ? $news['original_published_at'] : null,
            'original_author' => trim((string) ($news['original_author'] ?? '')),
            'original_source' => trim((string) ($news['original_source'] ?? '')),
            'original_url' => trim((string) ($news['original_url'] ?? '')),
            'archive_note' => trim((string) ($news['archive_note'] ?? '')),
            'views' => max(0, (int) ($news['views'] ?? 0)),
            'published_at' => !empty($news['published_at']) ? $news['published_at'] : null,
            'created_at' => !empty($news['created_at']) ? $news['created_at'] : date('Y-m-d H:i:s'),
            'updated_at' => !empty($news['updated_at']) ? $news['updated_at'] : date('Y-m-d H:i:s'),
        ];
    }

    private function categoryIdForImport(array $news): ?int
    {
        if (empty($news['category_slug']) || empty($news['category_name'])) {
            return null;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $news['category_slug']]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        $db->prepare(
            'INSERT INTO categories (name, slug, active, created_at, updated_at)
             VALUES (:name, :slug, 1, NOW(), NOW())'
        )->execute([
            'name' => trim((string) $news['category_name']),
            'slug' => trim((string) $news['category_slug']),
        ]);

        return (int) $db->lastInsertId();
    }

    private function syncImportedTags(int $newsId, array $tags): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM news_tags WHERE news_id = :news_id')->execute(['news_id' => $newsId]);

        $findTag = $db->prepare('SELECT id FROM tags WHERE slug = :slug LIMIT 1');
        $insertTag = $db->prepare('INSERT INTO tags (name, slug, created_at) VALUES (:name, :slug, NOW())');
        $attachTag = $db->prepare('INSERT IGNORE INTO news_tags (news_id, tag_id) VALUES (:news_id, :tag_id)');

        foreach ($tags as $tag) {
            if (empty($tag['name']) || empty($tag['slug'])) {
                continue;
            }

            $findTag->execute(['slug' => $tag['slug']]);
            $tagId = $findTag->fetchColumn();

            if (!$tagId) {
                $insertTag->execute([
                    'name' => trim((string) $tag['name']),
                    'slug' => trim((string) $tag['slug']),
                ]);
                $tagId = $db->lastInsertId();
            }

            $attachTag->execute(['news_id' => $newsId, 'tag_id' => (int) $tagId]);
        }
    }

    private function findNewsIdBySlug(string $slug): ?int
    {
        $stmt = Database::connection()->prepare('SELECT id FROM news WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $id = $stmt->fetchColumn();

        return $id ? (int) $id : null;
    }

    private function copyCoverFromZip(\ZipArchive $zip, ?string $cover): void
    {
        $cover = $this->cleanImportedCover($cover);
        if (!$cover) {
            return;
        }

        $entry = ltrim($cover, '/');
        if ($zip->locateName($entry) === false) {
            return;
        }

        $target = $this->publicUploadPath($cover);
        if (!$target) {
            return;
        }

        $directory = dirname($target);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $source = $zip->getStream($entry);
        if (!$source) {
            return;
        }

        $destination = fopen($target, 'wb');
        if ($destination) {
            stream_copy_to_stream($source, $destination);
            fclose($destination);
        }

        fclose($source);
    }

    private function cleanImportedCover(?string $cover): ?string
    {
        if (!$cover) {
            return null;
        }

        $cover = str_replace('\\', '/', $cover);
        if (!preg_match('#^/public/uploads/news/[A-Za-z0-9._-]+\.(jpe?g|png|webp)$#i', $cover)) {
            return null;
        }

        return $cover;
    }

    private function publicUploadPath(string $publicPath): ?string
    {
        $publicPath = $this->cleanImportedCover($publicPath);
        if (!$publicPath) {
            return null;
        }

        return dirname(__DIR__, 3) . $publicPath;
    }

    private function dumpDatabase(string $sqlPath): void
    {
        if (!function_exists('shell_exec')) {
            file_put_contents($sqlPath, '-- shell_exec esta desabilitado. Exporte o banco manualmente pelo phpMyAdmin.' . PHP_EOL);
            return;
        }

        $config = require dirname(__DIR__, 3) . '/config/database.php';
        $mysqldump = is_file('C:/xampp/mysql/bin/mysqldump.exe')
            ? 'C:/xampp/mysql/bin/mysqldump.exe'
            : 'mysqldump';

        $command = escapeshellarg($mysqldump)
            . ' --default-character-set=utf8mb4 --single-transaction --routines --triggers'
            . ' --host=' . escapeshellarg($config['host'])
            . ' --port=' . escapeshellarg((string) $config['port'])
            . ' --user=' . escapeshellarg($config['username']);

        if ($config['password'] !== '') {
            $command .= ' --password=' . escapeshellarg($config['password']);
        }

        $command .= ' ' . escapeshellarg($config['database'])
            . ' > ' . escapeshellarg($sqlPath) . ' 2>&1';

        shell_exec($command);

        if (!is_file($sqlPath) || filesize($sqlPath) < 100) {
            file_put_contents($sqlPath, '-- Falha ao gerar dump automatico. Verifique mysqldump, usuario e senha do banco.' . PHP_EOL);
        }
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $directory, string $zipBase): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $path = $file->getPathname();
            $relative = $zipBase . '/' . str_replace('\\', '/', substr($path, strlen($directory) + 1));

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($path, $relative);
            }
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }

    private function masterOnly(): void
    {
        $user = Auth::user();

        if (!$user || $user['role_slug'] !== 'master') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function validateCsrf(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/backups');
        }
    }
}
