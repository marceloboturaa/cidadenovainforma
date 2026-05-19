<?php
$landing = $landing ?? \App\Models\InstitutionLanding::get();
$projects = $projects ?? [];
$areas = $areas ?? [];
$hero = $landing['hero'];
$about = $landing['about'];
$gallery = $landing['gallery'];
$impact = $landing['impact'];
$support = $landing['support'];
$linkHref = static function (string $value): string {
    $value = \App\Models\InstitutionLanding::linkUrl($value);

    if ($value !== '' && str_starts_with($value, '/')) {
        return url($value);
    }

    return $value;
};
$isExternal = static fn (string $value): bool => (bool) preg_match('#^https?://#i', $value);
$heroImage = media_url($hero['image']);
$projectIcon = static function (array $area): string {
    $text = mb_strtolower(($area['slug'] ?? '') . ' ' . ($area['name'] ?? '') . ' ' . ($area['kicker'] ?? ''), 'UTF-8');

    if (str_contains($text, 'esporte')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="16"/><path d="m24 14 7 5-3 8h-8l-3-8 7-5Z"/><path d="M20 27 15 34"/><path d="m28 27 5 7"/><path d="m17 19-7 2"/><path d="m31 19 7 2"/></svg>';
    }

    if (str_contains($text, 'biblioteca') || str_contains($text, 'leitura')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 12h13a5 5 0 0 1 5 5v22H15a5 5 0 0 0-5 5V12Z"/><path d="M38 12H28a5 5 0 0 0-5 5v22h10a5 5 0 0 1 5 5V12Z"/><path d="M16 20h6"/><path d="M30 20h3"/><path d="M16 27h6"/><path d="M30 27h3"/></svg>';
    }

    if (str_contains($text, 'educa')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="m7 18 17-8 17 8-17 8-17-8Z"/><path d="M15 24v7c0 4 4 7 9 7s9-3 9-7v-7"/><path d="M39 19v10"/></svg>';
    }

    if (str_contains($text, 'horta') || str_contains($text, 'ambiental')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 41V22"/><path d="M24 23c-8 0-13-5-13-13 8 0 13 5 13 13Z"/><path d="M24 29c8 0 13-5 13-13-8 0-13 5-13 13Z"/><path d="M15 41h18"/><path d="M18 35h12"/></svg>';
    }

    if (str_contains($text, 'idos')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M19 22a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z"/><path d="M7 41c1.4-8 6-13 12-13s10.6 5 12 13"/><path d="M31 18a5 5 0 1 0 0-10"/><path d="M31 29c5 1 8 5 9 12"/></svg>';
    }

    return '<svg viewBox="0 0 48 48" aria-hidden="true"><rect x="17" y="8" width="14" height="22" rx="7"/><path d="M24 30v7"/><path d="M14 22v2a10 10 0 0 0 20 0v-2"/><path d="M18 40h12"/><path d="M21 15h6"/></svg>';
};
?>

