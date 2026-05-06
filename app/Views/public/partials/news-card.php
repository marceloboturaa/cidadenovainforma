<article class="news-card">
    <a href="<?= e(url('/noticia/' . $item['slug'])) ?>">
        <?php if (!empty($item['cover_image'])): ?>
            <img src="<?= e(url($item['cover_image'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
        <?php else: ?>
            <div class="image-placeholder"><?= e($item['category_name'] ?? 'Notícia') ?></div>
        <?php endif; ?>
        <span><?= e($item['category_name'] ?? 'Geral') ?></span>
        <?php if (!empty($item['is_archive'])): ?>
            <span>Acervo</span>
        <?php endif; ?>
        <h2><?= e($item['title']) ?></h2>
        <p><?= e($item['summary'] ?: substr(strip_tags($item['content']), 0, 140)) ?></p>
    </a>
</article>
