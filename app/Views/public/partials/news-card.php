<article class="news-card">
    <a href="<?= e(url('/noticia/' . $item['slug'])) ?>">
        <?php $publicImage = news_public_image($item); ?>
        <?php if ($publicImage): ?>
            <img src="<?= e(media_url($publicImage)) ?>" alt="<?= e($item['title']) ?>" loading="lazy" onerror="this.remove()">
        <?php endif; ?>
        <span><?= e($item['category_name'] ?? 'Geral') ?></span>
        <?php if (!empty($item['is_archive'])): ?>
            <span class="archive-badge"><i aria-hidden="true"></i>Acervo</span>
        <?php endif; ?>
        <h2><?= e($item['title']) ?></h2>
        <p><?= e($item['summary'] ?: substr(strip_tags($item['content']), 0, 140)) ?></p>
    </a>
</article>
