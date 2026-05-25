<?php

namespace App\Models;

use App\Core\Database;

class Consent
{
    private const DEFAULT_VERSION = '1.0';

    public static function bootstrap(): void
    {
        self::ensureSchema();
        self::seedAccess();
        self::seedDefaults();
    }

    public static function settings(): array
    {
        self::bootstrap();

        $row = Database::connection()->query('SELECT * FROM consent_settings WHERE id = 1 LIMIT 1')->fetch() ?: [];

        return [
            'banner_title' => $row['banner_title'] ?? 'Controle de privacidade',
            'banner_text' => $row['banner_text'] ?? 'Usamos cookies para melhorar sua experiência. Você pode aceitar, rejeitar ou personalizar os cookies opcionais.',
            'policy_title' => $row['policy_title'] ?? 'Política de Cookies',
            'policy_text' => $row['policy_text'] ?? 'Esta política explica como o Cidade Nova Informa utiliza cookies necessários e opcionais em conformidade com a LGPD.',
            'policy_version' => $row['policy_version'] ?? self::DEFAULT_VERSION,
            'accept_label' => $row['accept_label'] ?? 'Aceitar tudo',
            'reject_label' => $row['reject_label'] ?? 'Rejeitar tudo',
            'customize_label' => $row['customize_label'] ?? 'Personalizar',
            'save_label' => $row['save_label'] ?? 'Salvar preferências',
            'primary_color' => $row['primary_color'] ?? '#b91c1c',
            'secondary_color' => $row['secondary_color'] ?? '#111827',
            'background_color' => $row['background_color'] ?? '#ffffff',
            'text_color' => $row['text_color'] ?? '#111827',
        ];
    }

    public static function categories(bool $activeOnly = false): array
    {
        self::bootstrap();

        $sql = 'SELECT * FROM consent_categories';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return Database::connection()->query($sql)->fetchAll();
    }

    public static function scripts(bool $activeOnly = false): array
    {
        self::bootstrap();

        $sql = 'SELECT consent_scripts.*, consent_categories.name AS category_name, consent_categories.slug AS category_slug
                FROM consent_scripts
                INNER JOIN consent_categories ON consent_categories.id = consent_scripts.category_id';
        if ($activeOnly) {
            $sql .= ' WHERE consent_scripts.active = 1 AND consent_categories.active = 1 AND consent_categories.required = 0';
        }
        $sql .= ' ORDER BY consent_categories.sort_order ASC, consent_scripts.name ASC';

        return Database::connection()->query($sql)->fetchAll();
    }

    public static function publicConfig(): array
    {
        $settings = self::settings();
        $categories = array_map(static fn (array $category): array => [
            'slug' => $category['slug'],
            'name' => $category['name'],
            'description' => $category['description'],
            'required' => (bool) $category['required'],
        ], self::categories(true));
        $scripts = array_map(static fn (array $script): array => [
            'category' => $script['category_slug'],
            'name' => $script['name'],
            'type' => $script['script_type'],
            'src' => $script['src'],
            'code' => $script['code'],
            'position' => $script['position'],
        ], self::scripts(true));

        return [
            'settings' => $settings,
            'categories' => $categories,
            'scripts' => $scripts,
            'policyUrl' => url('/politica-de-cookies'),
            'apiUrl' => url('/api/consent'),
        ];
    }

    public static function updateSettings(array $data, ?int $userId): void
    {
        self::bootstrap();

        $current = self::settings();
        $version = trim((string) ($data['policy_version'] ?? $current['policy_version']));
        if ($version === $current['policy_version']) {
            $version = self::nextVersion($version);
        }

        Database::connection()->prepare(
            'UPDATE consent_settings
             SET banner_title = :banner_title,
                 banner_text = :banner_text,
                 policy_title = :policy_title,
                 policy_text = :policy_text,
                 policy_version = :policy_version,
                 accept_label = :accept_label,
                 reject_label = :reject_label,
                 customize_label = :customize_label,
                 save_label = :save_label,
                 primary_color = :primary_color,
                 secondary_color = :secondary_color,
                 background_color = :background_color,
                 text_color = :text_color,
                 updated_by = :updated_by,
                 updated_at = NOW()
             WHERE id = 1'
        )->execute([
            'banner_title' => trim((string) ($data['banner_title'] ?? $current['banner_title'])),
            'banner_text' => trim((string) ($data['banner_text'] ?? $current['banner_text'])),
            'policy_title' => trim((string) ($data['policy_title'] ?? $current['policy_title'])),
            'policy_text' => trim((string) ($data['policy_text'] ?? $current['policy_text'])),
            'policy_version' => $version,
            'accept_label' => trim((string) ($data['accept_label'] ?? $current['accept_label'])),
            'reject_label' => trim((string) ($data['reject_label'] ?? $current['reject_label'])),
            'customize_label' => trim((string) ($data['customize_label'] ?? $current['customize_label'])),
            'save_label' => trim((string) ($data['save_label'] ?? $current['save_label'])),
            'primary_color' => self::color($data['primary_color'] ?? $current['primary_color'], '#b91c1c'),
            'secondary_color' => self::color($data['secondary_color'] ?? $current['secondary_color'], '#111827'),
            'background_color' => self::color($data['background_color'] ?? $current['background_color'], '#ffffff'),
            'text_color' => self::color($data['text_color'] ?? $current['text_color'], '#111827'),
            'updated_by' => $userId,
        ]);

        self::audit('settings.update', 'Textos, política ou aparência atualizados.', $userId);
    }

