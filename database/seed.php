<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/database.php';

$dsn = sprintf(
    '%s:host=%s;port=%s;dbname=%s;charset=%s',
    $config['driver'],
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS menu_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT UNSIGNED NULL,
        label VARCHAR(120) NOT NULL,
        url VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT fk_menu_items_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS site_settings (
        name VARCHAR(120) PRIMARY KEY,
        value TEXT NULL,
        updated_at TIMESTAMP NULL
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS user_presence (
        user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        last_seen_at DATETIME NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        CONSTRAINT fk_user_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS user_roles (
        user_id BIGINT UNSIGNED NOT NULL,
        role_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        PRIMARY KEY (user_id, role_id),
        CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS people (
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
    ) ENGINE=InnoDB"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS library_events (
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
    ) ENGINE=InnoDB"
);

$peopleColumns = [
    'cep' => "ALTER TABLE people ADD COLUMN cep VARCHAR(12) NULL AFTER email",
    'address_number' => "ALTER TABLE people ADD COLUMN address_number VARCHAR(30) NULL AFTER address",
    'address_complement' => "ALTER TABLE people ADD COLUMN address_complement VARCHAR(120) NULL AFTER address_number",
    'city' => "ALTER TABLE people ADD COLUMN city VARCHAR(120) NULL AFTER district",
    'state' => "ALTER TABLE people ADD COLUMN state VARCHAR(2) NULL AFTER city",
    'is_minor' => "ALTER TABLE people ADD COLUMN is_minor TINYINT(1) NOT NULL DEFAULT 0 AFTER state",
    'guardian_relation' => "ALTER TABLE people ADD COLUMN guardian_relation VARCHAR(80) NULL AFTER guardian_name",
    'guardian_cpf' => "ALTER TABLE people ADD COLUMN guardian_cpf VARCHAR(20) NULL AFTER guardian_relation",
    'guardian_phone' => "ALTER TABLE people ADD COLUMN guardian_phone VARCHAR(30) NULL AFTER guardian_cpf",
    'guardian_email' => "ALTER TABLE people ADD COLUMN guardian_email VARCHAR(190) NULL AFTER guardian_phone",
];

$existingPeopleColumns = $pdo->query('SHOW COLUMNS FROM people')->fetchAll(PDO::FETCH_COLUMN);
foreach ($peopleColumns as $column => $sql) {
    if (!in_array($column, $existingPeopleColumns, true)) {
        $pdo->exec($sql);
    }
}

$eventColumns = [
    'cover_image' => "ALTER TABLE library_events ADD COLUMN cover_image VARCHAR(255) NULL AFTER location",
];

$existingEventColumns = $pdo->query('SHOW COLUMNS FROM library_events')->fetchAll(PDO::FETCH_COLUMN);
foreach ($eventColumns as $column => $sql) {
    if (!in_array($column, $existingEventColumns, true)) {
        $pdo->exec($sql);
    }
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS library_event_participants (
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
    ) ENGINE=InnoDB"
);

$pdo->prepare('INSERT IGNORE INTO site_settings (name, value, updated_at) VALUES (?, ?, NOW())')
    ->execute(['registration_enabled', '1']);

$institutionTags = [
    ['biblioteca', 'Biblioteca', 'biblioteca'],
    ['horta', 'Horta', 'horta'],
    ['radio', 'Rádio', 'radio'],
];

$tagColumns = $pdo->query('SHOW COLUMNS FROM tags')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('display_name', $tagColumns, true)) {
    $pdo->exec('ALTER TABLE tags ADD COLUMN display_name VARCHAR(120) NULL AFTER name');
    $pdo->exec('UPDATE tags SET display_name = name WHERE display_name IS NULL OR display_name = ""');
}
$pdo->exec('UPDATE tags SET display_name = name WHERE display_name IS NULL OR display_name = ""');
$pdo->exec('UPDATE tags SET name = slug WHERE slug IS NOT NULL AND slug <> "" AND name <> slug');

$stmt = $pdo->prepare('INSERT IGNORE INTO tags (name, display_name, slug, created_at) VALUES (?, ?, ?, NOW())');
foreach ($institutionTags as $tag) {
    $stmt->execute($tag);
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS institution_pages (
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
    ) ENGINE=InnoDB'
);

$institutionPageColumns = [
    'cover_image' => 'ALTER TABLE institution_pages ADD COLUMN cover_image VARCHAR(255) NULL AFTER galleries_json',
    'cta_label' => 'ALTER TABLE institution_pages ADD COLUMN cta_label VARCHAR(80) NULL AFTER cover_image',
    'cta_url' => 'ALTER TABLE institution_pages ADD COLUMN cta_url VARCHAR(255) NULL AFTER cta_label',
    'show_on_landing' => 'ALTER TABLE institution_pages ADD COLUMN show_on_landing TINYINT(1) NOT NULL DEFAULT 1 AFTER cta_url',
];

$existingInstitutionPageColumns = $pdo->query('SHOW COLUMNS FROM institution_pages')->fetchAll(PDO::FETCH_COLUMN);
foreach ($institutionPageColumns as $column => $sql) {
    if (!in_array($column, $existingInstitutionPageColumns, true)) {
        $pdo->exec($sql);
    }
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS institution_page_users (
        page_slug VARCHAR(80) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        PRIMARY KEY (page_slug, user_id),
        CONSTRAINT fk_institution_page_users_page FOREIGN KEY (page_slug) REFERENCES institution_pages(slug) ON DELETE CASCADE,
        CONSTRAINT fk_institution_page_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_institutions (
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
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_institution_users (
        institution_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        role_slug VARCHAR(40) NOT NULL DEFAULT "admin-local",
        can_issue TINYINT(1) NOT NULL DEFAULT 0,
        expires_at DATETIME NULL,
        approved_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL,
        PRIMARY KEY (institution_id, user_id),
        CONSTRAINT fk_certificate_institution_users_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE CASCADE,
        CONSTRAINT fk_certificate_institution_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_certificate_institution_users_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_categories (
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
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_templates (
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
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_template_versions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        template_id BIGINT UNSIGNED NOT NULL,
        version INT UNSIGNED NOT NULL,
        snapshot_json LONGTEXT NOT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL,
        UNIQUE KEY uq_certificate_template_versions (template_id, version),
        CONSTRAINT fk_certificate_template_versions_template FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE CASCADE,
        CONSTRAINT fk_certificate_template_versions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB'
);

$pdo->exec(
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

$teamDocumentColumns = $pdo->query('SHOW COLUMNS FROM team_documents')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('is_public', $teamDocumentColumns, true)) {
    $pdo->exec('ALTER TABLE team_documents ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER size_bytes');
}
if (!in_array('allow_download', $teamDocumentColumns, true)) {
    $pdo->exec('ALTER TABLE team_documents ADD COLUMN allow_download TINYINT(1) NOT NULL DEFAULT 1 AFTER is_public');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS team_document_users (
        document_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        PRIMARY KEY (document_id, user_id),
        CONSTRAINT fk_team_document_users_document FOREIGN KEY (document_id) REFERENCES team_documents(id) ON DELETE CASCADE,
        CONSTRAINT fk_team_document_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_courses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        summary TEXT NULL,
        cover_image VARCHAR(255) NULL,
        certificate_institution_id BIGINT UNSIGNED NULL,
        certificate_category_id BIGINT UNSIGNED NULL,
        certificate_template_id BIGINT UNSIGNED NULL,
        certificate_activity_type VARCHAR(40) NOT NULL DEFAULT "curso_livre",
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
    ) ENGINE=InnoDB'
);

$courseColumns = $pdo->query('SHOW COLUMNS FROM education_courses')->fetchAll(PDO::FETCH_COLUMN);
$courseCertificateColumns = [
    'certificate_institution_id' => 'ALTER TABLE education_courses ADD COLUMN certificate_institution_id BIGINT UNSIGNED NULL AFTER cover_image',
    'certificate_category_id' => 'ALTER TABLE education_courses ADD COLUMN certificate_category_id BIGINT UNSIGNED NULL AFTER certificate_institution_id',
    'certificate_template_id' => 'ALTER TABLE education_courses ADD COLUMN certificate_template_id BIGINT UNSIGNED NULL AFTER certificate_category_id',
    'certificate_activity_type' => 'ALTER TABLE education_courses ADD COLUMN certificate_activity_type VARCHAR(40) NOT NULL DEFAULT "curso_livre" AFTER certificate_template_id',
    'workload_hours' => 'ALTER TABLE education_courses ADD COLUMN workload_hours DECIMAL(6,2) NULL AFTER certificate_activity_type',
    'starts_at' => 'ALTER TABLE education_courses ADD COLUMN starts_at DATE NULL AFTER workload_hours',
    'ends_at' => 'ALTER TABLE education_courses ADD COLUMN ends_at DATE NULL AFTER starts_at',
    'public_enabled' => 'ALTER TABLE education_courses ADD COLUMN public_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER cover_image',
    'certificate_enabled' => 'ALTER TABLE education_courses ADD COLUMN certificate_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER public_enabled',
    'certificate_title' => 'ALTER TABLE education_courses ADD COLUMN certificate_title VARCHAR(180) NULL AFTER certificate_enabled',
    'certificate_text' => 'ALTER TABLE education_courses ADD COLUMN certificate_text TEXT NULL AFTER certificate_title',
    'certificate_font_family' => 'ALTER TABLE education_courses ADD COLUMN certificate_font_family VARCHAR(80) NULL AFTER certificate_text',
    'certificate_background' => 'ALTER TABLE education_courses ADD COLUMN certificate_background VARCHAR(255) NULL AFTER certificate_font_family',
    'certificate_min_frequency' => 'ALTER TABLE education_courses ADD COLUMN certificate_min_frequency TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER certificate_background',
    'certificate_show_recipient' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_recipient TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_min_frequency',
    'certificate_show_nature' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_nature TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_recipient',
    'certificate_show_modality' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_modality TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_nature',
    'certificate_show_period' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_period TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_modality',
    'certificate_show_approval' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_approval TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_period',
    'certificate_show_institution' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_institution TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_approval',
    'certificate_show_meta' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_meta TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_institution',
    'certificate_show_legal' => 'ALTER TABLE education_courses ADD COLUMN certificate_show_legal TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_meta',
    'certificate_course_nature' => 'ALTER TABLE education_courses ADD COLUMN certificate_course_nature VARCHAR(180) NULL AFTER certificate_show_legal',
    'certificate_modality' => 'ALTER TABLE education_courses ADD COLUMN certificate_modality VARCHAR(80) NULL AFTER certificate_course_nature',
    'certificate_approval_criteria' => 'ALTER TABLE education_courses ADD COLUMN certificate_approval_criteria VARCHAR(255) NULL AFTER certificate_modality',
    'certificate_legal_text' => 'ALTER TABLE education_courses ADD COLUMN certificate_legal_text TEXT NULL AFTER certificate_approval_criteria',
    'certificate_institution_name' => 'ALTER TABLE education_courses ADD COLUMN certificate_institution_name VARCHAR(180) NULL AFTER certificate_legal_text',
    'certificate_institution_city' => 'ALTER TABLE education_courses ADD COLUMN certificate_institution_city VARCHAR(120) NULL AFTER certificate_institution_name',
    'certificate_institution_cnpj' => 'ALTER TABLE education_courses ADD COLUMN certificate_institution_cnpj VARCHAR(32) NULL AFTER certificate_institution_city',
    'certificate_institution_site' => 'ALTER TABLE education_courses ADD COLUMN certificate_institution_site VARCHAR(180) NULL AFTER certificate_institution_cnpj',
    'certificate_objectives' => 'ALTER TABLE education_courses ADD COLUMN certificate_objectives TEXT NULL AFTER certificate_institution_site',
    'certificate_competencies' => 'ALTER TABLE education_courses ADD COLUMN certificate_competencies TEXT NULL AFTER certificate_objectives',
    'certificate_responsible_name' => 'ALTER TABLE education_courses ADD COLUMN certificate_responsible_name VARCHAR(180) NULL AFTER certificate_competencies',
    'certificate_responsible_credential' => 'ALTER TABLE education_courses ADD COLUMN certificate_responsible_credential VARCHAR(180) NULL AFTER certificate_responsible_name',
    'certificate_program_enabled' => 'ALTER TABLE education_courses ADD COLUMN certificate_program_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_responsible_credential',
    'certificate_program_background' => 'ALTER TABLE education_courses ADD COLUMN certificate_program_background VARCHAR(255) NULL AFTER certificate_program_enabled',
    'certificate_program_extra' => 'ALTER TABLE education_courses ADD COLUMN certificate_program_extra TEXT NULL AFTER certificate_program_background',
    'certificate_program_columns' => 'ALTER TABLE education_courses ADD COLUMN certificate_program_columns TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER certificate_program_extra',
];
foreach ($courseCertificateColumns as $column => $sql) {
    if (!in_array($column, $courseColumns, true)) {
        $pdo->exec($sql);
    }
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_modules (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        course_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL,
        summary TEXT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT fk_education_modules_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_lessons (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        course_id BIGINT UNSIGNED NOT NULL,
        module_id BIGINT UNSIGNED NULL,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        video_url VARCHAR(255) NULL,
        image_url VARCHAR(255) NULL,
        locked TINYINT(1) NOT NULL DEFAULT 0,
        available_at DATETIME NULL,
        attendance_mode VARCHAR(20) NOT NULL DEFAULT "video",
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT fk_education_lessons_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_education_lessons_module FOREIGN KEY (module_id) REFERENCES education_modules(id) ON DELETE SET NULL
    ) ENGINE=InnoDB'
);

$lessonColumns = $pdo->query('SHOW COLUMNS FROM education_lessons')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('module_id', $lessonColumns, true)) {
    $pdo->exec('ALTER TABLE education_lessons ADD COLUMN module_id BIGINT UNSIGNED NULL AFTER course_id');
}
if (!in_array('image_url', $lessonColumns, true)) {
    $pdo->exec('ALTER TABLE education_lessons ADD COLUMN image_url VARCHAR(255) NULL AFTER video_url');
}
if (!in_array('locked', $lessonColumns, true)) {
    $pdo->exec('ALTER TABLE education_lessons ADD COLUMN locked TINYINT(1) NOT NULL DEFAULT 0 AFTER image_url');
}
if (!in_array('available_at', $lessonColumns, true)) {
    $pdo->exec('ALTER TABLE education_lessons ADD COLUMN available_at DATETIME NULL AFTER locked');
}
if (!in_array('attendance_mode', $lessonColumns, true)) {
    $pdo->exec('ALTER TABLE education_lessons ADD COLUMN attendance_mode VARCHAR(20) NOT NULL DEFAULT "video" AFTER available_at');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_lesson_blocks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lesson_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(40) NOT NULL DEFAULT "text",
        title VARCHAR(180) NULL,
        content LONGTEXT NULL,
        media_url VARCHAR(255) NULL,
        file_path VARCHAR(255) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT fk_education_blocks_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_enrollments (
        course_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        PRIMARY KEY (course_id, user_id),
        CONSTRAINT fk_education_enrollments_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_education_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_lesson_progress (
        lesson_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        completed_at DATETIME NULL,
        updated_at TIMESTAMP NULL,
        PRIMARY KEY (lesson_id, user_id),
        CONSTRAINT fk_education_progress_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
        CONSTRAINT fk_education_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_batches (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        institution_id BIGINT UNSIGNED NULL,
        course_id BIGINT UNSIGNED NULL,
        template_id BIGINT UNSIGNED NULL,
        title VARCHAR(180) NOT NULL,
        source_filename VARCHAR(190) NULL,
        total_rows INT UNSIGNED NOT NULL DEFAULT 0,
        issued_count INT UNSIGNED NOT NULL DEFAULT 0,
        failed_count INT UNSIGNED NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT "draft",
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
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_certificates (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        course_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        person_id BIGINT UNSIGNED NULL,
        verification_code VARCHAR(48) NOT NULL,
        validation_hash CHAR(64) NULL,
        status VARCHAR(30) NOT NULL DEFAULT "issued",
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
    ) ENGINE=InnoDB'
);
$certificateColumns = $pdo->query('SHOW COLUMNS FROM education_certificates')->fetchAll(PDO::FETCH_COLUMN);
$certificateNameColumns = [
    'validation_hash' => 'ALTER TABLE education_certificates ADD COLUMN validation_hash CHAR(64) NULL AFTER verification_code',
    'status' => 'ALTER TABLE education_certificates ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT "issued" AFTER validation_hash',
    'batch_id' => 'ALTER TABLE education_certificates ADD COLUMN batch_id BIGINT UNSIGNED NULL AFTER status',
    'person_id' => 'ALTER TABLE education_certificates ADD COLUMN person_id BIGINT UNSIGNED NULL AFTER user_id',
    'student_name' => 'ALTER TABLE education_certificates ADD COLUMN student_name VARCHAR(180) NULL AFTER verification_code',
    'requested_student_name' => 'ALTER TABLE education_certificates ADD COLUMN requested_student_name VARCHAR(180) NULL AFTER student_name',
    'name_change_status' => 'ALTER TABLE education_certificates ADD COLUMN name_change_status VARCHAR(20) NULL AFTER requested_student_name',
    'name_change_requested_at' => 'ALTER TABLE education_certificates ADD COLUMN name_change_requested_at DATETIME NULL AFTER name_change_status',
    'name_change_reviewed_by' => 'ALTER TABLE education_certificates ADD COLUMN name_change_reviewed_by BIGINT UNSIGNED NULL AFTER name_change_requested_at',
    'name_change_reviewed_at' => 'ALTER TABLE education_certificates ADD COLUMN name_change_reviewed_at DATETIME NULL AFTER name_change_reviewed_by',
    'authorized_by' => 'ALTER TABLE education_certificates ADD COLUMN authorized_by BIGINT UNSIGNED NULL AFTER name_change_reviewed_at',
    'authorized_at' => 'ALTER TABLE education_certificates ADD COLUMN authorized_at DATETIME NULL AFTER authorized_by',
    'issued_by' => 'ALTER TABLE education_certificates ADD COLUMN issued_by BIGINT UNSIGNED NULL AFTER authorized_at',
    'revoked_by' => 'ALTER TABLE education_certificates ADD COLUMN revoked_by BIGINT UNSIGNED NULL AFTER issued_by',
    'revoked_at' => 'ALTER TABLE education_certificates ADD COLUMN revoked_at DATETIME NULL AFTER revoked_by',
    'revoked_reason' => 'ALTER TABLE education_certificates ADD COLUMN revoked_reason TEXT NULL AFTER revoked_at',
    'pdf_path' => 'ALTER TABLE education_certificates ADD COLUMN pdf_path VARCHAR(255) NULL AFTER revoked_reason',
    'sent_at' => 'ALTER TABLE education_certificates ADD COLUMN sent_at DATETIME NULL AFTER pdf_path',
    'verified_count' => 'ALTER TABLE education_certificates ADD COLUMN verified_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER sent_at',
    'last_verified_at' => 'ALTER TABLE education_certificates ADD COLUMN last_verified_at DATETIME NULL AFTER verified_count',
];
foreach ($certificateNameColumns as $column => $sql) {
    if (!in_array($column, $certificateColumns, true)) {
        $pdo->exec($sql);
    }
}
$pdo->exec('ALTER TABLE education_certificates MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
$pdo->exec('UPDATE education_certificates SET status = "issued" WHERE status IS NULL OR status = ""');
$pdo->exec('UPDATE education_certificates SET validation_hash = SHA2(CONCAT(course_id, "|", COALESCE(user_id, person_id, 0), "|", verification_code), 256) WHERE validation_hash IS NULL OR validation_hash = ""');

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS certificate_audit_logs (
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
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_forum_topics (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        course_id BIGINT UNSIGNED NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL,
        body TEXT NOT NULL,
        status ENUM("open","closed","hidden") NOT NULL DEFAULT "open",
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX idx_education_forum_topics_course (course_id),
        CONSTRAINT fk_education_topics_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_education_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS education_forum_replies (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        topic_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT fk_education_replies_topic FOREIGN KEY (topic_id) REFERENCES education_forum_topics(id) ON DELETE CASCADE,
        CONSTRAINT fk_education_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$storageDocumentsDir = dirname(__DIR__) . '/storage/documents';
if (!is_dir($storageDocumentsDir)) {
    mkdir($storageDocumentsDir, 0775, true);
}

$columns = [
    'public_visibility' => "ALTER TABLE news ADD COLUMN public_visibility VARCHAR(20) NOT NULL DEFAULT 'listed' AFTER status",
    'is_archive' => "ALTER TABLE news ADD COLUMN is_archive TINYINT(1) NOT NULL DEFAULT 0 AFTER urgent",
    'original_published_at' => "ALTER TABLE news ADD COLUMN original_published_at DATE NULL AFTER is_archive",
    'original_author' => "ALTER TABLE news ADD COLUMN original_author VARCHAR(160) NULL AFTER original_published_at",
    'original_source' => "ALTER TABLE news ADD COLUMN original_source VARCHAR(160) NULL AFTER original_author",
    'original_url' => "ALTER TABLE news ADD COLUMN original_url VARCHAR(255) NULL AFTER original_source",
    'archive_note' => "ALTER TABLE news ADD COLUMN archive_note TEXT NULL AFTER original_url",
];

$existingColumns = $pdo->query('SHOW COLUMNS FROM news')->fetchAll(PDO::FETCH_COLUMN);
foreach ($columns as $column => $sql) {
    if (!in_array($column, $existingColumns, true)) {
        $pdo->exec($sql);
    }
}

$roles = [
    ['MASTER', 'master', 100],
    ['ADMIN', 'admin', 80],
    ['ADMIN LOCAL', 'admin-local', 60],
    ['DELEGADO EMISSOR', 'delegado-emissor', 55],
    ['DIRETOR', 'diretor', 50],
    ['JORNALISTA', 'jornalista', 40],
    ['COLUNISTA', 'colunista', 35],
    ['PROFESSOR', 'professor', 30],
    ['EDITOR LGPD', 'editor-lgpd', 25],
    ['VOLUNTARIO', 'voluntario', 20],
    ['VISUALIZADOR LGPD', 'visualizador-lgpd', 15],
    ['ESTUDANTE', 'estudante', 10],
];

$pdo->exec("UPDATE roles SET name = 'VOLUNTARIO', slug = 'voluntario', level = 20, updated_at = NOW() WHERE slug = 'equipe' AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM roles WHERE slug = 'voluntario') AS existing_voluntario)");

$permissions = [
    ['Gerenciar usuários', 'users.manage'],
    ['Gerenciar permissões', 'permissions.manage'],
    ['Ver logs', 'logs.view'],
    ['Gerenciar notícias', 'news.manage'],
    ['Aprovar notícias', 'news.approve'],
    ['Criar notícias', 'news.create'],
    ['Gerenciar categorias', 'categories.manage'],
    ['Gerenciar tags', 'tags.manage'],
    ['Moderar comentários', 'comments.moderate'],
    ['Gerenciar publicidade', 'ads.manage'],
    ['Gerenciar regiões', 'regions.manage'],
    ['Gerenciar menu', 'menu.manage'],
    ['Ver documentos', 'documents.view'],
    ['Gerenciar documentos', 'documents.manage'],
    ['Gerenciar pessoas internas', 'people.manage'],
    ['Gerenciar eventos internos', 'events.manage'],
    ['Gerenciar participantes de eventos', 'event_participants.manage'],
    ['Gerenciar ensino', 'education.manage'],
    ['Criar cursos e aulas', 'education.teach'],
    ['Acessar ensino', 'education.view'],
    ['Participar do fórum de ensino', 'education.forum'],
    ['Gerenciar certificados digitais', 'certificates.manage'],
    ['Emitir certificados digitais', 'certificates.issue'],
    ['Ver auditoria de certificados', 'certificates.audit'],
    ['Gerenciar instituições certificadoras', 'certificates.institutions'],
    ['Gerenciar modelos de certificados', 'certificates.templates'],
    ['Aprovar lotes de certificados', 'certificates.batches.approve'],
    ['Ver fóruns', 'forum.view'],
    ['Criar tópicos e respostas nos fóruns', 'forum.create'],
    ['Moderar fóruns', 'forum.moderate'],
    ['Visualizar consentimentos LGPD', 'consent.view'],
    ['Editar textos e politicas LGPD', 'consent.texts'],
    ['Gerenciar CMP LGPD', 'consent.manage'],
];

$stmt = $pdo->prepare('INSERT IGNORE INTO roles (name, slug, level, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
foreach ($roles as $role) {
    $stmt->execute($role);
}

$stmt = $pdo->prepare('INSERT IGNORE INTO permissions (name, slug, created_at) VALUES (?, ?, NOW())');
foreach ($permissions as $permission) {
    $stmt->execute($permission);
}

$stmt = $pdo->prepare('UPDATE permissions SET name = ? WHERE slug = ?');
foreach ($permissions as $permission) {
    $stmt->execute($permission);
}

$roleRows = $pdo->query('SELECT id, slug FROM roles')->fetchAll();
$permissionRows = $pdo->query('SELECT id, slug FROM permissions')->fetchAll();
$roleMap = array_column($roleRows, 'id', 'slug');
$permissionIds = array_column($permissionRows, 'id', 'slug');

$grants = [
    'master' => array_keys($permissionIds),
    'admin' => ['users.manage', 'news.manage', 'news.approve', 'news.create', 'categories.manage', 'tags.manage', 'comments.moderate', 'ads.manage', 'education.manage', 'education.view', 'education.forum', 'certificates.manage', 'certificates.issue', 'certificates.audit', 'certificates.institutions', 'certificates.templates', 'certificates.batches.approve', 'forum.view', 'forum.create', 'forum.moderate', 'consent.view', 'consent.texts', 'consent.manage'],
    'admin-local' => ['news.manage', 'news.approve', 'news.create', 'categories.manage', 'education.manage', 'education.view', 'education.forum', 'certificates.manage', 'certificates.issue', 'certificates.templates', 'forum.view', 'forum.create'],
    'delegado-emissor' => ['education.view', 'certificates.issue'],
    'diretor' => ['documents.view', 'people.manage', 'education.manage', 'education.view', 'education.forum', 'forum.view', 'forum.create', 'forum.moderate'],
    'jornalista' => ['news.create'],
    'colunista' => ['news.create'],
    'professor' => ['education.teach', 'education.view', 'education.forum', 'forum.view', 'forum.create'],
    'voluntario' => ['people.manage', 'events.manage', 'event_participants.manage', 'forum.view', 'forum.create'],
    'editor-lgpd' => ['consent.view', 'consent.texts'],
    'visualizador-lgpd' => ['consent.view'],
    'estudante' => ['education.view', 'education.forum', 'forum.view', 'forum.create'],
];

$stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
foreach ($grants as $roleSlug => $rolePermissions) {
    foreach ($rolePermissions as $permissionSlug) {
        $stmt->execute([$roleMap[$roleSlug], $permissionIds[$permissionSlug]]);
    }
}

$pdo->prepare(
    'INSERT IGNORE INTO certificate_institutions
        (name, slug, city, state, site, active, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())'
)->execute(['Cidade Nova Informa - CNI', 'cidade-nova-informa-cni', 'Foz do Iguaçu', 'PR', 'www.cidadenovainforma.com.br']);

$pdo->prepare(
    'INSERT IGNORE INTO certificate_categories
        (institution_id, name, slug, description, active, created_at, updated_at)
     SELECT id, ?, ?, ?, 1, NOW(), NOW()
     FROM certificate_institutions
     WHERE slug = ?'
)->execute(['Cursos livres e capacitações', 'cursos-livres-capacitacoes', 'Certificados de cursos livres, oficinas, palestras e formações continuadas.', 'cidade-nova-informa-cni']);

$pdo->prepare(
    'INSERT IGNORE INTO certificate_templates
        (institution_id, category_id, name, slug, description, legal_text, version, active, created_at, updated_at)
     SELECT certificate_institutions.id,
            certificate_categories.id,
            ?, ?, ?, ?, 1, 1, NOW(), NOW()
     FROM certificate_institutions
     LEFT JOIN certificate_categories
       ON certificate_categories.institution_id = certificate_institutions.id
      AND certificate_categories.slug = ?
     WHERE certificate_institutions.slug = ?'
)->execute([
    'Modelo padrão CNI',
    'modelo-padrao-cni',
    'Modelo institucional usado como base para certificados digitais do Cidade Nova Informa.',
    'Certificado emitido conforme a Lei nº 9.394/96 (LDB) e Decreto nº 5.154/2004 referente a cursos livres e capacitações.',
    'cursos-livres-capacitacoes',
    'cidade-nova-informa-cni',
]);

$stmt = $pdo->prepare(
    'DELETE role_permissions
     FROM role_permissions
     INNER JOIN roles ON roles.id = role_permissions.role_id
     INNER JOIN permissions ON permissions.id = role_permissions.permission_id
     WHERE roles.slug IN ("jornalista", "colunista", "voluntario")
       AND permissions.slug IN ("education.manage", "education.teach")'
);
$stmt->execute();

$stmt = $pdo->prepare(
    'DELETE role_permissions
     FROM role_permissions
     INNER JOIN roles ON roles.id = role_permissions.role_id
     INNER JOIN permissions ON permissions.id = role_permissions.permission_id
     WHERE roles.slug IN ("admin", "admin-local", "diretor")
       AND permissions.slug = "education.teach"'
);
$stmt->execute();

$stmt = $pdo->prepare(
    'DELETE role_permissions
     FROM role_permissions
     INNER JOIN roles ON roles.id = role_permissions.role_id
     INNER JOIN permissions ON permissions.id = role_permissions.permission_id
     WHERE roles.slug = "professor"
       AND permissions.slug = "forum.moderate"'
);
$stmt->execute();

$stmt = $pdo->prepare(
    'DELETE role_permissions
     FROM role_permissions
     INNER JOIN roles ON roles.id = role_permissions.role_id
     INNER JOIN permissions ON permissions.id = role_permissions.permission_id
     WHERE roles.slug = "diretor"
       AND permissions.slug IN ("users.manage", "permissions.manage", "logs.view", "news.manage", "news.approve", "news.create", "categories.manage", "tags.manage", "comments.moderate", "ads.manage", "regions.manage", "menu.manage", "documents.manage")'
);
$stmt->execute();

$masterEmail = getenv('MASTER_EMAIL') ?: 'master@cidadenovainforma.local';
$masterPassword = getenv('MASTER_PASSWORD') ?: 'Master@12345';

$stmt = $pdo->prepare(
    'INSERT IGNORE INTO users (role_id, name, email, password_hash, active, created_at, updated_at)
     VALUES (:role_id, :name, :email, :password_hash, 1, NOW(), NOW())'
);
$stmt->execute([
    'role_id' => $roleMap['master'],
    'name' => 'Master',
    'email' => $masterEmail,
    'password_hash' => password_hash($masterPassword, PASSWORD_DEFAULT),
]);

$pdo->exec(
    'INSERT IGNORE INTO user_roles (user_id, role_id, created_at)
     SELECT id, role_id, NOW()
     FROM users'
);

$categories = [
    ['Geral', 'geral'],
    ['Bairro', 'bairro'],
    ['Política', 'politica'],
    ['Educação', 'educacao'],
    ['Saúde', 'saude'],
    ['Esporte', 'esporte'],
    ['Cultura', 'cultura'],
    ['Horta', 'horta'],
    ['Rádio', 'radio'],
];

$pdo->exec("UPDATE categories SET name = 'Bairro', slug = 'bairro', updated_at = NOW() WHERE slug = 'cidade' AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM categories WHERE slug = 'bairro') AS existing_bairro)");

$stmt = $pdo->prepare(
    'INSERT IGNORE INTO categories (name, slug, active, created_at, updated_at)
     VALUES (?, ?, 1, NOW(), NOW())'
);

foreach ($categories as $category) {
    $stmt->execute($category);
}

$stmt = $pdo->prepare('UPDATE categories SET name = ?, updated_at = NOW() WHERE slug = ?');
foreach ($categories as $category) {
    $stmt->execute($category);
}

$categoryRows = $pdo->query('SELECT id, name, slug FROM categories WHERE active = 1 ORDER BY FIELD(slug, "geral", "bairro", "politica", "educacao", "saude", "esporte", "cultura", "horta", "radio"), name')->fetchAll();
$menuCount = (int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();

if ($menuCount === 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO menu_items (category_id, label, url, sort_order, visible, created_at, updated_at)
         VALUES (?, ?, ?, ?, 1, NOW(), NOW())'
    );

    foreach ($categoryRows as $index => $category) {
        $stmt->execute([
            $category['id'],
            $category['name'],
            '/categoria/' . $category['slug'],
            ($index + 1) * 10,
        ]);
    }

    $stmt->execute([
        null,
        'Reprise',
        '/reprise',
        95,
    ]);
}

$pdo->exec(
    'UPDATE menu_items
     SET label = "Reprise", url = "/reprise", updated_at = NOW()
     WHERE url = "/acervo" OR label = "Acervo"'
);

$stmt = $pdo->prepare(
    'INSERT INTO menu_items (category_id, label, url, sort_order, visible, created_at, updated_at)
     SELECT NULL, "Reprise", "/reprise", 95, 1, NOW(), NOW()
     WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = "/reprise")'
);
$stmt->execute();

echo "Seed finalizado.\n";
echo "MASTER_EMAIL={$masterEmail}\n";
echo "MASTER_PASSWORD={$masterPassword}\n";
