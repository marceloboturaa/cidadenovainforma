-- Atualizacao consolidada para Hostinger
-- Execute no phpMyAdmin, no banco do site.
-- Nao apaga dados existentes.

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

CREATE TABLE IF NOT EXISTS user_presence (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    last_seen_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    CONSTRAINT fk_user_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS institution_pages (
    slug VARCHAR(80) PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    kicker VARCHAR(180) NOT NULL,
    summary TEXT NOT NULL,
    description TEXT NOT NULL,
    team_json TEXT NULL,
    materials_json TEXT NULL,
    photos_json TEXT NULL,
    galleries_json TEXT NULL,
    search_terms VARCHAR(255) NOT NULL,
    related_tags_json TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS institution_page_users (
    page_slug VARCHAR(80) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (page_slug, user_id),
    CONSTRAINT fk_institution_page_users_page FOREIGN KEY (page_slug) REFERENCES institution_pages(slug) ON DELETE CASCADE,
    CONSTRAINT fk_institution_page_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    cpf VARCHAR(20) NULL,
    birth_date DATE NULL,
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    cep VARCHAR(12) NULL,
    address VARCHAR(255) NULL,
    address_number VARCHAR(30) NULL,
    address_complement VARCHAR(120) NULL,
    district VARCHAR(120) NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(2) NULL,
    is_minor TINYINT(1) NOT NULL DEFAULT 0,
    guardian_name VARCHAR(160) NULL,
    guardian_relation VARCHAR(80) NULL,
    guardian_cpf VARCHAR(20) NULL,
    guardian_phone VARCHAR(30) NULL,
    guardian_email VARCHAR(190) NULL,
    contact_authorized TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_people_name (full_name),
    INDEX idx_people_contact (email, whatsapp),
    CONSTRAINT fk_people_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_people_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS library_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    location VARCHAR(160) NULL,
    cover_image VARCHAR(255) NULL,
    capacity INT UNSIGNED NULL,
    responsible_user_id BIGINT UNSIGNED NULL,
    status ENUM('aberto','encerrado','cancelado') NOT NULL DEFAULT 'aberto',
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_library_events_starts_at (starts_at),
    CONSTRAINT fk_library_events_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_library_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_library_events_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS library_event_participants (
    event_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    status ENUM('inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'inscrito',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (event_id, person_id),
    CONSTRAINT fk_event_participants_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_documents (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_document_users (
    document_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (document_id, user_id),
    CONSTRAINT fk_team_document_users_document FOREIGN KEY (document_id) REFERENCES team_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_document_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    cover_image VARCHAR(255) NULL,
    teacher_user_id BIGINT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_courses_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_courses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_courses_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_modules_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_lessons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    video_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_lessons_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_lessons_module FOREIGN KEY (module_id) REFERENCES education_modules(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_lesson_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'text',
    title VARCHAR(180) NULL,
    content LONGTEXT NULL,
    media_url VARCHAR(255) NULL,
    file_path VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_blocks_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_enrollments (
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (course_id, user_id),
    CONSTRAINT fk_education_enrollments_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_lesson_progress (
    lesson_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (lesson_id, user_id),
    CONSTRAINT fk_education_progress_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_forum_topics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('open','closed','hidden') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_education_forum_topics_course (course_id),
    CONSTRAINT fk_education_topics_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_forum_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_replies_topic FOREIGN KEY (topic_id) REFERENCES education_forum_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO roles (name, slug, level, created_at, updated_at)
VALUES
    ('MASTER', 'master', 100, NOW(), NOW()),
    ('ADMIN', 'admin', 80, NOW(), NOW()),
    ('ADMIN LOCAL', 'admin-local', 60, NOW(), NOW()),
    ('JORNALISTA', 'jornalista', 40, NOW(), NOW()),
    ('COLUNISTA', 'colunista', 35, NOW(), NOW()),
    ('PROFESSOR', 'professor', 30, NOW(), NOW()),
    ('EQUIPE', 'equipe', 20, NOW(), NOW()),
    ('ESTUDANTE', 'estudante', 10, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    level = VALUES(level),
    updated_at = NOW();

INSERT INTO permissions (name, slug, created_at)
VALUES
    ('Gerenciar usuários', 'users.manage', NOW()),
    ('Gerenciar permissões', 'permissions.manage', NOW()),
    ('Ver logs', 'logs.view', NOW()),
    ('Gerenciar notícias', 'news.manage', NOW()),
    ('Aprovar notícias', 'news.approve', NOW()),
    ('Criar notícias', 'news.create', NOW()),
    ('Gerenciar categorias', 'categories.manage', NOW()),
    ('Gerenciar tags', 'tags.manage', NOW()),
    ('Moderar comentários', 'comments.moderate', NOW()),
    ('Gerenciar publicidade', 'ads.manage', NOW()),
    ('Gerenciar regiões', 'regions.manage', NOW()),
    ('Gerenciar menu', 'menu.manage', NOW()),
    ('Ver documentos', 'documents.view', NOW()),
    ('Gerenciar documentos', 'documents.manage', NOW()),
    ('Gerenciar pessoas internas', 'people.manage', NOW()),
    ('Gerenciar eventos internos', 'events.manage', NOW()),
    ('Gerenciar participantes de eventos', 'event_participants.manage', NOW()),
    ('Gerenciar ensino', 'education.manage', NOW()),
    ('Criar cursos e aulas', 'education.teach', NOW()),
    ('Acessar ensino', 'education.view', NOW()),
    ('Participar do fórum de ensino', 'education.forum', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN (
    'users.manage',
    'permissions.manage',
    'logs.view',
    'news.manage',
    'news.approve',
    'news.create',
    'categories.manage',
    'tags.manage',
    'comments.moderate',
    'ads.manage',
    'regions.manage',
    'menu.manage',
    'documents.view',
    'documents.manage',
    'people.manage',
    'events.manage',
    'event_participants.manage',
    'education.manage',
    'education.teach',
    'education.view',
    'education.forum'
)
WHERE roles.slug = 'master';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN (
    'users.manage',
    'news.manage',
    'news.approve',
    'news.create',
    'categories.manage',
    'tags.manage',
    'comments.moderate',
    'ads.manage',
    'education.manage',
    'education.view',
    'education.forum'
)
WHERE roles.slug = 'admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('news.manage', 'news.approve', 'news.create', 'categories.manage')
WHERE roles.slug = 'admin-local';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug = 'news.create'
WHERE roles.slug IN ('jornalista', 'colunista');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('education.teach', 'education.view', 'education.forum')
WHERE roles.slug = 'professor';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN (
    'documents.view',
    'people.manage',
    'events.manage',
    'event_participants.manage',
    'education.manage',
    'education.view',
    'education.forum'
)
WHERE roles.slug = 'equipe';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('education.view', 'education.forum')
WHERE roles.slug = 'estudante';

-- Adiciona colunas novas em bancos que ja tinham tabelas antigas.
-- Requer MySQL/MariaDB com suporte a ADD COLUMN IF NOT EXISTS.
ALTER TABLE news ADD COLUMN IF NOT EXISTS is_archive TINYINT(1) NOT NULL DEFAULT 0 AFTER urgent;
ALTER TABLE news ADD COLUMN IF NOT EXISTS original_published_at DATE NULL AFTER is_archive;
ALTER TABLE news ADD COLUMN IF NOT EXISTS original_author VARCHAR(160) NULL AFTER original_published_at;
ALTER TABLE news ADD COLUMN IF NOT EXISTS original_source VARCHAR(160) NULL AFTER original_author;
ALTER TABLE news ADD COLUMN IF NOT EXISTS original_url VARCHAR(255) NULL AFTER original_source;
ALTER TABLE news ADD COLUMN IF NOT EXISTS archive_note TEXT NULL AFTER original_url;

ALTER TABLE team_documents ADD COLUMN IF NOT EXISTS is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER size_bytes;

ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS module_id BIGINT UNSIGNED NULL AFTER course_id;

ALTER TABLE people ADD COLUMN IF NOT EXISTS cep VARCHAR(12) NULL AFTER email;
ALTER TABLE people ADD COLUMN IF NOT EXISTS address_number VARCHAR(30) NULL AFTER address;
ALTER TABLE people ADD COLUMN IF NOT EXISTS address_complement VARCHAR(120) NULL AFTER address_number;
ALTER TABLE people ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER district;
ALTER TABLE people ADD COLUMN IF NOT EXISTS state VARCHAR(2) NULL AFTER city;
ALTER TABLE people ADD COLUMN IF NOT EXISTS is_minor TINYINT(1) NOT NULL DEFAULT 0 AFTER state;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_relation VARCHAR(80) NULL AFTER guardian_name;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_cpf VARCHAR(20) NULL AFTER guardian_relation;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_phone VARCHAR(30) NULL AFTER guardian_cpf;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_email VARCHAR(190) NULL AFTER guardian_phone;

ALTER TABLE library_events ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) NULL AFTER location;
