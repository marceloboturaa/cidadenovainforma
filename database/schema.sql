CREATE DATABASE IF NOT EXISTS cidadenovainforma
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cidadenovainforma;

CREATE TABLE IF NOT EXISTS regions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    level INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    bio TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_users_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
    related_links TEXT NULL,
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
    status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (event_id, person_id),
    CONSTRAINT fk_event_participants_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
    name VARCHAR(120) PRIMARY KEY,
    value TEXT NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

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
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_consent_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_consent_categories_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_consent_categories_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
    CONSTRAINT fk_consent_scripts_category FOREIGN KEY (category_id) REFERENCES consent_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_consent_scripts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_consent_scripts_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
    INDEX idx_consent_records_created (created_at),
    CONSTRAINT fk_consent_records_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consent_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_consent_audit_created (created_at),
    CONSTRAINT fk_consent_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS certificate_institutions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    cnpj VARCHAR(32) NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(2) NULL,
    site VARCHAR(180) NULL,
    logo_path VARCHAR(255) NULL,
    signature_path VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_certificate_institutions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_institutions_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS certificate_institution_users (
    institution_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role_slug VARCHAR(40) NOT NULL DEFAULT 'admin-local',
    can_issue TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (institution_id, user_id),
    CONSTRAINT fk_certificate_institution_users_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificate_institution_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificate_institution_users_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS certificate_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_certificate_categories_scope (institution_id, slug),
    CONSTRAINT fk_certificate_categories_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS certificate_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    front_background VARCHAR(255) NULL,
    back_background VARCHAR(255) NULL,
    legal_text TEXT NULL,
    layout_json LONGTEXT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_certificate_templates_scope (institution_id, slug),
    CONSTRAINT fk_certificate_templates_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_templates_category FOREIGN KEY (category_id) REFERENCES certificate_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_templates_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_templates_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS certificate_template_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL,
    snapshot_json LONGTEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    UNIQUE KEY uq_certificate_template_versions (template_id, version),
    CONSTRAINT fk_certificate_template_versions_template FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificate_template_versions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    region_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_categories_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    label VARCHAR(120) NOT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_menu_items_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    display_name VARCHAR(120) NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS news (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    region_id BIGINT UNSIGNED NULL,
    title VARCHAR(220) NOT NULL,
    slug VARCHAR(240) NOT NULL UNIQUE,
    summary TEXT NULL,
    content LONGTEXT NOT NULL,
    cover_image VARCHAR(255) NULL,
    type ENUM('noticia','reportagem','artigo','coluna') NOT NULL DEFAULT 'noticia',
    status ENUM('draft','pending','rejected','published','archived') NOT NULL DEFAULT 'draft',
    public_visibility VARCHAR(20) NOT NULL DEFAULT 'listed',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    urgent TINYINT(1) NOT NULL DEFAULT 0,
    is_archive TINYINT(1) NOT NULL DEFAULT 0,
    original_published_at DATE NULL,
    original_author VARCHAR(160) NULL,
    original_source VARCHAR(160) NULL,
    original_url VARCHAR(255) NULL,
    archive_note TEXT NULL,
    views BIGINT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FULLTEXT KEY ft_news_search (title, summary, content),
    CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users(id),
    CONSTRAINT fk_news_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_news_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_news_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS news_tags (
    news_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (news_id, tag_id),
    CONSTRAINT fk_news_tags_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_news_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    alt_text VARCHAR(190) NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_media_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_media_user FOREIGN KEY (user_id) REFERENCES users(id)
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

CREATE TABLE IF NOT EXISTS team_document_upload_users (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_team_document_upload_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS education_courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    cover_image VARCHAR(255) NULL,
    certificate_institution_id BIGINT UNSIGNED NULL,
    certificate_category_id BIGINT UNSIGNED NULL,
    certificate_template_id BIGINT UNSIGNED NULL,
    certificate_activity_type VARCHAR(40) NOT NULL DEFAULT 'curso_livre',
    workload_hours DECIMAL(6,2) NULL,
    starts_at DATE NULL,
    ends_at DATE NULL,
    public_enabled TINYINT(1) NOT NULL DEFAULT 0,
    certificate_enabled TINYINT(1) NOT NULL DEFAULT 0,
    certificate_title VARCHAR(180) NULL,
    certificate_text TEXT NULL,
    certificate_font_family VARCHAR(80) NULL,
    certificate_background VARCHAR(255) NULL,
    certificate_min_frequency TINYINT UNSIGNED NOT NULL DEFAULT 0,
    certificate_show_recipient TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_nature TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_modality TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_period TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_approval TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_institution TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_meta TINYINT(1) NOT NULL DEFAULT 1,
    certificate_show_legal TINYINT(1) NOT NULL DEFAULT 1,
    certificate_course_nature VARCHAR(180) NULL,
    certificate_modality VARCHAR(80) NULL,
    certificate_approval_criteria VARCHAR(255) NULL,
    certificate_legal_text TEXT NULL,
    certificate_institution_name VARCHAR(180) NULL,
    certificate_institution_city VARCHAR(120) NULL,
    certificate_institution_cnpj VARCHAR(32) NULL,
    certificate_institution_site VARCHAR(180) NULL,
    certificate_objectives TEXT NULL,
    certificate_competencies TEXT NULL,
    certificate_responsible_name VARCHAR(180) NULL,
    certificate_responsible_credential VARCHAR(180) NULL,
    certificate_program_enabled TINYINT(1) NOT NULL DEFAULT 1,
    certificate_program_background VARCHAR(255) NULL,
    certificate_program_extra TEXT NULL,
    certificate_program_columns TINYINT UNSIGNED NOT NULL DEFAULT 2,
    teacher_user_id BIGINT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_education_courses_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_courses_certificate_institution FOREIGN KEY (certificate_institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_courses_certificate_category FOREIGN KEY (certificate_category_id) REFERENCES certificate_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_courses_certificate_template FOREIGN KEY (certificate_template_id) REFERENCES certificate_templates(id) ON DELETE SET NULL,
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

CREATE TABLE IF NOT EXISTS certificate_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    institution_id BIGINT UNSIGNED NULL,
    course_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    source_filename VARCHAR(190) NULL,
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    issued_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    requested_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_certificate_batches_status (status),
    CONSTRAINT fk_certificate_batches_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_batches_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_batches_template FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_batches_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_batches_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    person_id BIGINT UNSIGNED NULL,
    verification_code VARCHAR(48) NOT NULL,
    validation_hash CHAR(64) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'issued',
    batch_id BIGINT UNSIGNED NULL,
    student_name VARCHAR(180) NULL,
    requested_student_name VARCHAR(180) NULL,
    name_change_status VARCHAR(20) NULL,
    name_change_requested_at DATETIME NULL,
    name_change_reviewed_by BIGINT UNSIGNED NULL,
    name_change_reviewed_at DATETIME NULL,
    authorized_by BIGINT UNSIGNED NULL,
    authorized_at DATETIME NULL,
    issued_by BIGINT UNSIGNED NULL,
    revoked_by BIGINT UNSIGNED NULL,
    revoked_at DATETIME NULL,
    revoked_reason TEXT NULL,
    pdf_path VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    verified_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_verified_at DATETIME NULL,
    issued_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_education_certificate_course_user (course_id, user_id),
    UNIQUE KEY uq_education_certificate_course_person (course_id, person_id),
    UNIQUE KEY uq_education_certificate_code (verification_code),
    INDEX idx_education_certificate_status (status),
    INDEX idx_education_certificate_hash (validation_hash),
    CONSTRAINT fk_education_certificate_batch FOREIGN KEY (batch_id) REFERENCES certificate_batches(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_certificate_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_certificate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_certificate_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_certificate_authorizer FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_certificate_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_certificate_revoker FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_education_certificate_reviewer FOREIGN KEY (name_change_reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS certificate_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_id BIGINT UNSIGNED NULL,
    institution_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    old_values_json LONGTEXT NULL,
    new_values_json LONGTEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_certificate_audit_certificate (certificate_id),
    INDEX idx_certificate_audit_action (action),
    CONSTRAINT fk_certificate_audit_certificate FOREIGN KEY (certificate_id) REFERENCES education_certificates(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_audit_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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

CREATE TABLE IF NOT EXISTS comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    author_name VARCHAR(140) NULL,
    author_email VARCHAR(190) NULL,
    content TEXT NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_comments_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS likes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_likes_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(140) NOT NULL,
    position VARCHAR(80) NOT NULL,
    image_path VARCHAR(255) NULL,
    target_url VARCHAR(255) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_access_logs_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
