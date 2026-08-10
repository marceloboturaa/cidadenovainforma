<?php
$heroPool = $featured ?: $latest;
$hero = $heroPool ? $heroPool[array_rand($heroPool)] : null;
$eventSlotsText = function (array $event): string {
    $capacity = (int) ($event['capacity'] ?? 0);
    if ($capacity <= 0) {
        return '';
    }
    $occupied = max(0, (int) ($event['participant_count'] ?? 0));
    return max(0, $capacity - $occupied) . ' de ' . $capacity . ' vaga(s)';
};
$homeNotice = $homeNotice ?? [];
$homeNoticeEnabled = ($homeNotice['enabled'] ?? '0') === '1' && (trim((string) ($homeNotice['title'] ?? '')) !== '' || trim((string) ($homeNotice['text'] ?? '')) !== '');
$homeNoticeUrl = trim((string) ($homeNotice['url'] ?? ''));
$homeNoticeLabel = trim((string) ($homeNotice['label'] ?? '')) ?: 'Saiba mais';
$homeNoticeHref = preg_match('/^https?:\/\//i', $homeNoticeUrl) ? $homeNoticeUrl : url($homeNoticeUrl);
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

<?php if ($homeNoticeEnabled): ?>
    <aside class="registration-open-note" id="aviso-principal" aria-label="Aviso principal">
        <div class="registration-open-copy">
            <span>Comunicado</span>
            <?php if (trim((string) ($homeNotice['title'] ?? '')) !== ''): ?>
                <strong><?= e($homeNotice['title']) ?></strong>
            <?php endif; ?>
            <?php if (trim((string) ($homeNotice['text'] ?? '')) !== ''): ?>
                <p><?= e($homeNotice['text']) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($homeNoticeUrl !== ''): ?>
            <a href="<?= e($homeNoticeHref) ?>"><?= e($homeNoticeLabel) ?></a>
        <?php endif; ?>
    </aside>
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

<section class="news-grid" data-latest-grid data-page-size="6">
    <?php foreach ($latest as $index => $item): ?>
        <?php $hiddenClass = $index >= 6 ? ' is-hidden' : ''; ?>
        <?php require dirname(__DIR__) . '/public/partials/news-card.php'; ?>
    <?php endforeach; ?>
</section>

<?php if (count($latest) > 6): ?>
    <div class="news-load-actions">
        <button class="news-load-button" type="button" data-latest-load>
            Carregar mais notícias
        </button>
    </div>
<?php endif; ?>

<?php if (!empty($publicCourses)): ?>
    <section class="section-heading public-courses-heading" id="cursos">
        <span>Formação aberta</span>
        <h2>Cursos públicos</h2>
    </section>

    <section class="public-courses-grid">
        <?php foreach ($publicCourses as $course): ?>
            <?php
            $courseHref = !empty($course['public_access_enabled'])
                ? url('/curso/' . $course['id'])
                : url('/admin/education/course?id=' . $course['id']);
            $courseActionLabel = !empty($course['public_access_enabled']) ? 'Acessar sem login' : 'Entrar para acessar';
            ?>
            <article class="public-course-card">
                <?php if (!empty($course['cover_image'])): ?>
                    <img src="<?= e(media_url($course['cover_image'])) ?>" alt="<?= e($course['title']) ?>" loading="lazy" onerror="this.remove()">
                <?php endif; ?>
                <div class="public-course-body">
                    <span><?= e((string) ($course['lesson_count'] ?? 0)) ?> aula(s)</span>
                    <h3><?= e($course['title']) ?></h3>
                    <p><?= e(text_excerpt($course['summary'] ?? '', 150)) ?></p>
                    <?php if (!empty($course['teacher_name'])): ?>
                        <small>Professor: <?= e($course['teacher_name']) ?></small>
                    <?php endif; ?>
                    <a class="public-course-more" href="<?= e($courseHref) ?>"><?= e($courseActionLabel) ?></a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if (!empty($libraryEvents)): ?>
    <section class="section-heading public-events-heading" id="eventos">
        <span>Agenda da comunidade e região</span>
        <h2>Eventos e atividades da biblioteca</h2>
    </section>

    <section class="public-events-grid">
        <?php foreach ($libraryEvents as $event): ?>
            <?php $eventImage = event_public_image($event); ?>
            <article class="public-event-card">
                <a class="public-event-media<?= $eventImage ? '' : ' is-empty' ?>" href="<?= e(url('/evento/' . $event['id'])) ?>">
                    <?php if ($eventImage): ?>
                        <img src="<?= e(media_url($eventImage)) ?>" alt="<?= e($event['title']) ?>" loading="lazy" onerror="this.parentElement.classList.add('is-empty'); this.remove()">
                    <?php endif; ?>
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                </a>
                <div class="public-event-body">
                    <span class="public-event-date"><?= e($event['starts_at'] ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Atividade aberta') ?></span>
                    <h3><?= e($event['title']) ?></h3>
                    <p><?= e(text_excerpt($event['description'] ?? '', 140)) ?></p>
                    <dl>
                        <?php if (!empty($event['public_show_location']) && !empty($event['location'])): ?>
                            <div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($event['public_show_capacity']) && !empty($event['capacity'])): ?>
                            <div><dt>Vagas</dt><dd><?= e($eventSlotsText($event)) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <a class="public-event-more" href="<?= e(url('/evento/' . $event['id'])) ?>">Ver detalhes</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<script>
(() => {
    const grid = document.querySelector('[data-latest-grid]');
    const loadButton = document.querySelector('[data-latest-load]');
    if (!grid || !loadButton) {
        return;
    }

    const pageSize = Number(grid.dataset.pageSize || 6);
    const revealNext = () => {
        const hidden = Array.from(grid.querySelectorAll('.news-card.is-hidden')).slice(0, pageSize);
        hidden.forEach((card) => card.classList.remove('is-hidden'));
        if (!grid.querySelector('.news-card.is-hidden')) {
            loadButton.closest('.news-load-actions')?.remove();
        }
    };

    loadButton.addEventListener('click', revealNext);
})();
</script>
