<?php
$editingLesson = $editingLesson ?? null;
$editingModule = $editingModule ?? null;
$canTakeAttendance = $canTakeAttendance ?? false;
$canManageOriginal = $canManageOriginal ?? $canManage;
$studentPreview = $studentPreview ?? false;
$editingCourseIntro = ($_GET['edit_course'] ?? '') === '1';
$creatingModule = ($_GET['create_module'] ?? '') === '1';
$modules = $modules ?? [];
$canAssignTeacher = $canAssignTeacher ?? false;
$teacherOptions = $teacherOptions ?? [];
$forumAuthorOptions = $forumAuthorOptions ?? [];
$lessonsByModule = [];
$moduleIds = array_map(fn (array $module): int => (int) $module['id'], $modules);

foreach ($lessons as $lessonItem) {
    $key = !empty($lessonItem['module_id']) && in_array((int) $lessonItem['module_id'], $moduleIds, true) ? (string) $lessonItem['module_id'] : 'none';
    $lessonsByModule[$key][] = $lessonItem;
}

$moduleAction = url('/admin/education/module?id=' . $course['id']);
$forumTopics = $forumTopics ?? [];
$forumRepliesByTopic = $forumRepliesByTopic ?? [];
$courseForms = $courseForms ?? [];
$certificateStatus = $certificateStatus ?? [];
$isEnrollmentPending = $isEnrollmentPending ?? false;
$certificateVerificationUrl = !empty($certificateStatus['certificate']['verification_code'] ?? null)
    ? url('/certificado/' . $certificateStatus['certificate']['verification_code'])
    : null;
$isStudentCourseView = !$canManage && !$canTakeAttendance;
$lessonCount = count($lessons);
$requiredLessonCount = count(array_filter($lessons, fn (array $lessonItem): bool => (int) ($lessonItem['module_required'] ?? 1) === 1));
$completedLessonCount = 0;
$nextLesson = null;

foreach ($lessons as $lessonItem) {
    $lessonRequired = (int) ($lessonItem['module_required'] ?? 1) === 1;
    $lessonIsDone = !empty($lessonItem['completed_at']);
    $lessonIsLocked = !empty($lessonItem['schedule_locked'])
        || !empty($lessonItem['sequence_locked'])
        || !empty($lessonItem['locked']);

    if ($lessonRequired && $lessonIsDone) {
        $completedLessonCount++;
    }

    if ($lessonRequired && $nextLesson === null && !$lessonIsDone && !$lessonIsLocked) {
        $nextLesson = $lessonItem;
    }
}

$nextLessonId = $nextLesson ? (int) $nextLesson['id'] : 0;
$courseProgressPercent = $requiredLessonCount > 0 ? (int) round(($completedLessonCount / $requiredLessonCount) * 100) : 0;
$previewSuffix = $studentPreview ? '&preview=student' : '';
?>

<div class="page-heading">
    <div>
        <p><?= e($course['teacher_name'] ?? 'Plataforma de ensino') ?></p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <?php if ($forumTopics): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-forum')) ?>"><i class="bi bi-chat-dots" aria-hidden="true"></i>Fórum do curso</a>
        <?php endif; ?>
        <?php if ($courseForms): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-forms')) ?>"><i class="bi bi-ui-checks" aria-hidden="true"></i>Formularios</a>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&preview=student')) ?>"><i class="bi bi-eye" aria-hidden="true"></i>Visualizar como estudante</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&edit_course=1')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar curso</a>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&create_module=1')) ?>"><i class="bi bi-collection-play" aria-hidden="true"></i>Novo módulo</a>
        <?php elseif ($studentPreview && $canManageOriginal): ?>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Voltar ao modo edição</a>
        <?php endif; ?>
        <?php if ($canTakeAttendance): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'])) ?>"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Chamada</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/students/report?id=' . $course['id'])) ?>"><i class="bi bi-people" aria-hidden="true"></i>Painel de alunos</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance/report?id=' . $course['id'])) ?>"><i class="bi bi-bar-chart" aria-hidden="true"></i>Relatório</a>
        <?php endif; ?>
        <?php if ($isStudentCourseView && $nextLesson): ?>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $nextLesson['id'] . $previewSuffix)) ?>"><i class="bi bi-play-circle" aria-hidden="true"></i>Continuar</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
    </div>
</div>

<?php if ($studentPreview && $canManageOriginal): ?>
    <section class="panel education-preview-banner">
        <div>
            <span class="eyebrow">Prévia do estudante</span>
            <strong>Você está vendo este curso sem as ferramentas de professor.</strong>
        </div>
        <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar curso</a>
    </section>
<?php endif; ?>

<?php if (!empty($course['summary'])): ?>
    <section class="panel education-course-intro">
        <?php if (!empty($course['cover_image'])): ?>
            <img src="<?= e(media_url($course['cover_image'])) ?>" alt="<?= e($course['title']) ?>" onerror="this.remove()">
        <?php endif; ?>
        <p><?= e($course['summary']) ?></p>
    </section>
