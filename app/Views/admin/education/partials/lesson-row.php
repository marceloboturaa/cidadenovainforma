<?php
$lessonScheduleLocked = !empty($lesson['schedule_locked']) && !$canManage;
$lessonSequenceLocked = !empty($lesson['sequence_locked']) && !$canManage;
$lessonHardLocked = !empty($lesson['locked']) && !$canManage;
$lessonAccessLocked = $lessonScheduleLocked || $lessonSequenceLocked || $lessonHardLocked;
$lessonAvailableAt = !empty($lesson['available_at']) ? date('d/m/Y H:i', strtotime((string) $lesson['available_at'])) : '';
$lessonAttendanceMode = (string) ($lesson['attendance_mode'] ?? 'video');
$lessonIsComplete = !empty($lesson['completed_at']);
$lessonIsNext = !$canManage && !$lessonAccessLocked && !$lessonIsComplete && !empty($nextLessonId) && (int) $lesson['id'] === (int) $nextLessonId;
$lessonButtonLabel = $lessonIsNext ? 'Continuar' : ($lessonIsComplete ? 'Rever' : 'Assistir');
?>
<article class="education-playlist-row <?= $lessonScheduleLocked ? 'is-scheduled' : '' ?> <?= $lessonIsComplete ? 'is-complete' : '' ?> <?= $lessonIsNext ? 'is-next' : '' ?> <?= $lessonAccessLocked ? 'is-locked' : '' ?>">
    <<?= $lessonAccessLocked ? 'div' : 'a' ?> class="education-playlist-main"<?= $lessonAccessLocked ? '' : ' href="' . e(url('/admin/education/lesson?id=' . $lesson['id'])) . '"' ?>>
        <span class="<?= $lessonIsComplete ? 'is-complete' : '' ?>">
            <i class="bi <?= $lessonAccessLocked ? 'bi-lock-fill' : ($lessonIsComplete ? 'bi-check-circle-fill' : ($lessonIsNext ? 'bi-play-circle-fill' : 'bi-circle')) ?>" aria-hidden="true"></i>
        </span>
        <span>
            <?php if ($lessonIsNext): ?>
                <em class="education-current-lesson-label">Próxima aula</em>
            <?php endif; ?>
            <strong><?= e($lesson['title']) ?></strong>
            <?php if ($lessonAccessLocked || $lessonAttendanceMode === 'manual' || $lessonAttendanceMode === 'none' || !empty($lesson['assignment_count']) || !empty($lesson['certificate_count'])): ?>
                <span class="education-playlist-badges">
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
            <span class="btn btn-sm btn-outline-secondary icon-btn disabled" aria-disabled="true">
                <i class="bi bi-lock-fill" aria-hidden="true"></i><?= $lessonScheduleLocked ? 'Agendada' : 'Bloqueada' ?>
            </span>
        <?php else: ?>
            <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>">
                <i class="bi <?= $lessonIsComplete ? 'bi-arrow-clockwise' : 'bi-play-circle' ?>" aria-hidden="true"></i><?= e($lessonButtonLabel) ?>
            </a>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <?php if ($lessonAttendanceMode === 'manual'): ?>
                <a class="btn btn-sm btn-outline-success icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'] . '&lesson_id=' . $lesson['id'])) ?>"><i class="bi bi-person-check" aria-hidden="true"></i>Validar presença</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>"><i class="bi bi-layers" aria-hidden="true"></i>Materiais</a>
            <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '&lesson_id=' . $lesson['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
            <form class="inline-form" method="post" action="<?= e(url('/admin/education/lesson/delete?id=' . $lesson['id'])) ?>" onsubmit="return confirm('Remover esta aula?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover</button>
            </form>
        <?php endif; ?>
    </div>
</article>
