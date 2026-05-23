<?php
$issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : date('d/m/Y');
$title = trim((string) ($course['certificate_title'] ?? ''));
if ($title === '') {
    $title = 'Certificado de conclusão';
}
$background = trim((string) ($course['certificate_background'] ?? ''));
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
                <small>Emitido em <?= e($issuedAt) ?> | Codigo <?= e($certificate['verification_code'] ?? '') ?></small>
                <?php if (!empty($course['teacher_name'])): ?>
                    <small>Professor: <?= e($course['teacher_name']) ?></small>
                <?php endif; ?>
                <small>Frequencia registrada: <?= e((string) ($certificateStatus['frequency'] ?? 0)) ?>%</small>
            </footer>
        </div>
    </article>
</section>
