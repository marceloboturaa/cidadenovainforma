<?php
$isEdit = (bool) $editing;
$startsAt = !empty($editing['starts_at']) ? date('Y-m-d\TH:i', strtotime($editing['starts_at'])) : '';
$endsAt = !empty($editing['ends_at']) ? date('Y-m-d\TH:i', strtotime($editing['ends_at'])) : '';
$now = time();
$happeningEvents = [];
$openEvents = [];
$pastEvents = [];
$canceledEvents = [];
$totalEventMinutes = 0;

$eventBucket = function (array $event) use ($now): string {
    $status = (string) ($event['status'] ?? 'aberto');
    $start = !empty($event['starts_at']) ? strtotime($event['starts_at']) : null;
    $end = !empty($event['ends_at']) ? strtotime($event['ends_at']) : null;

    if ($status === 'cancelado') {
        return 'canceled';
    }

    if ($status === 'encerrado' || (($end ?: $start) && ($end ?: $start) < $now)) {
        return 'past';
    }

    if ($status === 'aberto' && $start && $start <= $now && (!$end || $end >= $now)) {
        return 'happening';
    }

    return 'open';
};

$eventDurationMinutes = function (array $event): ?int {
    if (empty($event['starts_at']) || empty($event['ends_at'])) {
        return null;
    }

    $start = strtotime($event['starts_at']);
    $end = strtotime($event['ends_at']);
    if (!$start || !$end || $end <= $start) {
        return null;
    }

    return (int) round(($end - $start) / 60);
};

$formatDuration = function (?int $minutes): string {
    if (!$minutes) {
        return 'Não informado';
    }

    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    if ($hours > 0 && $remaining > 0) {
        return $hours . 'h ' . $remaining . 'min';
    }
    if ($hours > 0) {
        return $hours . 'h';
    }

    return $remaining . 'min';
};

foreach ($events as $event) {
    $duration = $eventDurationMinutes($event);
    if ($duration) {
        $totalEventMinutes += $duration;
    }

    match ($eventBucket($event)) {
        'happening' => $happeningEvents[] = $event,
        'past' => $pastEvents[] = $event,
        'canceled' => $canceledEvents[] = $event,
        default => $openEvents[] = $event,
    };
    }

$totalParticipants = array_sum(array_map(fn (array $event): int => (int) ($event['participant_count'] ?? 0), $events));
$formatDate = function (?string $value): string {
    return $value ? date('d/m/Y H:i', strtotime($value)) : 'Sem data definida';
};
$displayStatus = function (array $event) use ($eventBucket): string {
    return match ($eventBucket($event)) {
        'happening' => 'Acontecendo',
        'past' => 'Encerrado',
        'canceled' => 'Cancelado',
        default => 'Aberto',
    };
};
$statusLabel = ['aberto' => 'Aberto', 'encerrado' => 'Encerrado', 'cancelado' => 'Cancelado'];
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
            <span>Acontecendo</span>
            <strong><?= e((string) count($happeningEvents)) ?></strong>
            <small>em andamento agora</small>
        </article>
        <article>
            <span>Abertos</span>
            <strong><?= e((string) count($openEvents)) ?></strong>
            <small>futuros ou sem data</small>
        </article>
        <article>
            <span>Encerrados</span>
            <strong><?= e((string) count($pastEvents)) ?></strong>
            <small>já realizados</small>
        </article>
        <article>
            <span>Horas</span>
            <strong><?= e($formatDuration($totalEventMinutes)) ?></strong>
            <small><?= e((string) $totalParticipants) ?> participante(s)</small>
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
                    <span class="state-pill <?= $eventBucket($editing) === 'open' || $eventBucket($editing) === 'happening' ? 'is-active' : 'is-muted' ?>">
                        <?= e($displayStatus($editing)) ?>
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
                            <label class="form-label">Local / espaço</label>
                            <input class="form-control" name="location" value="<?= e($editing['location'] ?? '') ?>" placeholder="Ex.: Biblioteca, auditório, praça">
                        </div>
                        <div>
                            <label class="form-label">Endereço</label>
                            <input class="form-control" name="event_address" value="<?= e($editing['event_address'] ?? '') ?>" placeholder="Rua, número, bairro">
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
                        <label class="form-label">Mat&eacute;rias sobre o evento</label>
                        <textarea class="form-control" name="related_links" rows="4" placeholder="Titulo da materia | /noticia/exemplo&#10;https://site.com/materia"><?= e($editing['related_links'] ?? '') ?></textarea>
                        <small class="form-text">Use um link por linha. Se quiser, escreva: Titulo | URL.</small>
                    </div>

                    <div>
                        <label class="form-label">Observa&ccedil;&otilde;es internas</label>
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
                <li><strong>Acontecendo</strong> aparece automaticamente quando o horário atual está entre início e fim.</li>
                <li>Use <strong>Aberto</strong> para eventos futuros ou ainda disponíveis.</li>
                <li>Use <strong>Encerrado</strong> quando a atividade terminar.</li>
                <li>Use <strong>Cancelado</strong> para tirar da página pública.</li>
                <li>Preencha início e fim para o painel calcular a duração em horas.</li>
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
                <button type="button" data-event-filter="happening">Acontecendo</button>
                <button type="button" data-event-filter="open">Abertos</button>
                <button type="button" data-event-filter="past">Realizados</button>
                <button type="button" data-event-filter="canceled">Cancelados</button>
            </div>
        </div>

        <div class="events-admin-list" data-events-admin-list>
            <?php foreach ($events as $event): ?>
                <?php
                $bucket = $eventBucket($event);
                $capacity = !empty($event['capacity']) ? (int) $event['capacity'] : null;
                $participants = (int) ($event['participant_count'] ?? 0);
                $remaining = $capacity ? max(0, $capacity - $participants) : null;
                $duration = $eventDurationMinutes($event);
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
                            <span class="state-pill <?= in_array($bucket, ['open', 'happening'], true) ? 'is-active' : 'is-muted' ?>"><?= e($displayStatus($event)) ?></span>
                        </div>
                        <p><?= e(text_excerpt($event['description'] ?? '', 130)) ?></p>
                        <dl>
                            <div><dt>Quando</dt><dd><?= e($formatDate($event['starts_at'] ?? null)) ?></dd></div>
                            <div><dt>Duração</dt><dd><?= e($formatDuration($duration)) ?></dd></div>
                            <div><dt>Local</dt><dd><?= e($event['location'] ?: '-') ?></dd></div>
                            <div><dt>Endereço</dt><dd><?= e($event['event_address'] ?: '-') ?></dd></div>
                            <div><dt>Vagas</dt><dd><?= e((string) $participants) ?><?= $capacity ? ' / ' . e((string) $capacity) . ' · ' . e((string) $remaining) . ' livre(s)' : '' ?></dd></div>
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
