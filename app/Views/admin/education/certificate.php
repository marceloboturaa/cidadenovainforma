<?php
$issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : date('d/m/Y');
$title = trim((string) ($course['certificate_title'] ?? ''));
if ($title === '') {
    $title = 'Certificado de conclusão';
}
$background = trim((string) ($course['certificate_background'] ?? ''));
$programEnabled = (int) ($course['certificate_program_enabled'] ?? 1) === 1;
$programColumns = max(1, min(4, (int) ($course['certificate_program_columns'] ?? 2)));
$programExtra = trim((string) ($course['certificate_program_extra'] ?? ''));
$certificateProgram = $certificateProgram ?? [];
?>

<div class="page-heading certificate-toolbar">
    <div>
        <p>Certificado emitido</p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <button class="btn btn-primary icon-btn" type="button" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i>Imprimir</button>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-certificate')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
    </div>
</div>

<section class="panel education-certificate-sheet-panel">
    <article class="education-certificate-sheet<?= $background !== '' ? ' has-background' : '' ?>"<?= $background !== '' ? ' style="background-image: url(\'' . e(media_url($background)) . '\');"' : '' ?>>
        <div class="education-certificate-copy">
            <span>Certificado</span>
            <h2><?= e($title) ?></h2>
            <div><?= nl2br(e($certificateText)) ?></div>
            <footer>
                <strong><?= e($certificate['student_name'] ?? '') ?></strong>
            </footer>
        </div>
        <footer class="education-certificate-footnote">
            <span>Emitido em <?= e($issuedAt) ?></span>
            <span>Código <?= e($certificate['verification_code'] ?? '') ?></span>
            <?php if (!empty($course['teacher_name'])): ?>
                <span>Professor: <?= e($course['teacher_name']) ?></span>
            <?php endif; ?>
            <span>Frequência registrada: <?= e((string) ($certificateStatus['frequency'] ?? 0)) ?>%</span>
        </footer>
    </article>
    <?php if ($programEnabled): ?>
        <article class="education-certificate-sheet education-certificate-program-sheet" style="--certificate-program-columns: <?= e((string) $programColumns) ?>;">
            <header class="education-certificate-program-header">
                <span>Verso do certificado</span>
                <h2>Programação cursada</h2>
                <p><?= e($course['title'] ?? '') ?></p>
            </header>

            <?php if ($programExtra !== ''): ?>
                <section class="education-certificate-program-extra">
                    <?= nl2br(e($programExtra)) ?>
                </section>
            <?php endif; ?>

            <section class="education-certificate-program-list">
                <?php foreach ($certificateProgram as $module): ?>
                    <article class="education-certificate-program-module">
                        <h3><?= e($module['title'] ?? 'Módulo') ?></h3>
                        <?php if (!empty($module['summary'])): ?>
                            <p><?= e($module['summary']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($module['lessons'])): ?>
                            <ol>
                                <?php foreach ($module['lessons'] as $lesson): ?>
                                    <li>
                                        <strong><?= e($lesson['title'] ?? 'Aula') ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$certificateProgram): ?>
                    <p class="education-certificate-program-empty">Nenhuma aula cadastrada para este curso.</p>
                <?php endif; ?>
            </section>
        </article>
    <?php endif; ?>
</section>