    public static function saveCategory(array $data, ?int $userId): void
    {
        self::bootstrap();

        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $slug = self::slug((string) ($data['slug'] ?? $name));
        if ($name === '' || $slug === '') {
            throw new \InvalidArgumentException('Informe nome e slug da categoria.');
        }

        $existing = null;
        if ($id > 0) {
            $stmt = Database::connection()->prepare('SELECT required, slug FROM consent_categories WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $existing = $stmt->fetch() ?: null;
        }
        $lockedRequired = $slug === 'necessarios' || (int) ($existing['required'] ?? 0) === 1;

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($data['description'] ?? '')),
            'required' => $lockedRequired || !empty($data['required']) ? 1 : 0,
            'active' => $lockedRequired || !empty($data['active']) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'updated_by' => $userId,
        ];

        if ($id > 0) {
            $payload['id'] = $id;
            Database::connection()->prepare(
                'UPDATE consent_categories
                 SET name = :name, slug = :slug, description = :description, required = :required,
                     active = :active, sort_order = :sort_order, updated_by = :updated_by, updated_at = NOW()
                 WHERE id = :id'
            )->execute($payload);
            self::audit('category.update', 'Categoria atualizada: ' . $name, $userId);
            return;
        }

        Database::connection()->prepare(
            'INSERT INTO consent_categories
                (name, slug, description, required, active, sort_order, created_by, updated_by, created_at, updated_at)
             VALUES
                (:name, :slug, :description, :required, :active, :sort_order, :updated_by, :updated_by, NOW(), NOW())'
        )->execute($payload);
        self::audit('category.create', 'Categoria criada: ' . $name, $userId);
    }

    public static function deleteCategory(int $id, ?int $userId): void
    {
        self::bootstrap();

        $stmt = Database::connection()->prepare('SELECT required, name FROM consent_categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();
        if (!$category || (int) $category['required'] === 1) {
            throw new \InvalidArgumentException('Categorias necessárias não podem ser removidas.');
        }

        Database::connection()->prepare('DELETE FROM consent_categories WHERE id = :id')->execute(['id' => $id]);
        self::audit('category.delete', 'Categoria removida: ' . ($category['name'] ?? $id), $userId);
    }

    public static function saveScript(array $data, ?int $userId): void
    {
        self::bootstrap();

        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($name === '' || $categoryId <= 0) {
            throw new \InvalidArgumentException('Informe nome e categoria do script.');
        }

        $payload = [
            'category_id' => $categoryId,
            'name' => $name,
            'provider' => trim((string) ($data['provider'] ?? '')),
            'script_type' => in_array(($data['script_type'] ?? 'inline'), ['src', 'inline'], true) ? $data['script_type'] : 'inline',
            'src' => trim((string) ($data['src'] ?? '')),
            'code' => trim((string) ($data['code'] ?? '')),
            'position' => in_array(($data['position'] ?? 'footer'), ['head', 'footer'], true) ? $data['position'] : 'footer',
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_by' => $userId,
        ];

        if ($id > 0) {
            $payload['id'] = $id;
            Database::connection()->prepare(
                'UPDATE consent_scripts
                 SET category_id = :category_id, name = :name, provider = :provider, script_type = :script_type,
                     src = :src, code = :code, position = :position, active = :active,
                     updated_by = :updated_by, updated_at = NOW()
                 WHERE id = :id'
            )->execute($payload);
            self::audit('script.update', 'Script atualizado: ' . $name, $userId);
            return;
        }

        Database::connection()->prepare(
            'INSERT INTO consent_scripts
                (category_id, name, provider, script_type, src, code, position, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:category_id, :name, :provider, :script_type, :src, :code, :position, :active, :updated_by, :updated_by, NOW(), NOW())'
        )->execute($payload);
        self::audit('script.create', 'Script cadastrado: ' . $name, $userId);
    }

    public static function deleteScript(int $id, ?int $userId): void
    {
        self::bootstrap();
        Database::connection()->prepare('DELETE FROM consent_scripts WHERE id = :id')->execute(['id' => $id]);
        self::audit('script.delete', 'Script removido: #' . $id, $userId);
    }

    public static function registerConsent(array $data): array
    {
        self::bootstrap();

        $settings = self::settings();
        $visitorId = self::visitorId();
        $preferences = self::normalizePreferences($data['preferences'] ?? []);
        $source = substr(trim((string) ($data['source'] ?? 'banner')), 0, 80);
        $user = current_user();

        Database::connection()->prepare(
            'INSERT INTO consent_records
                (visitor_id, user_id, ip_anonymized, user_agent, policy_version, preferences_json, source, created_at)
             VALUES
                (:visitor_id, :user_id, :ip_anonymized, :user_agent, :policy_version, :preferences_json, :source, NOW())'
        )->execute([
            'visitor_id' => $visitorId,
            'user_id' => $user['id'] ?? null,
            'ip_anonymized' => self::anonymizeIp($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'policy_version' => $settings['policy_version'],
            'preferences_json' => json_encode($preferences, JSON_UNESCAPED_UNICODE),
            'source' => $source,
        ]);

        return [
            'visitorId' => $visitorId,
            'policyVersion' => $settings['policy_version'],
            'preferences' => $preferences,
        ];
    }

    public static function records(int $limit = 100): array
    {
        self::bootstrap();

        $limit = max(1, min(1000, $limit));
        return Database::connection()->query(
            'SELECT consent_records.*, users.name AS user_name
             FROM consent_records
             LEFT JOIN users ON users.id = consent_records.user_id
             ORDER BY consent_records.created_at DESC
             LIMIT ' . $limit
        )->fetchAll();
    }

    public static function auditLogs(int $limit = 80): array
    {
        self::bootstrap();

        $limit = max(1, min(500, $limit));
        return Database::connection()->query(
            'SELECT consent_audit_logs.*, users.name AS user_name
             FROM consent_audit_logs
             LEFT JOIN users ON users.id = consent_audit_logs.user_id
             ORDER BY consent_audit_logs.created_at DESC
             LIMIT ' . $limit
        )->fetchAll();
    }

    public static function stats(): array
    {
        self::bootstrap();

        $db = Database::connection();
        return [
            'records' => (int) $db->query('SELECT COUNT(*) FROM consent_records')->fetchColumn(),
            'today' => (int) $db->query('SELECT COUNT(*) FROM consent_records WHERE DATE(created_at) = CURDATE()')->fetchColumn(),
            'scripts' => (int) $db->query('SELECT COUNT(*) FROM consent_scripts WHERE active = 1')->fetchColumn(),
            'categories' => (int) $db->query('SELECT COUNT(*) FROM consent_categories WHERE active = 1')->fetchColumn(),
        ];
    }

    public static function exportRows(): array
    {
        return self::records(1000);
    }

    private static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS consent_settings (
                id TINYINT UNSIGNED PRIMARY KEY,
                banner_title VARCHAR(160) NOT NULL,
                banner_text TEXT NOT NULL,
                policy_title VARCHAR(160) NOT NULL,
                policy_text MEDIUMTEXT NOT NULL,
                policy_version VARCHAR(40) NOT NULL DEFAULT "1.0",
                accept_label VARCHAR(80) NOT NULL DEFAULT "Aceitar tudo",
                reject_label VARCHAR(80) NOT NULL DEFAULT "Rejeitar tudo",
                customize_label VARCHAR(80) NOT NULL DEFAULT "Personalizar",
                save_label VARCHAR(80) NOT NULL DEFAULT "Salvar preferências",
                primary_color VARCHAR(20) NOT NULL DEFAULT "#b91c1c",
                secondary_color VARCHAR(20) NOT NULL DEFAULT "#111827",
                background_color VARCHAR(20) NOT NULL DEFAULT "#ffffff",
                text_color VARCHAR(20) NOT NULL DEFAULT "#111827",
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS consent_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(80) NOT NULL UNIQUE,
                description TEXT NULL,
                required TINYINT(1) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS consent_scripts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(160) NOT NULL,
                provider VARCHAR(120) NULL,
                script_type ENUM("src", "inline") NOT NULL DEFAULT "inline",
                src VARCHAR(500) NULL,
                code MEDIUMTEXT NULL,
                position ENUM("head", "footer") NOT NULL DEFAULT "footer",
                active TINYINT(1) NOT NULL DEFAULT 0,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_consent_scripts_category (category_id),
                CONSTRAINT fk_consent_scripts_category FOREIGN KEY (category_id) REFERENCES consent_categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS consent_records (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                visitor_id CHAR(36) NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                ip_anonymized VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                policy_version VARCHAR(40) NOT NULL,
                preferences_json TEXT NOT NULL,
                source VARCHAR(80) NOT NULL DEFAULT "banner",
                created_at TIMESTAMP NULL,
                INDEX idx_consent_records_visitor (visitor_id),
                INDEX idx_consent_records_created (created_at)
            ) ENGINE=InnoDB'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS consent_audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                action VARCHAR(80) NOT NULL,
                description VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL,
                INDEX idx_consent_audit_created (created_at)
            ) ENGINE=InnoDB'
        );

        $done = true;
    }

    private static function seedDefaults(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $db = Database::connection();
        $db->exec(
            "INSERT IGNORE INTO consent_settings
                (id, banner_title, banner_text, policy_title, policy_text, policy_version, created_at, updated_at)
             VALUES
                (1, 'Controle de privacidade', 'Usamos cookies para melhorar sua experiência. Você pode aceitar, rejeitar ou personalizar os cookies opcionais.', 'Política de Cookies', 'Esta política explica o uso de cookies necessários, de análise, marketing e preferências. Cookies opcionais só são carregados após consentimento livre, informado e inequívoco.', '1.0', NOW(), NOW())"
        );

        $categories = [
            ['Necessários', 'necessarios', 'Essenciais para segurança, sessão, acessibilidade e funcionamento básico do site.', 1, 10],
            ['Análise', 'analise', 'Ajudam a entender audiência e desempenho de páginas, sem serem carregados antes do aceite.', 0, 20],
            ['Marketing', 'marketing', 'Permitem mensuração de campanhas e integrações de publicidade.', 0, 30],
            ['Preferências', 'preferencias', 'Guardam escolhas de experiência, idioma ou exibição.', 0, 40],
        ];
        $stmt = $db->prepare(
            'INSERT IGNORE INTO consent_categories
                (name, slug, description, required, active, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())'
        );
        foreach ($categories as $category) {
            $stmt->execute($category);
        }

        $done = true;
    }

    private static function seedAccess(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $db = Database::connection();
        $permissions = [
            ['Visualizar consentimentos LGPD', 'consent.view'],
            ['Editar textos e politicas LGPD', 'consent.texts'],
            ['Gerenciar CMP LGPD', 'consent.manage'],
        ];
        $stmt = $db->prepare('INSERT IGNORE INTO permissions (name, slug, created_at) VALUES (?, ?, NOW())');
        foreach ($permissions as $permission) {
            $stmt->execute($permission);
        }

        $roles = [
            ['EDITOR LGPD', 'editor-lgpd', 25],
            ['VISUALIZADOR LGPD', 'visualizador-lgpd', 15],
        ];
        $stmt = $db->prepare('INSERT IGNORE INTO roles (name, slug, level, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
        foreach ($roles as $role) {
            $stmt->execute($role);
        }

        $grants = [
            'admin' => ['consent.view', 'consent.texts', 'consent.manage'],
            'editor-lgpd' => ['consent.view', 'consent.texts'],
            'visualizador-lgpd' => ['consent.view'],
        ];
        $stmt = $db->prepare(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id)
             SELECT roles.id, permissions.id
             FROM roles
             INNER JOIN permissions ON permissions.slug = :permission
             WHERE roles.slug = :role'
        );
        foreach ($grants as $role => $permissions) {
            foreach ($permissions as $permission) {
                $stmt->execute(['role' => $role, 'permission' => $permission]);
            }
        }

        $done = true;
    }

    private static function audit(string $action, string $description, ?int $userId): void
    {
        Database::connection()->prepare(
            'INSERT INTO consent_audit_logs (user_id, action, description, created_at)
             VALUES (:user_id, :action, :description, NOW())'
        )->execute([
            'user_id' => $userId,
            'action' => $action,
            'description' => substr($description, 0, 255),
        ]);
    }

    private static function normalizePreferences(mixed $preferences): array
    {
        $incoming = is_array($preferences) ? $preferences : [];
        $normalized = [];
        foreach (self::categories(true) as $category) {
            $slug = (string) $category['slug'];
            $normalized[$slug] = (int) $category['required'] === 1 || !empty($incoming[$slug]);
        }

        return $normalized;
    }

    private static function visitorId(): string
    {
        $cookie = $_COOKIE['cni_consent_visitor'] ?? '';
        if (preg_match('/^[a-f0-9-]{36}$/i', $cookie)) {
            return strtolower($cookie);
        }

        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
        setcookie('cni_consent_visitor', $id, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        return $id;
    }

    private static function anonymizeIp(string $ip): ?string
    {
        if ($ip === '') {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice(array_pad($parts, 8, '0000'), 0, 4)) . '::';
        }

        return null;
    }

    private static function color(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);
        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : $fallback;
    }

    private static function slug(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
        $value = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '');
        return trim($value, '-');
    }

    private static function nextVersion(string $version): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $version, $matches)) {
            return $matches[1] . '.' . ((int) $matches[2] + 1);
        }

        return date('YmdHis');
    }
}
