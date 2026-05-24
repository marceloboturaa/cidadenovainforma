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
<?php $canManageInstitutionLanding = $user ? \App\Core\Auth::hasRole(['master', 'admin']) : false; ?>
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
<body class="admin-sidebar-collapsed">
    <div class="admin-menu-overlay" data-admin-menu-close></div>
    <div class="admin-layout">
        <aside class="sidebar" id="admin-sidebar">
            <div class="sidebar-head">
                <a class="brand" href="<?= e(url('/admin')) ?>"><span>Cidade Nova Informa</span></a>
                <button class="sidebar-collapse-toggle" type="button" data-sidebar-collapse-toggle aria-label="Ocultar menu lateral" aria-pressed="false" title="Ocultar menu">
                    <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                </button>
            </div>
            <nav>
                <a class="<?= $currentPath === '/admin' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>" title="Dashboard"><i class="bi bi-speedometer2" aria-hidden="true"></i><span>Dashboard</span></a>
                <?php if (\App\Core\Auth::can('users.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/users') ? 'active' : '' ?>" href="<?= e(url('/admin/users')) ?>" title="Usuários"><i class="bi bi-people" aria-hidden="true"></i><span>Usuários</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('news.create') || \App\Core\Auth::can('news.manage') || \App\Core\Auth::can('news.approve')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/news') ? 'active' : '' ?>" href="<?= e(url('/admin/news')) ?>" title="Notícias"><i class="bi bi-newspaper" aria-hidden="true"></i><span>Notícias</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('categories.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/categories') ? 'active' : '' ?>" href="<?= e(url('/admin/categories')) ?>" title="Categorias"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Categorias</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('tags.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/tags') ? 'active' : '' ?>" href="<?= e(url('/admin/tags')) ?>" title="Tags"><i class="bi bi-tags" aria-hidden="true"></i><span>Tags</span></a>
                <?php endif; ?>
                <?php if ($institutionPageAccess || $canManageInstitutionLanding): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/institution-pages') ? 'active' : '' ?>" href="<?= e(url('/admin/institution-pages')) ?>" title="Instituição"><i class="bi bi-building" aria-hidden="true"></i><span>Instituição</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('people.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/people') ? 'active' : '' ?>" href="<?= e(url('/admin/people')) ?>" title="Pessoas"><i class="bi bi-person-lines-fill" aria-hidden="true"></i><span>Pessoas</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('events.manage')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/library-events') ? 'active' : '' ?>" href="<?= e(url('/admin/library-events')) ?>" title="Eventos"><i class="bi bi-calendar-event" aria-hidden="true"></i><span>Eventos</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('documents.view') || (\App\Core\Auth::can('documents.manage') && !in_array('diretor', $roleSlugs, true)) || ($user && (\App\Models\Document::userCanUpload((int) $user['id']) || \App\Models\Document::userHasAnyAccess((int) $user['id'])))): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/documents') ? 'active' : '' ?>" href="<?= e(url('/admin/documents')) ?>" title="Documentos"><i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i><span>Documentos</span></a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a class="<?= ($currentPath === '/admin/education' || str_starts_with($currentPath, '/admin/education/course') || str_starts_with($currentPath, '/admin/education/lesson')) ? 'active' : '' ?>" href="<?= e(url('/admin/education')) ?>" title="Ensino"><i class="bi bi-mortarboard" aria-hidden="true"></i><span>Ensino</span></a>
                    <a class="<?= ($currentPath === '/admin/education/certificates' || $currentPath === '/admin/education/certificate') ? 'active' : '' ?>" href="<?= e(url('/admin/education/certificates')) ?>" title="Meus certificados"><i class="bi bi-award" aria-hidden="true"></i><span>Meus certificados</span></a>
                <?php endif; ?>
                <?php if (array_intersect($roleSlugs, ['master', 'admin', 'admin-local', 'diretor', 'professor']) || \App\Core\Auth::can('education.teach')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/education/manage') ? 'active' : '' ?>" href="<?= e(url('/admin/education/manage')) ?>" title="Cursos"><i class="bi bi-journal-richtext" aria-hidden="true"></i><span>Cursos</span></a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('forum.view') || \App\Core\Auth::can('forum.create') || \App\Core\Auth::can('forum.moderate')): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/forum') ? 'active' : '' ?>" href="<?= e(url('/admin/forum')) ?>" title="Fóruns"><i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Fóruns</span></a>
                <?php endif; ?>
                <?php if (($user['role_slug'] ?? '') === 'master'): ?>
                    <a class="<?= str_starts_with($currentPath, '/admin/menu') ? 'active' : '' ?>" href="<?= e(url('/admin/menu')) ?>" title="Menu"><i class="bi bi-list-ul" aria-hidden="true"></i><span>Menu</span></a>
                    <a class="<?= str_starts_with($currentPath, '/admin/backups') ? 'active' : '' ?>" href="<?= e(url('/admin/backups')) ?>" title="Backups"><i class="bi bi-cloud-arrow-down" aria-hidden="true"></i><span>Backups</span></a>
                <?php endif; ?>
                <a class="<?= $currentPath === '/admin/password' ? 'active' : '' ?>" href="<?= e(url('/admin/password')) ?>" title="Minha senha"><i class="bi bi-key" aria-hidden="true"></i><span>Minha senha</span></a>
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
