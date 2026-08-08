<?php
$blocks = $blocks ?? [];
$editingBlock = $editingBlock ?? null;
$canManage = $canManage ?? false;
$canManageOriginal = $canManageOriginal ?? $canManage;
$studentPreview = $studentPreview ?? false;
$isLocked = $isLocked ?? false;
$isScheduleLocked = $isScheduleLocked ?? false;
$hasVideo = $hasVideo ?? false;
$videoWatched = $videoWatched ?? !$hasVideo;
$requiresManualAttendance = !$canManage && (($lesson['attendance_mode'] ?? 'video') === 'manual');
$lessonForumTopics = $lessonForumTopics ?? [];
$lessonForumRepliesByTopic = $lessonForumRepliesByTopic ?? [];
$canAssignForumAuthor = $canAssignForumAuthor ?? false;
$forumAuthorOptions = $forumAuthorOptions ?? [];
$lessonForms = $lessonForms ?? [];
$assignmentSubmissionsByBlock = $assignmentSubmissionsByBlock ?? [];
$completionRequirements = $completionRequirements ?? ['complete' => true, 'pending' => [], 'required_video_count' => 0, 'watched_video_count' => 0, 'required_assignment_count' => 0, 'submitted_assignment_count' => 0, 'required_forum_count' => 0, 'replied_forum_count' => 0];
$requirementsComplete = !empty($completionRequirements['complete']);
$playlistRequired = !empty($course['playlist_required']);
$playlist = $playlist ?? [];
$modules = $modules ?? [];
$playlistByModule = [];
$moduleIds = array_map(fn (array $module): int => (int) $module['id'], $modules);
$currentIndex = null;
$currentPlaylistLesson = null;

foreach ($playlist as $index => $playlistLesson) {
    if ((int) $playlistLesson['id'] === (int) $lesson['id']) {
        $currentIndex = $index;
        $currentPlaylistLesson = $playlistLesson;
    }

    $key = !empty($playlistLesson['module_id']) && in_array((int) $playlistLesson['module_id'], $moduleIds, true) ? (string) $playlistLesson['module_id'] : 'none';
    $playlistByModule[$key][] = $playlistLesson;
}

$previousLesson = $currentIndex !== null && isset($playlist[$currentIndex - 1]) ? $playlist[$currentIndex - 1] : null;
$nextLesson = $currentIndex !== null && isset($playlist[$currentIndex + 1]) ? $playlist[$currentIndex + 1] : null;
$isCompleted = !empty($currentPlaylistLesson['completed_at']);
$previewSuffix = $studentPreview ? '&preview=student' : '';
$blockAction = $editingBlock
    ? url('/admin/education/block/update?id=' . $editingBlock['id'])
    : url('/admin/education/block?id=' . $lesson['id']);
$editingBlockSettings = [];
if ($editingBlock && !empty($editingBlock['settings_json'])) {
    $decodedSettings = json_decode((string) $editingBlock['settings_json'], true);
    $editingBlockSettings = is_array($decodedSettings) ? $decodedSettings : [];
}
$lessonDescriptionPosition = (string) ($lesson['description_position'] ?? 'after_media');
$lessonDescriptionPosition = in_array($lessonDescriptionPosition, ['top', 'after_media', 'hidden'], true) ? $lessonDescriptionPosition : 'after_media';
$showLessonDescription = !empty($lesson['description']) && $lessonDescriptionPosition !== 'hidden';
$editingLessonInline = ($_GET['edit_lesson'] ?? '') === '1';

$embed = function (?string $url): ?string {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }

    if (preg_match('#(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $match)) {
        return 'https://www.youtube.com/embed/' . $match[1] . '?enablejsapi=1';
    }

    if (preg_match('#open\.spotify\.com/(episode|show)/([A-Za-z0-9]+)#', $url, $match)) {
        return 'https://open.spotify.com/embed/' . $match[1] . '/' . $match[2];
    }

    return $url;
};

$isAudioSource = function (?string $url): bool {
    $path = parse_url((string) $url, PHP_URL_PATH) ?: (string) $url;
    return (bool) preg_match('/\.(mp3|m4a|aac|wav|ogg|oga|opus)(\?.*)?$/i', $path);
};

$renderLessonDescription = function () use ($lesson): void {
    ?>
    <section class="panel education-lesson-description" id="lesson-description">
        <div class="education-lesson-description-head">
            <span><i class="bi bi-card-text" aria-hidden="true"></i></span>
            <h2>Descrição da aula</h2>
        </div>
        <div class="education-block-text education-lesson-description-text"><?= article_html($lesson['description']) ?></div>
    </section>
    <?php
};
?>

<div class="page-heading education-lesson-heading">
    <div>
        <p>Aula</p>
        <h1><?= e($lesson['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <?php if ($showLessonDescription): ?>
            <a class="btn btn-outline-primary icon-btn" href="#lesson-description"><i class="bi bi-card-text" aria-hidden="true"></i>Descrição</a>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'] . '&edit_lesson=1#lesson-settings')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar aula</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'] . '&preview=student')) ?>"><i class="bi bi-eye" aria-hidden="true"></i>Visualizar como estudante</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $lesson['course_id'] . ($studentPreview ? '&preview=student' : ''))) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Curso</a>
        <?php if ($previousLesson): ?>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $previousLesson['id'] . $previewSuffix)) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i>Aula anterior</a>
        <?php endif; ?>
        <?php if ($nextLesson && ((empty($nextLesson['sequence_locked']) && empty($nextLesson['schedule_locked'])) || $canManage)): ?>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $nextLesson['id'] . $previewSuffix)) ?>">Próxima aula<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        <?php elseif ($nextLesson): ?>
            <span class="btn btn-outline-secondary icon-btn disabled" aria-disabled="true"><i class="bi bi-lock-fill" aria-hidden="true"></i>Próxima bloqueada</span>
        <?php endif; ?>
    </div>
</div>

