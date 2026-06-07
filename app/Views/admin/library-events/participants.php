<div class="page-heading participant-page-heading">
    <div>
        <p>Inscrições e participantes</p>
        <h1><?= e($event['title']) ?></h1>
    </div>
    <div class="export-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/library-events/edit?id=' . $event['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
    </div>
</div>

<section class="panel participant-report-panel">
    <div>
        <span>Relatórios do evento</span>
        <strong>Exportar listas</strong>
    </div>
    <form class="participant-export-form" method="get" action="<?= e(url('/admin/library-events/participants/export')) ?>">
        <input type="hidden" name="id" value="<?= e((string) $event['id']) ?>">
        <label>
            <span>Tipo de lista</span>
            <select class="form-select" name="report">
                <option value="participants">Cadastro completo</option>
                <option value="names">Somente nomes dos inscritos</option>
                <option value="attendance">Lista de chamada/presença</option>
            </select>
        </label>
        <label>
            <span>Situação</span>
            <select class="form-select" name="status" data-participant-export-status>
                <option value="">Todos os cadastros</option>
                <option value="pendente">Somente pendentes</option>
                <option value="inscrito">Somente inscritos</option>
                <option value="presente">Somente presentes</option>
                <option value="ausente">Somente ausentes</option>
                <option value="cancelado">Somente cancelados</option>
            </select>
        </label>
        <label>
            <span>Data da chamada</span>
            <input class="form-control" type="date" name="attendance_date" value="<?= e((string) ($attendanceDate ?? date('Y-m-d'))) ?>">
        </label>
        <label>
            <span>Status da chamada</span>
            <select class="form-select" name="attendance_status">
                <option value="">Todos da data</option>
                <option value="presente">Somente presentes</option>
                <option value="ausente">Somente ausentes</option>
                <option value="justificado">Somente justificados</option>
            </select>
        </label>
        <div class="participant-export-buttons">
            <button class="btn btn-outline-secondary icon-btn" name="format" value="csv"><i class="bi bi-filetype-csv" aria-hidden="true"></i>CSV</button>
            <button class="btn btn-primary icon-btn" name="format" value="pdf"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>PDF</button>
        </div>
    </form>
</section>
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

<details class="panel participant-attendance-panel">
    <summary class="section-heading">
        <h2><i class="bi bi-calendar-check" aria-hidden="true"></i> Chamada do dia</h2>
        <span><?= e(date('d/m/Y', strtotime((string) ($attendanceDate ?? date('Y-m-d'))))) ?></span>
    </summary>
    <form class="participant-attendance-date-form" method="get" action="<?= e(url('/admin/library-events/participants')) ?>">
        <input type="hidden" name="id" value="<?= e((string) $event['id']) ?>">
        <label>
            <span>Data do encontro</span>
            <input class="form-control" type="date" name="attendance_date" value="<?= e((string) ($attendanceDate ?? date('Y-m-d'))) ?>">
        </label>
        <button class="btn btn-outline-primary icon-btn"><i class="bi bi-search" aria-hidden="true"></i>Ver chamada</button>
    </form>

    <?php if (!empty($attendanceDates)): ?>
        <div class="attendance-history">
            <?php foreach ($attendanceDates as $dateRow): ?>
                <a href="<?= e(url('/admin/library-events/participants?id=' . $event['id'] . '&attendance_date=' . $dateRow['attendance_date'])) ?>">
                    <strong><?= e(date('d/m/Y', strtotime((string) $dateRow['attendance_date']))) ?></strong>
                    <span><?= e((string) ($dateRow['presentes'] ?? 0)) ?> presente(s)</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form class="participant-attendance-date-form" method="post" action="<?= e(url('/admin/library-events/participants/attendance-date?id=' . $event['id'])) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="old_attendance_date" value="<?= e((string) ($attendanceDate ?? date('Y-m-d'))) ?>">
        <label>
            <span>Alterar data desta chamada para</span>
            <input class="form-control" type="date" name="new_attendance_date" value="<?= e((string) ($attendanceDate ?? date('Y-m-d'))) ?>">
        </label>
        <button class="btn btn-outline-secondary icon-btn"><i class="bi bi-calendar2-week" aria-hidden="true"></i>Alterar data</button>
    </form>

    <?php if (!empty($attendanceRows)): ?>
        <form class="participant-attendance-form" method="post" action="<?= e(url('/admin/library-events/participants/attendance?id=' . $event['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="attendance_date" value="<?= e((string) ($attendanceDate ?? date('Y-m-d'))) ?>">
            <div class="attendance-row-list">
                <?php foreach ($attendanceRows as $row): ?>
                    <?php $attendanceStatus = (string) ($row['attendance_status'] ?? 'presente'); ?>
                    <article class="attendance-row">
                        <div>
                            <strong><?= e($row['full_name']) ?></strong>
                            <span><?= e($row['email'] ?: ($row['whatsapp'] ?: '-')) ?></span>
                        </div>
                        <select class="form-select form-select-sm" name="attendance[<?= e((string) $row['person_id']) ?>][status]" aria-label="Presença de <?= e($row['full_name']) ?>">
                            <option value="presente" <?= selected('presente', $attendanceStatus) ?>>Presente</option>
                            <option value="ausente" <?= selected('ausente', $attendanceStatus) ?>>Ausente</option>
                            <option value="justificado" <?= selected('justificado', $attendanceStatus) ?>>Justificado</option>
                        </select>
                        <input class="form-control form-control-sm" name="attendance[<?= e((string) $row['person_id']) ?>][notes]" value="<?= e($row['attendance_notes'] ?? '') ?>" placeholder="Observação">
                    </article>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar chamada do dia</button>
        </form>
    <?php else: ?>
        <div class="empty-state">Nenhum participante ativo para chamada nesta data.</div>
    <?php endif; ?>
