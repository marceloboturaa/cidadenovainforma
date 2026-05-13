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
                    <?php $heroImage = news_public_image($hero); ?>
                    <?php if ($heroImage): ?>
                        <img src="<?= e(media_url($heroImage)) ?>" alt="<?= e($hero['title']) ?>" onerror="this.remove()">
                    <?php endif; ?>
                    <span><?= e($hero['category_name'] ?? 'Destaque') ?></span>
                    <h1><?= e($hero['title']) ?></h1>
                    <p><?= e(text_excerpt($hero['summary'] ?: $hero['content'], 170)) ?></p>
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

<section class="news-grid" data-latest-grid data-page-size="9">
    <?php foreach ($latest as $index => $item): ?>
        <?php $hiddenClass = $index >= 9 ? ' is-hidden' : ''; ?>
        <?php require dirname(__DIR__) . '/public/partials/news-card.php'; ?>
    <?php endforeach; ?>
</section>

<?php if (count($latest) > 9): ?>
    <div class="news-load-sentinel" data-latest-sentinel aria-hidden="true"></div>
<?php endif; ?>

<?php if (!empty($libraryEvents)): ?>
    <section class="section-heading public-events-heading">
        <h2>Eventos e atividades da biblioteca</h2>
    </section>

    <section class="public-events-grid">
        <?php foreach ($libraryEvents as $event): ?>
            <article class="public-event-card">
                <?php if (!empty($event['cover_image'])): ?>
                    <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" loading="lazy" onerror="this.remove()">
                <?php endif; ?>
                <div>
                    <span><?= e($event['starts_at'] ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Atividade aberta') ?></span>
                    <h3><?= e($event['title']) ?></h3>
                    <p><?= e(text_excerpt($event['description'] ?? '', 140)) ?></p>
                    <dl>
                        <?php if (!empty($event['location'])): ?>
                            <div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($event['capacity'])): ?>
                            <div><dt>Vagas</dt><dd><?= e((string) $event['capacity']) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
(() => {
    const grid = document.querySelector('[data-latest-grid]');
    const sentinel = document.querySelector('[data-latest-sentinel]');
    if (!grid || !sentinel) {
        return;
    }

    const pageSize = Number(grid.dataset.pageSize || 9);
    let observer = null;
    const revealNext = () => {
        const hidden = Array.from(grid.querySelectorAll('.news-card.is-hidden')).slice(0, pageSize);
        hidden.forEach((card) => card.classList.remove('is-hidden'));
        if (!grid.querySelector('.news-card.is-hidden')) {
            sentinel.remove();
            if (observer) {
                observer.disconnect();
            }
        }
    };

    if (!('IntersectionObserver' in window)) {
        while (grid.querySelector('.news-card.is-hidden')) {
            revealNext();
        }
        return;
    }

    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            revealNext();
        }
    }, { rootMargin: '320px 0px' });

    observer.observe(sentinel);
})();
</script>
