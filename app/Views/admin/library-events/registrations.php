<?php
$totals = ['pendente' => 0, 'inscrito' => 0, 'presente' => 0, 'ausente' => 0, 'cancelado' => 0];
foreach ($eventSummaries as $summary) {
    foreach ($totals as $key => $value) {
        $totals[$key] += (int) ($summary[$key] ?? 0);
    }
}
?>

<div class="page-heading">
    <div>
        <p>Gestão de inscrições</p>
        <h1>Inscrições por evento</h1>
    </div>
    <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/library-events')) ?>"><i class="bi bi-calendar-plus" aria-hidden="true"></i>Eventos</a>
</div>

<section class="panel">
    <div class="section-heading">
        <h2>Resumo geral</h2>
        <span><?= e((string) array_sum($totals)) ?> inscrição(ões)</span>
    </div>
    <div class="events-admin-metrics participant-metrics">
        <article><span>Pendentes</span><strong><?= e((string) $totals['pendente']) ?></strong><small>precisam de conferência</small></article>
        <article><span>Confirmadas</span><strong><?= e((string) $totals['inscrito']) ?></strong><small>inscritas</small></article>
        <article><span>Presentes</span><strong><?= e((string) $totals['presente']) ?></strong><small>compareceram</small></article>
        <article><span>Ausentes</span><strong><?= e((string) $totals['ausente']) ?></strong><small>não compareceram</small></article>
        <article><span>Canceladas</span><strong><?= e((string) $totals['cancelado']) ?></strong><small>não validadas</small></article>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-clipboard-check" aria-hidden="true"></i> Eventos com inscrições</h2>
        <span><?= e((string) count($events)) ?> evento(s)</span>
    </div>
    <div class="events-admin-list">
        <?php foreach ($events as $event): ?>
            <?php $summary = $eventSummaries[(int) $event['id']] ?? []; ?>
            <article class="events-admin-item registration-event-item">
                <?php if (!empty($event['cover_image'])): ?>
                    <img src="<?= e(media_url($event['cover_image'])) ?>" alt="" loading="lazy" onerror="this.remove()">
                <?php else: ?>
                    <div class="events-admin-placeholder"><i class="bi bi-calendar-event" aria-hidden="true"></i></div>
                <?php endif; ?>
                <div class="events-admin-item-main">
                    <div class="events-admin-title-row">
                        <h3><?= e($event['title']) ?></h3>
                        <?php if (!empty($summary['pendente'])): ?>
                            <span class="state-pill is-pending"><?= e((string) $summary['pendente']) ?> pendente(s)</span>
                        <?php endif; ?>
                    </div>
                    <dl>
                        <div><dt>Pendentes</dt><dd><?= e((string) ($summary['pendente'] ?? 0)) ?></dd></div>
                        <div><dt>Inscritos</dt><dd><?= e((string) ($summary['inscrito'] ?? 0)) ?></dd></div>
                        <div><dt>Presentes</dt><dd><?= e((string) ($summary['presente'] ?? 0)) ?></dd></div>
                        <div><dt>Ausentes</dt><dd><?= e((string) ($summary['ausente'] ?? 0)) ?></dd></div>
                        <div><dt>Cancelados</dt><dd><?= e((string) ($summary['cancelado'] ?? 0)) ?></dd></div>
                    </dl>
                </div>
                <div class="events-admin-item-actions">
                    <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>">Gerenciar inscrições</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/library-events/participants/export?id=' . $event['id'] . '&format=csv')) ?>">Exportar CSV</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/evento/' . $event['id'])) ?>" target="_blank" rel="noopener">Página pública</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$events): ?>
            <div class="empty-state">Nenhum evento cadastrado.</div>
        <?php endif; ?>
    </div>
</section>
