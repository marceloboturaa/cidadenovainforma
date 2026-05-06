<section class="listing-head">
    <p>Cidade Nova Informa</p>
    <h1><?= e($heading) ?></h1>
</section>

<section class="news-grid">
    <?php foreach ($news as $item): ?>
        <?php require dirname(__DIR__) . '/public/partials/news-card.php'; ?>
    <?php endforeach; ?>
    <?php if (!$news): ?>
        <article class="empty-public">
            <h2>Nenhuma notícia encontrada</h2>
            <p>Tente outra busca ou acompanhe as próximas publicações.</p>
        </article>
    <?php endif; ?>
</section>
