<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $assets = require dirname(__DIR__, 3) . '/config/assets.php'; ?>
<?php $adminCssFiles = $assets['css']['admin'] ?? ['/public/assets/css/admin.css']; ?>
<?php $adminJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/admin.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/admin.js') : time(); ?>
<?php $faviconVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/img/favicon-education.svg'); ?>
<?php $isPublicFreeCourseLayout = false; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e($app['name']) ?> - Curso</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/public/assets/img/favicon-education.svg') . '?v=' . $faviconVersion) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php foreach ($adminCssFiles as $cssFile): ?>
        <link href="<?= e(versioned_asset_url($cssFile)) ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body class="education-public-page <?= $isPublicFreeCourseLayout ? 'is-free-course-layout' : '' ?>">
    <header class="education-public-topbar">
        <a class="education-public-brand" href="<?= e(url('/')) ?>">
            <?php if ($isPublicFreeCourseLayout): ?>
                <img src="<?= e(url('/public/assets/img/logo-cidade-nova-informa.svg')) ?>" alt="" aria-hidden="true">
            <?php endif; ?>
            <span><?= e($app['name']) ?></span>
        </a>
        <nav>
            <?php if ($isPublicFreeCourseLayout): ?>
                <a href="<?= e(url('/')) ?>"><i class="bi bi-house-door" aria-hidden="true"></i>Início</a>
                <a href="<?= e(url('/admin/education')) ?>"><i class="bi bi-book" aria-hidden="true"></i>Meus cursos</a>
            <?php endif; ?>
            <a href="<?= e(url('/login')) ?>">Entrar</a>
            <a class="btn btn-sm btn-outline-light" href="<?= e(url('/register')) ?>">Criar cadastro</a>
        </nav>
    </header>
    <main class="content education-public-content">
        <?php if ($message = flash('success')): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($message = flash('error')): ?>
            <div class="alert alert-danger"><?= e($message) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
    <script src="<?= e(url('/public/assets/js/admin.js') . '?v=' . $adminJsVersion) ?>"></script>
</body>
</html>
