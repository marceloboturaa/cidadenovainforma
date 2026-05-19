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
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="16"/><path d="M24 8c4.2 4 6.3 9.3 6.3 16S28.2 36 24 40"/><path d="M24 8c-4.2 4-6.3 9.3-6.3 16S19.8 36 24 40"/><path d="M9.8 18.5c4.3 2.2 9 3.3 14.2 3.3s9.9-1.1 14.2-3.3"/><path d="M9.8 29.5c4.3-2.2 9-3.3 14.2-3.3s9.9 1.1 14.2 3.3"/></svg>';
    }

    if (str_contains($text, 'biblioteca') || str_contains($text, 'leitura')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 12.5A4.5 4.5 0 0 1 14.5 8H40v29H16a6 6 0 0 0-6 6V12.5Z"/><path d="M16 37V8"/><path d="M22 16h11"/><path d="M22 23h11"/><path d="M22 30h8"/></svg>';
    }

    if (str_contains($text, 'educa')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="m5 17 19-9 19 9-19 9L5 17Z"/><path d="M13 22v10c0 4 5 7 11 7s11-3 11-7V22"/><path d="M42 17v12"/><path d="M42 32v.2"/></svg>';
    }

    if (str_contains($text, 'horta') || str_contains($text, 'ambiental')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 42V22"/><path d="M24 22c-9 0-14-5-14-14 9 0 14 5 14 14Z"/><path d="M24 29c9 0 14-5 14-14-9 0-14 5-14 14Z"/><path d="M12 42h24"/></svg>';
    }

    if (str_contains($text, 'idos')) {
        return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M18 22a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z"/><path d="M6 41c1.5-8 6-13 12-13s10.5 5 12 13"/><path d="M34 12c4 0 7 3 7 7 0 7-7 11-7 11s-7-4-7-11c0-4 3-7 7-7Z"/><path d="M31 19h6"/><path d="M34 16v6"/></svg>';
    }

    return '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M8 11h24a6 6 0 0 1 6 6v22H14a6 6 0 0 1-6-6V11Z"/><path d="M15 19h15"/><path d="M15 27h12"/><path d="M38 17h2a4 4 0 0 1 4 4v12a6 6 0 0 1-6 6"/><path d="M10 8h18"/></svg>';
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
        <div class="institution-section-head">
            <span>Nossos projetos</span>
            <h2>Frentes de atuação social e cultural</h2>
        </div>

        <div class="institution-project-grid institution-project-grid-modern">
            <?php foreach ($projects as $area): ?>
                <?php
                $projectUrl = $linkHref($area['cta_url'] ?? '') ?: url('/instituicao/' . $area['slug']);
                $projectLabel = trim((string) ($area['cta_label'] ?? '')) ?: 'Conhecer projeto';
                ?>
                <article class="institution-project-card institution-project-card-modern">
                    <a class="institution-project-media" href="<?= e($projectUrl) ?>" aria-label="<?= e($projectLabel . ': ' . $area['name']) ?>">
                        <span class="institution-project-icon">
                            <?= $projectIcon($area) ?>
                        </span>
                    </a>
                    <div>
                        <span><?= e($area['kicker']) ?></span>
                        <h3><?= e($area['name']) ?></h3>
                        <p><?= e($area['summary']) ?></p>
                        <a href="<?= e($projectUrl) ?>">
                            <?= e($projectLabel) ?>
                        </a>
                    </div>
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
