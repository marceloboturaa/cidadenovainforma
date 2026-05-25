-- Sistema de Gerenciamento de Consentimento LGPD
-- Execute em instalações existentes antes de liberar o painel.

INSERT IGNORE INTO roles (name, slug, level, created_at, updated_at) VALUES
('EDITOR LGPD', 'editor-lgpd', 25, NOW(), NOW()),
('VISUALIZADOR LGPD', 'visualizador-lgpd', 15, NOW(), NOW());

INSERT IGNORE INTO permissions (name, slug, created_at) VALUES
('Visualizar consentimentos LGPD', 'consent.view', NOW()),
('Editar textos e politicas LGPD', 'consent.texts', NOW()),
('Gerenciar CMP LGPD', 'consent.manage', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('consent.view', 'consent.texts', 'consent.manage')
WHERE roles.slug IN ('master', 'admin');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('consent.view', 'consent.texts')
WHERE roles.slug = 'editor-lgpd';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug = 'consent.view'
WHERE roles.slug = 'visualizador-lgpd';

CREATE TABLE IF NOT EXISTS consent_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    banner_title VARCHAR(160) NOT NULL,
    banner_text TEXT NOT NULL,
    policy_title VARCHAR(160) NOT NULL,
    policy_text MEDIUMTEXT NOT NULL,
    policy_version VARCHAR(40) NOT NULL DEFAULT '1.0',
    accept_label VARCHAR(80) NOT NULL DEFAULT 'Aceitar tudo',
    reject_label VARCHAR(80) NOT NULL DEFAULT 'Rejeitar tudo',
    customize_label VARCHAR(80) NOT NULL DEFAULT 'Personalizar',
    save_label VARCHAR(80) NOT NULL DEFAULT 'Salvar preferências',
    primary_color VARCHAR(20) NOT NULL DEFAULT '#b91c1c',
    secondary_color VARCHAR(20) NOT NULL DEFAULT '#111827',
    background_color VARCHAR(20) NOT NULL DEFAULT '#ffffff',
    text_color VARCHAR(20) NOT NULL DEFAULT '#111827',
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consent_categories (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consent_scripts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    provider VARCHAR(120) NULL,
    script_type ENUM('src','inline') NOT NULL DEFAULT 'inline',
    src VARCHAR(500) NULL,
    code MEDIUMTEXT NULL,
    position ENUM('head','footer') NOT NULL DEFAULT 'footer',
    active TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_consent_scripts_category (category_id),
    CONSTRAINT fk_update_consent_scripts_category FOREIGN KEY (category_id) REFERENCES consent_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consent_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_anonymized VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    policy_version VARCHAR(40) NOT NULL,
    preferences_json TEXT NOT NULL,
    source VARCHAR(80) NOT NULL DEFAULT 'banner',
    created_at TIMESTAMP NULL,
    INDEX idx_consent_records_visitor (visitor_id),
    INDEX idx_consent_records_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consent_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_consent_audit_created (created_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO consent_settings
    (id, banner_title, banner_text, policy_title, policy_text, policy_version, created_at, updated_at)
VALUES
    (1, 'Controle de privacidade', 'Usamos cookies para melhorar sua experiência. Você pode aceitar, rejeitar ou personalizar os cookies opcionais.', 'Política de Cookies', 'Esta política explica o uso de cookies necessários, de análise, marketing e preferências. Cookies opcionais só são carregados após consentimento livre, informado e inequívoco.', '1.0', NOW(), NOW());

INSERT IGNORE INTO consent_categories
    (name, slug, description, required, active, sort_order, created_at, updated_at)
VALUES
    ('Necessários', 'necessarios', 'Essenciais para segurança, sessão, acessibilidade e funcionamento básico do site.', 1, 1, 10, NOW(), NOW()),
    ('Análise', 'analise', 'Ajudam a entender audiência e desempenho de páginas, sem serem carregados antes do aceite.', 0, 1, 20, NOW(), NOW()),
    ('Marketing', 'marketing', 'Permitem mensuração de campanhas e integrações de publicidade.', 0, 1, 30, NOW(), NOW()),
    ('Preferências', 'preferencias', 'Guardam escolhas de experiência, idioma ou exibição.', 0, 1, 40, NOW(), NOW());
