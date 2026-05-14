-- Atualizacao do painel de usuarios para Hostinger
-- Execute este arquivo no phpMyAdmin, no banco do site.
-- Ele nao apaga usuarios nem altera senhas existentes.

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO user_roles (user_id, role_id, created_at)
SELECT id, role_id, NOW()
FROM users
WHERE role_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_password_resets_token (token_hash),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_presence (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    last_seen_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    CONSTRAINT fk_user_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
    name VARCHAR(120) PRIMARY KEY,
    value TEXT NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

INSERT INTO site_settings (name, value, updated_at)
VALUES ('registration_enabled', '1', NOW())
ON DUPLICATE KEY UPDATE
    value = value,
    updated_at = updated_at;

CREATE TABLE IF NOT EXISTS institution_page_users (
    page_slug VARCHAR(80) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (page_slug, user_id),
    CONSTRAINT fk_institution_page_users_page FOREIGN KEY (page_slug) REFERENCES institution_pages(slug) ON DELETE CASCADE,
    CONSTRAINT fk_institution_page_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
