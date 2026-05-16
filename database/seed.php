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

$teamDocumentColumns = $pdo->query('SHOW COLUMNS FROM team_documents')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('is_public', $teamDocumentColumns, true)) {
    $pdo->exec('ALTER TABLE team_documents ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER size_bytes');
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
        teacher_user_id BIGINT UNSIGNED NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_by BIGINT UNSIGNED NULL,
        updated_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT fk_education_courses_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_education_courses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_education_courses_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB'
);

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
    ['DIRETOR', 'diretor', 50],
    ['JORNALISTA', 'jornalista', 40],
    ['COLUNISTA', 'colunista', 35],
    ['PROFESSOR', 'professor', 30],
    ['EQUIPE', 'equipe', 20],
    ['ESTUDANTE', 'estudante', 10],
];

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
    ['Ver fóruns', 'forum.view'],
    ['Criar tópicos e respostas nos fóruns', 'forum.create'],
    ['Moderar fóruns', 'forum.moderate'],
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
    'admin' => ['users.manage', 'news.manage', 'news.approve', 'news.create', 'categories.manage', 'tags.manage', 'comments.moderate', 'ads.manage', 'education.manage', 'education.view', 'education.forum', 'forum.view', 'forum.create', 'forum.moderate'],
    'admin-local' => ['news.manage', 'news.approve', 'news.create', 'categories.manage', 'education.manage', 'education.view', 'education.forum', 'forum.view', 'forum.create'],
    'diretor' => ['documents.view', 'people.manage', 'education.manage', 'education.view', 'education.forum', 'forum.view', 'forum.create', 'forum.moderate'],
    'jornalista' => ['news.create'],
    'colunista' => ['news.create'],
    'professor' => ['education.teach', 'education.view', 'education.forum', 'forum.view', 'forum.create'],
    'equipe' => ['documents.view', 'people.manage', 'events.manage', 'event_participants.manage', 'forum.view', 'forum.create'],
    'estudante' => ['education.view', 'education.forum', 'forum.view', 'forum.create'],
];

$stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
foreach ($grants as $roleSlug => $rolePermissions) {
    foreach ($rolePermissions as $permissionSlug) {
        $stmt->execute([$roleMap[$roleSlug], $permissionIds[$permissionSlug]]);
    }
}

$stmt = $pdo->prepare(
    'DELETE role_permissions
     FROM role_permissions
     INNER JOIN roles ON roles.id = role_permissions.role_id
     INNER JOIN permissions ON permissions.id = role_permissions.permission_id
     WHERE roles.slug IN ("jornalista", "colunista", "equipe")
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
