<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $assets = require dirname(__DIR__, 3) . '/config/assets.php'; ?>
<?php $adminCssFiles = $assets['css']['admin'] ?? ['/public/assets/css/admin.css']; ?>
<?php $faviconVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/img/favicon-secondary.svg'); ?>
<?php $brandAssetVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/img/logo-cidade-nova-informa.svg'); ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($app['name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/public/assets/img/favicon-secondary.svg') . '?v=' . $faviconVersion) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php foreach ($adminCssFiles as $cssFile): ?>
        <link href="<?= e(versioned_asset_url($cssFile)) ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <aside class="auth-intro">
            <a class="auth-modern-brand" href="<?= e(url('/')) ?>" aria-label="<?= e($app['name']) ?>">
                <img src="<?= e(url('/public/assets/img/logo-cidade-nova-informa.svg') . '?v=' . $brandAssetVersion) ?>" alt="<?= e($app['name']) ?>">
            </a>
            <div>
                <span>Painel editorial</span>
                <p>Gestão de notícias, documentos e conteúdos institucionais em um ambiente restrito para a equipe.</p>
            </div>
        </aside>
        <?= $content ?>
    </main>
    <script src="<?= e(url('/public/assets/js/password-toggle.js')) ?>"></script>
</body>
</html>
