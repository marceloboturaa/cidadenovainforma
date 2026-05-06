<?php
$heroPool = $featured ?: $latest;
$hero = $heroPool ? $heroPool[array_rand($heroPool)] : null;
?>

<?php if ($urgent): ?>
    <section class="breaking-strip">
        <strong>Urgente</strong>
        <div>
            <?php foreach ($urgent as $item): ?>
                <a href="<?= e(url('/noticia/' . $item['slug'])) ?>"><?= e($item['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="home-grid">
    <div class="lead-area">
        <?php if ($hero): ?>
            <article class="lead-story">
                <a href="<?= e(url('/noticia/' . $hero['slug'])) ?>">
                    <?php if (!empty($hero['cover_image'])): ?>
                        <img src="<?= e(url($hero['cover_image'])) ?>" alt="<?= e($hero['title']) ?>">
                    <?php endif; ?>
                    <span><?= e($hero['category_name'] ?? 'Destaque') ?></span>
                    <h1><?= e($hero['title']) ?></h1>
                    <p><?= e($hero['summary'] ?: substr(strip_tags($hero['content']), 0, 170)) ?></p>
                </a>
            </article>
        <?php else: ?>
            <article class="empty-public">
                <h1>Cidade Nova Informa</h1>
                <p>As notícias publicadas pelo painel administrativo aparecerão aqui.</p>
            </article>
        <?php endif; ?>
    </div>

    <aside class="side-list">
        <h2>Mais lidas</h2>
        <?php foreach ($popular as $item): ?>
            <a href="<?= e(url('/noticia/' . $item['slug'])) ?>">
                <span><?= e($item['category_name'] ?? 'Geral') ?></span>
                <?= e($item['title']) ?>
            </a>
        <?php endforeach; ?>
        <?php if (!$popular): ?>
            <p>Nenhuma notícia publicada ainda.</p>
        <?php endif; ?>
    </aside>
</section>

<section class="section-heading">
    <h2>Últimas notícias</h2>
</section>

<section class="news-grid">
    <?php foreach ($latest as $item): ?>
        <?php require dirname(__DIR__) . '/public/partials/news-card.php'; ?>
    <?php endforeach; ?>
</section>
