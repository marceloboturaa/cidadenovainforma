<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $assets = require dirname(__DIR__, 3) . '/config/assets.php'; ?>
<?php $user = current_user(); ?>
<?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin'; ?>
<?php $adminCssFiles = $assets['css']['admin'] ?? ['/public/assets/css/admin.css']; ?>
<?php $adminJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/admin.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/admin.js') : time(); ?>
<?php $passwordToggleJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/password-toggle.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/password-toggle.js') : time(); ?>
<?php $tinyMceJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/tinymce-init.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/tinymce-init.js') : time(); ?>
<?php $faviconPath = str_starts_with($currentPath, '/admin/education') ? '/public/assets/img/favicon-education.svg' : '/public/assets/img/favicon-secondary.svg'; ?>
<?php $faviconVersion = filemtime(dirname(__DIR__, 3) . $faviconPath); ?>
<?php $navigationGroups = \App\Core\AdminNavigation::visibleGroups(); ?>
<?php $panelAnnouncements = $user ? \App\Models\Announcement::unreadForUser((int) $user['id'], 3) : []; ?>
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
                <?php foreach ($navigationGroups as $groupLabel => $items): ?>
                    <div class="sidebar-group">
                        <span class="sidebar-group-title"><?= e($groupLabel) ?></span>
                        <?php foreach ($items as [$itemPath, $itemLabel, $itemIcon]): ?>
                            <?php
                            $itemUrl = url($itemPath);
                            $menuPath = parse_url($itemUrl, PHP_URL_PATH) ?: $itemPath;
                            $active = $currentPath === $menuPath || ($itemPath !== '/admin' && $itemPath !== '/admin/education' && str_starts_with($currentPath, $menuPath . '/'));
                            if ($itemPath === '/admin/education') {
                                $active = $active || str_starts_with($currentPath, $menuPath . '/course') || str_starts_with($currentPath, $menuPath . '/lesson');
                            }
                            ?>
                            <a class="<?= $active ? 'active' : '' ?>" href="<?= e($itemUrl) ?>" title="<?= e($itemLabel) ?>"><i class="bi bi-<?= e($itemIcon) ?>" aria-hidden="true"></i><span><?= e($itemLabel) ?></span></a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
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
                <?php if ($panelAnnouncements): ?>
                    <div class="topbar-announcements">
                        <button class="btn btn-outline-light btn-sm icon-btn" type="button" data-announcement-toggle aria-expanded="false">
                            <i class="bi bi-bell" aria-hidden="true"></i>Avisos
                            <span><?= e((string) count($panelAnnouncements)) ?></span>
                        </button>
                        <div class="announcement-popover" data-announcement-popover hidden>
                            <?php foreach ($panelAnnouncements as $announcement): ?>
                                <?php $announcementUrl = (string) ($announcement['url'] ?? ''); ?>
                                <?php $announcementHref = preg_match('#^https?://#i', $announcementUrl) ? $announcementUrl : url($announcementUrl); ?>
                                <article>
                                    <strong><?= e($announcement['title']) ?></strong>
                                    <p><?= nl2br(e($announcement['body'])) ?></p>
                                    <footer>
                                        <?php if ($announcementUrl !== ''): ?>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?= e($announcementHref) ?>"><?= e($announcement['button_label'] ?: 'Abrir') ?></a>
                                        <?php endif; ?>
                                        <form method="post" action="<?= e(url('/admin/announcement/read')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="announcement_id" value="<?= e((string) $announcement['id']) ?>">
                                            <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/admin') ?>">
                                            <button class="btn btn-sm btn-primary" type="submit">Marcar como lido</button>
                                        </form>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
    <script src="<?= e(url('/public/assets/js/password-toggle.js') . '?v=' . $passwordToggleJsVersion) ?>"></script>
    <script src="<?= e(url('/public/assets/js/admin.js') . '?v=' . $adminJsVersion) ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="<?= e(url('/public/assets/js/tinymce-init.js') . '?v=' . $tinyMceJsVersion) ?>"></script>
</body>
</html>
