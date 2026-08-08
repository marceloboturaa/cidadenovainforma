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

ALTER TABLE users ADD COLUMN IF NOT EXISTS registration_origin VARCHAR(40) NOT NULL DEFAULT 'manual' AFTER active;
ALTER TABLE users ADD COLUMN IF NOT EXISTS registration_event_id BIGINT UNSIGNED NULL AFTER registration_origin;
ALTER TABLE users ADD COLUMN IF NOT EXISTS registration_person_id BIGINT UNSIGNED NULL AFTER registration_event_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS registration_course_id BIGINT UNSIGNED NULL AFTER registration_person_id;

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
    cover_image VARCHAR(255) NULL,
    cta_label VARCHAR(80) NULL,
    cta_url VARCHAR(255) NULL,
    show_on_landing TINYINT(1) NOT NULL DEFAULT 1,
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

ALTER TABLE institution_pages ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) NULL AFTER galleries_json;
ALTER TABLE institution_pages ADD COLUMN IF NOT EXISTS cta_label VARCHAR(80) NULL AFTER cover_image;
ALTER TABLE institution_pages ADD COLUMN IF NOT EXISTS cta_url VARCHAR(255) NULL AFTER cta_label;
ALTER TABLE institution_pages ADD COLUMN IF NOT EXISTS show_on_landing TINYINT(1) NOT NULL DEFAULT 1 AFTER cta_url;

UPDATE institution_pages
SET name = 'Biblioteca Comunitária'
WHERE slug = 'biblioteca'
  AND name = 'Biblioteca';

UPDATE institution_pages
SET name = 'Horta Comunitária'
WHERE slug = 'horta'
  AND name = 'Horta';

UPDATE institution_pages
SET name = 'Rádio Comunitária',
    show_on_landing = 0
WHERE slug = 'radio'
  AND name IN ('Rádio', 'Radio');

UPDATE institution_pages
SET sort_order = 20
WHERE slug = 'biblioteca'
  AND sort_order = 10;

UPDATE institution_pages
SET sort_order = 40
WHERE slug = 'horta'
  AND sort_order = 20;

UPDATE institution_pages
SET sort_order = 60
WHERE slug = 'radio'
  AND sort_order = 30;

UPDATE institution_pages
SET cover_image = '/public/assets/img/institution-hero-community.jpg'
WHERE cover_image IS NULL
   OR cover_image = '';

UPDATE institution_pages
SET cover_image = '/public/assets/img/institution-hero-community.jpg'
WHERE cover_image = '/public/assets/img/institution-hero-community.png';

INSERT INTO institution_pages
    (slug, name, kicker, summary, description, team_json, materials_json, photos_json, galleries_json, cover_image, cta_label, cta_url, show_on_landing, search_terms, related_tags_json, sort_order, created_at, updated_at)
