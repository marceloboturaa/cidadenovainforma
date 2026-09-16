<?php
$totals = ['issued' => 0, 'pending' => 0, 'revoked' => 0];
foreach ($courses as $course) foreach ($totals as $key => $value) $totals[$key] += (int) $course[$key];
$metrics = [
    'issued' => ['Emitidos', 'Certificados disponíveis aos alunos', 'patch-check'],
    'pending' => ['Aguardando revisão', 'Solicitações para conferir e liberar', 'hourglass-split'],
    'revoked' => ['Revogados', 'Documentos que não estão vigentes', 'shield-exclamation'],
];
?>
<div class="certificate-hub">
    <header class="page-heading">
        <div class="certificate-hub-heading-copy">
            <p>Ensino e eventos</p>
            <h1>Central de certificados</h1>
            <p><?= $ownCoursesOnly ? 'Seus cursos, suas solicitações e cada conquista dos seus alunos em um só lugar.' : 'Acompanhe emissões, organize solicitações e gerencie os certificados em um só lugar.' ?></p>
            <div class="heading-actions">
                <?php if ($canViewCourses): ?><a class="btn btn-outline-primary" href="<?= e(url('/admin/education/certificate-report')) ?>">Conferir envios por e-mail</a><?php endif; ?>
                <?php if ($canViewCourses): ?><a class="btn btn-primary icon-btn" href="#certificate-hub-courses"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> <?= $ownCoursesOnly ? 'Ver meus cursos' : 'Ver cursos' ?></a><?php endif; ?>
                <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/certificates')) ?>"><i class="bi bi-person-badge" aria-hidden="true"></i> Meus certificados</a>
            </div>
        </div>
    </header>

    <?php if ($canViewCourses): ?>
    <section class="metric-grid certificate-hub-metrics" aria-label="Resumo dos certificados">
        <?php foreach ($metrics as $key => [$label, $hint, $icon]): ?>
        <a class="metric-card certificate-hub-metric is-<?= e($key) ?>" href="<?= e(url('/admin/education/certificate-report?status=' . $key)) ?>">
            <div class="certificate-hub-metric-top"><span><?= e($label) ?></span><i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i></div>
            <strong><?= e(number_format($totals[$key], 0, ',', '.')) ?></strong>
            <div class="certificate-hub-metric-bottom"><small><?= e($hint) ?></small><i class="bi bi-arrow-up-right" aria-hidden="true"></i></div>
        </a>
        <?php endforeach; ?>
    </section>

    <?php if ($totals['pending'] > 0): ?>
    <aside class="certificate-hub-notice">
        <i class="bi bi-inbox" aria-hidden="true"></i>
        <div><strong>Há <?= (int) $totals['pending'] ?> solicitação(ões) aguardando sua atenção</strong><p>Confira os dados do certificado antes de liberar para o aluno.</p></div>
        <a href="<?= e(url('/admin/education/certificate-report?status=pending')) ?>">Revisar agora <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </aside>
    <?php endif; ?>

    <section class="panel certificate-hub-section" id="certificate-hub-courses">
        <header class="section-heading certificate-hub-section-heading">
            <div><span class="education-kicker">Gestão por curso</span><h2><?= $ownCoursesOnly ? 'Meus cursos' : 'Cursos' ?> <span class="certificate-hub-count"><?= count($courses) ?></span></h2><p>Configure a emissão e acompanhe as solicitações de cada turma.</p></div>
        </header>
        <?php if (!$courses): ?>
        <div class="empty-state certificate-hub-empty"><i class="bi bi-journal-bookmark" aria-hidden="true"></i><h3>Nenhum curso por aqui</h3><p>Os cursos sob sua responsabilidade aparecerão nesta área.</p></div>
        <?php else: ?>
        <div class="certificate-hub-course-grid">
            <?php foreach ($courses as $course):
                $mode = empty($course['certificate_enabled']) ? 'disabled' : (!empty($course['certificate_auto_release']) ? 'auto' : 'manual');
                $modeLabels = ['disabled' => 'Emissão desativada', 'auto' => 'Liberação automática', 'manual' => 'Revisão da equipe'];
            ?>
            <article class="education-course-card certificate-hub-course">
                <div class="certificate-hub-course-top"><span class="certificate-hub-course-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></span><span class="certificate-hub-badge is-<?= e($mode) ?>"><?= e($modeLabels[$mode]) ?></span></div>
                <h3><?= e($course['title']) ?></h3>
                <div class="certificate-hub-course-numbers">
                    <div><strong><?= (int) $course['pending'] ?></strong><span>Aguardando revisão</span></div>
                    <div><strong><?= (int) $course['issued'] ?></strong><span>Emitidos</span></div>
                </div>
                <div class="certificate-hub-course-actions">
                    <a class="btn <?= (int) $course['pending'] > 0 ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(url('/admin/education/certificate-report?status=pending&course_id=' . $course['id'])) ?>"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Revisar solicitações</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/education/certificate-report?course_id=' . $course['id'])) ?>">Ver emitidos</a>
                </div>
                <a class="certificate-hub-configure" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-certificate')) ?>"><i class="bi bi-sliders" aria-hidden="true"></i> Configurar certificado <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="certificate-hub-help"><i class="bi bi-info-circle" aria-hidden="true"></i> Na liberação automática, o aluno precisa cumprir os requisitos configurados. Na manual, a equipe confere e autoriza cada solicitação.</p>
    </section>
    <?php endif; ?>

    <section class="panel certificate-hub-section">
        <header class="section-heading certificate-hub-section-heading"><div><span class="education-kicker">Acesso rápido</span><h2>Explore outras áreas</h2><p>Encontre a ferramenta certa para cada tipo de certificado.</p></div></header>
        <div class="certificate-hub-shortcuts">
            <?php
            $shortcuts = [];
            if (\App\Core\Auth::can('event_participants.manage')) $shortcuts[] = ['calendar-event', 'Eventos', 'Certificados de participação e coordenação.', '/admin/registrations'];
            if ($canAdminister) {
                $shortcuts[] = ['stars', 'Reconhecimentos', 'Valorize contribuições e trajetórias.', '/admin/education/recognitions'];
                $shortcuts[] = ['building', 'Administração', 'Instituições emissoras e gestão institucional.', '/admin/education/certificate-administration'];
            }
            $shortcuts[] = ['qr-code-scan', 'Verificação pública', 'Consulte a autenticidade pelo código do documento.', '/certificados'];
            foreach ($shortcuts as [$icon, $label, $description, $path]): ?>
            <a class="education-course-card certificate-hub-shortcut" href="<?= e(url($path)) ?>"<?= $path === '/certificados' ? ' target="_blank" rel="noopener"' : '' ?>>
                <span class="certificate-hub-shortcut-icon"><i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i></span>
                <div><h3><?= e($label) ?></h3><p><?= e($description) ?></p></div><i class="bi bi-arrow-up-right" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
