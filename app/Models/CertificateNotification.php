<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Mailer;

class CertificateNotification
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }
        Announcement::ensureSchema();
        Database::connection()->exec('CREATE TABLE IF NOT EXISTS certificate_notifications (
            certificate_id BIGINT UNSIGNED PRIMARY KEY,
            announcement_id BIGINT UNSIGNED NULL,
            email_sent_at DATETIME NULL,
            email_attempted_at DATETIME NULL,
            email_attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_error VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_certificate_notifications_email (email_sent_at, email_attempted_at),
            CONSTRAINT fk_certificate_notification_certificate FOREIGN KEY (certificate_id) REFERENCES education_certificates(id) ON DELETE CASCADE,
            CONSTRAINT fk_certificate_notification_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE SET NULL
        ) ENGINE=InnoDB');
        self::$schemaReady = true;
    }

    /** Failure to notify must never undo an issued certificate. The worker retries. */
    public static function notify(int $certificateId): bool
    {
        try {
            $db = Database::connection();
            if ($db->inTransaction()) {
                return false;
            }
            self::ensureSchema();
            $stmt = $db->prepare('SELECT c.id, c.user_id, c.status, u.name, u.email, ec.title
                FROM education_certificates c
                INNER JOIN users u ON u.id = c.user_id AND u.active = 1
                INNER JOIN education_courses ec ON ec.id = c.course_id
                WHERE c.id = :id');
            $stmt->execute(['id' => $certificateId]);
            $certificate = $stmt->fetch();
            if (!$certificate) {
                return false;
            }
            if ($certificate['status'] !== 'issued') {
                $db->prepare('UPDATE announcements SET active = 0 WHERE id IN
                    (SELECT announcement_id FROM certificate_notifications WHERE certificate_id = :id)')
                    ->execute(['id' => $certificateId]);
                return false;
            }
            $path = '/admin/education/certificate?certificate_id=' . $certificateId;
            $db->beginTransaction();
            try {
                // The unique certificate key serializes concurrent notification attempts.
                $db->prepare('INSERT IGNORE INTO certificate_notifications (certificate_id, created_at) VALUES (:id, NOW())')
                    ->execute(['id' => $certificateId]);
                $lock = $db->prepare('SELECT announcement_id FROM certificate_notifications WHERE certificate_id = :id FOR UPDATE');
                $lock->execute(['id' => $certificateId]);
                if (!$lock->fetchColumn()) {
                    $db->prepare('INSERT INTO announcements (title, body, url, button_label, active, created_at, updated_at)
                        VALUES (:title, :body, :url, :label, 1, NOW(), NOW())')->execute([
                            'title' => 'Seu certificado está pronto!',
                            'body' => 'O certificado de ' . $certificate['title'] . ' já está disponível. Clique abaixo para visualizar e imprimir.',
                            'url' => $path,
                            'label' => 'Abrir certificado',
                        ]);
                    $announcementId = (int) $db->lastInsertId();
                    $db->prepare('INSERT INTO announcement_recipients (announcement_id, user_id, created_at) VALUES (:notice, :user, NOW())')
                        ->execute(['notice' => $announcementId, 'user' => $certificate['user_id']]);
                    $db->prepare('UPDATE certificate_notifications SET announcement_id = :notice WHERE certificate_id = :id')
                        ->execute(['notice' => $announcementId, 'id' => $certificateId]);
                }
                $db->prepare('UPDATE announcements SET active = 1 WHERE id IN
                    (SELECT announcement_id FROM certificate_notifications WHERE certificate_id = :id)')
                    ->execute(['id' => $certificateId]);
                $db->commit();
            } catch (\Throwable $exception) {
                $db->rollBack();
                throw $exception;
            }

            $claim = $db->prepare('UPDATE certificate_notifications SET email_attempted_at = NOW(), email_attempts = email_attempts + 1
                WHERE certificate_id = :id AND email_sent_at IS NULL
                AND (email_attempted_at IS NULL OR email_attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE))');
            $claim->execute(['id' => $certificateId]);
            if (!$claim->rowCount()) {
                return true;
            }
            try {
                if (!filter_var($certificate['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('O destinatário não tem um e-mail válido.');
                }
                $link = url($path);
                $html = '<p>Olá, ' . e($certificate['name']) . '!</p><p>Seu certificado de <strong>'
                    . e($certificate['title']) . '</strong> está pronto.</p><p><a href="' . e($link)
                    . '">Abrir certificado</a></p><p>Entre com sua conta para visualizar e imprimir o certificado.</p>';
                $text = 'Olá, ' . $certificate['name'] . "!\nSeu certificado de " . $certificate['title']
                    . " está pronto.\nEntre com sua conta para visualizar e imprimir: " . $link;
                if (!Mailer::send($certificate['email'], 'Seu certificado está pronto!', $html, $text)) {
                    throw new \RuntimeException('O serviço de e-mail não aceitou o envio.');
                }
                $db->prepare('UPDATE certificate_notifications SET email_sent_at = NOW(), last_error = NULL WHERE certificate_id = :id')
                    ->execute(['id' => $certificateId]);
                return true;
            } catch (\Throwable $exception) {
                $db->prepare('UPDATE certificate_notifications SET last_error = :error WHERE certificate_id = :id')
                    ->execute(['error' => 'Falha no envio. Verifique o endereço e a configuração de e-mail.', 'id' => $certificateId]);
                return false;
            }
        } catch (\Throwable $exception) {
            error_log('Certificate notification failed for certificate #' . $certificateId);
            return false;
        }
    }

    public static function processPending(int $limit = 50): array
    {
        Education::ensureSchema();
        self::ensureSchema();
        $limit = max(1, min(200, $limit));
        $rows = Database::connection()->query('SELECT c.id FROM education_certificates c
            INNER JOIN users u ON u.id = c.user_id AND u.active = 1
            LEFT JOIN certificate_notifications n ON n.certificate_id = c.id
            WHERE c.status = "issued" AND n.email_sent_at IS NULL
            AND (n.email_attempted_at IS NULL OR n.email_attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE))
            ORDER BY COALESCE(n.email_attempted_at, "1970-01-01"), c.id LIMIT ' . $limit)->fetchAll();
        $result = ['processed' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $result['processed']++;
            if (!self::notify((int) $row['id'])) {
                $result['failed']++;
            }
        }
        return $result;
    }
}
