<article class="education-playlist-row">
    <a class="education-playlist-main" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>">
        <span class="<?= !empty($lesson['completed_at']) ? 'is-complete' : '' ?>">
            <i class="bi <?= !empty($lesson['completed_at']) ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
        </span>
        <span>
            <strong><?= e($lesson['title']) ?></strong>
            <small><?= e(text_excerpt($lesson['description'] ?? '', 120)) ?></small>
        </span>
    </a>
    <div class="education-lesson-actions">
        <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>"><i class="bi bi-play-circle" aria-hidden="true"></i>Assistir</a>
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
