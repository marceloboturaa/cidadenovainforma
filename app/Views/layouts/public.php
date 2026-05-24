<?php $app = require dirname(__DIR__, 3) . '/config/app.php'; ?>
<?php $assets = require dirname(__DIR__, 3) . '/config/assets.php'; ?>
<?php $publicCssFiles = $assets['css']['public'] ?? ['/public/assets/css/public.css']; ?>
<?php $publicJsVersion = file_exists(dirname(__DIR__, 3) . '/public/assets/js/public-menu.js') ? filemtime(dirname(__DIR__, 3) . '/public/assets/js/public-menu.js') : time(); ?>
<?php $faviconVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/img/favicon-primary.svg'); ?>
<?php
$navigationItems = array_filter(($menuItems ?? []), function (array $item): bool {
    $label = mb_strtolower(trim($item['label'] ?? ''), 'UTF-8');
    $path = '/' . ltrim((string) ($item['url'] ?? ''), '/');

    return !in_array($path, ['/eventos', '/instituicao', '/documentos', '/certificado/validar', '/login'], true)
        && !in_array($label, ['eventos', 'evento', 'cursos', 'curso', 'certificados', 'certificado', 'verificar certificado', 'instituição', 'instituicao', 'documentos', 'documento', 'entrar', 'entrar no painel'], true);
});
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($basePath && str_starts_with($requestPath, '/' . $basePath)) {
    $requestPath = substr($requestPath, strlen('/' . $basePath)) ?: '/';
}
$secondaryPublicPaths = ['/instituicao', '/documentos'];
$faviconPath = in_array($requestPath, $secondaryPublicPaths, true)
    || str_starts_with($requestPath, '/instituicao/')
    ? '/public/assets/img/favicon-secondary.svg'
    : '/public/assets/img/favicon-primary.svg';
$currentUrl = $canonicalUrl ?? url('/');
$description = $metaDescription ?? ($app['description'] ?? 'Cidade Nova Informa traz notícias, serviços, cultura e informações de interesse público para os moradores de Cidade Nova e região.');
$socialTitle = $pageTitle ?? $app['name'];
$socialImage = $ogImage ?? url('/public/assets/img/institution-hero-community.jpg');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? $app['name']) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url($faviconPath) . '?v=' . $faviconVersion) ?>">
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl ?? ((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'))) ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?= e($app['name']) ?>">
    <meta property="og:title" content="<?= e($socialTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
    <meta property="og:url" content="<?= e($currentUrl) ?>">
    <meta property="og:image" content="<?= e($socialImage) ?>">
    <meta property="og:image:secure_url" content="<?= e(preg_replace('#^http://#i', 'https://', $socialImage)) ?>">
    <meta property="og:image:alt" content="<?= e($socialTitle) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($socialTitle) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($socialImage) ?>">
    <?php foreach ($publicCssFiles as $cssFile): ?>
        <link href="<?= e(versioned_asset_url($cssFile)) ?>" rel="stylesheet">
    <?php endforeach; ?>
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
                <a class="event-link" href="<?= e(url('/eventos')) ?>">Eventos</a>
                <a class="course-link" href="<?= e(url('/#cursos')) ?>">Cursos</a>
                <a class="certificate-link" href="<?= e(url('/certificado/validar')) ?>">Verificar certificado</a>
                <a class="institution-link" href="<?= e(url('/instituicao')) ?>">Instituição</a>
                <a class="institution-link" href="<?= e(url('/documentos')) ?>">Documentos</a>
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
                <a class="mobile-institution-link" href="<?= e(url('/eventos')) ?>">Eventos</a>
                <a class="mobile-institution-link" href="<?= e(url('/#cursos')) ?>">Cursos</a>
                <a class="mobile-certificate-link" href="<?= e(url('/certificado/validar')) ?>">Verificar certificado</a>
                <a class="mobile-institution-link" href="<?= e(url('/instituicao')) ?>">Instituição</a>
                <a class="mobile-institution-link" href="<?= e(url('/documentos')) ?>">Documentos</a>
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
    <?php if (!empty($usesMathJax)): ?>
        <script>
            window.MathJax = {
                tex: {
                    inlineMath: [['\\(', '\\)'], ['$', '$']],
                    displayMath: [['$$', '$$'], ['\\[', '\\]']],
                    processEscapes: true
                },
                options: {
                    skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
                }
            };
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>
    <?php endif; ?>
</body>
</html>
