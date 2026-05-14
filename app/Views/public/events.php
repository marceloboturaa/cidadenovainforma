<section class="events-page-hero">
    <span>Agenda da comunidade</span>
    <h1>Eventos e atividades</h1>
    <p>Confira a programação aberta da biblioteca e das ações comunitárias do Cidade Nova Informa.</p>
</section>

<?php if (!empty($events)): ?>
    <section class="events-page-grid">
        <?php foreach ($events as $event): ?>
            <article class="event-list-card">
                <?php if (!empty($event['cover_image'])): ?>
                    <a href="<?= e(url('/evento/' . $event['id'])) ?>" class="event-list-media">
                        <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" loading="lazy" onerror="this.remove()">
                    </a>
                <?php endif; ?>
                <div class="event-list-body">
                    <span class="public-event-date"><?= e($event['starts_at'] ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Atividade aberta') ?></span>
                    <h2><a href="<?= e(url('/evento/' . $event['id'])) ?>"><?= e($event['title']) ?></a></h2>
                    <p><?= e(text_excerpt($event['description'] ?? '', 190)) ?></p>
                    <dl>
                        <?php if (!empty($event['location'])): ?>
                            <div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($event['capacity'])): ?>
                            <div><dt>Vagas</dt><dd><?= e((string) $event['capacity']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($event['ends_at'])): ?>
                            <div><dt>Encerramento</dt><dd><?= e(date('d/m/Y H:i', strtotime($event['ends_at']))) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <a class="public-event-more" href="<?= e(url('/evento/' . $event['id'])) ?>">Ver mais informações</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <div class="empty-state">Nenhum evento aberto no momento.</div>
<?php endif; ?>