<?php endif; ?>

<?php if ($isEnrollmentPending): ?>
    <section class="panel">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Modo de espera</span>
                <h2>Matrícula aguardando liberação</h2>
            </div>
            <span class="state-pill is-muted">Pendente</span>
        </div>
        <p class="field-hint mb-0">Seu cadastro veio de uma inscrição de evento ligado a este curso. Um professor ou coordenador precisa aprovar sua matrícula para liberar aulas, formulários e fórum.</p>
    </section>
<?php endif; ?>

<?php if (!$isEnrollmentPending): ?>
<?php if ($isStudentCourseView): ?>
    <section class="panel education-student-overview">
        <div class="education-student-progress-card">
            <div>
                <span class="eyebrow">Seu progresso</span>
                <strong><?= e((string) $courseProgressPercent) ?>%</strong>
                <p><?= e((string) $completedLessonCount) ?> de <?= e((string) $requiredLessonCount) ?> aula(s) obrigatória(s) concluída(s)</p>
            </div>
            <div class="education-progress">
                <span><?= e((string) $courseProgressPercent) ?>%</span>
                <div><i style="width: <?= e((string) $courseProgressPercent) ?>%"></i></div>
            </div>
        </div>
        <div class="education-next-lesson-card">
            <span class="eyebrow">Próximo passo</span>
            <?php if ($nextLesson): ?>
                <h2><?= e($nextLesson['title']) ?></h2>
                <p><?= e(text_excerpt($nextLesson['description'] ?? 'Continue por esta aula.', 130)) ?></p>
                <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $nextLesson['id'] . $previewSuffix)) ?>"><i class="bi bi-play-circle" aria-hidden="true"></i>Continuar aula</a>
            <?php elseif ($lessonCount > 0): ?>
                <h2>Curso concluído</h2>
                <p>Todas as aulas disponíveis foram concluídas.</p>
                <?php if (!empty($course['certificate_enabled'])): ?>
                    <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-certificate')) ?>"><i class="bi bi-award" aria-hidden="true"></i>Ver certificado</a>
                <?php endif; ?>
            <?php else: ?>
                <h2>Aulas em preparação</h2>
                <p>As aulas deste curso ainda não foram publicadas.</p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="panel education-playlist-panel <?= $isStudentCourseView ? 'is-student-playlist' : '' ?>">
    <div class="section-heading">
        <div>
            <span class="eyebrow"><?= $isStudentCourseView ? 'Trilha do curso' : 'Organização do curso' ?></span>
            <h2>Módulos e aulas</h2>
        </div>
        <span><?= e((string) $lessonCount) ?> aula(s) em <?= e((string) count($modules)) ?> módulo(s)</span>
    </div>

    <div class="education-module-list">
        <?php foreach ($modules as $module): ?>
            <?php $moduleLessons = $lessonsByModule[(string) $module['id']] ?? []; ?>
            <?php $moduleHidden = empty($module['active']); ?>
            <article class="education-module-card <?= $moduleHidden ? 'is-hidden-module' : '' ?>">
                <header>
                    <div>
                        <span><?= $moduleHidden ? 'Módulo oculto' : 'Módulo' ?></span>
                        <h3><?= e($module['title']) ?></h3>
                        <span class="state-pill <?= !empty($module['required']) ? 'is-active' : 'is-muted' ?>"><?= !empty($module['required']) ? 'Obrigatório' : 'Material complementar' ?></span>
                        <?php if (!empty($module['summary'])): ?>
                            <p><?= e($module['summary']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="education-module-actions">
                        <?php if ($moduleHidden): ?>
                            <em>Oculto para alunos</em>
                        <?php endif; ?>
                        <strong><?= e((string) count($moduleLessons)) ?> aula(s)</strong>
                        <?php if ($canManage): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&module_id=' . $module['id'])) ?>">Editar módulo</a>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/module/visibility?module_id=' . $module['id'])) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="active" value="<?= $moduleHidden ? '1' : '0' ?>">
                                <button class="btn btn-sm <?= $moduleHidden ? 'btn-outline-primary' : 'btn-outline-danger' ?>">
                                    <?= $moduleHidden ? 'Mostrar' : 'Ocultar' ?>
                                </button>
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
                                <label class="form-label">Liberar em</label>
                                <input class="form-control" name="available_at" type="datetime-local">
                            </div>
                            <div>
                                <label class="form-label">Frequência da aula</label>
                                <select class="form-select" name="attendance_mode">
                                    <option value="video">Aluno conclui assistindo o vídeo</option>
                                    <option value="manual">Ao vivo: professor valida presença</option>
                                    <option value="none">Não contar para frequência</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Imagem principal opcional</label>
                                <input class="form-control" name="lesson_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                            </div>
                            <label class="forum-check-line grid-span-2">
                                <input type="checkbox" name="locked" value="1">
                                <span>Bloquear reprodução para alunos</span>
                            </label>
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
            <div class="empty-state">
                Crie o primeiro módulo. Depois aparecerá o botão para adicionar aulas dentro dele.
                <?php if ($canManage): ?>
                    <a class="btn btn-primary icon-btn mt-3" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&create_module=1')) ?>"><i class="bi bi-collection-play" aria-hidden="true"></i>Novo módulo</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!$isEnrollmentPending): ?>
