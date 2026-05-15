<?php
$isEdit = (bool) $editing;
$teacherOptions = $teacherOptions ?? $users;
$studentOptions = $studentOptions ?? $users;
$canManageAll = $canManageAll ?? true;
?>

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
        <span><?= $isEdit ? 'Ajuste os dados do curso e as matrículas quando necessário.' : 'Informe só o essencial agora. As aulas entram na próxima etapa.' ?></span>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/education/course/update?id=' . $editing['id']) : url('/admin/education/course')) ?>" class="education-course-form">
        <?= csrf_field() ?>
        <input type="hidden" name="enrollment_sync" value="1">
        <div class="education-course-title-field">
            <label class="form-label">Título do curso</label>
            <input class="form-control form-control-lg" name="title" maxlength="180" value="<?= e($editing['title'] ?? '') ?>" placeholder="Ex.: Informática básica" required autofocus>
        </div>
        <div class="education-course-summary-field">
            <label class="form-label">Resumo</label>
            <textarea class="form-control" name="summary" rows="3" placeholder="Descrição curta para os alunos."><?= e($editing['summary'] ?? '') ?></textarea>
        </div>
        <details class="education-course-advanced">
            <summary><i class="bi bi-sliders" aria-hidden="true"></i>Detalhes opcionais</summary>
            <div class="education-course-advanced-grid">
                <div>
                    <label class="form-label">Professor responsável</label>
                    <?php if ($canManageAll): ?>
                        <select class="form-select" name="teacher_user_id">
                            <option value="">Definir depois</option>
                            <?php foreach ($teacherOptions as $item): ?>
                                <option value="<?= e((string) $item['id']) ?>" <?= selected((string) $item['id'], (string) ($editing['teacher_user_id'] ?? '')) ?>>
                                    <?= e($item['name']) ?> - <?= e($item['role_names'] ?? $item['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="teacher_user_id" value="<?= e((string) (current_user()['id'] ?? '')) ?>">
                        <input class="form-control" value="<?= e(current_user()['name'] ?? 'Professor') ?>" disabled>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="form-label">Imagem de capa</label>
                    <input class="form-control" name="cover_image" value="<?= e($editing['cover_image'] ?? '') ?>" placeholder="/public/uploads/... ou URL">
                </div>
            </div>
        </details>
        <details class="education-access-details">
            <?php $enrolledUserIds = $isEdit ? \App\Models\Education::enrollmentUserIds((int) $editing['id']) : []; ?>
            <summary><i class="bi bi-people" aria-hidden="true"></i>Estudantes matriculados <span><?= e((string) count($enrolledUserIds)) ?> selecionado(s)</span></summary>
            <?php if ($isEdit): ?>
                <div class="education-picker-toolbar">
                    <input class="form-control" type="search" placeholder="Buscar estudante" data-education-student-search>
                    <button class="btn btn-sm btn-outline-secondary icon-btn" type="button" data-education-select-visible><i class="bi bi-check2-square" aria-hidden="true"></i>Selecionar visíveis</button>
                    <button class="btn btn-sm btn-outline-secondary icon-btn" type="button" data-education-clear-visible><i class="bi bi-square" aria-hidden="true"></i>Limpar visíveis</button>
                </div>
                <div class="education-user-picker" data-education-student-list>
                    <?php foreach ($studentOptions as $item): ?>
                        <label data-student-label="<?= e(strtolower($item['name'] . ' ' . $item['email'])) ?>">
                            <input type="checkbox" name="user_ids[]" value="<?= e((string) $item['id']) ?>" <?= checked(in_array((int) $item['id'], $enrolledUserIds, true)) ?>>
                            <span><?= e($item['name']) ?><small><?= e($item['email']) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!$studentOptions): ?>
                        <div class="empty-state">Nenhum estudante ativo encontrado. Crie um usuário com cargo ESTUDANTE em Usuários.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="field-hint mb-0">Salve o curso primeiro. Depois você poderá matricular estudantes e criar as aulas.</p>
            <?php endif; ?>
        </details>
        <div class="form-action-cell split-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Salvar alterações' : 'Criar curso e adicionar aulas' ?></button>
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