<article class="institution-page institution-modern institution-social">
    <header class="institution-landing-hero" style="--institution-hero-image: url('<?= e($heroImage) ?>');">
        <div class="institution-landing-hero-copy">
            <span><?= e($hero['eyebrow']) ?></span>
            <h1><?= e($hero['title']) ?></h1>
            <p><?= e($hero['subtitle']) ?></p>
            <?php $heroButtonUrl = $linkHref($hero['button_url']); ?>
            <?php if ($heroButtonUrl !== ''): ?>
                <a class="institution-primary-action" href="<?= e($heroButtonUrl) ?>">
                    <?= e($hero['button_label']) ?>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <section class="institution-about" id="quem-somos">
        <div class="institution-section-head">
            <span><?= e($about['eyebrow']) ?></span>
            <h2><?= e($about['title']) ?></h2>
        </div>
        <div class="institution-rich-text">
            <?= article_html($about['body']) ?>
        </div>
    </section>

    <section class="institution-projects institution-landing-section" id="projetos">
        <div class="institution-projects-showcase-head">
            <div class="institution-projects-showcase-copy">
                <span>Nossos projetos</span>
                <h2>Frentes de atuação social e cultural</h2>
                <i aria-hidden="true"></i>
                <p>Atuamos em diferentes áreas para fortalecer vínculos, promover cidadania e transformar realidades no território.</p>
            </div>
        </div>

        <div class="institution-project-grid institution-project-grid-modern">
            <?php foreach ($projects as $area): ?>
                <?php
                $projectImage = media_url($area['cover_image'] ?? $hero['image']);
                $projectUrl = $linkHref($area['cta_url'] ?? '') ?: url('/instituicao/' . $area['slug']);
                $projectLabel = trim((string) ($area['cta_label'] ?? '')) ?: 'Conhecer projeto';
                ?>
                <article class="institution-project-card institution-project-card-modern">
                    <div class="institution-project-card-copy">
                        <span class="institution-project-icon">
                            <?= $projectIcon($area) ?>
                        </span>
                        <span><?= e($area['kicker']) ?></span>
                        <h3><?= e($area['name']) ?></h3>
                        <p><?= e($area['summary']) ?></p>
                        <a href="<?= e($projectUrl) ?>">
                            <?= e($projectLabel) ?>
                        </a>
                    </div>
                    <a class="institution-project-media" href="<?= e($projectUrl) ?>" aria-label="<?= e($projectLabel . ': ' . $area['name']) ?>">
                        <img src="<?= e($projectImage) ?>" alt="" loading="lazy">
                    </a>
                </article>
            <?php endforeach; ?>
            <?php if (empty($projects)): ?>
                <article class="empty-public area-empty-news">
                    <h2>Projetos em atualização</h2>
                    <p>Os projetos institucionais aparecerão aqui assim que forem habilitados no painel.</p>
                </article>
            <?php endif; ?>
        </div>

    </section>
    <section class="institution-gallery-showcase institution-landing-section" id="galeria">
        <div class="institution-section-head">
            <span><?= e($gallery['eyebrow']) ?></span>
            <h2><?= e($gallery['title']) ?></h2>
            <p><?= e($gallery['intro']) ?></p>
        </div>

        <div class="institution-media-grid">
            <?php foreach ($gallery['items'] as $item): ?>
                <?php
                $itemUrl = $linkHref($item['url'] ?? '');
                $tagName = $itemUrl !== '' ? 'a' : 'div';
                $cover = media_url($item['cover'] ?: $hero['image']);
                ?>
                <<?= $tagName ?> class="institution-media-card" <?= $itemUrl !== '' ? 'href="' . e($itemUrl) . '"' : '' ?>>
                    <img src="<?= e($cover) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                    <div>
                        <span><?= e($item['type']) ?></span>
                        <h3><?= e($item['title']) ?></h3>
                        <?php if (!empty($item['description'])): ?>
                            <p><?= e($item['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </<?= $tagName ?>>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="institution-impact institution-landing-section" id="impacto">
        <div class="institution-section-head">
            <span><?= e($impact['eyebrow']) ?></span>
            <h2><?= e($impact['title']) ?></h2>
        </div>
        <div class="institution-impact-grid">
            <?php foreach ($impact['stats'] as $stat): ?>
                <article>
                    <strong><?= e($stat['value']) ?></strong>
                    <span><?= e($stat['label']) ?></span>
                    <?php if (!empty($stat['description'])): ?>
                        <p><?= e($stat['description']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="institution-support institution-landing-section" id="contato">
        <div class="institution-section-head">
            <span><?= e($support['eyebrow']) ?></span>
            <h2><?= e($support['title']) ?></h2>
            <p><?= e($support['body']) ?></p>
        </div>

        <div class="institution-support-grid">
            <?php foreach ($support['items'] as $item): ?>
                <?php $supportUrl = $linkHref($item['url'] ?? ''); ?>
                <article>
                    <h3><?= e($item['title']) ?></h3>
                    <p><?= e($item['description']) ?></p>
                    <?php if ($supportUrl !== ''): ?>
                        <a href="<?= e($supportUrl) ?>">
                            <?= e($item['button_label']) ?>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</article>

