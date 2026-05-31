<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Área interna</p>
        <h1>Pessoas cadastradas</h1>
    </div>
    <div class="export-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/people/export?format=csv' . (!empty($query) ? '&q=' . urlencode($query) : ''))) ?>"><i class="bi bi-filetype-csv" aria-hidden="true"></i>CSV</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/people/export?format=pdf' . (!empty($query) ? '&q=' . urlencode($query) : ''))) ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>PDF</a>
    </div>
</div>

<section class="panel person-register-panel">
    <div class="person-register-head">
        <div>
            <span>Cadastro interno</span>
            <h2><?= $isEdit ? 'Editar pessoa' : 'Nova pessoa' ?></h2>
        </div>
        <strong>Dados internos, não públicos</strong>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/people/update?id=' . $editing['id']) : url('/admin/people')) ?>" class="person-registration-form" data-person-form>
        <?= csrf_field() ?>

        <div class="person-form-main">
            <section class="person-form-section">
                <div class="person-section-title">
                    <i class="bi bi-person-vcard" aria-hidden="true"></i>
                    <h3>Dados pessoais</h3>
                </div>
                <div class="person-field-grid">
                    <div class="field-wide">
                        <label class="form-label">Nome completo</label>
                        <input class="form-control" name="full_name" value="<?= e($editing['full_name'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="form-label">CPF</label>
                        <input class="form-control" name="cpf" value="<?= e($editing['cpf'] ?? '') ?>" data-cpf-input>
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
                    <div class="field-wide">
                        <label class="form-label">E-mail</label>
                        <input class="form-control" name="email" type="email" value="<?= e($editing['email'] ?? '') ?>">
                    </div>
                </div>
            </section>

            <section class="person-form-section">
                <div class="person-section-title">
                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                    <h3>Endereço</h3>
                </div>
                <div class="person-field-grid">
                    <div>
                        <label class="form-label">CEP</label>
                        <div class="input-action-row">
                            <input class="form-control" name="cep" value="<?= e($editing['cep'] ?? '') ?>" data-cep-input>
                            <button class="btn btn-outline-secondary icon-btn" type="button" data-cep-search><i class="bi bi-search" aria-hidden="true"></i>CEP</button>
                        </div>
                    </div>
                    <div class="field-wide">
                        <label class="form-label">Endereço</label>
                        <input class="form-control" name="address" value="<?= e($editing['address'] ?? '') ?>" data-address-input>
                    </div>
                    <div>
                        <label class="form-label">Número</label>
                        <input class="form-control" name="address_number" value="<?= e($editing['address_number'] ?? '') ?>">
                    </div>
                    <div class="field-wide">
                        <label class="form-label">Complemento</label>
                        <input class="form-control" name="address_complement" value="<?= e($editing['address_complement'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Bairro</label>
                        <input class="form-control" name="district" value="<?= e($editing['district'] ?? '') ?>" data-district-input>
                    </div>
                    <div>
                        <label class="form-label">Cidade</label>
                        <input class="form-control" name="city" value="<?= e($editing['city'] ?? '') ?>" data-city-input>
                    </div>
                    <div>
                        <label class="form-label">UF</label>
                        <input class="form-control" name="state" maxlength="2" value="<?= e($editing['state'] ?? '') ?>" data-state-input>
                    </div>
                </div>
            </section>

            <section class="person-form-section guardian-fields" data-guardian-fields>
                <div class="person-section-title guardian-fields-head">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <div>
                        <h3>Dados do responsável</h3>
                        <span>Preencha quando o participante for menor de idade</span>
                    </div>
                </div>
                <div class="person-field-grid guardian-grid">
                    <div class="field-wide">
                        <label class="form-label">Nome do responsável</label>
                        <input class="form-control" name="guardian_name" value="<?= e($editing['guardian_name'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Parentesco</label>
                        <input class="form-control" name="guardian_relation" value="<?= e($editing['guardian_relation'] ?? '') ?>" placeholder="Mãe, pai, avó...">
                    </div>
                    <div>
                        <label class="form-label">CPF do responsável</label>
                        <input class="form-control" name="guardian_cpf" value="<?= e($editing['guardian_cpf'] ?? '') ?>" data-cpf-input>
                    </div>
                    <div>
                        <label class="form-label">Telefone/WhatsApp</label>
                        <input class="form-control" name="guardian_phone" value="<?= e($editing['guardian_phone'] ?? '') ?>">
                    </div>
                    <div class="field-wide">
                        <label class="form-label">E-mail do responsável</label>
                        <input class="form-control" name="guardian_email" type="email" value="<?= e($editing['guardian_email'] ?? '') ?>">
                    </div>
                </div>
            </section>

            <section class="person-form-section">
                <div class="person-section-title">
                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                    <h3>Observações</h3>
                </div>
                <label class="form-label">Observações internas</label>
                <textarea class="form-control" name="notes" rows="4"><?= e($editing['notes'] ?? '') ?></textarea>
            </section>
        </div>

        <aside class="person-form-side">
            <section class="person-side-card">
                <h3>Status do cadastro</h3>
                <label class="person-toggle-row">
                    <input class="form-check-input" type="checkbox" name="contact_authorized" value="1" <?= checked((bool) ($editing['contact_authorized'] ?? false)) ?>>
                    <span>
                        <strong>Autoriza contato</strong>
                        <small>Permite usar telefone, WhatsApp ou e-mail.</small>
                    </span>
                </label>
                <label class="person-toggle-row">
                    <input class="form-check-input" type="checkbox" name="is_minor" value="1" <?= checked((bool) ($editing['is_minor'] ?? false)) ?> data-minor-toggle>
                    <span>
                        <strong>Criança/adolescente</strong>
                        <small>Mostra os campos do responsável.</small>
                    </span>
                </label>
            </section>

            <div class="person-form-actions">
                <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar cadastro' : 'Cadastrar pessoa' ?></button>
                <?php if ($isEdit): ?>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/people')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar edição</a>
                <?php endif; ?>
            </div>
        </aside>
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
                        <div><dt>Bairro/Cidade</dt><dd><?= e(trim(($person['district'] ?? '') . ' ' . ($person['city'] ?? '')) ?: '-') ?></dd></div>
                    </dl>
                    <?php if ($person['is_minor']): ?>
                        <p class="admin-list-description"><strong>Responsável:</strong> <?= e($person['guardian_name'] ?? '-') ?><?= !empty($person['guardian_phone']) ? ' - ' . e($person['guardian_phone']) : '' ?></p>
                    <?php endif; ?>
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