<section class="education-player-layout <?= (!$canManage || $studentPreview) ? 'is-student-view' : '' ?>">
    <aside class="education-playlist-sidebar">
        <details class="education-playlist-toggle" data-education-playlist-toggle <?= (!$canManage || $studentPreview) ? 'open' : '' ?>>
            <summary class="education-playlist-title">
                <span class="education-playlist-label"><i class="bi bi-list-ul" aria-hidden="true"></i><span>Outras aulas do curso</span></span>
                <strong><?= e($course['title'] ?? 'Curso') ?></strong>
                <i class="bi bi-chevron-down education-playlist-caret" aria-hidden="true"></i>
            </summary>
            <div class="education-playlist-body">
                <div class="education-progress-inline">
                    <div>
                        <strong>Progresso</strong>
                        <span><?= $isCompleted ? 'Aula concluída' : 'Aula pendente' ?></span>
                    </div>
                    <?php if (!$isLocked && !$studentPreview): ?>
                        <div class="education-progress-actions">
                            <form method="post" action="<?= e(url('/admin/education/progress?id=' . $lesson['id'])) ?>" data-education-complete-form>
                                <?= csrf_field() ?>
                                <input type="hidden" name="completed" value="1">
                                <button class="btn btn-sm <?= $isCompleted ? 'btn-success' : 'btn-outline-success' ?> icon-btn" data-education-complete-button <?= ($hasVideo && !$videoWatched) || !$requirementsComplete || $requiresManualAttendance ? 'disabled' : '' ?>><i class="bi bi-check2-circle" aria-hidden="true"></i>Concluir</button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/education/progress?id=' . $lesson['id'])) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="completed" value="0">
                                <button class="btn btn-sm btn-outline-secondary icon-btn"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Pendente</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!$studentPreview && !$canManage && !$isCompleted && !$requirementsComplete): ?>
                        <div class="education-requirements-hint" data-education-requirements-hint data-pending-assignments="<?= e((string) max(0, (int) ($completionRequirements['required_assignment_count'] ?? 0) - (int) ($completionRequirements['submitted_assignment_count'] ?? 0))) ?>" data-pending-forums="<?= e((string) max(0, (int) ($completionRequirements['required_forum_count'] ?? 0) - (int) ($completionRequirements['replied_forum_count'] ?? 0))) ?>">
                            <strong>Para concluir esta aula</strong>
                            <?php if ((int) ($completionRequirements['required_video_count'] ?? 0) > 0): ?>
                                <span><?= e((string) ($completionRequirements['watched_video_count'] ?? 0)) ?>/<?= e((string) ($completionRequirements['required_video_count'] ?? 0)) ?> vídeo(s) obrigatório(s) assistido(s)</span>
                            <?php endif; ?>
                            <?php if ((int) ($completionRequirements['required_assignment_count'] ?? 0) > 0): ?>
                                <span><?= e((string) ($completionRequirements['submitted_assignment_count'] ?? 0)) ?>/<?= e((string) ($completionRequirements['required_assignment_count'] ?? 0)) ?> tarefa(s) entregue(s)</span>
                            <?php endif; ?>
                            <?php if ((int) ($completionRequirements['required_forum_count'] ?? 0) > 0): ?>
                                <span><?= e((string) ($completionRequirements['replied_forum_count'] ?? 0)) ?>/<?= e((string) ($completionRequirements['required_forum_count'] ?? 0)) ?> fórum(ns) respondido(s)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <div class="education-sidebar-scroll">
                    <?php foreach ($modules as $module): ?>
                        <?php $moduleLessons = $playlistByModule[(string) $module['id']] ?? []; ?>
                        <section class="education-sidebar-module">
                            <h2><?= e($module['title']) ?><?= empty($module['required']) ? ' (opcional)' : '' ?></h2>
                            <?php foreach ($moduleLessons as $playlistLesson): ?>
                                <a class="<?= (int) $playlistLesson['id'] === (int) $lesson['id'] ? 'active' : '' ?>" href="<?= e(url('/admin/education/lesson?id=' . $playlistLesson['id'] . $previewSuffix)) ?>">
                                    <i class="bi <?= (!empty($playlistLesson['locked']) || ((!empty($playlistLesson['sequence_locked']) || !empty($playlistLesson['schedule_locked'])) && !$canManage)) ? 'bi-lock-fill' : (!empty($playlistLesson['completed_at']) ? 'bi-check-circle-fill' : 'bi-circle') ?>" aria-hidden="true"></i>
                                    <span><?= e($playlistLesson['title']) ?></span>
                                    <?php if (!empty($playlistLesson['assignment_count'])): ?>
                                        <small><i class="bi bi-clipboard-check" aria-hidden="true"></i></small>
                                    <?php endif; ?>
                                    <?php if (!empty($playlistLesson['certificate_count'])): ?>
                                        <small><i class="bi bi-award" aria-hidden="true"></i></small>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$moduleLessons): ?>
                                <small>Nenhuma aula neste módulo.</small>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>

                    <?php if (!empty($playlistByModule['none'])): ?>
                        <section class="education-sidebar-module">
                            <h2>Sem módulo</h2>
                            <?php foreach ($playlistByModule['none'] as $playlistLesson): ?>
                                <a class="<?= (int) $playlistLesson['id'] === (int) $lesson['id'] ? 'active' : '' ?>" href="<?= e(url('/admin/education/lesson?id=' . $playlistLesson['id'] . $previewSuffix)) ?>">
                                    <i class="bi <?= (!empty($playlistLesson['locked']) || ((!empty($playlistLesson['sequence_locked']) || !empty($playlistLesson['schedule_locked'])) && !$canManage)) ? 'bi-lock-fill' : (!empty($playlistLesson['completed_at']) ? 'bi-check-circle-fill' : 'bi-circle') ?>" aria-hidden="true"></i>
                                    <span><?= e($playlistLesson['title']) ?></span>
                                    <?php if (!empty($playlistLesson['assignment_count'])): ?>
                                        <small><i class="bi bi-clipboard-check" aria-hidden="true"></i></small>
                                    <?php endif; ?>
                                    <?php if (!empty($playlistLesson['certificate_count'])): ?>
                                        <small><i class="bi bi-award" aria-hidden="true"></i></small>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </details>
    </aside>

    <article class="education-content-stack">
        <?php if ($canManage): ?>
            <details class="panel education-admin-details education-lesson-settings-panel" id="lesson-settings" <?= $editingLessonInline ? 'open' : '' ?>>
                <summary class="education-admin-details-summary">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    <span>
                        <strong>Dados da aula</strong>
                        <small>Título, descrição, imagem principal, vídeo principal e liberação.</small>
                    </span>
                    <em>Editar aula</em>
                </summary>
                <form method="post" action="<?= e(url('/admin/education/lesson/update?id=' . $lesson['id'])) ?>" enctype="multipart/form-data" class="education-lesson-form education-lesson-inline-form education-admin-details-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="<?= e('/admin/education/lesson?id=' . $lesson['id'] . '&edit_lesson=1#lesson-settings') ?>">
                    <div class="lesson-title-field">
                        <label class="form-label">Título da aula</label>
                        <input class="form-control" name="title" maxlength="180" value="<?= e($lesson['title'] ?? '') ?>" required>
                    </div>
                    <div class="lesson-module-field">
                        <label class="form-label">Módulo</label>
                        <select class="form-select" name="module_id">
                            <option value="">Sem módulo</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= e((string) $module['id']) ?>" <?= selected((string) $module['id'], (string) ($lesson['module_id'] ?? '')) ?>>
                                    <?= e($module['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lesson-order-field">
                        <label class="form-label">Ordem</label>
                        <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($lesson['sort_order'] ?? 0)) ?>">
                    </div>
                    <div>
                        <label class="form-label">Liberar em</label>
                        <input class="form-control" name="available_at" type="datetime-local" value="<?= !empty($lesson['available_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $lesson['available_at']))) : '' ?>">
                    </div>
                    <div>
                        <label class="form-label">Frequência</label>
                        <select class="form-select" name="attendance_mode">
                            <option value="video" <?= selected((string) ($lesson['attendance_mode'] ?? 'video'), 'video') ?>>Aluno conclui assistindo o vídeo</option>
                            <option value="manual" <?= selected((string) ($lesson['attendance_mode'] ?? 'video'), 'manual') ?>>Ao vivo: professor valida presença</option>
                            <option value="none" <?= selected((string) ($lesson['attendance_mode'] ?? 'video'), 'none') ?>>Não contar para frequência</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Posição da descrição</label>
                        <select class="form-select" name="description_position">
                            <option value="after_media" <?= selected((string) ($lesson['description_position'] ?? 'after_media'), 'after_media') ?>>Padrão: depois da imagem/vídeo</option>
                            <option value="top" <?= selected((string) ($lesson['description_position'] ?? ''), 'top') ?>>No topo da aula</option>
                            <option value="hidden" <?= selected((string) ($lesson['description_position'] ?? ''), 'hidden') ?>>Não exibir</option>
                        </select>
                    </div>
                    <details class="education-sequence-extra grid-span-2" open>
                        <summary><i class="bi bi-play-circle" aria-hidden="true"></i>Vídeo e imagem principal</summary>
                        <div class="education-sequence-extra-grid">
                            <div>
                                <label class="form-label">Vídeo principal</label>
                                <input class="form-control" name="video_url" value="<?= e($lesson['video_url'] ?? '') ?>" placeholder="YouTube, Vimeo ou vídeo direto">
                            </div>
                            <div>
                                <label class="form-label">Enviar imagem principal</label>
                                <input class="form-control" name="lesson_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                            </div>
                            <div class="grid-span-2">
                                <label class="form-label">Ou URL da imagem</label>
                                <input class="form-control" name="image_url" value="<?= e($lesson['image_url'] ?? '') ?>" placeholder="URL da imagem principal">
                            </div>
                        </div>
                    </details>
                    <input type="hidden" name="locked" value="0">
                    <label class="forum-check-line grid-span-2">
                        <input type="checkbox" name="locked" value="1" <?= checked(!empty($lesson['locked'])) ?>>
                        <span>Bloquear reprodução e materiais para alunos</span>
                    </label>
                    <div class="lesson-description-field grid-span-2">
                        <label class="form-label">Descrição da aula</label>
                        <textarea class="form-control" name="description" rows="7" data-tinymce><?= e($lesson['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-action-cell split-actions">
                        <button class="btn btn-primary icon-btn" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar aula</button>
                        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $lesson['course_id'])) ?>"><i class="bi bi-diagram-3" aria-hidden="true"></i>Organizar curso</a>
                    </div>
                </form>
            </details>

            <details class="panel education-admin-details education-sequence-form-panel <?= $editingBlock ? 'is-editing-material' : '' ?>" id="material-form" <?= $editingBlock ? 'open' : '' ?>>
                <summary class="education-admin-details-summary">
                    <i class="bi <?= $editingBlock ? 'bi-pencil-square' : 'bi-plus-circle' ?>" aria-hidden="true"></i>
                    <span>
                        <strong><?= $editingBlock ? 'Editar material' : 'Adicionar material' ?></strong>
                        <small><?= $editingBlock ? 'Você está alterando um item já cadastrado nesta aula.' : 'Crie textos, imagens, vídeos, podcasts, arquivos, tarefas ou certificados.' ?></small>
                    </span>
                    <em><?= $editingBlock ? 'Editando' : 'Novo item' ?></em>
                </summary>
                <form method="post" action="<?= e($blockAction) ?>" enctype="multipart/form-data" class="education-sequence-form">
                    <?= csrf_field() ?>
                    <fieldset class="education-material-fieldset">
                        <legend>Identificação</legend>
                        <div class="sequence-title-field">
                            <label class="form-label">Título</label>
                            <input class="form-control" name="title" maxlength="180" value="<?= e($editingBlock['title'] ?? '') ?>" placeholder="Ex.: Vídeo 1, Leitura, Material de apoio">
                        </div>
                        <div class="sequence-type-field">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="type">
                                <option value="text" <?= selected((string) ($editingBlock['type'] ?? 'text'), 'text') ?>>Texto</option>
                                <option value="video" <?= selected((string) ($editingBlock['type'] ?? ''), 'video') ?>>Vídeo</option>
                                <option value="podcast" <?= selected((string) ($editingBlock['type'] ?? ''), 'podcast') ?>>Podcast / áudio</option>
                                <option value="image" <?= selected((string) ($editingBlock['type'] ?? ''), 'image') ?>>Imagem</option>
                                <option value="file" <?= selected((string) ($editingBlock['type'] ?? ''), 'file') ?>>Documento para baixar</option>
                                <option value="assignment" <?= selected((string) ($editingBlock['type'] ?? ''), 'assignment') ?>>Tarefa</option>
                                <option value="certificate" <?= selected((string) ($editingBlock['type'] ?? ''), 'certificate') ?>>Certificado</option>
                            </select>
                        </div>
                        <div class="sequence-order-field">
                            <label class="form-label">Ordem</label>
                            <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingBlock['sort_order'] ?? ((count($blocks) + 1) * 10))) ?>">
                        </div>
                    </fieldset>
                    <input type="hidden" name="required" value="0">
                    <input type="hidden" name="active" value="0">
                    <div class="education-material-toggles">
                        <label class="forum-check-line">
                            <input type="checkbox" name="required" value="1" <?= checked((string) ($editingBlock['required'] ?? '1'), '1') ?>>
                            <span>Obrigatório para concluir a aula</span>
                        </label>
                        <label class="forum-check-line">
                            <input type="checkbox" name="active" value="1" <?= checked((string) ($editingBlock['active'] ?? '1'), '1') ?>>
                            <span>Visível para alunos</span>
                        </label>
                    </div>
                    <?php if ($editingBlock && !empty($editingBlock['file_path'])): ?>
                        <div class="education-current-file">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>
                            Arquivo atual mantido se nenhum novo for enviado.
                        </div>
                    <?php endif; ?>
                    <fieldset class="education-material-fieldset">
                        <legend>Conteúdo</legend>
                        <div class="grid-span-2">
                            <label class="form-label">Texto, explicação ou instruções</label>
                            <textarea class="form-control education-large-textarea" name="content" rows="10" data-tinymce placeholder="Texto que aparece junto com este material"><?= e($editingBlock['content'] ?? '') ?></textarea>
                        </div>
                        <div class="education-sequence-extra-grid">
                            <div>
                                <label class="form-label">Link externo</label>
                                <input class="form-control" name="media_url" value="<?= e($editingBlock['media_url'] ?? '') ?>" placeholder="YouTube, Spotify, áudio, imagem ou arquivo externo">
                            </div>
                            <div>
                                <label class="form-label">Tamanho da imagem</label>
                                <select class="form-select" name="image_width">
                                    <?php foreach ([100 => 'Grande', 70 => 'Médio', 50 => 'Pequeno', 35 => 'Mini'] as $widthValue => $widthLabel): ?>
                                        <option value="<?= e((string) $widthValue) ?>" <?= selected((string) ($editingBlockSettings['image_width'] ?? '100'), (string) $widthValue) ?>><?= e($widthLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Texto do material</label>
                                <select class="form-select" name="content_position">
                                    <option value="after_media" <?= selected((string) ($editingBlockSettings['content_position'] ?? 'after_media'), 'after_media') ?>>Depois da mídia</option>
                                    <option value="before_media" <?= selected((string) ($editingBlockSettings['content_position'] ?? ''), 'before_media') ?>>Antes da mídia</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Estilo do texto</label>
                                <select class="form-select" name="text_style">
                                    <option value="default" <?= selected((string) ($editingBlockSettings['text_style'] ?? 'default'), 'default') ?>>Padrão</option>
                                    <option value="highlight" <?= selected((string) ($editingBlockSettings['text_style'] ?? ''), 'highlight') ?>>Destaque suave</option>
                                    <option value="note" <?= selected((string) ($editingBlockSettings['text_style'] ?? ''), 'note') ?>>Nota</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="education-material-fieldset">
                        <legend>Arquivo</legend>
                        <div class="grid-span-2 education-block-document-field">
                            <label class="form-label">Enviar imagem, áudio, documento ou arquivo</label>
                            <input class="form-control" name="block_file" type="file">
                            <small class="form-text">Para podcast, envie áudio ou cole um link. Para documento, o arquivo fica disponível para baixar.</small>
                        </div>
                    </fieldset>
                    <div class="form-action-cell split-actions">
                        <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $editingBlock ? 'Atualizar item' : 'Adicionar à sequência' ?></button>
                        <?php if ($editingBlock): ?>
                            <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar edição</a>
                        <?php endif; ?>
                    </div>
                </form>
            </details>
        <?php endif; ?>

        <?php if ($showLessonDescription && $lessonDescriptionPosition === 'top'): ?>
            <?php $renderLessonDescription(); ?>
        <?php endif; ?>

        <?php if (!empty($lesson['image_url'])): ?>
            <section class="panel education-block-card education-lesson-main-media-card">
                <?php if ($canManage): ?>
                    <div class="education-block-heading">
                        <span class="education-block-type"><i class="bi bi-image" aria-hidden="true"></i> Imagem principal</span>
                        <strong><?= e($lesson['title']) ?></strong>
                    </div>
                <?php endif; ?>
                <img class="education-block-image education-lesson-main-image" src="<?= e(media_url($lesson['image_url'])) ?>" alt="<?= e($lesson['title']) ?>" onerror="this.remove()">
            </section>
        <?php endif; ?>

        <?php if ($isLocked): ?>
            <section class="panel education-block-card education-lesson-locked">
                <div class="education-block-heading">
                    <span class="education-block-type"><i class="bi bi-lock-fill" aria-hidden="true"></i> <?= $isScheduleLocked ? 'Aula agendada' : 'Aula bloqueada' ?></span>
                    <strong><?= e($lesson['title']) ?></strong>
                </div>
                <p class="mb-0"><?= $isScheduleLocked ? 'Esta aula já aparece na lista, mas só será liberada no horário agendado.' : 'O professor liberou a visualização desta aula na lista, mas bloqueou a reprodução e os materiais por enquanto.' ?></p>
            </section>
        <?php endif; ?>

        <?php if ($requiresManualAttendance): ?>
            <section class="panel education-block-card education-lesson-locked">
                <div class="education-block-heading">
                    <span class="education-block-type"><i class="bi bi-person-check" aria-hidden="true"></i> Presença ao vivo</span>
                    <strong><?= e($lesson['title']) ?></strong>
                </div>
                <p class="mb-0">Esta aula será concluída quando o professor validar sua presença no encontro ao vivo.</p>
            </section>
        <?php endif; ?>

        <?php if (!empty($videoEmbedUrl)): ?>
            <section class="panel education-block-card">
                <div class="education-block-heading">
                    <span class="education-block-type"><i class="bi bi-play-circle" aria-hidden="true"></i> Vídeo principal</span>
                    <strong><?= e($lesson['title']) ?></strong>
                </div>
                <?php if (!$studentPreview && $hasVideo && !$videoWatched && !$canManage): ?>
                    <p class="education-video-watch-hint" data-education-watch-hint>Assista ao vídeo até o final para liberar o botão Concluir e a próxima aula.</p>
                <?php endif; ?>
                    <div class="education-video-frame <?= $studentPreview ? 'is-preview-frame' : '' ?>">
                        <?php if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $videoEmbedUrl)): ?>
                            <video src="<?= e(media_url($videoEmbedUrl)) ?>" controls <?= (!$studentPreview && !$canManage && $hasVideo && !$videoWatched) ? 'data-education-video-watch data-watch-url="' . e(url('/admin/education/watch?id=' . $lesson['id'])) . '"' : '' ?>></video>
                        <?php else: ?>
                            <iframe src="<?= e($videoEmbedUrl) ?>" title="<?= e($lesson['title']) ?>" allowfullscreen <?= (!$studentPreview && !$canManage && $hasVideo && !$videoWatched) ? 'data-education-video-watch data-watch-url="' . e(url('/admin/education/watch?id=' . $lesson['id'])) . '"' : '' ?>></iframe>
                        <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($showLessonDescription && $lessonDescriptionPosition === 'after_media'): ?>
            <?php $renderLessonDescription(); ?>
        <?php endif; ?>

        <?php foreach ($blocks as $block): ?>
            <?php
            $type = (string) ($block['type'] ?? 'text');
            $blockTitle = $block['title'] ?: match ($type) {
                'video' => 'Vídeo da aula',
                'podcast', 'audio' => 'Podcast da aula',
                'image' => 'Imagem da aula',
                'file' => 'Documento para baixar',
                'assignment' => 'Tarefa da aula',
                'certificate' => 'Certificado',
                default => 'Material da aula',
            };
            $media = $embed($block['media_url'] ?? '');
            $blockSettings = [];
            if (!empty($block['settings_json'])) {
                $decodedBlockSettings = json_decode((string) $block['settings_json'], true);
                $blockSettings = is_array($decodedBlockSettings) ? $decodedBlockSettings : [];
            }
            $imageWidth = (int) ($blockSettings['image_width'] ?? 100);
            $imageWidth = in_array($imageWidth, [35, 50, 70, 100], true) ? $imageWidth : 100;
            $contentPosition = (string) ($blockSettings['content_position'] ?? 'after_media');
            $contentPosition = in_array($contentPosition, ['before_media', 'after_media'], true) ? $contentPosition : 'after_media';
            $textStyle = (string) ($blockSettings['text_style'] ?? 'default');
            $textStyle = in_array($textStyle, ['default', 'highlight', 'note'], true) ? $textStyle : 'default';
            $blockTextClass = 'education-block-text' . ($textStyle !== 'default' ? ' is-' . $textStyle : '');
            $isDocumentBlock = $type === 'file';
            $documentFilePath = (string) ($block['file_path'] ?? '');
            $documentMediaUrl = (string) ($block['media_url'] ?? '');
            $documentSource = $documentFilePath !== '' ? $documentFilePath : $documentMediaUrl;
            $documentExtension = strtolower(pathinfo(parse_url($documentSource, PHP_URL_PATH) ?: $documentSource, PATHINFO_EXTENSION));
            $documentExtensionLabel = $documentExtension !== '' ? strtoupper($documentExtension) : 'Arquivo';
            $documentCanPreview = $documentMediaUrl !== '' || in_array($documentExtension, ['pdf', 'gif', 'jpg', 'jpeg', 'png', 'webp', 'csv', 'txt'], true);
            $documentViewUrl = $documentFilePath !== ''
                ? url('/admin/education/block/download?id=' . $block['id'] . '&inline=1')
                : ($documentMediaUrl !== '' ? media_url($documentMediaUrl) : '');
            $documentDownloadUrl = $documentFilePath !== '' ? url('/admin/education/block/download?id=' . $block['id']) : '';
            ?>
            <?php $blockHidden = empty($block['active']); ?>
            <?php $blockRequired = !empty($block['required']) && ($type !== 'video' || $playlistRequired); ?>
            <?php $blockVideoWatched = !empty($block['block_video_completed_at']); ?>
            <section id="material-<?= e((string) $block['id']) ?>" class="panel education-block-card <?= $isDocumentBlock ? 'education-document-card' : '' ?> <?= $blockHidden ? 'is-hidden-block' : '' ?> <?= $editingBlock && (int) ($editingBlock['id'] ?? 0) === (int) $block['id'] ? 'is-being-edited' : '' ?>">
                <div class="education-block-heading">
                    <span class="education-block-type">
                        <?php if ($type === 'video'): ?>
                            <i class="bi bi-play-circle" aria-hidden="true"></i> Vídeo
                        <?php elseif (in_array($type, ['podcast', 'audio'], true)): ?>
                            <i class="bi bi-broadcast" aria-hidden="true"></i> Podcast
                        <?php elseif ($type === 'image'): ?>
                            <i class="bi bi-image" aria-hidden="true"></i> Imagem
                        <?php elseif ($type === 'file'): ?>
                            <i class="bi bi-download" aria-hidden="true"></i> Documento
                        <?php elseif ($type === 'assignment'): ?>
                            <i class="bi bi-clipboard-check" aria-hidden="true"></i> Tarefa
                        <?php elseif ($type === 'certificate'): ?>
                            <i class="bi bi-award" aria-hidden="true"></i> Certificado
                        <?php else: ?>
                            <i class="bi bi-card-text" aria-hidden="true"></i> Texto
                        <?php endif; ?>
                    </span>
                    <strong><?= e($blockTitle) ?></strong>
                    <span class="education-block-flags">
                        <?php if ($isDocumentBlock): ?>
                            <em><?= e($documentExtensionLabel) ?></em>
                        <?php endif; ?>
                        <?php if ($blockRequired && in_array($type, ['video', 'assignment'], true)): ?>
                            <em>Obrigatório</em>
                        <?php endif; ?>
                        <?php if ($blockHidden): ?>
                            <em>Oculto para alunos</em>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if (!empty($block['content']) && $contentPosition === 'before_media'): ?>
                    <div class="<?= e($blockTextClass) ?>"><?= article_html($block['content']) ?></div>
                <?php endif; ?>

                <?php if ($type === 'video' && $media): ?>
                    <?php if (!$studentPreview && !$canManage && $blockRequired && !$blockVideoWatched): ?>
                        <p class="education-video-watch-hint" data-education-watch-hint>Assista este vídeo obrigatório até o final para liberar a conclusão da aula.</p>
                    <?php elseif (!$canManage && $blockRequired): ?>
                        <p class="education-video-watch-hint is-complete">Vídeo obrigatório assistido.</p>
                    <?php endif; ?>
                    <div class="education-video-frame <?= $studentPreview ? 'is-preview-frame' : '' ?>">
                        <?php if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $media)): ?>
                            <video src="<?= e(media_url($media)) ?>" controls <?= (!$studentPreview && !$canManage && $blockRequired && !$blockVideoWatched) ? 'data-education-video-watch data-watch-url="' . e(url('/admin/education/watch?id=' . $lesson['id'] . '&block_id=' . $block['id'])) . '"' : '' ?>></video>
                        <?php else: ?>
                            <iframe src="<?= e($media) ?>" title="<?= e($blockTitle) ?>" allowfullscreen <?= (!$studentPreview && !$canManage && $blockRequired && !$blockVideoWatched) ? 'data-education-video-watch data-watch-url="' . e(url('/admin/education/watch?id=' . $lesson['id'] . '&block_id=' . $block['id'])) . '"' : '' ?>></iframe>
                        <?php endif; ?>
                    </div>
                    <?php if (!$studentPreview && !$canManage && $blockRequired && !$blockVideoWatched): ?>
                        <form method="post" action="<?= e(url('/admin/education/block/watch?id=' . $block['id'])) ?>" class="education-video-complete-form" data-education-video-fallback hidden>
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-success icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Marcar vídeo como concluído</button>
                        </form>
                    <?php endif; ?>
                <?php elseif (in_array($type, ['podcast', 'audio'], true) && (!empty($block['file_path']) || $media)): ?>
                    <?php $audioSource = (string) ($block['file_path'] ?: $media); ?>
                    <?php if ($isAudioSource($audioSource)): ?>
                        <audio class="education-audio-player" src="<?= e(media_url($audioSource)) ?>" controls></audio>
                    <?php elseif ($media && str_contains($media, 'open.spotify.com/embed/')): ?>
                        <iframe class="education-podcast-frame" src="<?= e($media) ?>" title="<?= e($blockTitle) ?>" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"></iframe>
                    <?php else: ?>
                        <a class="btn btn-outline-primary icon-btn education-download-btn" href="<?= e(media_url($audioSource)) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Abrir podcast
                        </a>
                    <?php endif; ?>
                <?php elseif ($type === 'image' && (!empty($block['file_path']) || $media)): ?>
                    <img class="education-block-image education-block-image-size-<?= e((string) $imageWidth) ?>" src="<?= e(media_url($block['file_path'] ?: $media)) ?>" alt="<?= e($blockTitle) ?>" onerror="this.remove()">
                <?php endif; ?>

                <?php if (!empty($block['content']) && $contentPosition === 'after_media'): ?>
                    <div class="<?= e($blockTextClass) ?>"><?= article_html($block['content']) ?></div>
                <?php endif; ?>

                <?php if ($isDocumentBlock): ?>
                    <div class="education-document-footer">
                        <div>
                            <strong>Material de leitura</strong>
                            <span>Abra em nova aba para consultar sem sair da aula, ou baixe para estudar offline.</span>
                        </div>
                        <div class="education-document-actions">
                            <?php if ($documentCanPreview && $documentViewUrl !== ''): ?>
                                <a class="btn btn-outline-primary icon-btn education-download-btn" href="<?= e($documentViewUrl) ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    Visualizar
                                </a>
                            <?php endif; ?>
                            <?php if ($documentDownloadUrl !== ''): ?>
                                <a class="btn btn-primary icon-btn education-download-btn" href="<?= e($documentDownloadUrl) ?>">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    Baixar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif (in_array($type, ['assignment', 'certificate'], true) && !empty($block['file_path'])): ?>
                    <a class="btn btn-outline-primary icon-btn education-download-btn" href="<?= e(url('/admin/education/block/download?id=' . $block['id'])) ?>">
                        <i class="bi bi-download" aria-hidden="true"></i>
                        Baixar documento
                    </a>
                <?php elseif (in_array($type, ['assignment', 'certificate'], true) && !empty($block['media_url'])): ?>
                    <a class="btn btn-outline-primary icon-btn education-download-btn" href="<?= e(media_url($block['media_url'])) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        Abrir documento
                    </a>
                <?php endif; ?>

                <?php if ($type === 'assignment'): ?>
                    <?php
                    $mySubmission = \App\Models\Education::assignmentSubmission((int) $block['id'], (int) (current_user()['id'] ?? 0));
                    $blockSubmissions = $assignmentSubmissionsByBlock[(int) $block['id']] ?? [];
                    ?>
                    <?php if (!$canManage): ?>
                        <form method="post" action="<?= e(url('/admin/education/assignment/submit?id=' . $block['id'])) ?>" enctype="multipart/form-data" class="education-assignment-form">
                            <?= csrf_field() ?>
                            <label class="form-label">
                                Resposta da tarefa
                                <textarea class="form-control" name="text_answer" rows="4" placeholder="Escreva uma observacao ou resposta"><?= e($mySubmission['text_answer'] ?? '') ?></textarea>
                            </label>
                            <label class="form-label">
                                Enviar arquivo
                                <input class="form-control" name="assignment_file" type="file" accept=".pdf,.doc,.docx,.odt,.txt,.rtf,.xls,.xlsx,.ods,.ppt,.pptx,.odp,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z">
                            </label>
                            <?php if (!empty($mySubmission['file_path'])): ?>
                                <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/assignment/download?id=' . $mySubmission['id'])) ?>"><i class="bi bi-download" aria-hidden="true"></i>Minha entrega atual</a>
                            <?php endif; ?>
                            <?php if ($mySubmission && (($mySubmission['correction_status'] ?? 'pending') !== 'pending' || !empty($mySubmission['grade']) || !empty($mySubmission['feedback']))): ?>
                                <div class="education-correction-note">
                                    <strong>Correcao do professor</strong>
                                    <span><?= e(['corrected' => 'Corrigido', 'redo' => 'Refazer', 'pending' => 'Pendente'][$mySubmission['correction_status'] ?? 'pending'] ?? 'Pendente') ?></span>
                                    <?php if (!empty($mySubmission['grade'])): ?><p><b>Nota:</b> <?= e($mySubmission['grade']) ?></p><?php endif; ?>
                                    <?php if (!empty($mySubmission['feedback'])): ?><p><?= e($mySubmission['feedback']) ?></p><?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <button type="<?= $studentPreview ? 'button' : 'submit' ?>" class="btn btn-sm btn-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i><?= $mySubmission ? 'Atualizar entrega' : 'Enviar tarefa' ?></button>
                        </form>
                    <?php else: ?>
                        <details class="education-assignment-submissions">
                            <summary><?= e((string) count($blockSubmissions)) ?> entrega(s) recebida(s)</summary>
                            <div>
                                <?php foreach ($blockSubmissions as $submission): ?>
                                    <article>
                                        <strong><?= e($submission['user_name']) ?></strong>
                                        <small><?= e($submission['updated_at'] ?? $submission['created_at'] ?? '') ?></small>
                                        <?php if (!empty($submission['text_answer'])): ?><p><?= e($submission['text_answer']) ?></p><?php endif; ?>
                                        <?php if (!empty($submission['file_path'])): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/education/assignment/download?id=' . $submission['id'])) ?>">Baixar arquivo</a>
                                        <?php endif; ?>
                                        <form method="post" action="<?= e(url('/admin/education/assignment/grade?submission_id=' . $submission['id'])) ?>" class="education-correction-form">
                                            <?= csrf_field() ?>
                                            <label class="form-label">
                                                Situacao
                                                <select class="form-control" name="correction_status">
                                                    <option value="pending" <?= ($submission['correction_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                                    <option value="corrected" <?= ($submission['correction_status'] ?? '') === 'corrected' ? 'selected' : '' ?>>Corrigido</option>
                                                    <option value="redo" <?= ($submission['correction_status'] ?? '') === 'redo' ? 'selected' : '' ?>>Refazer</option>
                                                </select>
                                            </label>
                                            <label class="form-label">
                                                Nota
                                                <input class="form-control" name="grade" maxlength="40" value="<?= e($submission['grade'] ?? '') ?>">
                                            </label>
                                            <label class="form-label grid-span-2">
                                                Comentario da correcao
                                                <textarea class="form-control" name="feedback" rows="3"><?= e($submission['feedback'] ?? '') ?></textarea>
                                            </label>
                                            <button class="btn btn-sm btn-outline-primary">Salvar correcao</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                                <?php if (!$blockSubmissions): ?><p class="form-text">Nenhum estudante enviou esta tarefa ainda.</p><?php endif; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($canManage): ?>
                    <div class="education-block-actions">
                        <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'] . '&block_id=' . $block['id'] . '#material-form')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar material</a>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/education/block/visibility?id=' . $block['id'])) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="active" value="<?= $blockHidden ? '1' : '0' ?>">
                            <button class="btn btn-sm <?= $blockHidden ? 'btn-outline-primary' : 'btn-outline-danger' ?> icon-btn"><i class="bi <?= $blockHidden ? 'bi-eye' : 'bi-eye-slash' ?>" aria-hidden="true"></i><?= $blockHidden ? 'Mostrar' : 'Ocultar' ?></button>
                        </form>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/education/block/delete?id=' . $block['id'])) ?>" onsubmit="return confirm('Excluir este item definitivamente? Esta ação remove o item da aula.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Excluir</button>
                        </form>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <?php if (!$blocks && empty($videoEmbedUrl) && empty($lesson['description']) && empty($lesson['image_url'])): ?>
            <div class="empty-state">Esta aula ainda não tem sequência cadastrada.</div>
        <?php endif; ?>

        <?php if ($canManage || $lessonForms): ?>
        <details class="panel education-admin-details education-course-forum education-form-board" id="lesson-forms">
            <summary class="education-admin-details-summary">
                <i class="bi bi-ui-checks" aria-hidden="true"></i>
                <span>
                    <strong>Formularios desta aula</strong>
                    <small>Crie e acompanhe questionarios vinculados a esta aula.</small>
                </span>
                <em><?= e((string) count($lessonForms)) ?> formulario(s)</em>
            </summary>
            <div class="education-admin-details-body">
            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('/admin/education/form?lesson_id=' . $lesson['id'])) ?>" class="education-sequence-form">
                    <?= csrf_field() ?>
                    <div class="sequence-title-field">
                        <label class="form-label">Titulo do formulario</label>
                        <input class="form-control" name="title" maxlength="180" placeholder="Ex.: Revisao da aula" required>
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
                <?php foreach ($lessonForms as $form): ?>
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
                                    <?php foreach ($questions as $question): ?><textarea class="form-control" name="questions[]" rows="2" required><?= e($question['question']) ?></textarea><?php endforeach; ?>
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
                                <button type="<?= $studentPreview ? 'button' : 'submit' ?>" class="btn btn-sm btn-primary"><?= $response ? 'Atualizar resposta' : 'Enviar resposta' ?></button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$lessonForms && !$canManage): ?><div class="empty-state">Nenhum formulario criado para esta aula.</div><?php endif; ?>
            </div>
        </details>
        <?php endif; ?>

        <?php if ($canManage || $lessonForumTopics): ?>
            <details class="panel education-admin-details education-course-forum" id="lesson-forum">
                <summary class="education-admin-details-summary">
                    <i class="bi bi-chat-dots" aria-hidden="true"></i>
                    <span>
                        <strong>Fórum deste tema</strong>
                        <small>Abra discussoes e respostas relacionadas a esta aula.</small>
                    </span>
                    <em><?= e((string) count($lessonForumTopics)) ?> tópico(s)</em>
                </summary>
                <div class="education-admin-details-body">
                <?php if ($canManage && !$lessonForumTopics): ?>
                    <form method="post" action="<?= e(url('/admin/education/forum/topic?lesson_id=' . $lesson['id'])) ?>" class="education-sequence-form">
                        <?= csrf_field() ?>
                        <div class="sequence-title-field">
                            <label class="form-label">Tema do fórum</label>
                            <input class="form-control" name="title" maxlength="180" value="<?= e($lesson['title']) ?>" required>
                        </div>
                        <div class="grid-span-2">
                            <label class="form-label">Mensagem inicial</label>
                            <textarea class="form-control" name="body" rows="4" data-tinymce required></textarea>
                        </div>
                        <div class="form-action-cell">
                            <button class="btn btn-primary icon-btn"><i class="bi bi-chat-dots" aria-hidden="true"></i>Criar fórum deste tema</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="forum-topic-list mt-3">
                    <?php foreach ($lessonForumTopics as $topic): ?>
                        <?php $topicReplies = $lessonForumRepliesByTopic[(int) $topic['id']] ?? []; ?>
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
                                            <?php if ($canAssignForumAuthor): ?>
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
                                                <?php if (!$replyHidden): ?>
                                                    <details class="education-forum-reply-interaction">
                                                        <summary class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-reply" aria-hidden="true"></i>Responder</summary>
                                                        <form method="post" action="<?= e(url('/admin/education/forum/reply?topic_id=' . $topic['id'])) ?>" class="education-forum-reply-form is-inline-reply">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="parent_reply_id" value="<?= e((string) $reply['id']) ?>">
                                                            <textarea class="form-control" name="body" rows="2" placeholder="Responder este comentario" required></textarea>
                                                            <input type="hidden" name="notify_author" value="0">
                                                            <label class="forum-check-line education-forum-notify-check">
                                                                <input type="checkbox" name="notify_author" value="1" checked>
                                                                <span>Notificar por e-mail</span>
                                                            </label>
                                                            <button type="<?= $studentPreview ? 'button' : 'submit' ?>" class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar</button>
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
                            <form method="post" action="<?= e(url('/admin/education/forum/reply?topic_id=' . $topic['id'])) ?>" class="education-forum-reply-form">
                                <?= csrf_field() ?>
                                <textarea class="form-control" name="body" rows="2" placeholder="Responder este tema" required></textarea>
                                <input type="hidden" name="notify_author" value="0">
                                <label class="forum-check-line education-forum-notify-check">
                                    <input type="checkbox" name="notify_author" value="1" checked>
                                    <span>Notificar por e-mail</span>
                                </label>
                                <button type="<?= $studentPreview ? 'button' : 'submit' ?>" class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-reply" aria-hidden="true"></i>Responder</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$lessonForumTopics && $canManage): ?>
                        <div class="empty-state">Crie um fórum para discutir o tema desta aula.</div>
                    <?php endif; ?>
                </div>
                </div>
            </details>
        <?php endif; ?>

        <nav class="education-player-nav" aria-label="Navegação da playlist">
            <?php if ($previousLesson): ?>
                <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $previousLesson['id'] . $previewSuffix)) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i>Aula anterior</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <a href="<?= e(url('/admin/education/course?id=' . $lesson['course_id'] . ($studentPreview ? '&preview=student' : ''))) ?>">Voltar para o curso</a>
            <?php if ($nextLesson && ((empty($nextLesson['sequence_locked']) && empty($nextLesson['schedule_locked'])) || $canManage)): ?>
                <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $nextLesson['id'] . $previewSuffix)) ?>">Próxima aula<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            <?php elseif ($nextLesson): ?>
                <span class="btn btn-outline-secondary icon-btn disabled" aria-disabled="true"><i class="bi bi-lock-fill" aria-hidden="true"></i>Próxima bloqueada</span>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </nav>
    </article>

</section>
