<div class="page-heading">
    <div>
        <p>Plataforma de ensino</p>
        <h1>Meu ensino</h1>
    </div>
    <?php if ($canManage): ?>
        <div class="heading-actions">
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/form-corrections')) ?>"><i class="bi bi-ui-checks" aria-hidden="true"></i>Corrigir formulários</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-journal-richtext" aria-hidden="true"></i>Gerenciar cursos</a>
        </div>
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
                    <?php if (($course['enrollment_status'] ?? 'approved') === 'pending'): ?>
                        <span class="state-pill is-muted">Aguardando aprovação</span>
                    <?php endif; ?>
                    <p><?= e(text_excerpt($course['summary'] ?? '', 150)) ?></p>
                </div>
                <?php if (($course['enrollment_status'] ?? 'approved') === 'pending'): ?>
                    <p class="field-hint mb-0">Sua inscrição neste curso está em modo de espera. Um professor ou coordenador precisa liberar o acesso às aulas.</p>
                    <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Acessar curso</a>
                <?php else: ?>
                    <div class="education-progress">
                        <span><?= e((string) $completedCount) ?>/<?= e((string) $lessonCount) ?> aula(s)</span>
                        <div><i style="width: <?= e((string) $progress) ?>%"></i></div>
                    </div>
                    <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-play-circle" aria-hidden="true"></i>Acessar curso</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$courses): ?>
        <div class="empty-state">Nenhum curso liberado para você ainda.</div>
    <?php endif; ?>
</section>
