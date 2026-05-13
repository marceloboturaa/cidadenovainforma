<div class="page-heading">
    <div>
        <p>Participantes</p>
        <h1><?= e($event['title']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/edit?id=' . $event['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
</div>

<section class="panel">
    <div class="section-heading">
        <h2>Adicionar participante</h2>
        <span>Use pessoas já cadastradas</span>
    </div>
    <form class="internal-search-form" method="get" action="<?= e(url('/admin/library-events/participants')) ?>">
        <input type="hidden" name="id" value="<?= e((string) $event['id']) ?>">
        <input class="form-control" name="q" value="<?= e($query ?? '') ?>" placeholder="Buscar pessoa por nome, CPF, e-mail ou WhatsApp">
        <button class="btn btn-outline-secondary icon-btn"><i class="bi bi-search" aria-hidden="true"></i>Buscar</button>
    </form>
    <div class="admin-card-list compact-list">
        <?php foreach ($people as $person): ?>
            <article class="admin-list-card internal-list-card">
                <div class="admin-list-main">
                    <strong class="admin-list-title"><?= e($person['full_name']) ?></strong>
                    <dl class="admin-list-meta">
                        <div><dt>WhatsApp</dt><dd><?= e($person['whatsapp'] ?? '-') ?></dd></div>
                        <div><dt>E-mail</dt><dd><?= e($person['email'] ?? '-') ?></dd></div>
                    </dl>
                </div>
                <form class="participant-add-form" method="post" action="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="person_id" value="<?= e((string) $person['id']) ?>">
                    <select class="form-select form-select-sm" name="status">
                        <option value="inscrito">Inscrito</option>
                        <option value="presente">Presente</option>
                        <option value="ausente">Ausente</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <button class="btn btn-sm btn-primary icon-btn"><i class="bi bi-plus-circle" aria-hidden="true"></i>Adicionar</button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if (!$people): ?>
            <div class="empty-state">Nenhuma pessoa encontrada. Cadastre primeiro em Pessoas.</div>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-people" aria-hidden="true"></i> Participantes vinculados</h2>
        <span><?= e((string) count($participants)) ?> participante(s)</span>
    </div>
    <div class="admin-card-list">
        <?php foreach ($participants as $participant): ?>
            <article class="admin-list-card internal-list-card">
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($participant['full_name']) ?></strong>
                        <span class="state-pill <?= $participant['status'] === 'presente' ? 'is-active' : 'is-muted' ?>"><?= e(ucfirst($participant['status'])) ?></span>
                    </div>
                    <dl class="admin-list-meta">
                        <div><dt>WhatsApp</dt><dd><?= e($participant['whatsapp'] ?? '-') ?></dd></div>
                        <div><dt>E-mail</dt><dd><?= e($participant['email'] ?? '-') ?></dd></div>
                        <div><dt>Bairro</dt><dd><?= e($participant['district'] ?? '-') ?></dd></div>
                    </dl>
                </div>
                <form class="inline-form" method="post" action="<?= e(url('/admin/library-events/participants/remove?id=' . $event['id'] . '&person_id=' . $participant['person_id'])) ?>" onsubmit="return confirm('Remover participante deste evento?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-x-circle" aria-hidden="true"></i>Remover</button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if (!$participants): ?>
            <div class="empty-state">Nenhum participante vinculado a este evento.</div>
        <?php endif; ?>
    </div>
</section>
