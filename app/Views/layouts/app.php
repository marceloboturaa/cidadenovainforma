<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $assets = require dirname(__DIR__, 3) . '/config/assets.php'; ?>
<?php $user = current_user(); ?>
<?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin'; ?>
<?php $adminCssFiles = $assets['css']['admin'] ?? ['/public/assets/css/admin.css']; ?>
<?php $adminJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/admin.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/admin.js') : time(); ?>
<?php $tinyMceJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/tinymce-init.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/tinymce-init.js') : time(); ?>
<?php $faviconPath = str_starts_with($currentPath, '/admin/education') ? '/public/assets/img/favicon-education.svg' : '/public/assets/img/favicon-secondary.svg'; ?>
<?php $faviconVersion = filemtime(dirname(__DIR__, 3) . $faviconPath); ?>
<?php $institutionPageAccess = $user ? \App\Models\InstitutionPage::manageableForUser((int) $user['id'], ($user['role_slug'] ?? '') === 'master') : []; ?>
<?php $roleSlugs = $user ? \App\Core\Auth::roleSlugs($user) : []; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <meta name="tinymce-upload-url" content="<?= e(url('/admin/media/tinymce')) ?>">
    <title>Painel - <?= e($app['name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url($faviconPath) . '?v=' . $faviconVersion) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php foreach ($adminCssFiles as $cssFile): ?>
        <link href="<?= e(versioned_asset_url($cssFile)) ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body>
    <div class="admin-menu-overlay" data-admin-menu-close></div>
    <div class="admin-layout">
        <aside class="sidebar" id="admin-sidebar">
            <a class="brand" href="<?= e(url('/admin')) ?>">Cidade Nova Informa</a>
            <nav>
                <a class="<?= $currentPath === '/admin' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>"><i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard</a>
                <?php if (\App\Core\Auth::can('users.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/users') ? 'active' : '' ?>" href="<?= e(url('/admin/users')) ?>"><i class="bi bi-people" aria-hidden="true"></i>Usuários</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('news.create') || \App\Core\Auth::can('news.manage') || \App\Core\Auth::can('news.approve')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/news') ? 'active' : '' ?>" href="<?= e(url('/admin/news')) ?>"><i class="bi bi-newspaper" aria-hidden="true"></i>Notícias</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('categories.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/categories') ? 'active' : '' ?>" href="<?= e(url('/admin/categories')) ?>"><i class="bi bi-folder2-open" aria-hidden="true"></i>Categorias</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('tags.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/tags') ? 'active' : '' ?>" href="<?= e(url('/admin/tags')) ?>"><i class="bi bi-tags" aria-hidden="true"></i>Tags</a>
                <?php endif; ?>
                <?php if ($institutionPageAccess): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/institution-pages') ? 'active' : '' ?>" href="<?= e(url('/admin/institution-pages')) ?>"><i class="bi bi-building" aria-hidden="true"></i>Instituição</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('people.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/people') ? 'active' : '' ?>" href="<?= e(url('/admin/people')) ?>"><i class="bi bi-person-lines-fill" aria-hidden="true"></i>Pessoas</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('events.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/library-events') ? 'active' : '' ?>" href="<?= e(url('/admin/library-events')) ?>"><i class="bi bi-calendar-event" aria-hidden="true"></i>Eventos</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('documents.view') || (\App\Core\Auth::can('documents.manage') && !in_array('diretor', $roleSlugs, true)) || ($user && \App\Models\Document::userHasAnyAccess((int) $user['id']))): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/documents') ? 'active' : '' ?>" href="<?= e(url('/admin/documents')) ?>"><i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>Documentos</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a class="<?= ($currentPath === '/admin/education' || str_starts_with($currentPath, '/admin/education/course') || str_starts_with($currentPath, '/admin/education/lesson')) ? 'active' : '' ?>" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-mortarboard" aria-hidden="true"></i>Ensino</a>
                <?php endif; ?>
                <?php if (array_intersect($roleSlugs, ['master', 'admin', 'admin-local', 'diretor', 'professor']) || \App\Core\Auth::can('education.teach')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/education/manage') ? 'active' : '' ?>" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-journal-richtext" aria-hidden="true"></i>Cursos</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('forum.view') || \App\Core\Auth::can('forum.create') || \App\Core\Auth::can('forum.moderate')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/forum') ? 'active' : '' ?>" href="<?= e(url('/admin/forum')) ?>"><i class="bi bi-chat-square-text" aria-hidden="true"></i>Fóruns</a>
                <?php endif; ?>
                <?php if (($user['role_slug'] ?? '') === 'master'): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/menu') ? 'active' : '' ?>" href="<?= e(url('/admin/menu')) ?>"><i class="bi bi-list-ul" aria-hidden="true"></i>Menu</a>
                    <a class="<?= str_starts_with($currentPath, '/admin/backups') ? 'active' : '' ?>" href="<?= e(url('/admin/backups')) ?>"><i class="bi bi-cloud-arrow-down" aria-hidden="true"></i>Backups</a>
                <?php endif; ?>
                <a class="<?= $currentPath === '/admin/password' ? 'active' : '' ?>" href="<?= e(url('/admin/password')) ?>"><i class="bi bi-key" aria-hidden="true"></i>Minha senha</a>
            </nav>
        </aside>
        <div class="main-panel">
            <header class="topbar">
                <button class="admin-menu-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>
                <div>
                    <strong><?= e($user['name'] ?? 'Usuário') ?></strong>
                    <span><?= e($user['role_names'] ?? $user['role_name'] ?? '') ?></span>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-light btn-sm icon-btn"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sair</button>
                </form>
            </header>
            <main class="content">
                <?php if ($message = flash('success')): ?>
                    <div class="alert alert-success"><?= e($message) ?></div>
                <?php endif; ?>
                <?php if ($message = flash('error')): ?>
                    <div class="alert alert-danger"><?= e($message) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
    <script src="<?= e(url('/public/assets/js/password-toggle.js')) ?>"></script>
    <script src="<?= e(url('/public/assets/js/admin.js') . '?v=' . $adminJsVersion) ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="<?= e(url('/public/assets/js/tinymce-init.js') . '?v=' . $tinyMceJsVersion) ?>"></script>
</body>
</html>
