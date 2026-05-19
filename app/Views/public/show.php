<?php $usesMathJax = true; ?>

<article class="article-page">
    <header class="article-header">
        <a class="article-category" href="<?= e(url('/categoria/' . ($news['category_slug'] ?? ''))) ?>"><?= e($news['category_name'] ?? 'Geral') ?></a>
        <h1><?= e($news['title']) ?></h1>
        <?php if (!empty($news['summary'])): ?>
            <p><?= e($news['summary']) ?></p>
        <?php endif; ?>
        <div class="article-meta">
            Por <?= e($news['author_name']) ?> |
            <?= e(date('d/m/Y H:i', strtotime($news['published_at'] ?? $news['created_at']))) ?> |
            <?= e((string) ((int) $news['views'] + 1)) ?> visualizações
        </div>
    </header>

    <?php if (!empty($news['is_archive'])): ?>
        <section class="archive-notice">
            <div class="archive-head">
                <span class="archive-badge archive-badge-lg"><i aria-hidden="true"></i>Reprise</span>
                <strong>Reportagem republicada</strong>
            </div>
            <div class="archive-meta-line">
                <span>
                    Original:
                    <?php if (!empty($news['original_published_at'])): ?>
                        <strong><?= e(date('d/m/Y', strtotime($news['original_published_at']))) ?></strong>
                    <?php else: ?>
                        <strong>data não informada</strong>
                    <?php endif; ?>
                </span>
                <?php if (!empty($news['original_author'])): ?>
                    <span>Por: <strong><?= e($news['original_author']) ?></strong></span>
                <?php endif; ?>
                <?php if (!empty($news['original_source'])): ?>
                    <span>Fonte: <strong><?= e($news['original_source']) ?></strong></span>
                <?php endif; ?>
                <span>Republicada: <strong><?= e(date('d/m/Y', strtotime($news['published_at'] ?? $news['created_at']))) ?></strong></span>
            </div>
            <div class="archive-foot">
                <p><?= e($news['archive_note'] ?: 'Conteúdo preservado como registro histórico; as informações refletem o contexto da época.') ?></p>
                <?php if (!empty($news['original_url'])): ?>
                    <a href="<?= e($news['original_url']) ?>" target="_blank" rel="noopener">Original</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php $publicImage = news_public_image($news); ?>
    <?php $hasCoverImage = media_available($news['cover_image'] ?? null); ?>
    <?php if ($hasCoverImage && $publicImage): ?>
        <img class="article-cover" src="<?= e(media_url($publicImage)) ?>" alt="<?= e($news['title']) ?>" onerror="this.remove()">
    <?php endif; ?>

    <div class="article-content">
        <?= article_html($news['content']) ?>
    </div>

    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $news['title'],
            'description' => $news['summary'],
            'datePublished' => $news['published_at'],
            'dateModified' => $news['updated_at'],
            'author' => [
                '@type' => 'Person',
                'name' => $news['author_name'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Cidade Nova Informa',
            ],
            'mainEntityOfPage' => url('/noticia/' . $news['slug']),
            'image' => $publicImage ? media_url($publicImage) : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>

    <?php if ($tags): ?>
        <footer class="article-tags">
            <?php foreach ($tags as $tag): ?>
                <a href="<?= e(url('/tag/' . $tag['slug'])) ?>">#<?= e($tag['display_name'] ?? $tag['name']) ?></a>
            <?php endforeach; ?>
        </footer>
    <?php endif; ?>
</article>

<?php $related = array_filter($related, fn ($item) => (int) $item['id'] !== (int) $news['id']); ?>
<?php if ($related): ?>
    <section class="section-heading">
        <h2>Relacionadas</h2>
    </section>
    <section class="news-grid compact-grid">
        <?php foreach ($related as $item): ?>
            <?php require dirname(__DIR__) . '/public/partials/news-card.php'; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