</details>

<section class="panel participant-email-panel">
    <div class="section-heading">
        <h2><i class="bi bi-envelope-paper" aria-hidden="true"></i> Enviar documento por e-mail</h2>
        <span>todos, selecionados ou chamada do dia</span>
    </div>
    <form id="participant-email-form" class="participant-email-form" method="post" action="<?= e(url('/admin/library-events/participants/email-document?id=' . $event['id'])) ?>" enctype="multipart/form-data" data-participant-email-form>
        <?= csrf_field() ?>
        <label>
            <span>Destinatários</span>
            <select class="form-select" name="recipient_mode" data-participant-email-mode>
                <option value="all">Todos os participantes ativos</option>
                <option value="selected">Somente marcados na lista abaixo</option>
                <option value="attendance">Participantes da chamada do dia</option>
            </select>
        </label>
        <label>
            <span>Data da chamada</span>
            <input class="form-control" type="date" name="attendance_date" value="<?= e((string) ($attendanceDate ?? date('Y-m-d'))) ?>">
        </label>
        <label>
            <span>Status na chamada</span>
            <select class="form-select" name="attendance_status">
                <option value="">Todos da data</option>
                <option value="presente">Presentes</option>
                <option value="ausente">Ausentes</option>
                <option value="justificado">Justificados</option>
            </select>
        </label>
        <label>
            <span>Assunto</span>
            <input class="form-control" name="subject" value="<?= e('Documento do evento ' . ($event['title'] ?? '')) ?>">
        </label>
        <label class="participant-email-message">
            <span>Mensagem</span>
            <textarea class="form-control" name="message" rows="3">Segue o documento do evento.</textarea>
        </label>
        <label>
            <span>Documento</span>
            <input class="form-control" type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" required>
        </label>
        <label>
            <span>Título público</span>
            <input class="form-control" name="public_document_title" value="<?= e('Documento do evento - ' . ($event['title'] ?? '')) ?>">
        </label>
        <label class="event-registration-check">
            <input type="checkbox" name="publish_public_document" value="1">
            <span>Publicar também em Documentos</span>
        </label>
        <label class="event-registration-check">
            <input type="checkbox" name="public_document_download" value="1" checked>
            <span>Permitir baixar no público</span>
        </label>
        <button class="btn btn-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar e-mail</button>
    </form>
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
                <label class="form-label">Como soube do evento?</label>
                <select class="form-select" name="heard_about">
                    <option value="">Não informado</option>
                    <option value="Internet">Internet</option>
                    <option value="Redes sociais">Redes sociais</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Indicação de amigo/familiar">Indicação de amigo/familiar</option>
                    <option value="Escola ou instituição">Escola ou instituição</option>
                    <option value="Comunidade/igreja">Comunidade/igreja</option>
                    <option value="Outro">Outro</option>
                </select>
                <label class="form-label">O que espera do evento?</label>
                <textarea class="form-control" name="event_expectations" rows="3"></textarea>
                <?php if (!empty($event['registration_question_label'])): ?>
                    <label class="form-label"><?= e($event['registration_question_label']) ?></label>
                    <textarea class="form-control" name="registration_extra_answer" rows="3"></textarea>
                <?php endif; ?>
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

