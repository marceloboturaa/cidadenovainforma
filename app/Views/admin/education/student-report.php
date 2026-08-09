<?php
$students = $students ?? [];
$activityItems = $activityItems ?? [];
$summary = $summary ?? [];
$statusLabels = [
    'corrected' => 'Corrigida',
    'redo' => 'Refazer',
    'pending' => 'Pendente',
];
$attentionLabels = [
    'ok' => 'Em dia',
    'notice' => 'Acompanhar',
    'warning' => 'Requer acao',
];
?>

<div class="page-heading">
    <div>
        <p>Painel de alunos</p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/students/report/export?id=' . $course['id'] . '&start_date=' . $startDate . '&end_date=' . $endDate)) ?>"><i class="bi bi-filetype-csv" aria-hidden="true"></i>Exportar CSV</a>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance/report?id=' . $course['id'] . '&start_date=' . $startDate . '&end_date=' . $endDate)) ?>"><i class="bi bi-bar-chart" aria-hidden="true"></i>Relatorio de chamada</a>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'])) ?>"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Fazer chamada</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
    </div>
</div>

<section class="panel education-student-report-panel">
    <div class="section-heading">
        <h2>Resumo da turma</h2>
        <span><?= e(date('d/m/Y', strtotime($startDate))) ?> ate <?= e(date('d/m/Y', strtotime($endDate))) ?></span>
    </div>

    <form class="education-attendance-date education-report-filter" method="get" action="<?= e(url('/admin/education/students/report')) ?>">
        <input type="hidden" name="id" value="<?= e((string) $course['id']) ?>">
        <div>
            <label class="form-label">Inicio</label>
            <input class="form-control" type="date" name="start_date" value="<?= e($startDate) ?>">
        </div>
        <div>
            <label class="form-label">Fim</label>
            <input class="form-control" type="date" name="end_date" value="<?= e($endDate) ?>">
        </div>
        <button class="btn btn-outline-primary icon-btn"><i class="bi bi-funnel" aria-hidden="true"></i>Filtrar</button>
    </form>

    <div class="education-report-summary education-student-report-summary">
        <div><span>Alunos</span><strong><?= e((string) ($summary['student_count'] ?? 0)) ?></strong></div>
        <div><span>Frequencia media</span><strong><?= e((string) ($summary['average_frequency'] ?? 0)) ?>%</strong></div>
        <div><span>Progresso medio</span><strong><?= e((string) ($summary['average_progress'] ?? 0)) ?>%</strong></div>
        <div><span>Atividades feitas</span><strong><?= e((string) ($summary['activity_done_percent'] ?? 0)) ?>%</strong></div>
        <div><span>Precisam de atencao</span><strong><?= e((string) ($summary['students_need_attention'] ?? 0)) ?></strong></div>
        <div><span>Aguardando correcao</span><strong><?= e((string) ($summary['pending_corrections'] ?? 0)) ?></strong></div>
    </div>

    <?php if ($students): ?>
        <div class="education-student-report-table">
            <div class="education-student-report-row education-student-report-head">
                <span>Aluno</span>
                <span>Situacao</span>
                <span>Frequencia</span>
                <span>Aulas</span>
                <span>Atividades</span>
                <span>Correcao</span>
            </div>
            <?php foreach ($students as $student): ?>
                <?php
                $activityTotal = (int) ($student['activity_total'] ?? 0);
                $activityDone = (int) ($student['activity_done'] ?? 0);
                $activityPercent = $activityTotal > 0 ? (int) round(($activityDone / $activityTotal) * 100) : 0;
                $attentionLevel = (string) ($student['attention_level'] ?? 'ok');
                $attentionClass = in_array($attentionLevel, ['ok', 'notice', 'warning'], true) ? $attentionLevel : 'ok';
                ?>
                <details class="education-student-report-row education-student-report-item is-<?= e($attentionClass) ?>">
                    <summary>
                        <div>
                            <strong><?= e($student['name']) ?></strong>
                            <small><?= e($student['email']) ?></small>
                        </div>
                        <span class="student-report-metric metric-status">
                            <strong><?= e($attentionLabels[$attentionClass] ?? 'Em dia') ?></strong>
                            <small><?= e($student['progress_label'] ?? 'Sem progresso') ?></small>
                        </span>
                        <span class="student-report-metric metric-frequency">
                            <strong><?= e((string) ($student['frequency'] ?? 0)) ?>%</strong>
                            <small><?= e((string) ($student['present_count'] ?? 0)) ?> presenca(s), <?= e((string) ($student['absent_count'] ?? 0)) ?> falta(s), <?= e((string) ($student['justified_count'] ?? 0)) ?> justificada(s)</small>
                        </span>
                        <span class="student-report-metric metric-lessons">
                            <strong><?= e((string) ($student['completed_lessons'] ?? 0)) ?>/<?= e((string) ($student['lesson_count'] ?? 0)) ?></strong>
                            <small><?= e((string) ($student['progress_percent'] ?? 0)) ?>% concluido</small>
                        </span>
                        <span class="student-report-metric metric-activities">
                            <strong><?= e((string) $activityDone) ?>/<?= e((string) $activityTotal) ?></strong>
                            <small><?= e((string) $activityPercent) ?>% feito</small>
                        </span>
                        <span class="student-report-metric metric-correction">
                            <strong><?= e((string) ($student['activity_corrected'] ?? 0)) ?> corrigida(s)</strong>
                            <small><?= e((string) ($student['activity_pending_correction'] ?? 0)) ?> aguardando</small>
                        </span>
                    </summary>
                    <div class="education-student-progress-detail">
                        <div class="education-student-progress-card">
                            <div class="education-student-progress-title">
                                <strong>Progresso do aluno</strong>
                                <span><?= e((string) ($student['progress_percent'] ?? 0)) ?>%</span>
                            </div>
                            <div class="education-progress" aria-hidden="true">
                                <div><i style="width: <?= e((string) min(100, max(0, (int) ($student['progress_percent'] ?? 0)))) ?>%"></i></div>
                            </div>
                            <small><?= e((string) ($student['completed_lessons'] ?? 0)) ?> de <?= e((string) ($student['lesson_count'] ?? 0)) ?> aula(s) obrigatoria(s) concluidas.</small>
                        </div>
                        <div class="education-student-action-card">
                            <strong>O que o professor precisa verificar</strong>
                            <?php if (!empty($student['teacher_actions']) || !empty($student['attention_reasons'])): ?>
                                <ul>
                                    <?php foreach (array_merge($student['teacher_actions'] ?? [], $student['attention_reasons'] ?? []) as $action): ?>
                                        <li><?= e($action) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <small>Nenhuma correcao ou pendencia critica encontrada neste periodo.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="education-student-activity-list">
                        <?php if ($activityItems): ?>
                            <div class="education-student-activity-head">
                                <span>Atividade</span>
                                <span>Entrega</span>
                                <span>Correcao</span>
                                <span>Nota</span>
                                <span>Atualizacao</span>
                            </div>
                            <?php foreach ($student['activities'] as $activity): ?>
                                <?php
                                $done = !empty($activity['done']);
                                $status = (string) ($activity['correction_status'] ?? '');
                                $statusClass = in_array($status, ['corrected', 'redo', 'pending'], true) ? $status : 'none';
                                ?>
                                <article class="<?= $done ? 'is-done' : 'is-pending' ?> status-<?= e($statusClass) ?>">
                                    <div>
                                        <strong><?= e($activity['title']) ?></strong>
                                        <small><?= e(($activity['type'] ?? '') === 'form' ? 'Formulario' : 'Tarefa') ?><?= !empty($activity['lesson_title']) ? ' - ' . e($activity['lesson_title']) : '' ?></small>
                                    </div>
                                    <span class="state-pill <?= $done ? 'is-active' : 'is-muted' ?>"><?= $done ? 'Feita' : 'Pendente' ?></span>
                                    <span><?= e($statusLabels[$status] ?? ($done ? 'Enviada' : '-')) ?></span>
                                    <span><?= e($activity['grade'] ?: '-') ?></span>
                                    <span><?= !empty($activity['updated_at']) ? e(date('d/m/Y', strtotime($activity['updated_at']))) : '-' ?></span>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">Nenhuma atividade cadastrada neste curso.</div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Nenhum aluno aprovado neste curso.</div>
    <?php endif; ?>
</section>
