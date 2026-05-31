<?php
$upcomingEvents = $upcomingEvents ?? [];
$pastEvents = $pastEvents ?? [];
$mode = $mode ?? 'all';
$totalEvents = count($upcomingEvents) + count($pastEvents);
$featuredEvent = $mode !== 'past' ? ($upcomingEvents[0] ?? null) : null;
$formatDate = fn (?string $value): string => $value ? date('d/m/Y H:i', strtotime($value)) : 'Data a definir';
$slotsText = function (array $event): string {
    $capacity = (int) ($event['capacity'] ?? 0);
    if ($capacity <= 0) {
        return '';
    }
    $occupied = max(0, (int) ($event['participant_count'] ?? 0));
    $remaining = max(0, $capacity - $occupied);
    return $remaining . ' de ' . $capacity . ' vaga(s)';
};
$eventStatus = function (array $event): string {
    $start = !empty($event['starts_at']) ? strtotime($event['starts_at']) : null;
    $end = !empty($event['ends_at']) ? strtotime($event['ends_at']) : null;
    if (($event['status'] ?? '') === 'aberto' && $start && $end && $start <= time() && $end >= time()) {
        return 'Acontecendo';
    }
    if (($event['status'] ?? '') === 'encerrado') {
        return 'Realizado';
    }
    if (($end ?: $start) && ($end ?: $start) < time()) {
        return 'Realizado';
    }
    return 'Próximo evento';
};
?>

<section class="events-hub-hero">
    <div class="events-hub-copy">
        <span>Agenda da comunidade e região</span>
        <h1><?= $mode === 'past' ? 'Eventos realizados' : ($mode === 'upcoming' ? 'Eventos futuros' : 'Eventos') ?></h1>
        <p>Programação, atividades, oficinas e encontros do Cidade Nova Informa em uma página organizada para acompanhar o que vem pela frente e o que já aconteceu.</p>
        <nav class="events-hub-tabs" aria-label="Seções de eventos">
            <a class="<?= $mode === 'all' ? 'is-active' : '' ?>" href="<?= e(url('/eventos')) ?>">Todos</a>
            <a class="<?= $mode === 'upcoming' ? 'is-active' : '' ?>" href="<?= e(url('/eventos/futuros')) ?>">Futuros</a>
            <a class="<?= $mode === 'past' ? 'is-active' : '' ?>" href="<?= e(url('/eventos/realizados')) ?>">Realizados</a>
        </nav>
    </div>

    <div class="events-hub-summary">
        <strong><?= e((string) $totalEvents) ?></strong>
        <span>evento(s) nesta página</span>
        <small><?= e((string) count($upcomingEvents)) ?> futuro(s) · <?= e((string) count($pastEvents)) ?> realizado(s)</small>
    </div>
</section>

