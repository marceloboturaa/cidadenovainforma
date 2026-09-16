<?php
$reportStatus = $reportStatus ?? 'issued';
$emailStatus = $emailStatus ?? '';
$emailLabels = ['sent' => 'Enviado ao serviço de e-mail', 'pending' => 'Pendente de envio', 'failed' => 'Falha no envio', 'unavailable' => 'Sem conta ativa para envio', 'not_released' => 'Certificado não liberado'];
$statusLabels = ['issued' => 'Emitidos', 'pending' => 'Aguardando revisão', 'revoked' => 'Revogados'];
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
<div class="dashboard-grid">
    <?php foreach (['certificates' => 'Certificados encontrados', 'recipients' => 'Destinatários', 'courses' => 'Cursos com certificados'] as $key => $label): ?>
        <article class="metric-card"><span><?= e($label) ?></span><strong><?= e((string) $report['totals'][$key]) ?></strong><small>Conforme os filtros selecionados</small></article>
    <?php endforeach; ?>
</div>
<section class="panel">
    <h2>Avisos de certificado por e-mail</h2>
    <p>Confira o envio do link do certificado para cada estudante. “Enviado” significa que o serviço de e-mail aceitou a mensagem; não confirma entrega na caixa de entrada ou leitura.</p>
    <div class="dashboard-grid">
        <?php foreach ($emailLabels as $state => $label): ?>
            <article class="metric-card"><span><?= e($label) ?></span><strong><?= (int) ($report['totals']['email_' . $state] ?? 0) ?></strong><small>Conforme os filtros; inclui todas as páginas</small></article>
        <?php endforeach; ?>
    </div>
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
    <div class="section-heading"><h2><?= e($statusLabels[$reportStatus]) ?></h2><span><?= e((string) $report['totals']['certificates']) ?> certificado(s)</span></div>
    <p class="field-hint"><?= $reportStatus === 'pending' ? 'Abra o certificado para conferir os dados antes de liberar ao estudante.' : 'A emissão não confirma que o destinatário abriu ou baixou o documento.' ?></p>
    <?php if (!$report['rows']): ?>
        <div class="empty-state">Nenhum certificado encontrado para estes filtros.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th scope="col">Destinatário</th><th scope="col">Curso</th><th scope="col">Professor</th><th scope="col"><?= $reportStatus === 'pending' ? 'Solicitação' : 'Emissão' ?></th><th scope="col">Código</th><th scope="col">Aviso por e-mail</th><th scope="col">Consulta</th></tr></thead>
                <tbody>
                    <?php foreach ($report['rows'] as $row): ?>
                        <tr>
                            <td><?= e($row['recipient_name']) ?><br><small><?= e($row['recipient_email'] ?: 'Sem e-mail cadastrado') ?></small></td>
                            <td><?= e($row['course_title']) ?></td>
                            <td><?= e($row['teacher_name'] ?? 'Não atribuído') ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($row['issued_at']))) ?></td>
                            <td><?= e($row['verification_code']) ?></td>
                            <td>
                                <strong><?= e($emailLabels[$row['email_status']]) ?></strong>
                                <?php if ($row['email_sent_at']): ?><br><small>Envio aceito em <?= e(date('d/m/Y H:i', strtotime($row['email_sent_at']))) ?></small><?php endif; ?>
                                <br><small><?= (int) $row['email_attempts'] ?> tentativa(s)</small>
                                <?php if ($row['email_attempted_at']): ?><br><small>Última tentativa: <?= e(date('d/m/Y H:i', strtotime($row['email_attempted_at']))) ?></small><?php endif; ?>
                                <?php if ($row['email_status'] === 'failed'): ?><br><small><?= e($row['last_error']) ?></small><?php endif; ?>
                            </td>
                            <td><a class="btn btn-sm btn-primary" href="<?= e(url('/admin/education/certificate?certificate_id=' . $row['id'])) ?>"><?= $reportStatus === 'pending' ? 'Conferir e liberar' : 'Abrir certificado' ?></a></td>
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
</section>
