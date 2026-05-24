<?php

namespace App\Models;

use App\Core\Database;

class Document
{
    public static function ensureSchema(): void
    {
        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS team_documents (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uploaded_by BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(120) NOT NULL,
                original_name VARCHAR(190) NOT NULL,
                size_bytes BIGINT UNSIGNED NOT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 0,
                allow_download TINYINT(1) NOT NULL DEFAULT 1,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_team_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $columns = Database::connection()->query('SHOW COLUMNS FROM team_documents')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('is_public', $columns, true)) {
            Database::connection()->exec('ALTER TABLE team_documents ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER size_bytes');
        }
        if (!in_array('allow_download', $columns, true)) {
            Database::connection()->exec('ALTER TABLE team_documents ADD COLUMN allow_download TINYINT(1) NOT NULL DEFAULT 1 AFTER is_public');
        }

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS team_document_users (
                document_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (document_id, user_id),
                CONSTRAINT fk_team_document_users_document FOREIGN KEY (document_id) REFERENCES team_documents(id) ON DELETE CASCADE,
                CONSTRAINT fk_team_document_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS team_document_upload_users (
                user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                created_at TIMESTAMP NULL,
                CONSTRAINT fk_team_document_upload_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        self::migratePublicDocumentsToStorage();
    }

    public static function absolutePath(array $document): ?string
    {
        if (self::isExternalLink($document)) {
            return null;
        }

        $root = dirname(__DIR__, 2);
        $path = str_replace('\\', '/', (string) ($document['path'] ?? ''));

        if ($path === '' || str_contains($path, '../')) {
            return null;
        }

        if (str_starts_with($path, '/storage/documents/')) {
            return $root . $path;
        }

        if (str_starts_with($path, 'storage/documents/')) {
            return $root . '/' . $path;
        }

        if (str_starts_with($path, '/public/uploads/documents/')) {
            return $root . $path;
        }

        return null;
    }

    public static function isExternalLink(array $document): bool
    {
        return preg_match('#^https?://#i', (string) ($document['path'] ?? '')) === 1;
    }

    public static function publicUrl(array $document): string
    {
        return url('/documentos/visualizar?id=' . $document['id']);
    }

    public static function typeLabel(array $document): string
    {
        if (self::isExternalLink($document)) {
            return 'LINK';
        }

        return strtoupper(pathinfo($document['original_name'] ?? '', PATHINFO_EXTENSION) ?: 'ARQ');
    }

    public static function canPreviewInline(array $document): bool
    {
        if (self::isExternalLink($document)) {
            return true;
        }

        $mime = strtolower((string) ($document['mime_type'] ?? ''));
        $extension = strtolower(pathinfo((string) ($document['original_name'] ?? ''), PATHINFO_EXTENSION));

        return str_starts_with($mime, 'image/')
            || in_array($mime, ['application/pdf', 'text/plain', 'text/csv'], true)
            || in_array($extension, ['pdf', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }

    private static function migratePublicDocumentsToStorage(): void
    {
        $root = dirname(__DIR__, 2);
        $storageDir = $root . '/storage/documents';

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $stmt = Database::connection()->query(
            "SELECT id, path FROM team_documents
             WHERE path LIKE '/public/uploads/documents/%'"
        );
        $update = Database::connection()->prepare('UPDATE team_documents SET path = :path, updated_at = NOW() WHERE id = :id');

        foreach ($stmt->fetchAll() as $document) {
            $oldRelative = str_replace('\\', '/', (string) $document['path']);

            if (str_contains($oldRelative, '../')) {
                continue;
            }

            $oldPath = $root . $oldRelative;
            if (!is_file($oldPath)) {
                continue;
            }

            $filename = basename($oldRelative);
            $newRelative = '/storage/documents/' . $filename;
            $newPath = $root . $newRelative;

            if (!is_file($newPath)) {
                rename($oldPath, $newPath);
            }

            $update->execute([
                'id' => $document['id'],
                'path' => $newRelative,
            ]);
        }
    }

    public static function all(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                'SELECT team_documents.*, users.name AS uploader_name
                 FROM team_documents
                 INNER JOIN users ON users.id = team_documents.uploaded_by
                 WHERE team_documents.active = 1
                 ORDER BY team_documents.created_at DESC, team_documents.id DESC'
            )
            ->fetchAll();
    }

    public static function visibleForUser(int $userId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT team_documents.*, users.name AS uploader_name
             FROM team_documents
             INNER JOIN users ON users.id = team_documents.uploaded_by
             LEFT JOIN team_document_users ON team_document_users.document_id = team_documents.id
             WHERE team_documents.active = 1
               AND (team_documents.is_public = 1 OR team_document_users.user_id = :user_id)
             ORDER BY team_documents.created_at DESC, team_documents.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function publicAll(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                'SELECT team_documents.*, users.name AS uploader_name
                 FROM team_documents
                 INNER JOIN users ON users.id = team_documents.uploaded_by
                 WHERE team_documents.active = 1 AND team_documents.is_public = 1
                 ORDER BY team_documents.created_at DESC, team_documents.id DESC'
            )
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT team_documents.*, users.name AS uploader_name
             FROM team_documents
             INNER JOIN users ON users.id = team_documents.uploaded_by
             WHERE team_documents.id = :id AND team_documents.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function fileExistsOnServer(array $document): bool
    {
        $path = self::absolutePath($document);

        return $path !== null && is_file($path);
    }

    public static function userCanAccess(int $documentId, int $userId): bool
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM team_documents
             LEFT JOIN team_document_users ON team_document_users.document_id = team_documents.id
             WHERE team_documents.id = :document_id
               AND team_documents.active = 1
               AND (team_documents.is_public = 1 OR team_document_users.user_id = :user_id)
             LIMIT 1'
        );
        $stmt->execute([
            'document_id' => $documentId,
            'user_id' => $userId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public static function userHasAnyAccess(int $userId): bool
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM team_documents
             LEFT JOIN team_document_users ON team_document_users.document_id = team_documents.id
             WHERE team_documents.active = 1
               AND (team_documents.is_public = 1 OR team_document_users.user_id = :user_id)
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO team_documents (uploaded_by, title, path, mime_type, original_name, size_bytes, is_public, allow_download, active, created_at, updated_at)
             VALUES (:uploaded_by, :title, :path, :mime_type, :original_name, :size_bytes, :is_public, :allow_download, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'uploaded_by' => $data['uploaded_by'],
            'title' => trim($data['title']),
            'path' => $data['path'],
            'mime_type' => $data['mime_type'],
            'original_name' => $data['original_name'],
            'size_bytes' => $data['size_bytes'],
            'is_public' => (int) !empty($data['is_public']),
            'allow_download' => (int) !empty($data['allow_download']),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        self::ensureSchema();

        $fields = [
            'title = :title',
            'updated_at = NOW()',
        ];
        $params = [
            'id' => $id,
            'title' => trim((string) $data['title']),
        ];

        foreach (['path', 'mime_type', 'original_name', 'size_bytes'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        Database::connection()
            ->prepare('UPDATE team_documents SET ' . implode(', ', $fields) . ' WHERE id = :id')
            ->execute($params);
    }

    public static function updateAccess(int $id, bool $isPublic, array $userIds, bool $allowDownload = true): void
    {
        self::ensureSchema();

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('UPDATE team_documents SET is_public = :is_public, allow_download = :allow_download, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'id' => $id,
                    'is_public' => (int) $isPublic,
                    'allow_download' => (int) $allowDownload,
                ]);

            $db->prepare('DELETE FROM team_document_users WHERE document_id = :document_id')
                ->execute(['document_id' => $id]);

            $stmt = $db->prepare(
                'INSERT IGNORE INTO team_document_users (document_id, user_id, created_at)
                 VALUES (:document_id, :user_id, NOW())'
            );

            foreach (array_unique(array_map('intval', $userIds)) as $userId) {
                if ($userId > 0) {
                    $stmt->execute([
                        'document_id' => $id,
                        'user_id' => $userId,
                    ]);
                }
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function accessUserIds(int $id): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT user_id FROM team_document_users WHERE document_id = :document_id');
        $stmt->execute(['document_id' => $id]);

        return array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
    }

    public static function uploadUserIds(): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->query('SELECT user_id FROM team_document_upload_users');

        return array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
    }

    public static function userCanUpload(int $userId): bool
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM team_document_upload_users WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function syncUploadUsers(array $userIds): void
    {
        self::ensureSchema();

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->exec('DELETE FROM team_document_upload_users');
            $stmt = $db->prepare(
                'INSERT IGNORE INTO team_document_upload_users (user_id, created_at)
                 VALUES (:user_id, NOW())'
            );

            foreach (array_unique(array_map('intval', $userIds)) as $userId) {
                if ($userId > 0) {
                    $stmt->execute(['user_id' => $userId]);
                }
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function deactivate(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE team_documents SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
