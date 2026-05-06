<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $user = current_user(); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel - <?= e($app['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('/public/assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <a class="brand" href="<?= e(url('/admin')) ?>">Cidade Nova Informa</a>
            <nav>
                <a href="<?= e(url('/admin')) ?>">Dashboard</a>
                <?php if (\App\Core\Auth::can('users.manage')): ?>
                    <a href="<?= e(url('/admin/users')) ?>">Usuários</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('news.create') || \App\Core\Auth::can('news.manage') || \App\Core\Auth::can('news.approve')): ?>
                    <a href="<?= e(url('/admin/news')) ?>">Notícias</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('categories.manage')): ?>
                    <a href="<?= e(url('/admin/categories')) ?>">Categorias</a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('tags.manage')): ?>
                    <a href="<?= e(url('/admin/tags')) ?>">Tags</a>
                <?php endif; ?>
                <?php if (($user['role_slug'] ?? '') === 'master'): ?>
                    <a href="<?= e(url('/admin/menu')) ?>">Menu</a>
                    <a href="<?= e(url('/admin/backups')) ?>">Backups</a>
                <?php endif; ?>
            </nav>
        </aside>
        <div class="main-panel">
            <header class="topbar">
                <div>
                    <strong><?= e($user['name'] ?? 'Usuário') ?></strong>
                    <span><?= e($user['role_name'] ?? '') ?></span>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-light btn-sm">Sair</button>
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
</body>
</html>
