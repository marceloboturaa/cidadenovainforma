<?php
$editingLesson = $editingLesson ?? null;
$editingModule = $editingModule ?? null;
$canTakeAttendance = $canTakeAttendance ?? false;
$editingCourseIntro = ($_GET['edit_course'] ?? '') === '1';
$modules = $modules ?? [];
$lessonsByModule = [];
$moduleIds = array_map(fn (array $module): int => (int) $module['id'], $modules);

foreach ($lessons as $lessonItem) {
    $key = !empty($lessonItem['module_id']) && in_array((int) $lessonItem['module_id'], $moduleIds, true) ? (string) $lessonItem['module_id'] : 'none';
    $lessonsByModule[$key][] = $lessonItem;
}

$moduleAction = url('/admin/education/module?id=' . $course['id']);
?>

<div class="page-heading">
    <div>
        <p><?= e($course['teacher_name'] ?? 'Plataforma de ensino') ?></p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <?php if ($canManage): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&edit_course=1')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar curso</a>
        <?php endif; ?>
        <?php if ($canTakeAttendance): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'])) ?>"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Chamada</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance/report?id=' . $course['id'])) ?>"><i class="bi bi-bar-chart" aria-hidden="true"></i>Relatório</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
    </div>
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
            <h2>Criar módulo</h2>
            <span>Use módulos para organizar as aulas por etapas</span>
        </div>
        <form method="post" action="<?= e($moduleAction) ?>" class="education-module-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Título do módulo</label>
                <input class="form-control" name="title" maxlength="180" placeholder="Ex.: Módulo 01 [40 horas]" required>
            </div>
            <div>
                <label class="form-label">Ordem</label>
                <input class="form-control" name="sort_order" type="number" value="0">
            </div>
            <div class="grid-span-2">
                <label class="form-label">Resumo</label>
                <textarea class="form-control" name="summary" rows="3"></textarea>
            </div>
            <div class="form-action-cell split-actions">
                <button class="btn btn-primary icon-btn"><i class="bi bi-collection-play" aria-hidden="true"></i>Criar módulo</button>
            </div>
        </form>
    </section>
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

<?php if ($canManage && $editingCourseIntro): ?>
    <div class="forum-modal is-open education-edit-modal" id="education-course-edit-modal" aria-hidden="false">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="education-course-edit-title">
            <header>
                <div>
                    <span>Edição separada</span>
                    <h2 id="education-course-edit-title">Editar introdução do curso</h2>
                </div>
                <a class="forum-icon-button" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </header>
            <form method="post" action="<?= e(url('/admin/education/course/update?id=' . $course['id'])) ?>" class="education-modal-form">
                <?= csrf_field() ?>
                <input type="hidden" name="teacher_user_id" value="<?= e((string) ($course['teacher_user_id'] ?? '')) ?>">
                <div>
                    <label class="form-label">Título do curso</label>
                    <input class="form-control" name="title" maxlength="180" value="<?= e($course['title'] ?? '') ?>" required autofocus>
                </div>
                <div>
                    <label class="form-label">Imagem de capa</label>
                    <input class="form-control" name="cover_image" value="<?= e($course['cover_image'] ?? '') ?>" placeholder="/public/uploads/... ou URL">
                </div>
                <div>
                    <label class="form-label">Resumo / introdução</label>
                    <textarea class="form-control" name="summary" rows="6"><?= e($course['summary'] ?? '') ?></textarea>
                </div>
                <footer class="split-actions">
                    <button class="btn btn-primary icon-btn" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar curso</button>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                </footer>
            </form>
        </section>
    </div>
<?php endif; ?>

<?php if ($canManage && $editingModule): ?>
    <div class="forum-modal is-open education-edit-modal" id="education-module-edit-modal" aria-hidden="false">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog forum-modal-small" role="dialog" aria-modal="true" aria-labelledby="education-module-edit-title">
            <header>
                <div>
                    <span>Edição separada</span>
                    <h2 id="education-module-edit-title">Editar módulo</h2>
                </div>
                <a class="forum-icon-button" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </header>
            <form method="post" action="<?= e(url('/admin/education/module/update?module_id=' . $editingModule['id'])) ?>" class="education-modal-form">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Título do módulo</label>
                    <input class="form-control" name="title" maxlength="180" value="<?= e($editingModule['title'] ?? '') ?>" required autofocus>
                </div>
                <div>
                    <label class="form-label">Ordem</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingModule['sort_order'] ?? 0)) ?>">
                </div>
                <div>
                    <label class="form-label">Resumo</label>
                    <textarea class="form-control" name="summary" rows="4"><?= e($editingModule['summary'] ?? '') ?></textarea>
                </div>
                <footer class="split-actions">
                    <button class="btn btn-primary icon-btn" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar módulo</button>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                </footer>
            </form>
        </section>
    </div>
<?php endif; ?>

<?php if ($canManage && $editingLesson): ?>
    <div class="forum-modal is-open education-edit-modal" id="education-lesson-edit-modal" aria-hidden="false">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="education-lesson-edit-title">
            <header>
                <div>
                    <span>Edição separada</span>
                    <h2 id="education-lesson-edit-title">Editar aula</h2>
                </div>
                <a class="forum-icon-button" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </header>
            <form method="post" action="<?= e(url('/admin/education/lesson/update?id=' . $editingLesson['id'])) ?>" enctype="multipart/form-data" class="education-lesson-form education-lesson-edit-form education-modal-form">
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
                    <input class="form-control" name="title" maxlength="180" value="<?= e($editingLesson['title'] ?? '') ?>" required autofocus>
                </div>
                <div class="lesson-order-field">
                    <label class="form-label">Ordem</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingLesson['sort_order'] ?? 0)) ?>">
                </div>
                <details class="education-sequence-extra grid-span-2" open>
                    <summary><i class="bi bi-play-circle" aria-hidden="true"></i>Vídeo e imagem</summary>
                    <div class="education-sequence-extra-grid">
                        <div>
                            <label class="form-label">Vídeo principal</label>
                            <input class="form-control" name="video_url" value="<?= e($editingLesson['video_url'] ?? '') ?>" placeholder="Cole um link do YouTube ou vídeo direto">
                        </div>
                        <div>
                            <label class="form-label">Enviar imagem</label>
                            <input class="form-control" name="lesson_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                        </div>
                        <div class="grid-span-2">
                            <label class="form-label">Ou cole a URL da imagem</label>
                            <input class="form-control" name="image_url" value="<?= e($editingLesson['image_url'] ?? '') ?>" placeholder="URL da imagem">
                        </div>
                    </div>
                </details>
                <div class="lesson-description-field grid-span-2">
                    <label class="form-label">Descrição da aula</label>
                    <textarea class="form-control" name="description" rows="7" data-tinymce><?= e($editingLesson['description'] ?? '') ?></textarea>
                </div>
                <div class="form-action-cell split-actions">
                    <button class="btn btn-primary icon-btn" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar aula</button>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                </div>
            </form>
        </section>
    </div>
<?php endif; ?>
