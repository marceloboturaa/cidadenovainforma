<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $app['name']) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'Notícias de Cidade Nova e região.') ?>">
    <link rel="canonical" href="<?= e($canonicalUrl ?? ((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'))) ?>">
    <meta property="og:title" content="<?= e($pageTitle ?? $app['name']) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? 'Notícias de Cidade Nova e região.') ?>">
    <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= e((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <link href="<?= e(url('/public/assets/css/public.css')) ?>" rel="stylesheet">
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <a class="site-brand" href="<?= e(url('/')) ?>">Cidade Nova Informa</a>
            <form class="search-form" action="<?= e(url('/buscar')) ?>" method="get">
                <input name="q" value="<?= e($query ?? '') ?>" placeholder="Buscar notícias">
                <button>Buscar</button>
            </form>
            <a class="admin-link" href="<?= e(url('/login')) ?>">Painel</a>
        </div>
        <nav class="category-nav">
            <?php foreach (($menuItems ?? []) as $item): ?>
                <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : url($item['url'])) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <strong>Cidade Nova Informa</strong>
        <span>Jornalismo comunitário com qualidade e compromisso.</span>
    </footer>
</body>
</html>
