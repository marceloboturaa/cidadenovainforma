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
    'notice' => 'Observar',
    'warning' => 'Precisa de ação',
];
?>

<div class="page-heading">
    <div>
        <p>Painel de alunos</p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/students/report/export?id=' . $course['id'] . '&start_date=' . $startDate . '&end_date=' . $endDate)) ?>"><i class="bi bi-filetype-csv" aria-hidden="true"></i>Exportar CSV</a>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance/report?id=' . $course['id'] . '&start_date=' . $startDate . '&end_date=' . $endDate)) ?>"><i class="bi bi-bar-chart" aria-hidden="true"></i>Relatório de chamada</a>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'])) ?>"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Fazer chamada</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
    </div>
</div>

<section class="panel education-student-report-panel">
    <div class="section-heading">
        <h2>Resumo da turma</h2>
        <span><?= e(date('d/m/Y', strtotime($startDate))) ?> até <?= e(date('d/m/Y', strtotime($endDate))) ?></span>
    </div>

    <form class="education-attendance-date education-report-filter" method="get" action="<?= e(url('/admin/education/students/report')) ?>">
        <input type="hidden" name="id" value="<?= e((string) $course['id']) ?>">
        <div>
            <label class="form-label">Início</label>
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
        <div><span>Frequência média</span><strong><?= e((string) ($summary['average_frequency'] ?? 0)) ?>%</strong></div>
        <div><span>Progresso médio</span><strong><?= e((string) ($summary['average_progress'] ?? 0)) ?>%</strong></div>
        <div><span>Atividades feitas</span><strong><?= e((string) ($summary['activity_done_percent'] ?? 0)) ?>%</strong></div>
        <div><span>Precisam de atenção</span><strong><?= e((string) ($summary['students_need_attention'] ?? 0)) ?></strong></div>
        <div><span>Aguardando correção</span><strong><?= e((string) ($summary['pending_corrections'] ?? 0)) ?></strong></div>
        <div><span>Para refazer</span><strong><?= e((string) ($summary['redo_requests'] ?? 0)) ?></strong></div>
    </div>

    <?php if ($students): ?>
        <div class="education-student-report-tools">
            <label>
                <span>Pesquisar aluno</span>
                <input class="form-control" type="search" placeholder="Digite ou escolha um aluno" list="student-report-options" data-student-report-search>
            </label>
            <datalist id="student-report-options">
                <?php foreach ($students as $studentOption): ?>
                    <option value="<?= e($studentOption['name'] ?? '') ?>"><?= e($studentOption['email'] ?? '') ?></option>
                <?php endforeach; ?>
            </datalist>
            <button class="btn btn-outline-primary icon-btn" type="button" data-student-report-find><i class="bi bi-search" aria-hidden="true"></i>Localizar</button>
            <button class="btn btn-outline-secondary icon-btn" type="button" data-student-report-clear><i class="bi bi-x-circle" aria-hidden="true"></i>Limpar</button>
            <strong><span data-student-report-visible-count><?= e((string) count($students)) ?></span> aluno(s) encontrado(s)</strong>
        </div>

        <div class="education-student-report-table" data-student-report-list>
            <div class="education-student-report-row education-student-report-head">
                <span>Aluno</span>
                <span>Status</span>
                <span>Frequência</span>
                <span>Aulas</span>
                <span>Atividades</span>
                <span>Correção</span>
            </div>
            <?php foreach ($students as $student): ?>
                <?php
                $activityTotal = (int) ($student['activity_total'] ?? 0);
                $activityDone = (int) ($student['activity_done'] ?? 0);
                $activityPercent = $activityTotal > 0 ? (int) round(($activityDone / $activityTotal) * 100) : 0;
                $attentionLevel = (string) ($student['attention_level'] ?? 'ok');
                $attentionClass = in_array($attentionLevel, ['ok', 'notice', 'warning'], true) ? $attentionLevel : 'ok';
                $statusLabel = $attentionLabels[$attentionClass] ?? 'Em dia';
                $statusDetail = $student['progress_label'] ?? 'Sem progresso';
                if ((int) ($student['activity_pending_correction'] ?? 0) > 0) {
                    $statusLabel = 'Corrigir entrega';
                    $statusDetail = (int) $student['activity_pending_correction'] . ' correção(ões) pendente(s)';
                } elseif ((int) ($student['activity_redo'] ?? 0) > 0) {
                    $statusLabel = 'Aguardando refazer';
                    $statusDetail = (int) $student['activity_redo'] . ' atividade(s) devolvida(s)';
                } elseif ((int) ($student['attendance_records'] ?? 0) === 0) {
                    $statusLabel = 'Sem chamada';
                    $statusDetail = 'Sem registro no período';
                } elseif ((int) ($student['attendance_records'] ?? 0) > 0 && (int) ($student['frequency'] ?? 0) < 75) {
                    $statusLabel = 'Frequência baixa';
                    $statusDetail = (int) ($student['frequency'] ?? 0) . '% no período';
                } elseif ((int) ($student['lesson_count'] ?? 0) > 0 && (int) ($student['progress_percent'] ?? 0) === 0) {
                    $statusLabel = 'Sem progresso';
                    $statusDetail = 'Nenhuma aula concluída';
                } elseif ((int) ($student['lesson_count'] ?? 0) > 0 && (int) ($student['progress_percent'] ?? 0) < 35) {
                    $statusLabel = 'Progresso baixo';
                    $statusDetail = (int) ($student['progress_percent'] ?? 0) . '% concluído';
                } elseif ((int) ($student['activity_pending'] ?? 0) > 0) {
                    $statusLabel = 'Atividade pendente';
                    $statusDetail = (int) $student['activity_pending'] . ' entrega(s) faltando';
                }
                $searchText = implode(' ', array_filter([
                    $student['name'] ?? '',
                    $student['email'] ?? '',
                    $statusLabel,
                    $statusDetail,
                    $student['progress_label'] ?? '',
                    implode(' ', $student['teacher_actions'] ?? []),
                    implode(' ', $student['attention_reasons'] ?? []),
                    implode(' ', array_column($student['activities'] ?? [], 'title')),
                    implode(' ', array_column($student['activities'] ?? [], 'lesson_title')),
                ]));
                ?>
                <details class="education-student-report-row education-student-report-item is-<?= e($attentionClass) ?>" data-student-report-row data-student-report-name="<?= e($student['name'] ?? '') ?>" data-student-report-email="<?= e($student['email'] ?? '') ?>" data-student-report-search-text="<?= e($searchText) ?>">
                    <summary>
                        <div>
                            <strong><?= e($student['name']) ?></strong>
                            <small><?= e($student['email']) ?></small>
                        </div>
                        <span class="student-report-metric metric-status">
                            <strong><?= e($statusLabel) ?></strong>
                            <small><?= e($statusDetail) ?></small>
                        </span>
                        <span class="student-report-metric metric-frequency">
                            <strong><?= e((string) ($student['frequency'] ?? 0)) ?>%</strong>
                            <small><?= e((string) ($student['present_count'] ?? 0)) ?> presença(s), <?= e((string) ($student['absent_count'] ?? 0)) ?> falta(s), <?= e((string) ($student['justified_count'] ?? 0)) ?> justificada(s)</small>
                        </span>
                        <span class="student-report-metric metric-lessons">
                            <strong><?= e((string) ($student['completed_lessons'] ?? 0)) ?>/<?= e((string) ($student['lesson_count'] ?? 0)) ?></strong>
                            <small><?= e((string) ($student['progress_percent'] ?? 0)) ?>% concluído</small>
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
                            <small><?= e((string) ($student['completed_lessons'] ?? 0)) ?> de <?= e((string) ($student['lesson_count'] ?? 0)) ?> aula(s) obrigatória(s) concluídas.</small>
                            <dl>
                                <div><dt>Frequência</dt><dd><?= e((string) ($student['frequency'] ?? 0)) ?>%</dd></div>
                                <div><dt>Atividades pendentes</dt><dd><?= e((string) ($student['activity_pending'] ?? 0)) ?></dd></div>
                                <div><dt>Correções pendentes</dt><dd><?= e((string) ($student['activity_pending_correction'] ?? 0)) ?></dd></div>
                                <div><dt>Para refazer</dt><dd><?= e((string) ($student['activity_redo'] ?? 0)) ?></dd></div>
                            </dl>
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
                                <small>Nenhuma correção ou pendência crítica encontrada neste período.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="education-student-activity-list">
                        <?php if ($activityItems): ?>
                            <div class="education-student-activity-head">
                                <span>Atividade</span>
                                <span>Entrega</span>
                                <span>Correção</span>
                                <span>Nota</span>
                                <span>Atualização</span>
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
                                        <small><?= e(($activity['type'] ?? '') === 'form' ? 'Formulário' : 'Tarefa') ?><?= !empty($activity['lesson_title']) ? ' - ' . e($activity['lesson_title']) : '' ?></small>
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
            <div class="empty-state" data-student-report-empty hidden>Nenhum aluno encontrado com esse termo.</div>
        </div>
    <?php else: ?>
        <div class="empty-state">Nenhum aluno aprovado neste curso.</div>
    <?php endif; ?>
</section>
