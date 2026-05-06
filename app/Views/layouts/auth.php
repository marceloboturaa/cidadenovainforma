<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($app['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('/public/assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <?= $content ?>
    </main>
</body>
</html>
