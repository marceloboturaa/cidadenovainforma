<?php
$students = $students ?? [];
$records = $records ?? [];
$lesson = $lesson ?? null;
$lessons = $lessons ?? [];
$statusLabels = [
    'present' => 'Presente',
    'absent' => 'Falta',
    'justified' => 'Justificada',
];
?>

<div class="page-heading">
    <div>
        <p><?= $lesson ? 'Validação de presença' : 'Chamada' ?></p>
        <h1><?= e($lesson['title'] ?? $course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance/report?id=' . $course['id'])) ?>"><i class="bi bi-bar-chart" aria-hidden="true"></i>Relatório</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
    </div>
</div>

<section class="panel education-attendance-panel">
    <div class="section-heading">
        <h2>Registrar presença</h2>
        <span><?= e((string) count($students)) ?> estudante(s) matriculado(s)</span>
    </div>

    <form class="education-attendance-date" method="get" action="<?= e(url('/admin/education/attendance')) ?>">
        <input type="hidden" name="id" value="<?= e((string) $course['id']) ?>">
        <div>
            <label class="form-label">Aula ao vivo</label>
            <select class="form-select" name="lesson_id">
                <option value="">Chamada geral do curso</option>
                <?php foreach ($lessons as $item): ?>
                    <?php if (($item['attendance_mode'] ?? 'video') !== 'manual') { continue; } ?>
                    <option value="<?= e((string) $item['id']) ?>" <?= selected((string) ($lesson['id'] ?? ''), (string) $item['id']) ?>>
                        <?= e($item['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Data da chamada</label>
            <input class="form-control" type="date" name="date" value="<?= e($date) ?>">
        </div>
        <button class="btn btn-outline-primary icon-btn"><i class="bi bi-calendar-check" aria-hidden="true"></i>Carregar</button>
    </form>

    <?php if ($students): ?>
        <form method="post" action="<?= e(url('/admin/education/attendance?id=' . $course['id'])) ?>" class="education-attendance-form">
            <?= csrf_field() ?>
            <input type="hidden" name="attendance_date" value="<?= e($date) ?>">
            <input type="hidden" name="lesson_id" value="<?= e((string) ($lesson['id'] ?? 0)) ?>">
            <?php if ($lesson): ?>
                <div class="education-attendance-live-note">
                    <i class="bi bi-person-check" aria-hidden="true"></i>
                    <span>Marcar como presente também conclui esta aula para o aluno. Falta ou justificada remove a conclusão desta aula.</span>
                </div>
            <?php endif; ?>
            <div class="education-attendance-list">
                <?php foreach ($students as $student): ?>
                    <?php $record = $records[(int) $student['id']] ?? []; ?>
                    <?php $currentStatus = (string) ($record['status'] ?? 'present'); ?>
                    <article class="education-attendance-row">
                        <div>
                            <strong><?= e($student['name']) ?></strong>
                            <small><?= e($student['email']) ?></small>
                        </div>
                        <div class="education-attendance-status">
                            <?php foreach ($statusLabels as $status => $label): ?>
                                <label>
                                    <input type="radio" name="attendance[<?= e((string) $student['id']) ?>][status]" value="<?= e($status) ?>" <?= checked($currentStatus, $status) ?>>
                                    <span><?= e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <input class="form-control" name="attendance[<?= e((string) $student['id']) ?>][notes]" value="<?= e($record['notes'] ?? '') ?>" maxlength="255" placeholder="Observação opcional">
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="form-action-cell">
                <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar chamada</button>
            </div>
        </form>
    <?php else: ?>
        <div class="empty-state">Nenhum estudante matriculado neste curso. Matricule os alunos em Cursos antes de registrar chamada.</div>
    <?php endif; ?>
</section>