VALUES
    ('jornalismo-comunitario', 'Jornalismo Comunitário', 'Comunicação popular e utilidade pública', 'Produção de notícias, memória local, serviços e informações de interesse público para o território.', 'O Jornalismo Comunitário é a base do Cidade Nova Informa. A frente organiza pautas do bairro, registra histórias, acompanha serviços públicos e fortalece a circulação de informações úteis para moradores, lideranças e parceiros.', '["Equipe editorial","Colaboradores locais","Moradores e fontes comunitárias"]', '["Sugestões de pauta","Cobertura comunitária","Registros de memória local"]', '[]', '[]', '/public/assets/img/institution-hero-community.jpg', 'Conhecer o jornalismo', '', 1, 'jornalismo comunicação comunidade bairro notícia noticias', '["jornalismo","bairro","comunidade"]', 10, NOW(), NOW()),
    ('educacao', 'Educação', 'Educação popular e formação cidadã', 'Atividades educativas, oficinas, cursos e ações de aprendizagem ligadas à comunicação e ao território.', 'A frente de Educação reúne oficinas, formações, cursos e atividades de educação popular. O objetivo é ampliar oportunidades de aprendizagem, fortalecer autonomia comunitária e aproximar estudantes, educadores e moradores.', '["Educadores parceiros","Coordenação pedagógica","Voluntários e estudantes"]', '["Oficinas comunitárias","Cursos e formações","Materiais de aprendizagem"]', '[]', '[]', '/public/assets/img/institution-hero-community.jpg', 'Ver ações educativas', '', 1, 'educação educacao curso oficina formação formacao', '["educacao","formacao"]', 30, NOW(), NOW()),
    ('idosos', 'Projeto com Idosos', 'Convivência, cuidado e pertencimento', 'Ações de convivência, escuta, cultura e fortalecimento de vínculos com pessoas idosas da comunidade.', 'O Projeto com Idosos valoriza convivência, escuta e participação social. As ações buscam criar oportunidades de encontro, cuidado, memória, cultura e fortalecimento de vínculos entre gerações.', '["Coordenação social","Voluntários","Parceiros da rede comunitária"]', '["Rodas de conversa","Atividades culturais","Ações de convivência"]', '[]', '[]', '/public/assets/img/institution-hero-community.jpg', 'Conhecer o projeto', '', 1, 'idosos terceira idade convivência memoria', '["idosos","comunidade"]', 50, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    updated_at = updated_at;

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
    image_authorized TINYINT(1) NOT NULL DEFAULT 0,
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
    event_cep VARCHAR(12) NULL,
    event_address VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    related_links TEXT NULL,
    event_course_id BIGINT UNSIGNED NULL,
    capacity INT UNSIGNED NULL,
    public_enabled TINYINT(1) NOT NULL DEFAULT 1,
    registration_enabled TINYINT(1) NOT NULL DEFAULT 0,
    public_show_location TINYINT(1) NOT NULL DEFAULT 1,
    public_show_address TINYINT(1) NOT NULL DEFAULT 1,
    public_show_capacity TINYINT(1) NOT NULL DEFAULT 1,
    public_show_responsible TINYINT(1) NOT NULL DEFAULT 1,
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
    status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente',
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
    allow_download TINYINT(1) NOT NULL DEFAULT 1,
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

CREATE TABLE IF NOT EXISTS library_event_courses (
    event_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (event_id, course_id),
    CONSTRAINT fk_library_event_courses_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_library_event_courses_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE
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
    description_position VARCHAR(20) NOT NULL DEFAULT 'after_media',
    video_url VARCHAR(255) NULL,
    image_url VARCHAR(255) NULL,
    locked TINYINT(1) NOT NULL DEFAULT 0,
    available_at DATETIME NULL,
    attendance_mode VARCHAR(20) NOT NULL DEFAULT 'video',
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
    settings_json LONGTEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_blocks_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE education_lesson_blocks ADD COLUMN IF NOT EXISTS settings_json LONGTEXT NULL AFTER file_path;

CREATE TABLE IF NOT EXISTS education_enrollments (
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (course_id, user_id),
    CONSTRAINT fk_education_enrollments_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE education_enrollments ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER user_id;
ALTER TABLE education_enrollments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL AFTER created_at;

CREATE TABLE IF NOT EXISTS education_lesson_progress (
    lesson_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (lesson_id, user_id),
    CONSTRAINT fk_education_progress_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_lesson_watches (
    lesson_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (lesson_id, user_id),
    CONSTRAINT fk_education_watches_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_watches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','justified') NOT NULL DEFAULT 'present',
    notes VARCHAR(255) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_education_attendance_course_user_date_lesson (course_id, user_id, attendance_date, lesson_id),
    INDEX idx_education_attendance_course_date (course_id, attendance_date),
    INDEX idx_education_attendance_lesson (lesson_id),
    CONSTRAINT fk_education_attendance_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_attendance_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_forum_topics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NULL,
    lesson_id BIGINT UNSIGNED NULL,
    central_topic_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('open','closed','hidden') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_education_forum_topics_course (course_id),
    INDEX idx_education_forum_topics_lesson (lesson_id),
    CONSTRAINT fk_education_topics_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_topics_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE education_forum_topics ADD COLUMN IF NOT EXISTS lesson_id BIGINT UNSIGNED NULL AFTER course_id;
ALTER TABLE education_forum_topics ADD COLUMN IF NOT EXISTS central_topic_id BIGINT UNSIGNED NULL AFTER lesson_id;

CREATE TABLE IF NOT EXISTS education_forum_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NOT NULL,
    parent_reply_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_education_forum_replies_parent (parent_reply_id),
    CONSTRAINT fk_education_replies_topic FOREIGN KEY (topic_id) REFERENCES education_forum_topics(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_replies_parent FOREIGN KEY (parent_reply_id) REFERENCES education_forum_replies(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE education_forum_replies
    ADD COLUMN IF NOT EXISTS parent_reply_id BIGINT UNSIGNED NULL AFTER topic_id;

INSERT INTO roles (name, slug, level, created_at, updated_at)
VALUES
    ('MASTER', 'master', 100, NOW(), NOW()),
    ('ADMIN', 'admin', 80, NOW(), NOW()),
    ('ADMIN LOCAL', 'admin-local', 60, NOW(), NOW()),
    ('DELEGADO EMISSOR', 'delegado-emissor', 55, NOW(), NOW()),
    ('DIRETOR', 'diretor', 50, NOW(), NOW()),
    ('JORNALISTA', 'jornalista', 40, NOW(), NOW()),
    ('COLUNISTA', 'colunista', 35, NOW(), NOW()),
    ('PROFESSOR', 'professor', 30, NOW(), NOW()),
    ('VOLUNTARIO', 'voluntario', 20, NOW(), NOW()),
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
    ('Participar do fórum de ensino', 'education.forum', NOW()),
    ('Gerenciar certificados digitais', 'certificates.manage', NOW()),
    ('Emitir certificados digitais', 'certificates.issue', NOW()),
    ('Ver auditoria de certificados', 'certificates.audit', NOW()),
    ('Gerenciar instituições certificadoras', 'certificates.institutions', NOW()),
    ('Gerenciar modelos de certificados', 'certificates.templates', NOW()),
    ('Aprovar lotes de certificados', 'certificates.batches.approve', NOW()),
    ('Ver fóruns', 'forum.view', NOW()),
    ('Criar tópicos no fórum', 'forum.create', NOW()),
    ('Moderar fóruns', 'forum.moderate', NOW()),
    ('Visualizar consentimentos LGPD', 'consent.view', NOW()),
    ('Gerenciar consentimentos LGPD', 'consent.manage', NOW()),
    ('Editar textos LGPD', 'consent.texts', NOW())
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
    'education.forum',
    'certificates.manage',
    'certificates.issue',
    'certificates.audit',
    'certificates.institutions',
    'certificates.templates',
    'certificates.batches.approve',
    'forum.view',
    'forum.create',
    'forum.moderate',
    'consent.view',
    'consent.manage',
    'consent.texts'
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
    'education.forum',
    'certificates.manage',
    'certificates.issue',
    'certificates.audit',
    'certificates.institutions',
    'certificates.templates',
    'certificates.batches.approve',
    'forum.view',
    'forum.create',
    'forum.moderate',
    'consent.view',
    'consent.manage',
    'consent.texts'
)
WHERE roles.slug = 'admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('news.manage', 'news.approve', 'news.create', 'categories.manage', 'people.manage', 'events.manage', 'event_participants.manage', 'education.manage', 'education.view', 'education.forum', 'certificates.manage', 'certificates.issue', 'certificates.templates', 'forum.view', 'forum.create')
WHERE roles.slug = 'admin-local';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('documents.view', 'people.manage', 'education.manage', 'education.view', 'education.forum', 'forum.view', 'forum.create', 'forum.moderate')
WHERE roles.slug = 'diretor';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug = 'news.create'
WHERE roles.slug IN ('jornalista', 'colunista');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('education.teach', 'education.view', 'education.forum', 'forum.view', 'forum.create')
WHERE roles.slug = 'professor';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('education.view', 'certificates.issue')
WHERE roles.slug = 'delegado-emissor';

DELETE role_permissions
FROM role_permissions
INNER JOIN roles ON roles.id = role_permissions.role_id
INNER JOIN permissions ON permissions.id = role_permissions.permission_id
WHERE roles.slug IN ('admin', 'admin-local', 'diretor')
  AND permissions.slug = 'education.teach';

DELETE role_permissions
FROM role_permissions
INNER JOIN roles ON roles.id = role_permissions.role_id
INNER JOIN permissions ON permissions.id = role_permissions.permission_id
WHERE roles.slug = 'professor'
  AND permissions.slug = 'forum.moderate';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN (
    'people.manage',
    'events.manage',
    'event_participants.manage',
    'forum.view',
    'forum.create'
)
WHERE roles.slug = 'voluntario';

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
ALTER TABLE team_documents ADD COLUMN IF NOT EXISTS allow_download TINYINT(1) NOT NULL DEFAULT 1 AFTER is_public;

CREATE TABLE IF NOT EXISTS announcement_recipients (
    announcement_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (announcement_id, user_id),
    INDEX idx_announcement_recipients_user (user_id),
    CONSTRAINT fk_announcement_recipients_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    CONSTRAINT fk_announcement_recipients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_document_annotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    page_number INT UNSIGNED NOT NULL DEFAULT 1,
    type VARCHAR(20) NOT NULL DEFAULT 'highlight',
    x DECIMAL(8,6) NOT NULL DEFAULT 0,
    y DECIMAL(8,6) NOT NULL DEFAULT 0,
    width DECIMAL(8,6) NOT NULL DEFAULT 0,
    height DECIMAL(8,6) NOT NULL DEFAULT 0,
    color VARCHAR(20) NOT NULL DEFAULT '#facc15',
    note TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_team_document_annotations_document (document_id, page_number, active),
    CONSTRAINT fk_team_document_annotations_document FOREIGN KEY (document_id) REFERENCES team_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_document_annotations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS module_id BIGINT UNSIGNED NULL AFTER course_id;
ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) NULL AFTER video_url;
ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS locked TINYINT(1) NOT NULL DEFAULT 0 AFTER image_url;
ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS available_at DATETIME NULL AFTER locked;
ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS attendance_mode VARCHAR(20) NOT NULL DEFAULT 'video' AFTER available_at;
ALTER TABLE education_lessons ADD COLUMN IF NOT EXISTS description_position VARCHAR(20) NOT NULL DEFAULT 'after_media' AFTER description;

ALTER TABLE education_attendance ADD COLUMN IF NOT EXISTS lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER user_id;

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
ALTER TABLE people ADD COLUMN IF NOT EXISTS image_authorized TINYINT(1) NOT NULL DEFAULT 0 AFTER contact_authorized;

ALTER TABLE library_events ADD COLUMN IF NOT EXISTS event_cep VARCHAR(12) NULL AFTER location;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS event_address VARCHAR(255) NULL AFTER location;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS public_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER capacity;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS registration_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER capacity;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS public_show_location TINYINT(1) NOT NULL DEFAULT 1 AFTER registration_enabled;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS public_show_address TINYINT(1) NOT NULL DEFAULT 1 AFTER public_show_location;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS public_show_capacity TINYINT(1) NOT NULL DEFAULT 1 AFTER public_show_address;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS public_show_responsible TINYINT(1) NOT NULL DEFAULT 1 AFTER public_show_capacity;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) NULL AFTER location;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS related_links TEXT NULL AFTER cover_image;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS event_course_id BIGINT UNSIGNED NULL AFTER related_links;

INSERT IGNORE INTO library_event_courses (event_id, course_id, created_at)
SELECT id, event_course_id, NOW()
FROM library_events
WHERE event_course_id IS NOT NULL;

-- Renomeia o menu publico antigo de Acervo para Reprise.
UPDATE menu_items
SET label = 'Reprise',
    url = '/reprise',
    updated_at = NOW()
WHERE url = '/acervo'
   OR label = 'Acervo';

INSERT INTO menu_items (category_id, label, url, sort_order, visible, created_at, updated_at)
SELECT NULL, 'Reprise', '/reprise', 95, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = '/reprise');