<?php if ($featuredEvent): ?>
    <section class="events-featured">
        <div class="events-featured-media">
            <?php if (!empty($featuredEvent['cover_image'])): ?>
                <img src="<?= e(media_url($featuredEvent['cover_image'])) ?>" alt="<?= e($featuredEvent['title']) ?>" loading="lazy" onerror="this.parentElement.classList.add('is-empty'); this.remove()">
            <?php else: ?>
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
            <?php endif; ?>
        </div>
        <div class="events-featured-body">
            <span class="event-status-badge"><?= e($eventStatus($featuredEvent)) ?></span>
            <h2><a href="<?= e(url('/evento/' . $featuredEvent['id'])) ?>"><?= e($featuredEvent['title']) ?></a></h2>
            <p><?= e(text_excerpt($featuredEvent['description'] ?? '', 230)) ?></p>
            <dl class="events-info-strip">
                <div><dt>Quando</dt><dd><?= e($formatDate($featuredEvent['starts_at'] ?? null)) ?></dd></div>
                <?php if (!empty($featuredEvent['public_show_location']) && !empty($featuredEvent['location'])): ?>
                    <div><dt>Local</dt><dd><?= e($featuredEvent['location']) ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($featuredEvent['public_show_capacity']) && !empty($featuredEvent['capacity'])): ?>
                    <div><dt>Vagas</dt><dd><?= e($slotsText($featuredEvent)) ?></dd></div>
                <?php endif; ?>
            </dl>
            <a class="public-event-more" href="<?= e(url('/evento/' . $featuredEvent['id'])) ?>">Abrir página do evento</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($mode !== 'past'): ?>
    <section class="events-section-block">
        <div class="events-section-heading">
            <div>
                <span>Programação</span>
                <h2>Eventos futuros</h2>
            </div>
            <a href="<?= e(url('/eventos/futuros')) ?>">Ver somente futuros</a>
        </div>
        <?php if ($upcomingEvents): ?>
            <div class="events-modern-grid">
                <?php foreach ($upcomingEvents as $event): ?>
                    <article class="events-modern-card">
                        <?php if (!empty($event['cover_image'])): ?>
                            <a class="events-modern-media" href="<?= e(url('/evento/' . $event['id'])) ?>">
                                <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" loading="lazy" onerror="this.remove()">
                            </a>
                        <?php endif; ?>
                        <div class="events-modern-body">
                            <span class="event-status-badge">Próximo evento</span>
                            <h3><a href="<?= e(url('/evento/' . $event['id'])) ?>"><?= e($event['title']) ?></a></h3>
                            <p><?= e(text_excerpt($event['description'] ?? '', 150)) ?></p>
                            <dl>
                                <div><dt>Quando</dt><dd><?= e($formatDate($event['starts_at'] ?? null)) ?></dd></div>
                                <?php if (!empty($event['public_show_location']) && !empty($event['location'])): ?><div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div><?php endif; ?>
                                <?php if (!empty($event['public_show_capacity']) && !empty($event['capacity'])): ?><div><dt>Vagas</dt><dd><?= e($slotsText($event)) ?></dd></div><?php endif; ?>
                            </dl>
                            <a class="events-card-link" href="<?= e(url('/evento/' . $event['id'])) ?>">Detalhes</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">Nenhum evento futuro publicado no momento.</div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($mode !== 'upcoming'): ?>
    <section class="events-section-block">
        <div class="events-section-heading">
            <div>
                <span>Histórico</span>
                <h2>Eventos já realizados</h2>
            </div>
            <?php if ($mode !== 'past'): ?>
                <a href="<?= e(url('/eventos/realizados')) ?>">Ver somente realizados</a>
            <?php endif; ?>
        </div>
        <?php if ($pastEvents): ?>
            <div class="events-history-list">
                <?php foreach ($pastEvents as $event): ?>
                    <article class="events-history-item">
                        <a class="events-history-media" href="<?= e(url('/evento/' . $event['id'])) ?>">
                            <?php if (!empty($event['cover_image'])): ?>
                                <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" loading="lazy" onerror="this.parentElement.classList.add('is-empty'); this.remove()">
                            <?php else: ?>
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                            <?php endif; ?>
                        </a>
                        <div>
                            <span><?= e($formatDate($event['starts_at'] ?? null)) ?></span>
                            <h3><a href="<?= e(url('/evento/' . $event['id'])) ?>"><?= e($event['title']) ?></a></h3>
                            <p><?= e(text_excerpt($event['description'] ?? '', 150)) ?></p>
                        </div>
                        <dl>
                            <?php if (!empty($event['public_show_location']) && !empty($event['location'])): ?><div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div><?php endif; ?>
                            <?php if (!empty($event['public_show_capacity']) && !empty($event['capacity'])): ?><div><dt>Vagas</dt><dd><?= e($slotsText($event)) ?></dd></div><?php endif; ?>
                        </dl>
                        <a class="events-card-link" href="<?= e(url('/evento/' . $event['id'])) ?>">Ver registro</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">Nenhum evento realizado publicado ainda.</div>
        <?php endif; ?>
    </section>
<?php endif; ?>
