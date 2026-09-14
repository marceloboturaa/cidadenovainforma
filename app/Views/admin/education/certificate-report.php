<?php
$pageUrl = static fn (int $page): string => url('/admin/education/certificate-report?' . http_build_query(['q' => $search, 'course_id' => $courseId, 'page' => $page]));
?>
<div class="page-heading">
    <div>
        <p>Ensino</p>
        <h1>Painel de certificados</h1>
        <p><?= $ownCoursesOnly ? 'Certificados emitidos nos seus cursos.' : 'Certificados emitidos em todos os cursos.' ?></p>
    </div>
</div>
<div class="dashboard-grid">
    <?php foreach (['certificates' => 'Certificados válidos', 'recipients' => 'Destinatários', 'courses' => 'Cursos com certificados'] as $key => $label): ?>
        <article class="metric-card"><span><?= e($label) ?></span><strong><?= e((string) $report['totals'][$key]) ?></strong><small>Conforme os filtros selecionados</small></article>
    <?php endforeach; ?>
</div>
<section class="panel">
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
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/admin/education/certificate-report')) ?>">Limpar filtros</a>
        </div>
    </form>
    <div class="section-heading"><h2>Quem recebeu certificado</h2><span><?= e((string) $report['totals']['certificates']) ?> certificado(s)</span></div>
    <p class="field-hint">A lista mostra certificados emitidos e válidos. A emissão não confirma que o destinatário abriu ou baixou o documento.</p>
    <?php if (!$report['rows']): ?>
        <div class="empty-state">Nenhum certificado emitido encontrado para estes filtros.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th scope="col">Destinatário</th><th scope="col">Curso</th><th scope="col">Professor</th><th scope="col">Emissão</th><th scope="col">Código</th><th scope="col">Consulta</th></tr></thead>
                <tbody>
                    <?php foreach ($report['rows'] as $row): ?>
                        <tr>
                            <td><?= e($row['recipient_name']) ?></td>
                            <td><?= e($row['course_title']) ?></td>
                            <td><?= e($row['teacher_name'] ?? 'Não atribuído') ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($row['issued_at']))) ?></td>
                            <td><?= e($row['verification_code']) ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/certificado/' . rawurlencode($row['verification_code']))) ?>" target="_blank" rel="noopener">Verificar</a></td>
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
