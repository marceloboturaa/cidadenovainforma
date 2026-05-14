<?php
$isEdit = (bool) $editing;
$startsAt = !empty($editing['starts_at']) ? date('Y-m-d\TH:i', strtotime($editing['starts_at'])) : '';
$endsAt = !empty($editing['ends_at']) ? date('Y-m-d\TH:i', strtotime($editing['ends_at'])) : '';
?>

<div class="page-heading">
    <div>
        <p>Área interna</p>
        <h1>Eventos e atividades</h1>
    </div>
</div>

<section class="panel internal-editor-panel">
    <div class="section-heading">
        <h2><?= $isEdit ? 'Editar evento' : 'Novo evento' ?></h2>
        <span>Atividades internas da biblioteca</span>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/library-events/update?id=' . $editing['id']) : url('/admin/library-events')) ?>" class="admin-form-grid internal-event-form" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Nome do evento</label>
            <input class="form-control" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
        </div>
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
            <input class="form-control" name="location" value="<?= e($editing['location'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">Imagem de capa</label>
            <input class="form-control" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <?php if (!empty($editing['cover_image'])): ?>
                <img class="cover-preview event-cover-preview" src="<?= e(media_url($editing['cover_image'])) ?>" alt="" onerror="this.remove()">
            <?php endif; ?>
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
                <?php foreach (['aberto' => 'Aberto', 'encerrado' => 'Encerrado', 'cancelado' => 'Cancelado'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= selected($value, (string) ($editing['status'] ?? 'aberto')) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="description" rows="3" data-tinymce><?= e($editing['description'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="form-label">Observações internas</label>
            <textarea class="form-control" name="notes" rows="3"><?= e($editing['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-action-cell split-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/participants?id=' . $editing['id'])) ?>"><i class="bi bi-people" aria-hidden="true"></i>Participantes</a>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-calendar-event" aria-hidden="true"></i> Eventos cadastrados</h2>
        <span><?= e((string) count($events)) ?> evento(s)</span>
    </div>
    <div class="admin-card-list">
        <?php foreach ($events as $event): ?>
            <article class="admin-list-card internal-list-card">
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($event['title']) ?></strong>
                        <span class="state-pill <?= $event['status'] === 'aberto' ? 'is-active' : 'is-muted' ?>"><?= e(ucfirst($event['status'])) ?></span>
                    </div>
                    <?php if (!empty($event['cover_image'])): ?>
                        <img class="event-admin-thumb" src="<?= e(media_url($event['cover_image'])) ?>" alt="" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                    <dl class="admin-list-meta">
                        <div><dt>Data</dt><dd><?= e($event['starts_at'] ?? '-') ?></dd></div>
                        <div><dt>Local</dt><dd><?= e($event['location'] ?? '-') ?></dd></div>
                        <div><dt>Participantes</dt><dd><?= e((string) $event['participant_count']) ?><?= $event['capacity'] ? ' / ' . e((string) $event['capacity']) : '' ?></dd></div>
                    </dl>
                </div>
                <div class="admin-list-actions">
                    <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>"><i class="bi bi-people" aria-hidden="true"></i>Participantes</a>
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/edit?id=' . $event['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                    <?php if ($canDeactivate): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/library-events/delete?id=' . $event['id'])) ?>" onsubmit="return confirm('Desativar este evento?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Desativar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$events): ?>
            <div class="empty-state">Nenhum evento cadastrado.</div>
        <?php endif; ?>
    </div>
</section>