<section class="panel participant-linked-panel">
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
    <div class="registration-directory-tools participant-linked-tools">
        <div class="registration-search-field">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input class="form-control" type="search" placeholder="Buscar por nome, e-mail, WhatsApp, bairro ou observacao" data-linked-participant-search>
        </div>
        <div class="registration-directory-filters" role="group" aria-label="Filtrar participantes vinculados">
            <button type="button" class="is-active" data-linked-participant-filter="all">Todos</button>
            <button type="button" data-linked-participant-filter="pendente">Pendentes</button>
            <button type="button" data-linked-participant-filter="inscrito">Inscritos</button>
            <button type="button" data-linked-participant-filter="presente">Presentes</button>
            <button type="button" data-linked-participant-filter="ausente">Ausentes</button>
            <button type="button" data-linked-participant-filter="cancelado">Cancelados</button>
        </div>
        <button class="btn btn-sm btn-outline-secondary icon-btn" type="button" data-linked-participant-limit aria-pressed="false"><i class="bi bi-list-ol" aria-hidden="true"></i>Mostrar todos</button>
        <span><strong data-linked-participant-count><?= e((string) count($participants)) ?></strong> resultado(s)</span>
    </div>
    <div class="admin-card-list" data-linked-participants>
        <?php foreach ($participants as $participant): ?>
            <?php
            $participantSearchText = implode(' ', [
                $participant['full_name'] ?? '',
                $participant['email'] ?? '',
                $participant['phone'] ?? '',
                $participant['whatsapp'] ?? '',
                $participant['district'] ?? '',
                $participant['status'] ?? '',
                $participant['notes'] ?? '',
                $participant['heard_about'] ?? '',
                $participant['event_expectations'] ?? '',
                $participant['registration_extra_answer'] ?? '',
            ]);
            ?>
            <article class="admin-list-card internal-list-card participant-list-card" data-linked-participant-card data-linked-participant-status="<?= e((string) $participant['status']) ?>" data-linked-participant-search="<?= e($participantSearchText) ?>">
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
                        <div><dt>Soube por</dt><dd><?= e($participant['heard_about'] ?? '-') ?></dd></div>
                    </dl>
                    <?php if (!empty($participant['event_expectations'])): ?>
                        <p class="admin-list-description"><strong>Espera do evento:</strong> <?= e(text_excerpt($participant['event_expectations'], 180)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($participant['registration_extra_answer'])): ?>
                        <p class="admin-list-description"><strong><?= e($event['registration_question_label'] ?? 'Resposta do evento') ?>:</strong> <?= e(text_excerpt($participant['registration_extra_answer'], 180)) ?></p>
                    <?php endif; ?>
                </div>
                <div class="participant-management-actions">
                    <form class="participant-add-form" method="post" action="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="person_id" value="<?= e((string) $participant['person_id']) ?>">
                        <select class="form-select form-select-sm participant-status-field" name="status">
                            <?php foreach (['pendente' => 'Pendente', 'inscrito' => 'Inscrito', 'presente' => 'Presente', 'ausente' => 'Ausente', 'cancelado' => 'Cancelado'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= selected($value, (string) $participant['status']) ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control form-control-sm participant-notes-field" name="notes" value="<?= e($participant['notes'] ?? '') ?>" placeholder="Observação da inscrição">
                        <select class="form-select form-select-sm participant-source-field" name="heard_about" aria-label="Como soube do evento">
                            <?php foreach (['' => 'Não informado', 'Internet' => 'Internet', 'Redes sociais' => 'Redes sociais', 'WhatsApp' => 'WhatsApp', 'Indicação de amigo/familiar' => 'Indicação', 'Escola ou instituição' => 'Escola/instituição', 'Comunidade/igreja' => 'Comunidade/igreja', 'Outro' => 'Outro'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= selected($value, (string) ($participant['heard_about'] ?? '')) ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control form-control-sm participant-expectation-field" name="event_expectations" value="<?= e($participant['event_expectations'] ?? '') ?>" placeholder="O que espera do evento">
                        <input class="form-control form-control-sm participant-extra-answer-field" name="registration_extra_answer" value="<?= e($participant['registration_extra_answer'] ?? '') ?>" placeholder="Resposta da pergunta">
                        <button class="btn btn-sm btn-primary icon-btn participant-save-button"><i class="bi bi-check2" aria-hidden="true"></i>Salvar</button>
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
        <div class="empty-state" data-linked-participant-empty hidden>Nenhum participante encontrado com este filtro.</div>
    </div>
</section>
