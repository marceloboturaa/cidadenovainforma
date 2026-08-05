<?php
$rows = $rows ?? [];
$dates = $dates ?? [];
$totalPresent = 0;
$totalAbsent = 0;
$totalJustified = 0;

foreach ($rows as $row) {
    $totalPresent += (int) ($row['present_count'] ?? 0);
    $totalAbsent += (int) ($row['absent_count'] ?? 0);
    $totalJustified += (int) ($row['justified_count'] ?? 0);
}

$totalRecords = $totalPresent + $totalAbsent + $totalJustified;
?>

<div class="page-heading">
    <div>
        <p>Relatório de chamada</p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/students/report?id=' . $course['id'] . '&start_date=' . $startDate . '&end_date=' . $endDate)) ?>"><i class="bi bi-people" aria-hidden="true"></i>Painel de alunos</a>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/attendance?id=' . $course['id'])) ?>"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Fazer chamada</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
    </div>
</div>

<section class="panel education-attendance-panel">
    <div class="section-heading">
        <h2>Resumo do período</h2>
        <span><?= e(date('d/m/Y', strtotime($startDate))) ?> até <?= e(date('d/m/Y', strtotime($endDate))) ?></span>
    </div>

    <form class="education-attendance-date education-report-filter" method="get" action="<?= e(url('/admin/education/attendance/report')) ?>">
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

    <div class="education-report-summary">
        <div><span>Chamadas</span><strong><?= e((string) count($dates)) ?></strong></div>
        <div><span>Presenças</span><strong><?= e((string) $totalPresent) ?></strong></div>
        <div><span>Faltas</span><strong><?= e((string) $totalAbsent) ?></strong></div>
        <div><span>Justificadas</span><strong><?= e((string) $totalJustified) ?></strong></div>
    </div>

    <?php if ($rows): ?>
        <div class="education-report-table">
            <div class="education-report-row education-report-head">
                <span>Aluno</span>
                <span>Presenças</span>
                <span>Faltas</span>
                <span>Justificadas</span>
                <span>Frequência</span>
            </div>
            <?php foreach ($rows as $row): ?>
                <?php
                $present = (int) ($row['present_count'] ?? 0);
                $absent = (int) ($row['absent_count'] ?? 0);
                $justified = (int) ($row['justified_count'] ?? 0);
                $studentRecords = $present + $absent + $justified;
                $frequency = $studentRecords > 0 ? (int) round(($present / $studentRecords) * 100) : 0;
                ?>
                <div class="education-report-row">
                    <div>
                        <strong><?= e($row['name']) ?></strong>
                        <small><?= e($row['email']) ?></small>
                    </div>
                    <span><?= e((string) $present) ?></span>
                    <span><?= e((string) $absent) ?></span>
                    <span><?= e((string) $justified) ?></span>
                    <span><?= e((string) $frequency) ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Nenhum estudante matriculado neste curso.</div>
    <?php endif; ?>

    <?php if ($dates): ?>
        <div class="education-report-dates">
            <h2>Dias com chamada</h2>
            <?php foreach ($dates as $item): ?>
                <div>
                    <strong><?= e(date('d/m/Y', strtotime($item['attendance_date']))) ?></strong>
                    <span><?= e((string) (int) $item['present_count']) ?> presença(s)</span>
                    <span><?= e((string) (int) $item['absent_count']) ?> falta(s)</span>
                    <span><?= e((string) (int) $item['justified_count']) ?> justificada(s)</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($totalRecords === 0): ?>
        <div class="empty-state">Nenhuma chamada registrada neste período.</div>
    <?php endif; ?>
</section>
