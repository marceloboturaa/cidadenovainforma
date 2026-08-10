<?php
$lessonScheduleLocked = !empty($lesson['schedule_locked']) && !$canManage;
$lessonSequenceLocked = !empty($lesson['sequence_locked']) && !$canManage;
$lessonHardLocked = !empty($lesson['locked']) && !$canManage;
$lessonAccessLocked = $lessonScheduleLocked || $lessonSequenceLocked || $lessonHardLocked;
$lessonAvailableAt = !empty($lesson['available_at']) ? date('d/m/Y H:i', strtotime((string) $lesson['available_at'])) : '';
$lessonAttendanceMode = (string) ($lesson['attendance_mode'] ?? 'video');
$lessonModuleRequired = (int) ($lesson['module_required'] ?? 1) === 1;
$lessonIsComplete = !empty($lesson['completed_at']);
$lessonIsNext = !$canManage && !$lessonAccessLocked && !$lessonIsComplete && !empty($nextLessonId) && (int) $lesson['id'] === (int) $nextLessonId;
$lessonHasVideo = trim((string) ($lesson['video_url'] ?? '')) !== '';
$lessonContentLabel = $lessonHasVideo ? 'Vídeo' : ($lessonAttendanceMode === 'manual' ? 'Encontro' : 'Material');
$lessonButtonLabel = 'Acessar';
$lessonButtonIcon = $lessonHasVideo ? ($lessonIsComplete ? 'bi-arrow-clockwise' : 'bi-play-circle') : 'bi-journal-text';
$lessonHref = isset($lessonUrl) && is_callable($lessonUrl)
    ? $lessonUrl($lesson)
    : url('/admin/education/lesson?id=' . $lesson['id'] . (!empty($studentPreview) ? '&preview=student' : ''));
?>
<article class="education-playlist-row <?= !$lessonModuleRequired ? 'is-complementary' : '' ?> <?= $lessonScheduleLocked ? 'is-scheduled' : '' ?> <?= $lessonIsComplete ? 'is-complete' : '' ?> <?= $lessonIsNext ? 'is-next' : '' ?> <?= $lessonAccessLocked ? 'is-locked' : '' ?> <?= !$lessonHasVideo ? 'is-non-video' : 'is-video' ?>">
    <<?= $lessonAccessLocked ? 'div' : 'a' ?> class="education-playlist-main"<?= $lessonAccessLocked ? '' : ' href="' . e($lessonHref) . '"' ?>>
        <span class="<?= $lessonIsComplete ? 'is-complete' : '' ?>">
            <?php if (isset($lessonPosition)): ?>
                <strong><?= e(str_pad((string) $lessonPosition, 2, '0', STR_PAD_LEFT)) ?></strong>
            <?php else: ?>
                <i class="bi <?= $lessonAccessLocked ? 'bi-lock-fill' : ($lessonIsComplete ? 'bi-check-circle-fill' : ($lessonIsNext ? 'bi-play-circle-fill' : 'bi-circle')) ?>" aria-hidden="true"></i>
            <?php endif; ?>
        </span>
        <span>
            <?php if ($lessonIsNext): ?>
                <em class="education-current-lesson-label">Próxima aula</em>
            <?php endif; ?>
            <em class="education-lesson-kind <?= !$lessonHasVideo ? 'is-non-video' : '' ?>"><?= e($lessonContentLabel) ?></em>
            <strong><?= e($lesson['title']) ?></strong>
            <?php if (!$lessonModuleRequired || $lessonAccessLocked || $lessonAttendanceMode === 'manual' || $lessonAttendanceMode === 'none' || !empty($lesson['assignment_count']) || !empty($lesson['certificate_count'])): ?>
                <span class="education-playlist-badges">
                    <?php if (!$lessonModuleRequired): ?>
                        <em><i class="bi bi-journal-bookmark" aria-hidden="true"></i>opcional</em>
                    <?php endif; ?>
                    <?php if ($lessonScheduleLocked): ?>
                        <em><i class="bi bi-calendar-event" aria-hidden="true"></i><?= e($lessonAvailableAt) ?></em>
                    <?php endif; ?>
                    <?php if ($lessonSequenceLocked): ?>
                        <em><i class="bi bi-lock-fill" aria-hidden="true"></i>conclua a anterior</em>
                    <?php endif; ?>
                    <?php if ($lessonHardLocked): ?>
                        <em><i class="bi bi-lock-fill" aria-hidden="true"></i>bloqueada</em>
                    <?php endif; ?>
                    <?php if ($lessonAttendanceMode === 'manual'): ?>
                        <em><i class="bi bi-person-check" aria-hidden="true"></i>presença ao vivo</em>
                    <?php elseif ($lessonAttendanceMode === 'none'): ?>
                        <em><i class="bi bi-dash-circle" aria-hidden="true"></i>sem frequência</em>
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
    </<?= $lessonAccessLocked ? 'div' : 'a' ?>>
    <div class="education-lesson-actions">
        <?php if ($lessonAccessLocked): ?>
            <?php if (!empty($publicCourseView)): ?>
                <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e($courseRegistrationUrl ?? url('/register?course_id=' . $course['id'])) ?>">
                    <i class="bi bi-lock-fill" aria-hidden="true"></i>Inscrever-se
                </a>
            <?php else: ?>
                <span class="btn btn-sm btn-outline-secondary icon-btn disabled" aria-disabled="true">
                    <i class="bi bi-lock-fill" aria-hidden="true"></i><?= $lessonScheduleLocked ? 'Agendada' : 'Bloqueada' ?>
                </span>
            <?php endif; ?>
        <?php else: ?>
            <a class="btn btn-sm btn-primary icon-btn" href="<?= e($lessonHref) ?>">
                <i class="bi <?= e($lessonButtonIcon) ?>" aria-hidden="true"></i><?= e($lessonButtonLabel) ?>
            </a>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <?php if ($lessonAttendanceMode === 'manual'): ?>
                <a class="btn btn-sm btn-outline-success icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'] . '&lesson_id=' . $lesson['id'])) ?>"><i class="bi bi-person-check" aria-hidden="true"></i>Validar presença</a>
            <?php endif; ?>
            <form class="inline-form" method="post" action="<?= e(url('/admin/education/lesson/notify?lesson_id=' . $lesson['id'])) ?>" onsubmit="return confirm('Enviar aviso desta aula para todos os alunos aprovados neste curso?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-warning icon-btn"><i class="bi bi-bell" aria-hidden="true"></i>Avisar alunos</button>
            </form>
            <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>"><i class="bi bi-layers" aria-hidden="true"></i>Materiais</a>
            <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&lesson_id=' . $lesson['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
            <form class="inline-form" method="post" action="<?= e(url('/admin/education/lesson/delete?id=' . $lesson['id'])) ?>" onsubmit="return confirm('Remover esta aula?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover</button>
            </form>
        <?php endif; ?>
    </div>
</article>
