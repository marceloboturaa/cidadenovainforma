<div class="page-heading">
    <div>
        <p>Inscrições e participantes</p>
        <h1><?= e($event['title']) ?></h1>
    </div>
    <div class="export-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/participants/export?id=' . $event['id'] . '&format=csv')) ?>"><i class="bi bi-filetype-csv" aria-hidden="true"></i>CSV</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/participants/export?id=' . $event['id'] . '&format=pdf')) ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>PDF</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/edit?id=' . $event['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
    </div>
</div>

<section class="panel">
    <div class="section-heading">
        <h2>Resumo da lista</h2>
        <span><?= e((string) count($participants)) ?> registro(s)</span>
    </div>
    <div class="events-admin-metrics participant-metrics">
        <article><span>Pendentes</span><strong><?= e((string) ($participantStats['pendente'] ?? 0)) ?></strong><small>aguardando confirmação</small></article>
        <article><span>Inscritos</span><strong><?= e((string) ($participantStats['inscrito'] ?? 0)) ?></strong><small>aguardando presença</small></article>
        <article><span>Presentes</span><strong><?= e((string) ($participantStats['presente'] ?? 0)) ?></strong><small>confirmados no evento</small></article>
        <article><span>Ausentes</span><strong><?= e((string) ($participantStats['ausente'] ?? 0)) ?></strong><small>não compareceram</small></article>
        <article><span>Cancelados</span><strong><?= e((string) ($participantStats['cancelado'] ?? 0)) ?></strong><small>não validados</small></article>
    </div>
</section>

<section class="panel person-register-panel">
    <div class="person-register-head">
        <div>
            <span>Nova inscrição</span>
            <h2>Cadastrar pessoa no evento</h2>
        </div>
        <strong>Cadastro + vínculo</strong>
    </div>
    <form method="post" action="<?= e(url('/admin/library-events/participants/create?id=' . $event['id'])) ?>" class="person-registration-form" data-person-form>
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
                        <input class="form-control" name="full_name" required>
                    </div>
                    <div>
                        <label class="form-label">CPF</label>
                        <input class="form-control" name="cpf" data-cpf-input>
                    </div>
                    <div>
                        <label class="form-label">Nascimento</label>
                        <input class="form-control" name="birth_date" type="date">
                    </div>
                    <div>
                        <label class="form-label">Telefone</label>
                        <input class="form-control" name="phone">
                    </div>
                    <div>
                        <label class="form-label">WhatsApp</label>
                        <input class="form-control" name="whatsapp">
                    </div>
                    <div class="field-wide">
                        <label class="form-label">E-mail</label>
                        <input class="form-control" name="email" type="email">
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
                            <input class="form-control" name="cep" data-cep-input>
                            <button class="btn btn-outline-secondary icon-btn" type="button" data-cep-search><i class="bi bi-search" aria-hidden="true"></i>CEP</button>
                        </div>
                    </div>
                    <div class="field-wide">
                        <label class="form-label">Endereço</label>
                        <input class="form-control" name="address" data-address-input>
                    </div>
                    <div>
                        <label class="form-label">Número</label>
                        <input class="form-control" name="address_number">
                    </div>
                    <div class="field-wide">
                        <label class="form-label">Complemento</label>
                        <input class="form-control" name="address_complement">
                    </div>
                    <div>
                        <label class="form-label">Bairro</label>
                        <input class="form-control" name="district" data-district-input>
                    </div>
                    <div>
                        <label class="form-label">Cidade</label>
                        <input class="form-control" name="city" data-city-input>
                    </div>
                    <div>
                        <label class="form-label">UF</label>
                        <input class="form-control" name="state" maxlength="2" data-state-input>
                    </div>
                </div>
            </section>

            <section class="person-form-section guardian-fields" data-guardian-fields hidden>
                <div class="person-section-title guardian-fields-head">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <div>
                        <h3>Dados do responsável</h3>
                        <span>Preencha quando a pessoa inscrita for menor de idade</span>
                    </div>
                </div>
                <div class="person-field-grid guardian-grid">
                    <div class="field-wide">
                        <label class="form-label">Nome do responsável</label>
                        <input class="form-control" name="guardian_name">
                    </div>
                    <div>
                        <label class="form-label">Parentesco</label>
                        <input class="form-control" name="guardian_relation">
                    </div>
                    <div>
                        <label class="form-label">CPF do responsável</label>
                        <input class="form-control" name="guardian_cpf" data-cpf-input>
                    </div>
                    <div>
                        <label class="form-label">Telefone/WhatsApp</label>
                        <input class="form-control" name="guardian_phone">
                    </div>
                    <div class="field-wide">
                        <label class="form-label">E-mail do responsável</label>
                        <input class="form-control" name="guardian_email" type="email">
                    </div>
                </div>
            </section>
        </div>
        <aside class="person-form-side">
            <section class="person-side-card">
                <h3>Status da inscrição</h3>
                <label class="form-label">Situação</label>
                <select class="form-select" name="status">
                    <option value="pendente">Pendente</option>
                    <option value="inscrito">Inscrito</option>
                    <option value="presente">Presente</option>
                    <option value="ausente">Ausente</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <label class="person-toggle-row">
                    <input class="form-check-input" type="checkbox" name="contact_authorized" value="1">
                    <span><strong>Autoriza contato</strong><small>Permite usar telefone, WhatsApp ou e-mail.</small></span>
                </label>
                <label class="person-toggle-row">
                    <input class="form-check-input" type="checkbox" name="is_minor" value="1" data-minor-toggle>
                    <span><strong>Criança/adolescente</strong><small>Mostra campos do responsável.</small></span>
                </label>
                <label class="form-label">Observações da inscrição</label>
                <textarea class="form-control" name="participant_notes" rows="4"></textarea>
            </section>
            <div class="person-form-actions">
                <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Cadastrar inscrição</button>
            </div>
        </aside>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2>Adicionar pessoa já cadastrada</h2>
        <span>Busque no cadastro interno</span>
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
                        <option value="pendente">Pendente</option>
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
                        <span class="state-pill <?= in_array($participant['status'], ['inscrito', 'presente'], true) ? 'is-active' : 'is-muted' ?>"><?= e(ucfirst($participant['status'])) ?></span>
                    </div>
                    <dl class="admin-list-meta">
                        <div><dt>WhatsApp</dt><dd><?= e($participant['whatsapp'] ?? '-') ?></dd></div>
                        <div><dt>E-mail</dt><dd><?= e($participant['email'] ?? '-') ?></dd></div>
                        <div><dt>Bairro</dt><dd><?= e($participant['district'] ?? '-') ?></dd></div>
                    </dl>
                </div>
                <div class="participant-management-actions">
                    <form class="participant-add-form" method="post" action="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="person_id" value="<?= e((string) $participant['person_id']) ?>">
                        <select class="form-select form-select-sm" name="status">
                            <?php foreach (['pendente' => 'Pendente', 'inscrito' => 'Inscrito', 'presente' => 'Presente', 'ausente' => 'Ausente', 'cancelado' => 'Cancelado'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= selected($value, (string) $participant['status']) ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control form-control-sm" name="notes" value="<?= e($participant['notes'] ?? '') ?>" placeholder="Observação">
                        <button class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-check2" aria-hidden="true"></i>Salvar</button>
                    </form>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/library-events/participants/remove?id=' . $event['id'] . '&person_id=' . $participant['person_id'])) ?>" onsubmit="return confirm('Remover participante deste evento?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-x-circle" aria-hidden="true"></i>Remover</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$participants): ?>
            <div class="empty-state">Nenhum participante vinculado a este evento.</div>
        <?php endif; ?>
    </div>
</section>
