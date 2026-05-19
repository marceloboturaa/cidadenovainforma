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

    if (str_contains($text, 'biblioteca') || str_contains($text, 'leitura')) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H7a3 3 0 0 0-3 3V5.5Z"/><path d="M7 19V3"/><path d="M9.5 7H17"/><path d="M9.5 11H16"/></svg>';
    }

    if (str_contains($text, 'educa')) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 8 9-4 9 4-9 4-9-4Z"/><path d="m7 10.5v5c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5v-5"/><path d="M21 8v6"/></svg>';
    }

    if (str_contains($text, 'horta') || str_contains($text, 'ambiental')) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21V10"/><path d="M12 10c-4.5 0-7-2.5-7-7 4.5 0 7 2.5 7 7Z"/><path d="M12 14c4.5 0 7-2.5 7-7-4.5 0-7 2.5-7 7Z"/></svg>';
    }

    if (str_contains($text, 'idos')) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0"/><path d="M4 21a8 8 0 0 1 16 0"/><path d="M18 4v5"/><path d="M20.5 6.5H15.5"/></svg>';
    }

    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h12a3 3 0 0 1 3 3v11H7a3 3 0 0 1-3-3V5Z"/><path d="M8 9h7"/><path d="M8 13h7"/><path d="M19 8h1a2 2 0 0 1 2 2v6a3 3 0 0 1-3 3"/></svg>';
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
