<?php $editingLesson = $editingLesson ?? null; ?>

<div class="page-heading">
    <div>
        <p><?= e($course['teacher_name'] ?? 'Plataforma de ensino') ?></p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
</div>

<?php if (!empty($course['summary'])): ?>
    <section class="panel education-course-intro">
        <?php if (!empty($course['cover_image'])): ?>
            <img src="<?= e(media_url($course['cover_image'])) ?>" alt="<?= e($course['title']) ?>" onerror="this.remove()">
        <?php endif; ?>
        <p><?= e($course['summary']) ?></p>
    </section>
<?php endif; ?>

<?php if ($canManage): ?>
    <section class="panel education-editor-panel">
        <div class="section-heading">
            <h2><?= $editingLesson ? 'Editar aula' : 'Nova aula' ?></h2>
            <span>Crie a aula e depois organize textos, vídeos e arquivos dentro dela</span>
        </div>
        <form method="post" action="<?= e($editingLesson ? url('/admin/education/lesson/update?id=' . $editingLesson['id']) : url('/admin/education/lesson?id=' . $course['id'])) ?>" class="education-lesson-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Título da aula</label>
                <input class="form-control" name="title" maxlength="180" value="<?= e($editingLesson['title'] ?? '') ?>" required>
            </div>
            <div>
                <label class="form-label">Ordem</label>
                <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingLesson['sort_order'] ?? 0)) ?>">
            </div>
            <div>
                <label class="form-label">Vídeo principal opcional</label>
                <input class="form-control" name="video_url" value="<?= e($editingLesson['video_url'] ?? '') ?>" placeholder="Você também pode adicionar vídeos depois, dentro da aula">
            </div>
            <div>
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="description" rows="3"><?= e($editingLesson['description'] ?? '') ?></textarea>
            </div>
            <div class="form-action-cell split-actions">
                <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $editingLesson ? 'Atualizar aula' : 'Criar aula' ?></button>
                <?php if ($editingLesson): ?>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="section-heading">
        <h2>Aulas do curso</h2>
        <span><?= e((string) count($lessons)) ?> aula(s)</span>
    </div>
    <div class="education-lesson-list">
        <?php foreach ($lessons as $lesson): ?>
            <article class="education-lesson-row">
                <div class="education-lesson-number"><?= e((string) ((int) $lesson['sort_order'] ?: $lesson['id'])) ?></div>
                <div>
                    <h3><?= e($lesson['title']) ?></h3>
                    <p><?= e(text_excerpt($lesson['description'] ?? '', 150)) ?></p>
                    <small><?= !empty($lesson['completed_at']) ? 'Concluída em ' . e(date('d/m/Y H:i', strtotime($lesson['completed_at']))) : 'Pendente' ?></small>
                </div>
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
        <?php endforeach; ?>
        <?php if (!$lessons): ?>
            <div class="empty-state">Nenhuma aula cadastrada neste curso.</div>
        <?php endif; ?>
    </div>
</section>
