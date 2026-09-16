<?php
$reportStatus = $reportStatus ?? 'issued';
$emailStatus = $emailStatus ?? '';
$reportError = $reportError ?? null;
$emailLabels = ['sent' => 'Enviado ao serviço de e-mail', 'pending' => 'Pendente de envio', 'failed' => 'Falha no envio', 'unavailable' => 'Sem conta ativa para envio', 'not_released' => 'Certificado não liberado'];
$statusLabels = ['all' => 'Todas as situações', 'issued' => 'Emitidos', 'pending' => 'Aguardando revisão', 'revoked' => 'Revogados'];
$pageUrl = static fn (int $page): string => url('/admin/education/certificate-report?' . http_build_query(['q' => $search, 'course_id' => $courseId, 'page' => $page, 'status' => $reportStatus, 'email_status' => $emailStatus]));
?>
<div class="page-heading">
    <div>
        <p>Ensino</p>
        <h1><?= e($statusLabels[$reportStatus]) ?></h1>
        <p><?= $ownCoursesOnly ? 'Certificados dos seus cursos.' : 'Certificados de todos os cursos.' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/education/certificate-center')) ?>">Central de certificados</a>
</div>
<?php if ($reportError): ?><div class="alert alert-danger" role="alert"><?= e($reportError) ?></div><?php endif; ?>
<?php if (!$reportError): ?>
<div class="dashboard-grid">
    <?php foreach (['certificates' => 'Certificados encontrados', 'recipients' => 'Destinatários', 'courses' => 'Cursos com certificados'] as $key => $label): ?>
        <article class="metric-card"><span><?= e($label) ?></span><strong><?= e((string) $report['totals'][$key]) ?></strong><small>Conforme os filtros selecionados</small></article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<section class="panel">
    <h2>Avisos de certificado por e-mail</h2>
    <p>Confira o envio do link do certificado para cada estudante. “Enviado” significa que o serviço de e-mail aceitou a mensagem; não confirma entrega na caixa de entrada ou leitura.</p>
    <?php if (!$reportError): ?><div class="dashboard-grid">
        <?php foreach ($emailLabels as $state => $label): ?>
            <article class="metric-card"><span><?= e($label) ?></span><strong><?= (int) ($report['totals']['email_' . $state] ?? 0) ?></strong><small>Conforme os filtros; inclui todas as páginas</small></article>
        <?php endforeach; ?>
    </div><?php endif; ?>
    <p class="field-hint">Este relatório mostra certificados existentes. Alunos sem certificado emitido não estão incluídos. Pendências e falhas são processadas pela rotina de avisos configurada na hospedagem.</p>
    <form method="get" action="<?= e(url('/admin/education/certificate-report')) ?>" class="row g-3 mb-4">
        <div class="col-md-5">
            <label class="form-label" for="certificate-search">Aluno ou código do certificado</label>
            <input class="form-control" id="certificate-search" name="q" maxlength="180" value="<?= e($search) ?>" placeholder="Digite o nome ou código">
        </div>
        <div class="col-md-5">
            <label class="form-label" for="certificate-course">Curso</label>
            <select class="form-select" id="certificate-course" name="course_id">
                <option value="0">Todos os cursos</option>
                <?php foreach ($report['courses'] as $option): ?>
                    <option value="<?= e((string) $option['id']) ?>" <?= (int) $option['id'] === $courseId ? 'selected' : '' ?>><?= e($option['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="certificate-status">Situação</label>
            <select class="form-select" id="certificate-status" name="status">
                <?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $value === $reportStatus ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="certificate-email-status">Envio do e-mail</label>
            <select class="form-select" id="certificate-email-status" name="email_status">
                <option value="">Todas as situações</option>
                <?php foreach ($emailLabels as $value => $label): ?><option value="<?= e($value) ?>" <?= $value === $emailStatus ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/admin/education/certificate-report')) ?>">Limpar filtros</a>
        </div>
    </form>
    <?php if (!$reportError): ?>
    <div class="section-heading"><h2><?= e($statusLabels[$reportStatus]) ?></h2><span><?= e((string) $report['totals']['certificates']) ?> certificado(s)</span></div>
    <p class="field-hint"><?= $reportStatus === 'pending' ? 'Abra o certificado para conferir os dados antes de liberar ao estudante.' : 'A emissão não confirma que o destinatário abriu ou baixou o documento.' ?></p>
    <?php if (!$report['rows']): ?>
        <div class="empty-state">Nenhum certificado encontrado para estes filtros.</div>
        <?php if ($reportStatus === 'issued' && $emailStatus === 'not_released'): ?>
            <p>“Emitidos” e “Certificado não liberado” são situações incompatíveis. Selecione “Todas as situações” no filtro Situação para consultar os certificados não liberados.</p>
        <?php endif; ?>
    <?php else: ?>
        <form id="notify-selected-certificates" method="post" action="<?= e(url('/admin/education/certificate/notify-selected')) ?>" class="heading-actions">
            <?= csrf_field() ?>
            <label><input type="checkbox" id="select-page-certificates"> Selecionar todos desta página</label>
            <button type="submit" class="btn btn-primary" id="notify-selected-button" disabled>Avisar selecionados (0)</button>
            <span>Selecione até 25 avisos pendentes ou com falha nesta página.</span>
        </form>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th scope="col">Destinatário</th><th scope="col">Curso</th><th scope="col">Professor</th><th scope="col"><?= $reportStatus === 'pending' ? 'Solicitação' : 'Emissão' ?></th><th scope="col">Código</th><th scope="col">Aviso por e-mail</th><th scope="col">Consulta</th></tr></thead>
                <tbody>
                    <?php foreach ($report['rows'] as $row): ?>
                        <tr>
                            <td>
                                <?php if (in_array($row['email_status'], ['pending', 'failed'], true)): ?>
                                    <input type="checkbox" class="certificate-selection" form="notify-selected-certificates" name="certificate_ids[]" value="<?= (int) $row['id'] ?>" aria-label="<?= e('Selecionar certificado de ' . $row['recipient_name']) ?>">
                                <?php endif; ?>
                                <?= e($row['recipient_name']) ?><br><small><?= e($row['recipient_email'] ?: 'Sem e-mail cadastrado') ?></small>
                            </td>
                            <td><?= e($row['course_title']) ?></td>
                            <td><?= e($row['teacher_name'] ?? 'Não atribuído') ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($row['issued_at']))) ?></td>
                            <td><?= e($row['verification_code']) ?><br><small><?= e($statusLabels[$row['certificate_status']]) ?></small></td>
                            <td>
                                <strong><?= e($emailLabels[$row['email_status']]) ?></strong>
                                <?php if ($row['email_sent_at']): ?><br><small>Envio aceito em <?= e(date('d/m/Y H:i', strtotime($row['email_sent_at']))) ?></small><?php endif; ?>
                                <br><small><?= (int) $row['email_attempts'] ?> tentativa(s)</small>
                                <?php if ($row['email_attempted_at']): ?><br><small>Última tentativa: <?= e(date('d/m/Y H:i', strtotime($row['email_attempted_at']))) ?></small><?php endif; ?>
                                <?php if ($row['email_status'] === 'failed'): ?><br><small><?= e($row['last_error']) ?></small><?php endif; ?>
                                <?php if (in_array($row['email_status'], ['pending', 'failed'], true)): ?>
                                    <form method="post" action="<?= e(url('/admin/education/certificate/notify')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="certificate_id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><?= $row['email_status'] === 'failed' ? 'Tentar avisar novamente' : 'Avisar estudante' ?></button>
                                    </form>
                                    <small>Intervalo mínimo de 15 minutos entre tentativas.</small>
                                <?php endif; ?>
                            </td>
                            <td><a class="btn btn-sm btn-primary" href="<?= e(url('/admin/education/certificate?certificate_id=' . $row['id'])) ?>"><?= $row['certificate_status'] === 'pending' ? 'Conferir e liberar' : 'Abrir certificado' ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <nav class="heading-actions" aria-label="Paginação dos certificados">
            <?php if ($report['page'] > 1): ?><a class="btn btn-outline-secondary" href="<?= e($pageUrl($report['page'] - 1)) ?>">Anterior</a><?php endif; ?>
            <span>Página <?= e((string) $report['page']) ?> de <?= e((string) $report['pages']) ?></span>
            <?php if ($report['page'] < $report['pages']): ?><a class="btn btn-outline-secondary" href="<?= e($pageUrl($report['page'] + 1)) ?>">Próxima</a><?php endif; ?>
        </nav>
    <?php endif; ?>
    <?php endif; ?>
</section>
<script>
(() => {
    const form = document.getElementById('notify-selected-certificates');
    if (!form) return;
    const boxes = [...document.querySelectorAll('.certificate-selection')];
    const all = document.getElementById('select-page-certificates');
    const button = document.getElementById('notify-selected-button');
    const update = () => {
        const count = boxes.filter(box => box.checked).length;
        button.disabled = count === 0;
        button.textContent = `Avisar selecionados (${count})`;
        all.disabled = boxes.length === 0;
        all.checked = count > 0 && count === boxes.length;
        all.indeterminate = count > 0 && count < boxes.length;
    };
    all.addEventListener('change', () => { boxes.forEach(box => box.checked = all.checked); update(); });
    boxes.forEach(box => box.addEventListener('change', update));
    form.addEventListener('submit', event => {
        if (!boxes.some(box => box.checked)) { event.preventDefault(); return; }
        button.disabled = true;
        button.textContent = 'Enviando avisos…';
    });
    window.addEventListener('pageshow', update);
    update();
})();
</script>
