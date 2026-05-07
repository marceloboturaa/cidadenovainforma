<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $publicCssVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/css/public.css'); ?>
<?php $publicJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/public-menu.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/public-menu.js') : time(); ?>
<?php
$navigationItems = array_filter(($menuItems ?? []), function (array $item): bool {
    $label = mb_strtolower(trim($item['label'] ?? ''), 'UTF-8');
    $path = '/' . ltrim((string) ($item['url'] ?? ''), '/');

    return !in_array($path, ['/instituicao', '/login'], true)
        && !in_array($label, ['instituição', 'instituicao', 'entrar', 'entrar no painel'], true);
});
?>
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
    <link href="<?= e(url('/public/assets/css/public.css') . '?v=' . $publicCssVersion) ?>" rel="stylesheet">
</head>
<body>
    <header class="site-header">
        <div class="header-top">
            <a class="site-brand" href="<?= e(url('/')) ?>">Cidade Nova Informa</a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu-panel">
                <span></span>
                <span></span>
                <span></span>
                <strong>Menu</strong>
            </button>
            <div class="header-actions">
                <a class="institution-link" href="<?= e(url('/instituicao')) ?>">Instituição</a>
                <a class="admin-link" href="<?= e(url('/login')) ?>">Entrar</a>
            </div>
        </div>
        <div class="nav-shell" id="site-menu-panel">
            <nav class="category-nav" id="site-menu">
                <a href="<?= e(url('/')) ?>">Início</a>
                <?php foreach ($navigationItems as $item): ?>
                    <a href="<?= e(str_starts_with($item['url'], 'http') ? $item['url'] : url($item['url'])) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <form class="search-form" action="<?= e(url('/buscar')) ?>" method="get">
                <input name="q" value="<?= e($query ?? '') ?>" placeholder="Buscar notícias">
                <button aria-label="Buscar notícias">
                    <span class="search-icon" aria-hidden="true"></span>
                    <strong>Buscar</strong>
                </button>
            </form>
            <nav class="mobile-actions" aria-label="Ações rápidas">
                <a class="mobile-institution-link" href="<?= e(url('/instituicao')) ?>">Instituição</a>
                <a class="mobile-login-link" href="<?= e(url('/login')) ?>">Entrar no painel</a>
            </nav>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <strong>Cidade Nova Informa</strong>
        <span>Jornalismo comunitário com qualidade e compromisso.</span>
    </footer>
    <script src="<?= e(url('/public/assets/js/public-menu.js') . '?v=' . $publicJsVersion) ?>"></script>
</body>
</html>
