<article class="event-detail">
    <nav class="institution-breadcrumb" aria-label="Caminho">
        <a href="<?= e(url('/eventos')) ?>">Eventos</a>
        <span><?= e($event['title']) ?></span>
    </nav>

    <header class="event-detail-hero">
        <div>
            <span class="public-event-date"><?= e($event['starts_at'] ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Atividade aberta') ?></span>
            <h1><?= e($event['title']) ?></h1>
            <?php if (!empty($event['description'])): ?>
                <p><?= e(text_excerpt($event['description'], 220)) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($event['cover_image'])): ?>
            <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" onerror="this.remove()">
        <?php endif; ?>
    </header>

    <section class="event-detail-layout">
        <div class="event-detail-content">
            <h2>Informações do evento</h2>
            <?php if (!empty($event['description'])): ?>
                <div class="event-detail-text">
                    <?= article_html($event['description']) ?>
                </div>
            <?php else: ?>
                <p class="event-detail-text">Mais informações serão divulgadas em breve.</p>
            <?php endif; ?>
        </div>

        <aside class="event-detail-info">
            <h2>Resumo</h2>
            <dl>
                <div>
                    <dt>Data e horário</dt>
                    <dd><?= e($event['starts_at'] ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Atividade aberta') ?></dd>
                </div>
                <?php if (!empty($event['ends_at'])): ?>
                    <div>
                        <dt>Encerramento</dt>
                        <dd><?= e(date('d/m/Y H:i', strtotime($event['ends_at']))) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($event['location'])): ?>
                    <div>
                        <dt>Local</dt>
                        <dd><?= e($event['location']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($event['capacity'])): ?>
                    <div>
                        <dt>Vagas</dt>
                        <dd><?= e((string) $event['capacity']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($event['responsible_name'])): ?>
                    <div>
                        <dt>Responsável</dt>
                        <dd><?= e($event['responsible_name']) ?></dd>
                    </div>
                <?php endif; ?>
                <div>
                    <dt>Status</dt>
                    <dd><?= e(ucfirst((string) $event['status'])) ?></dd>
                </div>
            </dl>
            <a class="public-event-more" href="<?= e(url('/eventos')) ?>">Voltar para eventos</a>
        </aside>
    </section>
</article>
