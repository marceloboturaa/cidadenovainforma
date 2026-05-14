<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Plataforma de ensino</p>
        <h1>Cursos</h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Meu ensino</a>
</div>

<section class="panel education-editor-panel">
    <div class="section-heading">
        <h2><?= $isEdit ? 'Editar curso' : 'Novo curso' ?></h2>
        <span>Cadastro inicial do módulo de ensino</span>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/education/course/update?id=' . $editing['id']) : url('/admin/education/course')) ?>" class="education-course-form">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Título do curso</label>
            <input class="form-control" name="title" maxlength="180" value="<?= e($editing['title'] ?? '') ?>" required>
        </div>
        <div>
            <label class="form-label">Professor responsável</label>
            <select class="form-select" name="teacher_user_id">
                <option value="">Sem professor definido</option>
                <?php foreach ($users as $item): ?>
                    <option value="<?= e((string) $item['id']) ?>" <?= selected((string) $item['id'], (string) ($editing['teacher_user_id'] ?? '')) ?>>
                        <?= e($item['name']) ?> - <?= e($item['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Imagem de capa</label>
            <input class="form-control" name="cover_image" value="<?= e($editing['cover_image'] ?? '') ?>" placeholder="/public/uploads/... ou URL">
        </div>
        <div>
            <label class="form-label">Resumo</label>
            <textarea class="form-control" name="summary" rows="4"><?= e($editing['summary'] ?? '') ?></textarea>
        </div>
        <details class="education-access-details" <?= $isEdit ? 'open' : '' ?>>
            <summary>Alunos com acesso</summary>
            <?php $enrolledUserIds = $isEdit ? \App\Models\Education::enrollmentUserIds((int) $editing['id']) : []; ?>
            <div class="education-user-picker">
                <?php foreach ($users as $item): ?>
                    <label>
                        <input type="checkbox" name="user_ids[]" value="<?= e((string) $item['id']) ?>" <?= checked(in_array((int) $item['id'], $enrolledUserIds, true)) ?>>
                        <span><?= e($item['name']) ?><small><?= e($item['role_name']) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </details>
        <div class="form-action-cell split-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar curso' : 'Criar curso' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2>Cursos cadastrados</h2>
        <span><?= e((string) count($courses)) ?> curso(s)</span>
    </div>
    <div class="admin-card-list">
        <?php foreach ($courses as $course): ?>
            <article class="admin-list-card">
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($course['title']) ?></strong>
                        <span class="state-pill is-active"><?= e((string) ($course['lesson_count'] ?? 0)) ?> aula(s)</span>
                    </div>
                    <dl class="admin-list-meta">
                        <div><dt>Professor</dt><dd><?= e($course['teacher_name'] ?? '-') ?></dd></div>
                        <div><dt>Alunos</dt><dd><?= e((string) ($course['student_count'] ?? 0)) ?></dd></div>
                        <div><dt>Criado em</dt><dd><?= e($course['created_at'] ?? '-') ?></dd></div>
                    </dl>
                    <?php if (!empty($course['summary'])): ?>
                        <p class="admin-list-description"><?= e(text_excerpt($course['summary'], 180)) ?></p>
                    <?php endif; ?>
                </div>
                <div class="admin-list-actions">
                    <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-collection-play" aria-hidden="true"></i>Aulas</a>
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/manage?id=' . $course['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/education/course/delete?id=' . $course['id'])) ?>" onsubmit="return confirm('Remover este curso da lista?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$courses): ?>
            <div class="empty-state">Nenhum curso cadastrado.</div>
        <?php endif; ?>
    </div>
</section>
