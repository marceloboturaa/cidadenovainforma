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
        search_terms VARCHAR(255) NOT NULL,
        related_tags_json TEXT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    ) ENGINE=InnoDB'
);

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
    ['JORNALISTA', 'jornalista', 40],
    ['EQUIPE', 'equipe', 20],
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
    'admin' => ['users.manage', 'news.manage', 'news.approve', 'news.create', 'categories.manage', 'tags.manage', 'comments.moderate', 'ads.manage'],
    'admin-local' => ['news.manage', 'news.approve', 'news.create', 'categories.manage'],
    'jornalista' => ['news.create'],
    'equipe' => ['documents.view'],
];

$stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
foreach ($grants as $roleSlug => $rolePermissions) {
    foreach ($rolePermissions as $permissionSlug) {
        $stmt->execute([$roleMap[$roleSlug], $permissionIds[$permissionSlug]]);
    }
}

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
        'Acervo',
        '/acervo',
        95,
    ]);
}

$stmt = $pdo->prepare(
    'INSERT INTO menu_items (category_id, label, url, sort_order, visible, created_at, updated_at)
     SELECT NULL, "Acervo", "/acervo", 95, 1, NOW(), NOW()
     WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = "/acervo")'
);
$stmt->execute();

echo "Seed finalizado.\n";
echo "MASTER_EMAIL={$masterEmail}\n";
echo "MASTER_PASSWORD={$masterPassword}\n";
