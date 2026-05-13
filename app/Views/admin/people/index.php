<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Área interna</p>
        <h1>Pessoas cadastradas</h1>
    </div>
</div>

<section class="panel internal-editor-panel">
    <div class="section-heading">
        <h2><?= $isEdit ? 'Editar pessoa' : 'Nova pessoa' ?></h2>
        <span>Dados internos, não públicos</span>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/people/update?id=' . $editing['id']) : url('/admin/people')) ?>" class="admin-form-grid internal-person-form">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Nome completo</label>
            <input class="form-control" name="full_name" value="<?= e($editing['full_name'] ?? '') ?>" required>
        </div>
        <div>
            <label class="form-label">CPF</label>
            <input class="form-control" name="cpf" value="<?= e($editing['cpf'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">Nascimento</label>
            <input class="form-control" name="birth_date" type="date" value="<?= e($editing['birth_date'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">Telefone</label>
            <input class="form-control" name="phone" value="<?= e($editing['phone'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">WhatsApp</label>
            <input class="form-control" name="whatsapp" value="<?= e($editing['whatsapp'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">E-mail</label>
            <input class="form-control" name="email" type="email" value="<?= e($editing['email'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">Endereço</label>
            <input class="form-control" name="address" value="<?= e($editing['address'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">Bairro</label>
            <input class="form-control" name="district" value="<?= e($editing['district'] ?? '') ?>">
        </div>
        <div>
            <label class="form-label">Responsável</label>
            <input class="form-control" name="guardian_name" value="<?= e($editing['guardian_name'] ?? '') ?>">
        </div>
        <div class="form-check-cell">
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="contact_authorized" value="1" <?= checked((bool) ($editing['contact_authorized'] ?? false)) ?>>
                <span class="form-check-label">Autoriza contato</span>
            </label>
        </div>
        <div>
            <label class="form-label">Observações internas</label>
            <textarea class="form-control" name="notes" rows="3"><?= e($editing['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-action-cell split-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar' : 'Cadastrar' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/people')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Cadastros internos</h2>
        <span><?= e((string) count($people)) ?> pessoa(s)</span>
    </div>
    <form class="internal-search-form" method="get" action="<?= e(url('/admin/people')) ?>">
        <input class="form-control" name="q" value="<?= e($query ?? '') ?>" placeholder="Buscar por nome, CPF, e-mail, telefone ou WhatsApp">
        <button class="btn btn-outline-secondary icon-btn"><i class="bi bi-search" aria-hidden="true"></i>Buscar</button>
    </form>
    <div class="admin-card-list">
        <?php foreach ($people as $person): ?>
            <article class="admin-list-card internal-list-card">
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($person['full_name']) ?></strong>
                        <span class="state-pill <?= $person['contact_authorized'] ? 'is-active' : 'is-muted' ?>"><?= $person['contact_authorized'] ? 'Contato autorizado' : 'Sem autorização' ?></span>
                    </div>
                    <dl class="admin-list-meta">
                        <div><dt>WhatsApp</dt><dd><?= e($person['whatsapp'] ?? '-') ?></dd></div>
                        <div><dt>E-mail</dt><dd><?= e($person['email'] ?? '-') ?></dd></div>
                        <div><dt>Bairro</dt><dd><?= e($person['district'] ?? '-') ?></dd></div>
                    </dl>
                    <?php if (!empty($person['notes'])): ?>
                        <p class="admin-list-description"><?= e(text_excerpt($person['notes'], 180)) ?></p>
                    <?php endif; ?>
                </div>
                <div class="admin-list-actions">
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/people/edit?id=' . $person['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                    <?php if ($canDeactivate): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/people/delete?id=' . $person['id'])) ?>" onsubmit="return confirm('Desativar este cadastro interno?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Desativar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$people): ?>
            <div class="empty-state">Nenhuma pessoa cadastrada.</div>
        <?php endif; ?>
    </div>
</section>
