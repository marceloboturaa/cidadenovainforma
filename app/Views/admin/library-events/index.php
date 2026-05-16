<?php
$isEdit = (bool) $editing;
$startsAt = !empty($editing['starts_at']) ? date('Y-m-d\TH:i', strtotime($editing['starts_at'])) : '';
$endsAt = !empty($editing['ends_at']) ? date('Y-m-d\TH:i', strtotime($editing['ends_at'])) : '';
$now = time();
$upcomingEvents = [];
$pastEvents = [];
$canceledEvents = [];

foreach ($events as $event) {
    $eventTime = !empty($event['ends_at'] ?: $event['starts_at']) ? strtotime($event['ends_at'] ?: $event['starts_at']) : null;
    if (($event['status'] ?? '') === 'cancelado') {
        $canceledEvents[] = $event;
    } elseif (($event['status'] ?? '') === 'encerrado' || ($eventTime && $eventTime < $now)) {
        $pastEvents[] = $event;
    } else {
        $upcomingEvents[] = $event;
    }
}

$totalParticipants = array_sum(array_map(fn (array $event): int => (int) ($event['participant_count'] ?? 0), $events));
$formatDate = function (?string $value): string {
    return $value ? date('d/m/Y H:i', strtotime($value)) : 'Sem data definida';
};
$statusLabel = ['aberto' => 'Aberto', 'encerrado' => 'Realizado', 'cancelado' => 'Cancelado'];
?>