<?php if ($canManage || !empty($course['certificate_enabled'])): ?>
<section class="panel education-certificate-panel" id="course-certificate">
    <div class="section-heading">
        <h2>Certificado do curso</h2>
        <span><?= !empty($course['certificate_enabled']) ? 'Emissão liberada por solicitação do aluno' : 'Configure o modelo antes de liberar' ?></span>
    </div>

    <?php if ($canManage): ?>
        <form method="post" action="<?= e(url('/admin/education/certificate/settings?id=' . $course['id'])) ?>" enctype="multipart/form-data" class="education-certificate-settings">
            <?= csrf_field() ?>
            <label class="forum-check-line grid-span-2">
                <input type="checkbox" name="certificate_enabled" value="1" <?= checked(!empty($course['certificate_enabled'])) ?>>
                <span>Liberar certificado quando o aluno concluir o curso</span>
            </label>
            <?php if (!empty($certificateInstitutions ?? [])): ?>
                <div class="grid-span-2">
                    <label class="form-label">Instituição emissora</label>
                    <select class="form-select" name="certificate_institution_id">
                        <option value="">Usar dados preenchidos manualmente</option>
                        <?php foreach ($certificateInstitutions as $institution): ?>
                            <option value="<?= e((string) $institution['id']) ?>" <?= selected((string) ($institution['id'] ?? ''), (string) ($course['certificate_institution_id'] ?? '')) ?>>
                                <?= e($institution['name'] ?? '') ?>
                                <?php if (!empty($institution['city']) || !empty($institution['state'])): ?>
                                    - <?= e(trim(($institution['city'] ?? '') . '/' . ($institution['state'] ?? ''), '/')) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-hint">Quando selecionada, a instituição oficial preenche nome, CNPJ, cidade/UF e site do certificado.</small>
                </div>
            <?php endif; ?>
            <div>
                <label class="form-label">Título do certificado</label>
                <input class="form-control" name="certificate_title" maxlength="180" value="<?= e($course['certificate_title'] ?? '') ?>" placeholder="Certificado de conclusão">
            </div>
            <div>
                <label class="form-label">Frequência mínima</label>
                <input class="form-control" name="certificate_min_frequency" type="number" min="0" max="100" value="<?= e((string) ($course['certificate_min_frequency'] ?? 0)) ?>">
                <small class="field-hint">Use 0 para não bloquear a emissão pela chamada.</small>
            </div>
            <div>
                <label class="form-label">Natureza do curso</label>
                <input class="form-control" name="certificate_course_nature" maxlength="180" value="<?= e($course['certificate_course_nature'] ?? '') ?>" placeholder="Curso Livre de Capacitação Profissional - Formação Continuada">
            </div>
            <div>
                <label class="form-label">Modalidade</label>
                <input class="form-control" name="certificate_modality" maxlength="80" value="<?= e($course['certificate_modality'] ?? '') ?>" placeholder="Online, presencial ou híbrida">
            </div>
            <div>
                <label class="form-label">Fundo por link</label>
                <input class="form-control" name="certificate_background" value="<?= e($course['certificate_background'] ?? '') ?>" placeholder="/public/uploads/... ou URL">
            </div>
            <div>
                <label class="form-label">Enviar imagem de fundo</label>
                <input class="form-control" name="certificate_background_upload" type="file" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="grid-span-2">
                <label class="form-label">Texto do certificado</label>
                <textarea class="form-control" name="certificate_text" rows="5" placeholder="Certificamos que {student_name} concluiu o curso {course_title} em {issued_at}."><?= e($course['certificate_text'] ?? '') ?></textarea>
                <small class="field-hint">Campos automáticos: {student_name}, {course_title}, {teacher_name}, {frequency}, {issued_at}, {verification_code}.</small>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Critério de aprovação</label>
                <input class="form-control" name="certificate_approval_criteria" maxlength="255" value="<?= e($course['certificate_approval_criteria'] ?? '') ?>" placeholder="Certificado concedido mediante frequência mínima de 75% e aproveitamento satisfatório.">
            </div>
            <div class="grid-span-2">
                <label class="form-label">Base legal</label>
                <textarea class="form-control" name="certificate_legal_text" rows="2" placeholder="Curso Livre de Capacitação Profissional ofertado nos termos da Lei nº 9.394/96 (LDB) e Decreto nº 5.154/04."><?= e($course['certificate_legal_text'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Instituição</label>
                <input class="form-control" name="certificate_institution_name" maxlength="180" value="<?= e($course['certificate_institution_name'] ?? '') ?>" placeholder="Cidade Nova Informa - CNI">
            </div>
            <div>
                <label class="form-label">Cidade/UF</label>
                <input class="form-control" name="certificate_institution_city" maxlength="120" value="<?= e($course['certificate_institution_city'] ?? '') ?>" placeholder="Foz do Iguaçu - PR">
            </div>
            <div>
                <label class="form-label">CNPJ</label>
                <input class="form-control" name="certificate_institution_cnpj" maxlength="32" value="<?= e($course['certificate_institution_cnpj'] ?? '') ?>" placeholder="Informe o CNPJ da instituição">
            </div>
            <div>
                <label class="form-label">Site oficial</label>
                <input class="form-control" name="certificate_institution_site" maxlength="180" value="<?= e($course['certificate_institution_site'] ?? '') ?>" placeholder="www.cidadenovainforma.com.br">
            </div>
            <label class="forum-check-line">
                <input type="checkbox" name="certificate_program_enabled" value="1" <?= checked((int) ($course['certificate_program_enabled'] ?? 1) === 1) ?>>
                <span>Incluir programação no verso</span>
            </label>
            <div>
                <label class="form-label">Colunas do verso</label>
                <input class="form-control" name="certificate_program_columns" type="number" min="1" max="4" value="<?= e((string) ($course['certificate_program_columns'] ?? 2)) ?>">
            </div>
            <div>
                <label class="form-label">Fundo do verso por link</label>
                <input class="form-control" name="certificate_program_background" value="<?= e($course['certificate_program_background'] ?? '') ?>" placeholder="/public/uploads/... ou URL">
            </div>
            <div>
                <label class="form-label">Enviar fundo do verso</label>
                <input class="form-control" name="certificate_program_background_upload" type="file" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="grid-span-2">
                <label class="form-label">Informações extras do verso</label>
                <textarea class="form-control" name="certificate_program_extra" rows="4" placeholder="Ex.: carga horária, critérios de avaliação, observações ou conteúdo complementar."><?= e($course['certificate_program_extra'] ?? '') ?></textarea>
                <small class="field-hint">A programação lista automaticamente os módulos e aulas cadastrados no curso.</small>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Objetivos do curso</label>
                <textarea class="form-control" name="certificate_objectives" rows="3" placeholder="Descreva os objetivos do curso"><?= e($course['certificate_objectives'] ?? '') ?></textarea>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Competências desenvolvidas</label>
                <textarea class="form-control" name="certificate_competencies" rows="4" placeholder="Uma competência por linha"><?= e($course['certificate_competencies'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Responsável pelo curso</label>
                <input class="form-control" name="certificate_responsible_name" maxlength="180" value="<?= e($course['certificate_responsible_name'] ?? '') ?>" placeholder="Nome do professor responsável">
            </div>
            <div>
                <label class="form-label">Formação do responsável</label>
                <input class="form-control" name="certificate_responsible_credential" maxlength="180" value="<?= e($course['certificate_responsible_credential'] ?? '') ?>" placeholder="Formação ou credencial do responsável">
            </div>
            <div class="form-action-cell">
                <button class="btn btn-primary icon-btn"><i class="bi bi-award" aria-hidden="true"></i>Salvar certificado</button>
            </div>
        </form>
        <?php if (!empty($certificateNameRequests)): ?>
            <div class="education-certificate-name-requests">
                <h3>Alterações de nome pendentes</h3>
                <?php foreach ($certificateNameRequests as $request): ?>
                    <article>
                        <div>
                            <strong><?= e($request['student_name'] ?? $request['user_name'] ?? '') ?></strong>
                            <span><?= e($request['student_email'] ?? '') ?></span>
                            <small>Solicitado: <?= e($request['requested_student_name'] ?? '') ?></small>
                        </div>
                        <form method="post" action="<?= e(url('/admin/education/certificate/name-review?certificate_id=' . $request['id'])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-primary" name="decision" value="approve">Autorizar</button>
                            <button class="btn btn-sm btn-outline-danger" name="decision" value="reject">Recusar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="education-certificate-request">
            <div class="education-certificate-checks">
                <div>
                    <span>Aulas concluidas</span>
                    <strong><?= e((string) ($certificateStatus['completed_count'] ?? 0)) ?>/<?= e((string) ($certificateStatus['lesson_count'] ?? 0)) ?></strong>
                </div>
                <div>
                    <span>Frequencia</span>
                    <strong><?= e((string) ($certificateStatus['frequency'] ?? 0)) ?>%</strong>
                </div>
                <div>
                    <span>Minimo exigido</span>
                    <strong><?= e((string) ($certificateStatus['minimum_frequency'] ?? 0)) ?>%</strong>
                </div>
            </div>
            <?php if (!empty($certificateStatus['certificate'])): ?>
                <div class="education-certificate-actions">
                    <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/certificate?id=' . $course['id'])) ?>"><i class="bi bi-printer" aria-hidden="true"></i>Abrir certificado</a>
                    <?php if ($certificateVerificationUrl): ?>
                        <a class="btn btn-outline-primary icon-btn" href="<?= e($certificateVerificationUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar certificado</a>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($certificateStatus['eligible'])): ?>
                <form method="post" action="<?= e(url('/admin/education/certificate/request?id=' . $course['id'])) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary icon-btn"><i class="bi bi-award" aria-hidden="true"></i>Solicitar certificado</button>
                </form>
            <?php else: ?>
                <p class="field-hint mb-0">O certificado aparece aqui depois que o curso estiver concluido e a frequencia minima for atingida.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canManage || $courseForms): ?>
<section class="panel education-course-forum education-form-board" id="course-forms">
    <div class="section-heading">
        <h2>Formularios do curso</h2>
        <span><?= e((string) count($courseForms)) ?> formulario(s)</span>
    </div>
    <?php if ($canManage): ?>
        <form method="post" action="<?= e(url('/admin/education/form?id=' . $course['id'])) ?>" class="education-sequence-form">
            <?= csrf_field() ?>
            <div class="sequence-title-field">
                <label class="form-label">Titulo do formulario</label>
                <input class="form-control" name="title" maxlength="180" placeholder="Ex.: Avaliacao do curso" required>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Descricao</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Perguntas</label>
                <textarea class="form-control" name="questions[]" rows="2" placeholder="Pergunta 1" required></textarea>
                <textarea class="form-control mt-2" name="questions[]" rows="2" placeholder="Pergunta 2"></textarea>
                <textarea class="form-control mt-2" name="questions[]" rows="2" placeholder="Pergunta 3"></textarea>
            </div>
            <div class="form-action-cell">
                <button class="btn btn-primary icon-btn"><i class="bi bi-ui-checks" aria-hidden="true"></i>Criar formulario</button>
            </div>
        </form>
    <?php endif; ?>
    <div class="education-form-list">
        <?php foreach ($courseForms as $form): ?>
            <?php
            $questions = \App\Models\Education::formQuestions((int) $form['id']);
            $response = \App\Models\Education::formResponse((int) $form['id'], (int) (current_user()['id'] ?? 0));
            $answers = \App\Models\Education::formAnswersForResponse((int) ($response['id'] ?? 0));
            $responses = $canManage ? \App\Models\Education::formResponses((int) $form['id']) : [];
            ?>
            <article class="education-form-card">
                <header>
                    <div>
                        <strong><?= e($form['title']) ?></strong>
                        <?php if (!empty($form['description'])): ?><p><?= e($form['description']) ?></p><?php endif; ?>
                    </div>
                    <span class="state-pill is-active"><?= e((string) ($form['response_count'] ?? 0)) ?> resposta(s)</span>
                </header>
                <?php if ($canManage): ?>
                <div class="education-form-question-preview">
                    <strong>Perguntas</strong>
                    <ol>
                        <?php foreach ($questions as $question): ?>
                            <li><?= e($question['question']) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>
                <?php if ($canManage): ?>
                    <details>
                        <summary>Editar e ver respostas</summary>
                        <form method="post" action="<?= e(url('/admin/education/form/update?form_id=' . $form['id'])) ?>" class="education-form-manage">
                            <?= csrf_field() ?>
                            <label class="form-label">Titulo</label>
                            <input class="form-control" name="title" value="<?= e($form['title']) ?>" required>
                            <label class="form-label">Descricao</label>
                            <textarea class="form-control" name="description" rows="3"><?= e($form['description'] ?? '') ?></textarea>
                            <label class="form-label">Perguntas</label>
                            <?php foreach ($questions as $question): ?>
                                <textarea class="form-control" name="questions[]" rows="2" required><?= e($question['question']) ?></textarea>
                            <?php endforeach; ?>
                            <textarea class="form-control" name="questions[]" rows="2" placeholder="Nova pergunta"></textarea>
                            <button class="btn btn-sm btn-outline-primary">Salvar formulario</button>
                        </form>
                        <div class="education-response-list">
                            <?php foreach ($responses as $item): ?>
                                <?php $itemAnswers = \App\Models\Education::formAnswersForResponse((int) $item['id']); ?>
                                <div>
                                    <strong><?= e($item['user_name']) ?></strong>
                                    <small><?= e($item['updated_at'] ?? $item['created_at'] ?? '') ?></small>
                                    <?php foreach ($questions as $question): ?>
                                        <p><b><?= e($question['question']) ?>:</b> <?= e($itemAnswers[(int) $question['id']] ?? '-') ?></p>
                                    <?php endforeach; ?>
                                    <form method="post" action="<?= e(url('/admin/education/form/grade?response_id=' . $item['id'])) ?>" class="education-correction-form">
                                        <?= csrf_field() ?>
                                        <label class="form-label">
                                            Situacao
                                            <select class="form-control" name="correction_status">
                                                <option value="pending" <?= ($item['correction_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                                <option value="corrected" <?= ($item['correction_status'] ?? '') === 'corrected' ? 'selected' : '' ?>>Corrigido</option>
                                                <option value="redo" <?= ($item['correction_status'] ?? '') === 'redo' ? 'selected' : '' ?>>Refazer</option>
                                            </select>
                                        </label>
                                        <label class="form-label">
                                            Nota
                                            <input class="form-control" name="grade" maxlength="40" value="<?= e($item['grade'] ?? '') ?>">
                                        </label>
                                        <label class="form-label grid-span-2">
                                            Comentario da correcao
                                            <textarea class="form-control" name="feedback" rows="3"><?= e($item['feedback'] ?? '') ?></textarea>
                                        </label>
                                        <button class="btn btn-sm btn-outline-primary">Salvar correcao</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$responses): ?><p class="form-text">Nenhuma resposta enviada ainda.</p><?php endif; ?>
                        </div>
                        <form method="post" action="<?= e(url('/admin/education/form/delete?form_id=' . $form['id'])) ?>" onsubmit="return confirm('Remover este formulario?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Remover formulario</button>
                        </form>
                    </details>
                <?php elseif ($studentPreview): ?>
                    <div class="education-preview-note">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                        Este formulário aparecerá para o estudante responder.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/education/form/submit?form_id=' . $form['id'])) ?>" class="education-form-answer">
                        <?= csrf_field() ?>
                        <?php foreach ($questions as $question): ?>
                            <label class="form-label education-form-answer-question">
                                <strong><?= e($question['question']) ?></strong>
                                <textarea class="form-control" name="answers[<?= e((string) $question['id']) ?>]" rows="3" required><?= e($answers[(int) $question['id']] ?? '') ?></textarea>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($response && (($response['correction_status'] ?? 'pending') !== 'pending' || !empty($response['grade']) || !empty($response['feedback']))): ?>
                            <div class="education-correction-note">
                                <strong>Correcao do professor</strong>
                                <span><?= e(['corrected' => 'Corrigido', 'redo' => 'Refazer', 'pending' => 'Pendente'][$response['correction_status'] ?? 'pending'] ?? 'Pendente') ?></span>
                                <?php if (!empty($response['grade'])): ?><p><b>Nota:</b> <?= e($response['grade']) ?></p><?php endif; ?>
                                <?php if (!empty($response['feedback'])): ?><p><?= e($response['feedback']) ?></p><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-primary"><?= $response ? 'Atualizar resposta' : 'Enviar resposta' ?></button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$courseForms): ?><div class="empty-state">Nenhum formulario criado para este curso.</div><?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($canManage || $forumTopics): ?>
<section class="panel education-course-forum" id="course-forum">
    <div class="section-heading">
        <h2>Fórum do curso</h2>
        <span><?= e((string) count($forumTopics)) ?> tópico(s)</span>
    </div>
    <?php if ($canManage): ?>
        <form method="post" action="<?= e(url('/admin/education/forum/topic?id=' . $course['id'])) ?>" class="education-sequence-form">
            <?= csrf_field() ?>
            <div class="sequence-title-field">
                <label class="form-label">Título</label>
                <input class="form-control" name="title" maxlength="180" placeholder="Aviso ou tema geral do curso" required>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Mensagem</label>
                <textarea class="form-control" name="body" rows="4" data-tinymce required></textarea>
            </div>
            <div class="form-action-cell">
                <button class="btn btn-primary icon-btn"><i class="bi bi-chat-dots" aria-hidden="true"></i>Publicar no fórum</button>
            </div>
        </form>
    <?php endif; ?>
    <div class="forum-topic-list mt-3">
        <?php foreach ($forumTopics as $topic): ?>
            <?php $topicReplies = $forumRepliesByTopic[(int) $topic['id']] ?? []; ?>
            <article class="forum-topic-item education-forum-topic-starter">
                <div class="forum-topic-main">
                    <span class="forum-topic-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                    <span>
                        <strong><?= e($topic['title']) ?></strong>
                        <small><?= e(text_excerpt($topic['body'] ?? '', 150)) ?></small>
                    </span>
                </div>
                <div class="forum-topic-meta">
                    <span><?= e($topic['user_name'] ?? 'Usuário') ?></span>
                    <span><?= e((string) ($topic['reply_count'] ?? 0)) ?> resposta(s)</span>
                    <span><?= e($topic['created_at'] ?? '') ?></span>
                </div>
                <div class="education-forum-topic-actions">
                    <?php if (!empty($topic['central_topic_id'])): ?>
                        <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/forum/topic?id=' . $topic['central_topic_id'])) ?>"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Central de fóruns</a>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                        <details class="education-forum-edit">
                            <summary class="btn btn-sm btn-outline-secondary icon-btn"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar fórum</summary>
                            <form method="post" action="<?= e(url('/admin/education/forum/topic/update?topic_id=' . $topic['id'])) ?>" class="education-forum-edit-form">
                                <?= csrf_field() ?>
                                <label class="form-label">Título</label>
                                <input class="form-control" name="title" maxlength="180" value="<?= e($topic['title'] ?? '') ?>" required>
                                <?php if ($canAssignTeacher): ?>
                                    <label class="form-label">Autor exibido</label>
                                    <select class="form-select" name="user_id">
                                        <?php foreach ($forumAuthorOptions as $item): ?>
                                            <option value="<?= e((string) $item['id']) ?>" <?= selected((string) $item['id'], (string) ($topic['user_id'] ?? '')) ?>>
                                                <?= e($item['name']) ?> - <?= e($item['role_names'] ?? $item['role_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <label class="form-label">Mensagem</label>
                                <textarea class="form-control" name="body" rows="4" data-tinymce required><?= e($topic['body'] ?? '') ?></textarea>
                                <button class="btn btn-sm btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar fórum</button>
                            </form>
                        </details>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/education/forum/delete?topic_id=' . $topic['id'])) ?>" onsubmit="return confirm('Remover este fórum? A cópia da central também será ocultada.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover fórum</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php require __DIR__ . '/partials/forum-replies.php'; $topicReplies = []; ?>
                <?php if ($topicReplies): ?>
                    <div class="education-forum-replies">
                        <?php foreach ($topicReplies as $replyIndex => $reply): ?>
                            <?php $replyHidden = empty($reply['active']); ?>
                            <div class="education-forum-reply-card reply-tone-<?= e((string) (((int) $replyIndex % 6) + 1)) ?> <?= $replyHidden ? 'is-hidden-reply' : '' ?>">
                                <div class="education-forum-reply-head">
                                    <strong>
                                        <?= e($reply['user_name'] ?? 'Usuário') ?>
                                        <?php if ($replyHidden): ?>
                                            <span class="education-hidden-badge">Oculto para estudantes</span>
                                        <?php endif; ?>
                                        <?php if (!empty($reply['parent_user_name'])): ?>
                                            <span class="education-reply-parent">Em resposta a <?= e($reply['parent_user_name']) ?></span>
                                        <?php endif; ?>
                                    </strong>
                                    <div class="education-forum-reply-actions">
                                    <?php if (!$studentPreview && !$replyHidden): ?>
                                        <details class="education-forum-reply-interaction">
                                            <summary class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-reply" aria-hidden="true"></i>Responder</summary>
                                            <form method="post" action="<?= e(url('/admin/education/forum/reply?topic_id=' . $topic['id'])) ?>" class="education-forum-reply-form is-inline-reply">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="parent_reply_id" value="<?= e((string) $reply['id']) ?>">
                                                <textarea class="form-control" name="body" rows="2" placeholder="Responder <?= e($reply['user_name'] ?? 'este comentÃ¡rio') ?>" required></textarea>
                                                <button class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                    <?php if ($canManage): ?>
                                        <?php if ($replyHidden): ?>
                                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/forum/reply/restore?reply_id=' . $reply['id'])) ?>">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-success icon-btn"><i class="bi bi-eye" aria-hidden="true"></i>Restaurar</button>
                                            </form>
                                        <?php else: ?>
                                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/forum/reply/delete?reply_id=' . $reply['id'])) ?>" onsubmit="return confirm('Ocultar este comentário para estudantes?');">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-eye-slash" aria-hidden="true"></i>Ocultar</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    </div>
                                </div>
                                <div><?= article_html($reply['body'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($studentPreview): ?>
                    <div class="education-preview-note">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                        O campo de resposta do fórum aparece para estudantes, mas fica desativado nesta prévia.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/education/forum/reply?topic_id=' . $topic['id'])) ?>" class="education-forum-reply-form">
                        <?= csrf_field() ?>
                        <textarea class="form-control" name="body" rows="2" placeholder="Responder este tópico" required></textarea>
                        <button class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-reply" aria-hidden="true"></i>Responder</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$forumTopics): ?>
            <div class="empty-state">Nenhum tópico no fórum deste curso ainda.</div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>

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
            <form method="post" action="<?= e(url('/admin/education/course/update?id=' . $course['id'])) ?>" enctype="multipart/form-data" class="education-modal-form">
                <?= csrf_field() ?>
                <input type="hidden" name="public_enabled" value="<?= e((string) ($course['public_enabled'] ?? 0)) ?>">
                <?php if ($canAssignTeacher): ?>
                    <div>
                        <label class="form-label">Professor responsável</label>
                        <select class="form-select" name="teacher_user_id">
                            <option value="">Definir depois</option>
                            <?php foreach ($teacherOptions as $item): ?>
                                <option value="<?= e((string) $item['id']) ?>" <?= selected((string) $item['id'], (string) ($course['teacher_user_id'] ?? '')) ?>>
                                    <?= e($item['name']) ?> - <?= e($item['role_names'] ?? $item['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="teacher_user_id" value="<?= e((string) ($course['teacher_user_id'] ?? '')) ?>">
                <?php endif; ?>
                <div>
                    <label class="form-label">Título do curso</label>
                    <input class="form-control" name="title" maxlength="180" value="<?= e($course['title'] ?? '') ?>" required autofocus>
                </div>
                <div>
                    <label class="form-label">Imagem de capa por link</label>
                    <input class="form-control" name="cover_image" value="<?= e($course['cover_image'] ?? '') ?>" placeholder="/public/uploads/... ou URL">
                </div>
                <div>
                    <label class="form-label">Enviar imagem de capa</label>
                    <input class="form-control" name="course_cover" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                </div>
                <div>
                    <label class="form-label">Resumo / introdução</label>
                    <textarea class="form-control" name="summary" rows="6"><?= e($course['summary'] ?? '') ?></textarea>
                </div>
                <input type="hidden" name="playlist_required" value="0">
                <label class="education-public-toggle education-playlist-required-toggle">
                    <input type="checkbox" name="playlist_required" value="1" <?= checked((string) ($course['playlist_required'] ?? '1'), '1') ?>>
                    <span>
                        <strong>Exigir playlist para concluir o curso</strong>
                        <small>Quando desmarcado, o aluno pode abrir aulas fora da ordem e concluir sem assistir todos os vídeos. Tarefas obrigatórias continuam exigidas.</small>
                    </span>
                </label>
                <footer class="split-actions">
                    <button class="btn btn-primary icon-btn" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar curso</button>
                    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
                </footer>
            </form>
        </section>
    </div>
<?php endif; ?>

<?php if ($canManage && $creatingModule): ?>
    <div class="forum-modal is-open education-edit-modal" id="education-module-create-modal" aria-hidden="false">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog forum-modal-small" role="dialog" aria-modal="true" aria-labelledby="education-module-create-title">
            <header>
                <div>
                    <span>Edição separada</span>
                    <h2 id="education-module-create-title">Criar módulo</h2>
                </div>
                <a class="forum-icon-button" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </header>
            <form method="post" action="<?= e($moduleAction) ?>" class="education-modal-form">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Título do módulo</label>
                    <input class="form-control" name="title" maxlength="180" placeholder="Ex.: Módulo 01 [40 horas]" required autofocus>
                </div>
                <div>
                    <label class="form-label">Ordem</label>
                    <input class="form-control" name="sort_order" type="number" value="0">
                </div>
                <div>
                    <label class="form-label">Resumo</label>
                    <textarea class="form-control" name="summary" rows="4"></textarea>
                </div>
                <input type="hidden" name="required" value="0">
                <label class="forum-check-line">
                    <input type="checkbox" name="required" value="1" checked>
                    <span>Módulo obrigatório para conclusão do curso</span>
                </label>
                <footer class="split-actions">
                    <button class="btn btn-primary icon-btn" type="submit"><i class="bi bi-collection-play" aria-hidden="true"></i>Criar módulo</button>
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
                <input type="hidden" name="required" value="0">
                <label class="forum-check-line">
                    <input type="checkbox" name="required" value="1" <?= checked((string) ($editingModule['required'] ?? '1'), '1') ?>>
                    <span>Módulo obrigatório para conclusão do curso</span>
                </label>
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
                <div>
                    <label class="form-label">Liberar em</label>
                    <input class="form-control" name="available_at" type="datetime-local" value="<?= !empty($editingLesson['available_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $editingLesson['available_at']))) : '' ?>">
                </div>
                <div>
                    <label class="form-label">Frequência da aula</label>
                    <select class="form-select" name="attendance_mode">
                        <option value="video" <?= selected((string) ($editingLesson['attendance_mode'] ?? 'video'), 'video') ?>>Aluno conclui assistindo o vídeo</option>
                        <option value="manual" <?= selected((string) ($editingLesson['attendance_mode'] ?? 'video'), 'manual') ?>>Ao vivo: professor valida presença</option>
                        <option value="none" <?= selected((string) ($editingLesson['attendance_mode'] ?? 'video'), 'none') ?>>Não contar para frequência</option>
                    </select>
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
                <label class="forum-check-line grid-span-2">
                    <input type="checkbox" name="locked" value="1" <?= checked(!empty($editingLesson['locked'])) ?>>
                    <span>Bloquear reprodução para alunos</span>
                </label>
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
