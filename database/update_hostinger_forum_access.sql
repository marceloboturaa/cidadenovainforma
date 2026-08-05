-- Atualizacao de cargos, cursos e foruns integrados.
-- Execute no phpMyAdmin depois dos scripts anteriores.

INSERT INTO roles (name, slug, level, created_at, updated_at)
VALUES ('DIRETOR', 'diretor', 50, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), level = VALUES(level), updated_at = NOW();

INSERT INTO permissions (name, slug, created_at)
VALUES
    ('Ver foruns', 'forum.view', NOW()),
    ('Criar topicos e respostas nos foruns', 'forum.create', NOW()),
    ('Moderar foruns', 'forum.moderate', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('documents.view', 'people.manage', 'education.manage', 'education.view', 'education.forum', 'forum.view', 'forum.create', 'forum.moderate')
WHERE roles.slug = 'diretor';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('forum.view', 'forum.create')
WHERE roles.slug IN ('professor', 'estudante', 'voluntario');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('forum.view', 'forum.create', 'forum.moderate')
WHERE roles.slug IN ('master', 'admin');

DELETE role_permissions
FROM role_permissions
INNER JOIN roles ON roles.id = role_permissions.role_id
INNER JOIN permissions ON permissions.id = role_permissions.permission_id
WHERE roles.slug IN ('jornalista', 'colunista', 'voluntario')
  AND permissions.slug IN ('education.manage', 'education.teach');

DELETE role_permissions
FROM role_permissions
INNER JOIN roles ON roles.id = role_permissions.role_id
INNER JOIN permissions ON permissions.id = role_permissions.permission_id
WHERE roles.slug = 'diretor'
  AND permissions.slug IN ('users.manage', 'permissions.manage', 'logs.view', 'news.manage', 'news.approve', 'news.create', 'categories.manage', 'tags.manage', 'comments.moderate', 'ads.manage', 'regions.manage', 'menu.manage', 'documents.manage');

CREATE TABLE IF NOT EXISTS forum_areas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    description TEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_area_roles (
    area_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 1,
    can_post TINYINT(1) NOT NULL DEFAULT 1,
    can_moderate TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (area_id, role_id),
    CONSTRAINT fk_forum_area_roles_area FOREIGN KEY (area_id) REFERENCES forum_areas(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_area_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_forum_category_area_slug (area_id, slug),
    CONSTRAINT fk_forum_categories_area FOREIGN KEY (area_id) REFERENCES forum_areas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_topics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('open','closed','hidden') NOT NULL DEFAULT 'open',
    pinned TINYINT(1) NOT NULL DEFAULT 0,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_forum_topics_area (area_id),
    INDEX idx_forum_topics_category (category_id),
    CONSTRAINT fk_forum_topics_area FOREIGN KEY (area_id) REFERENCES forum_areas(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_topics_category FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_forum_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NOT NULL,
    parent_reply_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_forum_replies_parent (parent_reply_id),
    CONSTRAINT fk_forum_replies_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_replies_parent FOREIGN KEY (parent_reply_id) REFERENCES forum_replies(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE forum_replies
    ADD COLUMN IF NOT EXISTS parent_reply_id BIGINT UNSIGNED NULL AFTER topic_id;

CREATE TABLE IF NOT EXISTS forum_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NULL,
    reply_id BIGINT UNSIGNED NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_forum_attachments_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_attachments_reply FOREIGN KEY (reply_id) REFERENCES forum_replies(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    topic_id BIGINT UNSIGNED NOT NULL,
    reply_id BIGINT UNSIGNED NULL,
    message VARCHAR(190) NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_forum_notifications_user (user_id, read_at),
    CONSTRAINT fk_forum_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_notifications_topic FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_forum_notifications_reply FOREIGN KEY (reply_id) REFERENCES forum_replies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO forum_areas (name, slug, description, is_public, active, sort_order, created_at, updated_at)
VALUES
    ('Forum da direcao', 'direcao', 'Discussões privadas da direção.', 0, 1, 10, NOW(), NOW()),
    ('Forum dos professores', 'professores', 'Planejamento pedagógico e suporte docente.', 0, 1, 20, NOW(), NOW()),
    ('Forum dos estudantes', 'estudantes', 'Dúvidas e conversas dos estudantes.', 0, 1, 30, NOW(), NOW()),
    ('Forum institucional interno', 'institucional-interno', 'Assuntos internos autorizados da instituição.', 0, 1, 40, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order), updated_at = NOW();

INSERT IGNORE INTO forum_categories (area_id, name, slug, active, sort_order, created_at, updated_at)
SELECT id, 'Geral', 'geral', 1, 10, NOW(), NOW()
FROM forum_areas;

INSERT INTO forum_area_roles (area_id, role_id, can_view, can_post, can_moderate, created_at)
SELECT forum_areas.id, roles.id, 1, 1, IF(roles.slug IN ('master', 'diretor'), 1, 0), NOW()
FROM forum_areas
INNER JOIN roles ON roles.slug IN ('master', 'diretor')
WHERE forum_areas.slug = 'direcao'
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_post = VALUES(can_post), can_moderate = VALUES(can_moderate);

INSERT INTO forum_area_roles (area_id, role_id, can_view, can_post, can_moderate, created_at)
SELECT forum_areas.id, roles.id, 1, 1, IF(roles.slug IN ('master', 'diretor'), 1, 0), NOW()
FROM forum_areas
INNER JOIN roles ON roles.slug IN ('master', 'diretor', 'professor')
WHERE forum_areas.slug = 'professores'
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_post = VALUES(can_post), can_moderate = VALUES(can_moderate);

INSERT INTO forum_area_roles (area_id, role_id, can_view, can_post, can_moderate, created_at)
SELECT forum_areas.id, roles.id, 1, 1, IF(roles.slug IN ('master', 'diretor', 'professor'), 1, 0), NOW()
FROM forum_areas
INNER JOIN roles ON roles.slug IN ('master', 'diretor', 'professor', 'estudante')
WHERE forum_areas.slug = 'estudantes'
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_post = VALUES(can_post), can_moderate = VALUES(can_moderate);

INSERT INTO forum_area_roles (area_id, role_id, can_view, can_post, can_moderate, created_at)
SELECT forum_areas.id, roles.id, 1, 1, IF(roles.slug IN ('master', 'admin', 'admin-local', 'diretor'), 1, 0), NOW()
FROM forum_areas
INNER JOIN roles ON roles.slug IN ('master', 'admin', 'admin-local', 'diretor', 'professor', 'voluntario')
WHERE forum_areas.slug = 'institucional-interno'
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_post = VALUES(can_post), can_moderate = VALUES(can_moderate);
