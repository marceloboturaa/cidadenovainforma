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
        <aside class="auth-intro">
            <a class="auth-logo" href="<?= e(url('/')) ?>">
                <img src="<?= e(url('/public/assets/img/logo-cidade-nova-informa.svg')) ?>" alt="<?= e($app['name']) ?>">
            </a>
            <div>
                <span>Painel editorial</span>
                <h1>Cidade Nova Informa</h1>
                <p>Gestão de notícias, documentos e conteúdos institucionais em um ambiente restrito para a equipe.</p>
            </div>
        </aside>
        <?= $content ?>
    </main>
    <script src="<?= e(url('/public/assets/js/password-toggle.js')) ?>"></script>
</body>
</html>
