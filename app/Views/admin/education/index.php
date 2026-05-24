<div class="page-heading">
    <div>
        <p>Plataforma de ensino</p>
        <h1>Meu ensino</h1>
    </div>
    <?php if ($canManage): ?>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-journal-richtext" aria-hidden="true"></i>Gerenciar cursos</a>
    <?php endif; ?>
</div>

<section class="education-grid">
    <?php foreach ($courses as $course): ?>
        <?php
            $lessonCount = (int) ($course['lesson_count'] ?? 0);
            $completedCount = (int) ($course['completed_count'] ?? 0);
            $progress = $lessonCount > 0 ? min(100, (int) round(($completedCount / $lessonCount) * 100)) : 0;
        ?>
        <article class="education-course-card">
            <?php if (!empty($course['cover_image'])): ?>
                <img src="<?= e(media_url($course['cover_image'])) ?>" alt="<?= e($course['title']) ?>" onerror="this.remove()">
            <?php endif; ?>
            <div class="education-course-body">
                <div>
                    <span class="education-kicker"><?= e($course['teacher_name'] ?? 'Curso') ?></span>
                    <h2><?= e($course['title']) ?></h2>
                    <p><?= e(text_excerpt($course['summary'] ?? '', 150)) ?></p>
                </div>
                <div class="education-progress">
                    <span><?= e((string) $completedCount) ?>/<?= e((string) $lessonCount) ?> aula(s)</span>
                    <div><i style="width: <?= e((string) $progress) ?>%"></i></div>
                </div>
                <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-play-circle" aria-hidden="true"></i>Acessar curso</a>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$courses): ?>
        <div class="empty-state">Nenhum curso liberado para você ainda.</div>
    <?php endif; ?>
</section>

<section class="panel education-my-certificates-panel">
    <div class="section-heading">
        <h2>Meus certificados</h2>
        <span><?= e((string) count($certificates ?? [])) ?> emitido(s)</span>
    </div>

    <?php if (!empty($certificates)): ?>
        <div class="education-certificate-list">
            <?php foreach ($certificates as $certificate): ?>
                <?php
                    $issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : '';
                    $certificateTitle = trim((string) ($certificate['certificate_title'] ?? ''));
                    if ($certificateTitle === '') {
                        $certificateTitle = 'Certificado de conclusao';
                    }
                ?>
                <article class="education-certificate-card">
                    <div class="education-certificate-icon">
                        <i class="bi bi-award" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span><?= e($certificateTitle) ?></span>
                        <h3><?= e($certificate['course_title'] ?? 'Curso') ?></h3>
                        <p>
                            Emitido em <?= e($issuedAt) ?>
                            <?php if (!empty($certificate['teacher_name'])): ?>
                                &middot; Professor: <?= e($certificate['teacher_name']) ?>
                            <?php endif; ?>
                        </p>
                        <small>C&oacute;digo <?= e($certificate['verification_code'] ?? '') ?></small>
                    </div>
                    <div class="education-certificate-card-actions">
                        <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/education/certificate?id=' . $certificate['course_id'])) ?>">
                            <i class="bi bi-eye" aria-hidden="true"></i>Ver
                        </a>
                        <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/certificado/' . $certificate['verification_code'])) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-patch-check" aria-hidden="true"></i>Validar
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Seus certificados emitidos aparecer&atilde;o aqui abaixo dos cursos.</div>
    <?php endif; ?>
</section>
