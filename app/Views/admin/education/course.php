<?php
$editingLesson = $editingLesson ?? null;
$editingModule = $editingModule ?? null;
$canTakeAttendance = $canTakeAttendance ?? false;
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
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&edit_course=1')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar curso</a>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&create_module=1')) ?>"><i class="bi bi-collection-play" aria-hidden="true"></i>Novo módulo</a>
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
                <?php else: ?>
                    <form method="post" action="<?= e(url('/admin/education/form/submit?form_id=' . $form['id'])) ?>" class="education-form-answer">
                        <?= csrf_field() ?>
                        <?php foreach ($questions as $question): ?>
                            <label class="form-label"><?= e($question['question']) ?><textarea class="form-control" name="answers[<?= e((string) $question['id']) ?>]" rows="3" required><?= e($answers[(int) $question['id']] ?? '') ?></textarea></label>
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
                                    </strong>
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
                                <div><?= article_html($reply['body'] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/admin/education/forum/reply?topic_id=' . $topic['id'])) ?>" class="education-forum-reply-form">
                    <?= csrf_field() ?>
                    <textarea class="form-control" name="body" rows="2" placeholder="Responder este tópico" required></textarea>
                    <button class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-reply" aria-hidden="true"></i>Responder</button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if (!$forumTopics): ?>
            <div class="empty-state">Nenhum tópico no fórum deste curso ainda.</div>
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
            <form method="post" action="<?= e(url('/admin/education/course/update?id=' . $course['id'])) ?>" enctype="multipart/form-data" class="education-modal-form">
                <?= csrf_field() ?>
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
