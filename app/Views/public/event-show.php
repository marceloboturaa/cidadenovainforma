<?php
$startsAt = !empty($event['starts_at']) ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Data a definir';
$endsAt = !empty($event['ends_at']) ? date('d/m/Y H:i', strtotime($event['ends_at'])) : null;
$startTime = !empty($event['starts_at']) ? strtotime($event['starts_at']) : null;
$endTime = !empty($event['ends_at']) ? strtotime($event['ends_at']) : null;
$isHappening = ($event['status'] ?? '') === 'aberto' && $startTime && $endTime && $startTime <= time() && $endTime >= time();
$isPast = ($event['status'] ?? '') === 'encerrado' || (($endTime ?: $startTime) && ($endTime ?: $startTime) < time());
$statusText = $isHappening ? 'Acontecendo' : ($isPast ? 'Evento realizado' : 'Próximo evento');
?>

<article class="event-show-page">
    <nav class="institution-breadcrumb" aria-label="Caminho">
        <a href="<?= e(url('/eventos')) ?>">Eventos</a>
        <span><?= e($event['title']) ?></span>
    </nav>

    <header class="event-show-hero">
        <div class="event-show-copy">
            <span class="event-status-badge"><?= e($statusText) ?></span>
            <h1><?= e($event['title']) ?></h1>
            <?php if (!empty($event['description'])): ?>
                <p><?= e(text_excerpt($event['description'], 240)) ?></p>
            <?php endif; ?>
            <div class="event-show-actions">
                <a class="public-event-more" href="<?= e($isPast ? url('/eventos/realizados') : url('/eventos/futuros')) ?>">
                    <?= $isPast ? 'Ver eventos realizados' : 'Ver eventos futuros' ?>
                </a>
                <a class="events-card-link" href="<?= e(url('/eventos')) ?>">Agenda completa</a>
            </div>
        </div>
        <div class="event-show-media">
            <?php if (!empty($event['cover_image'])): ?>
                <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" onerror="this.parentElement.classList.add('is-empty'); this.remove()">
            <?php else: ?>
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
            <?php endif; ?>
        </div>
    </header>

    <section class="event-show-layout">
        <div class="event-show-content">
            <h2>Sobre o evento</h2>
            <?php if (!empty($event['description'])): ?>
                <div class="event-detail-text">
                    <?= article_html($event['description']) ?>
                </div>
            <?php else: ?>
                <p class="event-detail-text">Mais informações serão divulgadas em breve.</p>
            <?php endif; ?>
        </div>

        <aside class="event-show-sidebar">
            <h2>Informações</h2>
            <dl>
                <div><dt>Data e horário</dt><dd><?= e($startsAt) ?></dd></div>
                <?php if ($endsAt): ?><div><dt>Encerramento</dt><dd><?= e($endsAt) ?></dd></div><?php endif; ?>
                <?php if (!empty($event['location'])): ?><div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div><?php endif; ?>
                <?php if (!empty($event['capacity'])): ?><div><dt>Vagas</dt><dd><?= e((string) $event['capacity']) ?></dd></div><?php endif; ?>
                <?php if (!empty($event['responsible_name'])): ?><div><dt>Responsável</dt><dd><?= e($event['responsible_name']) ?></dd></div><?php endif; ?>
                <div><dt>Status</dt><dd><?= e($statusText) ?></dd></div>
            </dl>
        </aside>
    </section>
</article>
