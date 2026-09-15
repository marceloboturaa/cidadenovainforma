<?php
$totals = ['issued' => 0, 'pending' => 0, 'revoked' => 0];
foreach ($courses as $course) foreach ($totals as $key => $value) $totals[$key] += (int) $course[$key];
?>
<div class="page-heading">
    <div><p>Ensino e eventos</p><h1>Central de certificados</h1><p><?= $ownCoursesOnly ? 'Acompanhe os certificados dos cursos sob sua responsabilidade.' : 'Acompanhe os certificados e as solicitações dos cursos.' ?></p></div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/education/certificates')) ?>">Meus certificados</a>
</div>
<?php if ($canViewCourses): ?>
<div class="dashboard-grid">
    <?php foreach (['issued' => 'Emitidos', 'pending' => 'Aguardando revisão', 'revoked' => 'Revogados'] as $key => $label): ?>
    <article class="metric-card"><span><?= e($label) ?></span><strong><?= $totals[$key] ?></strong><a href="<?= e(url('/admin/education/certificate-report?status=' . $key)) ?>">Consultar <?= e(mb_strtolower($label)) ?></a></article>
    <?php endforeach; ?>
</div>
<section class="panel">
    <div class="section-heading"><h2><?= $ownCoursesOnly ? 'Meus cursos' : 'Cursos' ?></h2><span>Configuração, revisão e emissão</span></div>
    <p>Na liberação manual, abra as solicitações para conferir e autorizar. Na automática, o certificado é liberado quando o aluno cumpre os requisitos configurados.</p>
    <?php if (!$courses): ?><div class="empty-state">Nenhum curso sob sua responsabilidade.</div><?php else: ?>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th scope="col">Curso</th><th scope="col">Liberação</th><th scope="col">Pendentes</th><th scope="col">Emitidos</th><th scope="col">Ações</th></tr></thead>
        <tbody><?php foreach ($courses as $course): ?><tr>
            <td><?= e($course['title']) ?></td>
            <td><?= empty($course['certificate_enabled']) ? 'Desativada' : (!empty($course['certificate_auto_release']) ? 'Automática' : 'Revisão da equipe') ?></td>
            <td><?= (int) $course['pending'] ?></td><td><?= (int) $course['issued'] ?></td>
            <td>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-certificate')) ?>">Configurar</a>
                <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/education/certificate-report?status=pending&course_id=' . $course['id'])) ?>">Revisar solicitações</a>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/education/certificate-report?course_id=' . $course['id'])) ?>">Ver emitidos</a>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
    <h2>Outras áreas de certificados</h2>
    <div class="dashboard-grid">
        <?php if (\App\Core\Auth::can('event_participants.manage')): ?><article class="metric-card"><strong>Eventos</strong><p>Participação e coordenação, com os dois certificados por pessoa quando aplicável.</p><a href="<?= e(url('/admin/registrations')) ?>">Escolher evento e inscritos</a></article><?php endif; ?>
        <?php if ($canAdminister): ?>
        <article class="metric-card"><strong>Reconhecimentos</strong><p>Emitir e gerenciar certificados de reconhecimento.</p><a href="<?= e(url('/admin/education/recognitions')) ?>">Abrir reconhecimentos</a></article>
        <article class="metric-card"><strong>Administração</strong><p>Instituições emissoras e gestão institucional.</p><a href="<?= e(url('/admin/education/certificate-administration')) ?>">Abrir administração</a></article>
        <?php endif; ?>
        <article class="metric-card"><strong>Verificação pública</strong><p>Consultar autenticidade pelo código, com identificação abreviada.</p><a href="<?= e(url('/certificados')) ?>" target="_blank" rel="noopener">Verificar um certificado</a></article>
    </div>
</section>