<div class="events-admin-shell">
    <header class="events-admin-hero">
        <div>
            <span class="eyebrow">Agenda institucional</span>
            <h1>Eventos</h1>
            <p>Cadastre atividades, organize responsáveis, acompanhe vagas e separe próximos eventos dos já realizados.</p>
        </div>
        <div class="events-admin-actions">
            <a class="btn btn-outline-secondary" href="<?= e(url('/eventos')) ?>" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                Página pública
            </a>
            <?php if ($isEdit): ?>
                <a class="btn btn-primary" href="<?= e(url('/admin/library-events')) ?>">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Novo evento
                </a>
            <?php endif; ?>
        </div>
    </header>

    <section class="events-admin-metrics" aria-label="Resumo de eventos">
        <article>
            <span>Próximos</span>
            <strong><?= e((string) count($upcomingEvents)) ?></strong>
            <small>em aberto</small>
        </article>
        <article>
            <span>Realizados</span>
            <strong><?= e((string) count($pastEvents)) ?></strong>
            <small>histórico</small>
        </article>
        <article>
            <span>Participantes</span>
            <strong><?= e((string) $totalParticipants) ?></strong>
            <small>em todos os eventos</small>
        </article>
        <article>
            <span>Cancelados</span>
            <strong><?= e((string) count($canceledEvents)) ?></strong>
            <small>fora da agenda pública</small>
        </article>
    </section>

    <section class="events-admin-board">
        <article class="events-editor-card" id="evento-form">
            <div class="events-card-heading">
                <div>
                    <span class="eyebrow"><?= $isEdit ? 'Edição' : 'Cadastro' ?></span>
                    <h2><?= $isEdit ? 'Editar evento' : 'Novo evento' ?></h2>
                </div>
                <?php if ($isEdit): ?>
                    <span class="state-pill <?= ($editing['status'] ?? '') === 'aberto' ? 'is-active' : 'is-muted' ?>">
                        <?= e($statusLabel[$editing['status']] ?? ucfirst((string) $editing['status'])) ?>
                    </span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e($isEdit ? url('/admin/library-events/update?id=' . $editing['id']) : url('/admin/library-events')) ?>" class="events-modern-form" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="events-form-section is-main">
                    <div>
                        <label class="form-label">Nome do evento</label>
                        <input class="form-control" name="title" value="<?= e($editing['title'] ?? '') ?>" placeholder="Ex.: Oficina de leitura" required>
                    </div>
                    <div>
                        <label class="form-label">Descrição pública</label>
                        <textarea class="form-control" name="description" rows="7" data-tinymce><?= e($editing['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="events-form-section">
                    <div class="events-form-grid">
                        <div>
                            <label class="form-label">Início</label>
                            <input class="form-control" name="starts_at" type="datetime-local" value="<?= e($startsAt) ?>">
                        </div>
                        <div>
                            <label class="form-label">Fim</label>
                            <input class="form-control" name="ends_at" type="datetime-local" value="<?= e($endsAt) ?>">
                        </div>
                        <div>
                            <label class="form-label">Local</label>
                            <input class="form-control" name="location" value="<?= e($editing['location'] ?? '') ?>" placeholder="Endereço ou espaço">
                        </div>
                        <div>
                            <label class="form-label">Vagas</label>
                            <input class="form-control" name="capacity" type="number" min="0" value="<?= e((string) ($editing['capacity'] ?? '')) ?>">
                        </div>
                        <div>
                            <label class="form-label">Responsável</label>
                            <select class="form-select" name="responsible_user_id">
                                <option value="">Sem responsável</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= e((string) $user['id']) ?>" <?= selected((string) $user['id'], (string) ($editing['responsible_user_id'] ?? '')) ?>>
                                        <?= e($user['name']) ?> - <?= e($user['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($statusLabel as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= selected($value, (string) ($editing['status'] ?? 'aberto')) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Imagem de capa</label>
                        <input class="form-control" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                        <?php if (!empty($editing['cover_image'])): ?>
                            <img class="event-cover-preview" src="<?= e(media_url($editing['cover_image'])) ?>" alt="" onerror="this.remove()">
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Observações internas</label>
                        <textarea class="form-control" name="notes" rows="4" placeholder="Informações visíveis apenas no painel"><?= e($editing['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="events-form-actions">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <?= $isEdit ? 'Atualizar evento' : 'Cadastrar evento' ?>
                    </button>
                    <?php if ($isEdit): ?>
                        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/library-events/participants?id=' . $editing['id'])) ?>">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            Participantes
                        </a>
                        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/library-events')) ?>">Cancelar edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </article>

        <aside class="events-admin-guide">
            <h2>Como organizar</h2>
            <ul>
                <li>Use <strong>Aberto</strong> para aparecer em eventos futuros.</li>
                <li>Use <strong>Realizado</strong> quando a atividade terminar.</li>
                <li>Use <strong>Cancelado</strong> para tirar da página pública.</li>
                <li>Adicione capa, local e horário para deixar a página pública completa.</li>
            </ul>
        </aside>
    </section>

    <section class="events-list-panel">
        <div class="events-card-heading">
            <div>
                <span class="eyebrow">Gestão</span>
                <h2>Eventos cadastrados</h2>
            </div>
            <div class="events-admin-tabs" role="group" aria-label="Filtrar eventos">
                <button type="button" class="is-active" data-event-filter="all">Todos</button>
                <button type="button" data-event-filter="upcoming">Futuros</button>
                <button type="button" data-event-filter="past">Realizados</button>
                <button type="button" data-event-filter="canceled">Cancelados</button>
            </div>
        </div>

        <div class="events-admin-list" data-events-admin-list>
            <?php foreach ($events as $event): ?>
                <?php
                $eventTime = !empty($event['ends_at'] ?: $event['starts_at']) ? strtotime($event['ends_at'] ?: $event['starts_at']) : null;
                $bucket = ($event['status'] ?? '') === 'cancelado' ? 'canceled' : ((($event['status'] ?? '') === 'encerrado' || ($eventTime && $eventTime < $now)) ? 'past' : 'upcoming');
                $capacity = !empty($event['capacity']) ? (int) $event['capacity'] : null;
                $participants = (int) ($event['participant_count'] ?? 0);
                ?>
                <article class="events-admin-item" data-event-card data-event-bucket="<?= e($bucket) ?>">
                    <?php if (!empty($event['cover_image'])): ?>
                        <img src="<?= e(media_url($event['cover_image'])) ?>" alt="" loading="lazy" onerror="this.remove()">
                    <?php else: ?>
                        <div class="events-admin-placeholder"><i class="bi bi-calendar-event" aria-hidden="true"></i></div>
                    <?php endif; ?>
                    <div class="events-admin-item-main">
                        <div class="events-admin-title-row">
                            <h3><?= e($event['title']) ?></h3>
                            <span class="state-pill <?= $bucket === 'upcoming' ? 'is-active' : 'is-muted' ?>"><?= e($statusLabel[$event['status']] ?? ucfirst((string) $event['status'])) ?></span>
                        </div>
                        <p><?= e(text_excerpt($event['description'] ?? '', 130)) ?></p>
                        <dl>
                            <div><dt>Quando</dt><dd><?= e($formatDate($event['starts_at'] ?? null)) ?></dd></div>
                            <div><dt>Local</dt><dd><?= e($event['location'] ?: '-') ?></dd></div>
                            <div><dt>Participantes</dt><dd><?= e((string) $participants) ?><?= $capacity ? ' / ' . e((string) $capacity) : '' ?></dd></div>
                            <div><dt>Responsável</dt><dd><?= e($event['responsible_name'] ?? '-') ?></dd></div>
                        </dl>
                    </div>
                    <div class="events-admin-item-actions">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/library-events/edit?id=' . $event['id'])) ?>">Editar</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>">Participantes</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/evento/' . $event['id'])) ?>" target="_blank" rel="noopener">Ver página</a>
                        <?php if ($canDeactivate): ?>
                            <form method="post" action="<?= e(url('/admin/library-events/delete?id=' . $event['id'])) ?>" onsubmit="return confirm('Desativar este evento?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Desativar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$events): ?>
                <div class="empty-state">Nenhum evento cadastrado.</div>
            <?php endif; ?>
            <div class="empty-state" data-events-empty hidden>Nenhum evento encontrado neste filtro.</div>
        </div>
    </section>
</div>
