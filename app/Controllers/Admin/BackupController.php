<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

class BackupController
{
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
}
