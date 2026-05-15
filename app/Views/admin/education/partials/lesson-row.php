<article class="education-playlist-row">
    <a class="education-playlist-main" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>">
        <span class="<?= !empty($lesson['completed_at']) ? 'is-complete' : '' ?>">
            <i class="bi <?= !empty($lesson['sequence_locked']) && !$canManage ? 'bi-lock-fill' : (!empty($lesson['completed_at']) ? 'bi-check-circle-fill' : 'bi-circle') ?>" aria-hidden="true"></i>
        </span>
        <span>
            <strong><?= e($lesson['title']) ?></strong>
            <?php if ((!empty($lesson['sequence_locked']) && !$canManage) || !empty($lesson['locked']) || !empty($lesson['assignment_count']) || !empty($lesson['certificate_count'])): ?>
                <span class="education-playlist-badges">
                    <?php if (!empty($lesson['sequence_locked']) && !$canManage): ?>
                        <em><i class="bi bi-lock-fill" aria-hidden="true"></i>conclua a anterior</em>
                    <?php endif; ?>
                    <?php if (!empty($lesson['locked'])): ?>
                        <em><i class="bi bi-lock-fill" aria-hidden="true"></i>bloqueada</em>
                    <?php endif; ?>
                    <?php if (!empty($lesson['assignment_count'])): ?>
                        <em><i class="bi bi-clipboard-check" aria-hidden="true"></i><?= e((string) $lesson['assignment_count']) ?> tarefa(s)</em>
                    <?php endif; ?>
                    <?php if (!empty($lesson['certificate_count'])): ?>
                        <em><i class="bi bi-award" aria-hidden="true"></i>certificado</em>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <small><?= e(text_excerpt($lesson['description'] ?? '', 120)) ?></small>
        </span>
    </a>
    <div class="education-lesson-actions">
        <a class="btn btn-sm <?= ((!empty($lesson['locked']) || !empty($lesson['sequence_locked'])) && !$canManage) ? 'btn-outline-secondary' : 'btn-primary' ?> icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>">
            <i class="bi <?= ((!empty($lesson['locked']) || !empty($lesson['sequence_locked'])) && !$canManage) ? 'bi-lock-fill' : 'bi-play-circle' ?>" aria-hidden="true"></i><?= ((!empty($lesson['locked']) || !empty($lesson['sequence_locked'])) && !$canManage) ? 'Bloqueada' : 'Assistir' ?>
        </a>
        <?php if ($canManage): ?>
            <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>"><i class="bi bi-layers" aria-hidden="true"></i>Materiais</a>
            <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&lesson_id=' . $lesson['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
            <form class="inline-form" method="post" action="<?= e(url('/admin/education/lesson/delete?id=' . $lesson['id'])) ?>" onsubmit="return confirm('Remover esta aula?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover</button>
            </form>
        <?php endif; ?>
    </div>
</article>
