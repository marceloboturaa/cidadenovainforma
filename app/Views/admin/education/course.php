<?php
$editingLesson = $editingLesson ?? null;
$editingModule = $editingModule ?? null;
$modules = $modules ?? [];
$lessonsByModule = [];
$moduleIds = array_map(fn (array $module): int => (int) $module['id'], $modules);

foreach ($lessons as $lessonItem) {
    $key = !empty($lessonItem['module_id']) && in_array((int) $lessonItem['module_id'], $moduleIds, true) ? (string) $lessonItem['module_id'] : 'none';
    $lessonsByModule[$key][] = $lessonItem;
}

$moduleAction = $editingModule
    ? url('/admin/education/module/update?module_id=' . $editingModule['id'])
    : url('/admin/education/module?id=' . $course['id']);
?>

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
    <section class="panel education-simple-panel">
        <div class="section-heading">
            <h2><?= $editingModule ? 'Editar módulo' : 'Criar módulo' ?></h2>
            <span>Primeiro crie o módulo, depois adicione as aulas dentro dele</span>
        </div>
        <form method="post" action="<?= e($moduleAction) ?>" class="education-module-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Título do módulo</label>
                <input class="form-control" name="title" maxlength="180" value="<?= e($editingModule['title'] ?? '') ?>" placeholder="Ex.: Módulo 01 [40 horas]" required>
            </div>
            <div>
                <label class="form-label">Ordem</label>
                <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingModule['sort_order'] ?? 0)) ?>">
            </div>
            <div class="grid-span-2">
                <label class="form-label">Resumo</label>
                <textarea class="form-control" name="summary" rows="3"><?= e($editingModule['summary'] ?? '') ?></textarea>
            </div>
            <div class="form-action-cell split-actions">
                <button class="btn btn-primary icon-btn"><i class="bi bi-collection-play" aria-hidden="true"></i><?= $editingModule ? 'Atualizar módulo' : 'Criar módulo' ?></button>
                <?php if ($editingModule): ?>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if ($editingLesson): ?>
        <section class="panel education-simple-panel education-lesson-edit-panel" id="editar-aula">
            <div class="section-heading">
                <h2>Editar aula</h2>
                <span>Para criar outra aula, use o formulário dentro do módulo desejado</span>
                <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>">
                    <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                    Sair da edição
                </a>
            </div>
            <form method="post" action="<?= e(url('/admin/education/lesson/update?id=' . $editingLesson['id'])) ?>" enctype="multipart/form-data" class="education-lesson-form education-lesson-edit-form">
                <?= csrf_field() ?>
                <div class="lesson-module-field">
                    <label class="form-label">Módulo</label>
                    <select class="form-select" name="module_id">
                        <option value="">Sem módulo</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= e((string) $module['id']) ?>" <?= selected((string) $module['id'], (string) ($editingLesson['module_id'] ?? '')) ?>>
                                <?= e($module['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lesson-title-field">
                    <label class="form-label">Título da aula</label>
                    <input class="form-control" name="title" maxlength="180" value="<?= e($editingLesson['title'] ?? '') ?>" required>
                </div>
                <div class="lesson-order-field">
                    <label class="form-label">Ordem</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingLesson['sort_order'] ?? 0)) ?>">
                </div>
                <div class="lesson-video-field">
                    <label class="form-label">Vídeo principal opcional</label>
                    <input class="form-control" name="video_url" value="<?= e($editingLesson['video_url'] ?? '') ?>" placeholder="Cole um link do YouTube ou vídeo direto">
                </div>
                <div class="lesson-image-field">
                    <label class="form-label">Imagem principal opcional</label>
                    <div class="education-image-inputs">
                        <input class="form-control" name="lesson_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                        <input class="form-control" name="image_url" value="<?= e($editingLesson['image_url'] ?? '') ?>" placeholder="Ou cole a URL da imagem">
                    </div>
                    <?php if (!empty($editingLesson['image_url'])): ?>
                        <img class="education-lesson-image-preview" src="<?= e(media_url($editingLesson['image_url'])) ?>" alt="" onerror="this.remove()">
                    <?php endif; ?>
                </div>
                <div class="lesson-description-field grid-span-2">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="description" rows="6" data-tinymce><?= e($editingLesson['description'] ?? '') ?></textarea>
                </div>
                <div class="form-action-cell split-actions">
                    <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Atualizar aula</button>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                </div>
            </form>
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="panel education-playlist-panel">
    <div class="section-heading">
        <h2>Módulos e aulas</h2>
        <span><?= e((string) count($lessons)) ?> aula(s) em <?= e((string) count($modules)) ?> módulo(s)</span>
    </div>

    <div class="education-module-list">
        <?php foreach ($modules as $module): ?>
            <?php $moduleLessons = $lessonsByModule[(string) $module['id']] ?? []; ?>
            <article class="education-module-card">
                <header>
                    <div>
                        <span>Módulo</span>
                        <h3><?= e($module['title']) ?></h3>
                        <?php if (!empty($module['summary'])): ?>
                            <p><?= e($module['summary']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="education-module-actions">
                        <strong><?= e((string) count($moduleLessons)) ?> aula(s)</strong>
                        <?php if ($canManage): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&module_id=' . $module['id'])) ?>">Editar módulo</a>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/module/delete?module_id=' . $module['id'])) ?>" onsubmit="return confirm('Remover este módulo? As aulas ficam no curso, sem módulo.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($canManage): ?>
                    <details class="education-inline-create" <?= !$moduleLessons ? 'open' : '' ?>>
                        <summary><i class="bi bi-plus-circle" aria-hidden="true"></i> Adicionar aula neste módulo</summary>
                        <form method="post" action="<?= e(url('/admin/education/lesson?id=' . $course['id'])) ?>" enctype="multipart/form-data" class="education-quick-lesson-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="module_id" value="<?= e((string) $module['id']) ?>">
                            <div>
                                <label class="form-label">Título da aula</label>
                                <input class="form-control" name="title" maxlength="180" required>
                            </div>
                            <div>
                                <label class="form-label">Ordem</label>
                                <input class="form-control" name="sort_order" type="number" value="<?= e((string) ((count($moduleLessons) + 1) * 10)) ?>">
                            </div>
                            <div>
                                <label class="form-label">Vídeo principal opcional</label>
                                <input class="form-control" name="video_url" placeholder="Cole o link do YouTube ou vídeo">
                            </div>
                            <div>
                                <label class="form-label">Imagem principal opcional</label>
                                <input class="form-control" name="lesson_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                            </div>
                            <div class="grid-span-2">
                                <label class="form-label">Descrição</label>
                                <textarea class="form-control" name="description" rows="4" data-tinymce placeholder="Texto inicial da aula"></textarea>
                            </div>
                            <button class="btn btn-primary icon-btn"><i class="bi bi-plus-circle" aria-hidden="true"></i>Criar aula neste módulo</button>
                        </form>
                    </details>
                <?php endif; ?>

                <div class="education-playlist-lessons">
                    <?php foreach ($moduleLessons as $lesson): ?>
                        <?php require __DIR__ . '/partials/lesson-row.php'; ?>
                    <?php endforeach; ?>
                    <?php if (!$moduleLessons): ?>
                        <div class="empty-state">Nenhuma aula cadastrada neste módulo.</div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!empty($lessonsByModule['none'])): ?>
            <article class="education-module-card">
                <header>
                    <div>
                        <span>Sem módulo</span>
                        <h3>Aulas ainda não organizadas</h3>
                    </div>
                </header>
                <div class="education-playlist-lessons">
                    <?php foreach ($lessonsByModule['none'] as $lesson): ?>
                        <?php require __DIR__ . '/partials/lesson-row.php'; ?>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endif; ?>

        <?php if (!$modules && !$lessons): ?>
            <div class="empty-state">Crie o primeiro módulo. Depois aparecerá o botão para adicionar aulas dentro dele.</div>
        <?php endif; ?>
    </div>
</section>
