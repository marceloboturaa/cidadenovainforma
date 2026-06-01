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
<?php
$whatsappLink = function (?string $phone, string $name, string $eventTitle): ?string {
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if (!$digits) {
        return null;
    }
    if (strlen($digits) <= 11) {
        $digits = '55' . $digits;
    }
    $message = 'Olá, ' . $name . '. Sua inscrição para "' . $eventTitle . '" foi atualizada. Acompanhe as informações com a equipe do Cidade Nova Informa.';
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
};
$capacity = !empty($event['capacity']) ? (int) $event['capacity'] : null;
$occupiedSlots = (int) (($participantStats['pendente'] ?? 0) + ($participantStats['inscrito'] ?? 0) + ($participantStats['presente'] ?? 0) + ($participantStats['ausente'] ?? 0));
$remainingSlots = $capacity ? max(0, $capacity - $occupiedSlots) : null;
$slotPercent = $capacity ? min(100, (int) round(($occupiedSlots / $capacity) * 100)) : 0;
$users = array_values(array_filter($users ?? [], fn (array $user): bool => ($user['role_slug'] ?? '') !== 'master'));
$participantIds = array_map(fn (array $participant): int => (int) ($participant['person_id'] ?? 0), $participants ?? []);
$participantEmails = array_filter(array_map(fn (array $participant): string => strtolower((string) ($participant['email'] ?? '')), $participants ?? []));
$userEmails = array_filter(array_map(fn (array $user): string => strtolower((string) ($user['email'] ?? '')), $users));
$directoryPeople = array_values(array_filter($people ?? [], function (array $person) use ($userEmails, $participantIds): bool {
    $email = strtolower((string) ($person['email'] ?? ''));
    return !in_array((int) ($person['id'] ?? 0), $participantIds, true)
        && ($email === '' || !in_array($email, $userEmails, true));
}));
$directoryUsers = array_values(array_filter($users, function (array $user) use ($participantEmails): bool {
    $email = strtolower((string) ($user['email'] ?? ''));
    return $email === '' || !in_array($email, $participantEmails, true);
}));
$registrationRole = function (array $record, string $fallback = 'person'): string {
    $role = strtolower((string) ($record['role_slug'] ?? $record['role_name'] ?? $fallback));
    $role = strtr($role, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ç' => 'c']);

    if (str_contains($role, 'estudante') || str_contains($role, 'student')) {
        return 'estudante';
    }
    if (str_contains($role, 'voluntario') || str_contains($role, 'volunt')) {
        return 'voluntario';
    }
    if (str_contains($role, 'jornalista') || str_contains($role, 'colunista')) {
        return 'comunicacao';
    }
    if (str_contains($role, 'admin') || str_contains($role, 'diretor') || str_contains($role, 'equipe')) {
        return 'equipe';
    }

    return $fallback;
};
?>

<section class="panel">
    <div class="section-heading">
        <h2>Resumo da lista</h2>
        <span><?= e((string) count($participants)) ?> registro(s)</span>
    </div>
    <div class="events-admin-metrics participant-metrics">
        <article><span>Vagas livres</span><strong><?= $capacity ? e((string) $remainingSlots) : '-' ?></strong><small><?= $capacity ? e((string) $capacity) . ' vagas totais' : 'sem limite definido' ?></small></article>
        <article><span>Pendentes</span><strong><?= e((string) ($participantStats['pendente'] ?? 0)) ?></strong><small>aguardando confirmação</small></article>
        <article><span>Inscritos</span><strong><?= e((string) ($participantStats['inscrito'] ?? 0)) ?></strong><small>aguardando presença</small></article>
        <article><span>Presentes</span><strong><?= e((string) ($participantStats['presente'] ?? 0)) ?></strong><small>confirmados no evento</small></article>
        <article><span>Ausentes</span><strong><?= e((string) ($participantStats['ausente'] ?? 0)) ?></strong><small>não compareceram</small></article>
        <article><span>Cancelados</span><strong><?= e((string) ($participantStats['cancelado'] ?? 0)) ?></strong><small>não validados</small></article>
    </div>
    <?php if ($capacity): ?>
        <div class="event-capacity-bar" aria-label="Ocupação de vagas">
            <div style="width: <?= e((string) $slotPercent) ?>%"></div>
        </div>
        <p class="event-capacity-caption"><?= e((string) $occupiedSlots) ?> vaga(s) ocupada(s) de <?= e((string) $capacity) ?>. <?= e((string) $remainingSlots) ?> livre(s).</p>
    <?php endif; ?>
</section>

<details class="panel person-register-panel">
    <summary class="person-register-head">
        <div>
            <span>Nova inscrição</span>
            <h2>Cadastrar pessoa no evento</h2>
        </div>
        <strong>Cadastro + vínculo</strong>
    </summary>
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
                        <input class="form-control" name="birth_date" type="text" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="bday" data-birth-date-input>
                    </div>
                    <div>
                        <label class="form-label">Telefone</label>
                        <input class="form-control" name="phone" inputmode="tel" autocomplete="tel" data-phone-input>
                    </div>
                    <div>
                        <label class="form-label">WhatsApp</label>
                        <input class="form-control" name="whatsapp" inputmode="tel" autocomplete="tel" data-phone-input>
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
                        <input class="form-control" name="guardian_phone" inputmode="tel" autocomplete="tel" data-phone-input>
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
                    <input class="form-check-input" type="checkbox" name="image_authorized" value="1">
                    <span><strong>Autoriza uso de imagem</strong><small>Nos termos da LGPD, permite fotos e vídeos em divulgação institucional.</small></span>
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
</details>

<section class="panel registration-directory-panel registration-search-panel">
    <div class="section-heading">
        <div>
            <span>Busca inteligente</span>
            <h2>Adicionar cadastrado ao evento</h2>
        </div>
        <span>Digite para buscar em pessoas, estudantes e usuários</span>
    </div>
    <div class="registration-directory-tools">
        <div class="registration-search-field">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input class="form-control" type="search" placeholder="Digite nome, e-mail, CPF, WhatsApp ou perfil" data-registration-directory-search>
        </div>
        <div class="registration-directory-filters" role="group" aria-label="Filtrar cadastros">
            <button type="button" class="is-active" data-registration-filter="all">Todos</button>
            <button type="button" data-registration-filter="estudante">Estudantes</button>
            <button type="button" data-registration-filter="user">Usuários</button>
            <button type="button" data-registration-filter="person">Pessoas</button>
            <button type="button" data-registration-filter="voluntario">Voluntários</button>
            <button type="button" data-registration-filter="comunicacao">Comunicação</button>
            <button type="button" data-registration-filter="equipe">Equipe</button>
        </div>
        <span><strong data-registration-directory-count>0</strong> resultado(s)</span>
    </div>
    <div class="registration-search-empty" data-registration-directory-start>
        <i class="bi bi-person-plus" aria-hidden="true"></i>
        <strong>Pesquise para adicionar alguém</strong>
        <span>Os cadastros disponíveis ficam ocultos até você digitar, evitando uma lista grande e confusa.</span>
    </div>
    <div class="admin-card-list compact-list registration-directory-list" data-registration-directory>
        <?php foreach ($directoryUsers as $user): ?>
            <?php $roleFilter = $registrationRole($user, 'user'); ?>
            <article class="admin-list-card internal-list-card registered-user-card" data-registration-card data-registration-type="user" data-registration-role="<?= e($roleFilter) ?>" data-registration-search="<?= e(($user['name'] ?? '') . ' ' . ($user['email'] ?? '') . ' usuario login ' . ($user['role_name'] ?? '') . ' ' . $roleFilter) ?>">
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($user['name']) ?></strong>
                        <span class="state-pill is-active"><?= e($user['role_name'] ?? 'Usuário') ?></span>
                    </div>
                    <dl class="admin-list-meta">
                        <div><dt>E-mail/login</dt><dd><?= e($user['email'] ?? '-') ?></dd></div>
                        <div><dt>Origem</dt><dd>Login/usuário do sistema</dd></div>
                    </dl>
                </div>
                <form class="participant-add-form" method="post" action="<?= e(url('/admin/library-events/participants/user?id=' . $event['id'])) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                    <select class="form-select form-select-sm" name="status">
                        <option value="inscrito">Inscrito</option>
                        <option value="pendente">Pendente</option>
                        <option value="presente">Presente</option>
                        <option value="ausente">Ausente</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <button class="btn btn-sm btn-primary icon-btn"><i class="bi bi-person-plus" aria-hidden="true"></i>Adicionar</button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php foreach ($directoryPeople as $person): ?>
            <article class="admin-list-card internal-list-card existing-person-card" data-registration-card data-registration-type="person" data-registration-role="person" data-registration-search="<?= e(($person['full_name'] ?? '') . ' ' . ($person['email'] ?? '') . ' ' . ($person['cpf'] ?? '') . ' ' . ($person['whatsapp'] ?? '') . ' pessoa cadastro interno') ?>">
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($person['full_name']) ?></strong>
                        <span class="state-pill is-muted">Pessoa já cadastrada</span>
                    </div>
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
        <?php if (empty($directoryUsers) && empty($directoryPeople)): ?>
            <div class="empty-state">Nenhum cadastro encontrado.</div>
        <?php endif; ?>
        <div class="empty-state" data-registration-directory-empty hidden>Nenhum cadastro encontrado para esta busca e filtro.</div>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-people" aria-hidden="true"></i> Participantes vinculados</h2>
        <div class="participant-bulk-actions">
            <span><?= e((string) count($participants)) ?> participante(s)</span>
            <?php if ($participants): ?>
                <form id="bulk-status-form" class="participant-status-bulk-form" method="post" action="<?= e(url('/admin/library-events/participants/bulk-status?id=' . $event['id'])) ?>">
                    <?= csrf_field() ?>
                    <select class="form-select form-select-sm" name="status" aria-label="Novo status em lote">
                        <option value="inscrito">Inscrito</option>
                        <option value="presente">Presente</option>
                        <option value="ausente">Ausente</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="pendente">Pendente</option>
                    </select>
                    <button class="btn btn-sm btn-primary icon-btn" name="bulk_action" value="selected" type="submit"><i class="bi bi-check2-square" aria-hidden="true"></i>Aplicar nos marcados</button>
                    <button class="btn btn-sm btn-outline-primary icon-btn" name="bulk_action" value="all_pending" type="submit" onclick="return confirm('Confirmar todas as inscrições pendentes deste evento?');"><i class="bi bi-check2-all" aria-hidden="true"></i>Aceitar pendentes</button>
                </form>
                <button class="btn btn-sm btn-outline-secondary icon-btn" type="button" data-participant-select-all><i class="bi bi-ui-checks" aria-hidden="true"></i>Selecionar todos</button>
                <form method="post" action="<?= e(url('/admin/library-events/participants/remove-all?id=' . $event['id'])) ?>" onsubmit="return confirm('Remover TODOS os participantes deste evento? Esta ação não apaga os cadastros de pessoas, só desvincula deste evento.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="confirm_remove_all" value="REMOVER">
                    <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover todos</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="admin-card-list">
        <?php foreach ($participants as $participant): ?>
            <article class="admin-list-card internal-list-card participant-list-card">
                <label class="participant-select-box" title="Selecionar participante">
                    <input type="checkbox" form="bulk-status-form" name="person_ids[]" value="<?= e((string) $participant['person_id']) ?>" data-participant-select>
                    <span>Selecionar</span>
                </label>
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($participant['full_name']) ?></strong>
                        <span class="state-pill <?= in_array($participant['status'], ['inscrito', 'presente'], true) ? 'is-active' : 'is-muted' ?>"><?= e(ucfirst($participant['status'])) ?></span>
                    </div>
                    <dl class="admin-list-meta">
                        <div><dt>WhatsApp</dt><dd><?= e($participant['whatsapp'] ?? '-') ?></dd></div>
                        <div><dt>E-mail</dt><dd><?= e($participant['email'] ?? '-') ?></dd></div>
                        <div><dt>Bairro</dt><dd><?= e($participant['district'] ?? '-') ?></dd></div>
                        <div><dt>Imagem</dt><dd><?= !empty($participant['image_authorized']) ? 'Autorizada' : 'Não autorizada' ?></dd></div>
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
                    <?php if ($link = $whatsappLink($participant['whatsapp'] ?? $participant['phone'] ?? null, (string) $participant['full_name'], (string) $event['title'])): ?>
                        <a class="btn btn-sm btn-outline-success icon-btn" href="<?= e($link) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i>WhatsApp</a>
                    <?php endif; ?>
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
