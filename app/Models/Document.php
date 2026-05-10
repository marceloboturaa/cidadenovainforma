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

        self::migratePublicDocumentsToStorage();
    }

    public static function absolutePath(array $document): ?string
    {
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

        $stmt = Database::connection()->prepare('SELECT * FROM team_documents WHERE id = :id AND active = 1 LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
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
            'INSERT INTO team_documents (uploaded_by, title, path, mime_type, original_name, size_bytes, is_public, active, created_at, updated_at)
             VALUES (:uploaded_by, :title, :path, :mime_type, :original_name, :size_bytes, :is_public, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'uploaded_by' => $data['uploaded_by'],
            'title' => trim($data['title']),
            'path' => $data['path'],
            'mime_type' => $data['mime_type'],
            'original_name' => $data['original_name'],
            'size_bytes' => $data['size_bytes'],
            'is_public' => (int) !empty($data['is_public']),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateAccess(int $id, bool $isPublic, array $userIds): void
    {
        self::ensureSchema();

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('UPDATE team_documents SET is_public = :is_public, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'id' => $id,
                    'is_public' => (int) $isPublic,
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

    public static function deactivate(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE team_documents SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
