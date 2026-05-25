<?php
$issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : date('d/m/Y');
$title = trim((string) ($course['certificate_title'] ?? ''));
if ($title === '') {
    $title = 'Certificado de conclusão';
}
$background = trim((string) ($course['certificate_background'] ?? ''));
$programBackground = trim((string) ($course['certificate_program_background'] ?? ''));
$programEnabled = (int) ($course['certificate_program_enabled'] ?? 1) === 1;
$programColumns = max(1, min(4, (int) ($course['certificate_program_columns'] ?? 2)));
$programExtra = trim((string) ($course['certificate_program_extra'] ?? ''));
$certificateProgram = $certificateProgram ?? [];
$certificatePeriod = $certificatePeriod ?? [];
$formatCertificateDate = static function (?string $value) use ($issuedAt): string {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d/m/Y', $timestamp) : $issuedAt;
};
$periodStart = $formatCertificateDate($certificatePeriod['start'] ?? null);
$periodEnd = $formatCertificateDate($certificatePeriod['end'] ?? ($certificate['issued_at'] ?? null));
$minimumFrequency = max(75, (int) ($certificateStatus['minimum_frequency'] ?? 0));
$courseNature = trim((string) ($course['certificate_course_nature'] ?? '')) ?: 'Curso Livre de Capacitação Profissional - Formação Continuada';
$courseModality = trim((string) ($course['certificate_modality'] ?? '')) ?: 'Online';
$approvalCriteria = trim((string) ($course['certificate_approval_criteria'] ?? '')) ?: 'Certificado concedido mediante frequência mínima de ' . $minimumFrequency . '% e aproveitamento satisfatório.';
$legalText = trim((string) ($course['certificate_legal_text'] ?? '')) ?: 'Curso Livre de Capacitação Profissional ofertado nos termos da Lei nº 9.394/96 (LDB) e Decreto nº 5.154/04.';
$institutionName = trim((string) ($course['certificate_institution_name'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_NAME') ?: 'Cidade Nova Informa - CNI');
$institutionCity = trim((string) ($course['certificate_institution_city'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_CITY') ?: 'Foz do Iguaçu - PR');
$institutionCnpj = trim((string) ($course['certificate_institution_cnpj'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_CNPJ') ?: '');
$institutionSite = trim((string) ($course['certificate_institution_site'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_SITE') ?: 'www.cidadenovainforma.com.br');
$certificatePublicVerify = $institutionSite . '/certificados';
$courseObjectives = trim((string) ($course['certificate_objectives'] ?? ''));
$courseCompetencies = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($course['certificate_competencies'] ?? '')) ?: [])));
$courseResponsible = trim((string) ($course['certificate_responsible_name'] ?? '')) ?: trim((string) ($course['teacher_name'] ?? ''));
$courseResponsibleCredential = trim((string) ($course['certificate_responsible_credential'] ?? ''));
$hasProgramSummary = $courseObjectives !== '' || $courseCompetencies || $courseResponsible !== '' || $courseResponsibleCredential !== '';
$verificationUrl = url('/certificado/' . ($certificate['verification_code'] ?? ''));
$verificationQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=8&data=' . rawurlencode($verificationUrl);
?>

<div class="page-heading certificate-toolbar">
    <div>
        <p>Certificado emitido</p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="heading-actions">
        <button class="btn btn-primary icon-btn" type="button" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i>Imprimir</button>
        <a class="btn btn-outline-primary icon-btn" href="<?= e($verificationUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar certificado</a>
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $course['id'] . '#course-certificate')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
    </div>
</div>

<section class="panel education-certificate-sheet-panel">
    <article class="education-certificate-sheet<?= $background !== '' ? ' has-background' : '' ?>"<?= $background !== '' ? ' style="background-image: url(\'' . e(media_url($background)) . '\');"' : '' ?>>
        <div class="education-certificate-copy">
            <span>Certificado</span>
            <h2><?= e($title) ?></h2>
            <p class="education-certificate-nature"><?= e($courseNature) ?></p>
            <div><?= nl2br(e($certificateText)) ?></div>
            <section class="education-certificate-details" aria-label="Detalhes do certificado">
                <span>Modalidade: <?= e($courseModality) ?></span>
                <span>Realizado de <?= e($periodStart) ?> até <?= e($periodEnd) ?></span>
                <span><?= e($approvalCriteria) ?></span>
            </section>
            <footer>
                <strong><?= e($certificate['student_name'] ?? '') ?></strong>
            </footer>
        </div>
        <footer class="education-certificate-footnote">
            <div class="education-certificate-footnote-text">
                <div class="education-certificate-institution">
                    <strong><?= e($institutionName) ?></strong>
                    <span><?= e($institutionCity) ?></span>
                    <?php if ($institutionCnpj !== ''): ?>
                        <span>CNPJ: <?= e($institutionCnpj) ?></span>
                    <?php endif; ?>
                    <span><?= e($institutionSite) ?></span>
                </div>
                <div class="education-certificate-footnote-meta">
                    <span>Emitido em <?= e($issuedAt) ?></span>
                    <span>Código <?= e($certificate['verification_code'] ?? '') ?></span>
                    <?php if (!empty($course['teacher_name'])): ?>
                        <span>Professor: <?= e($course['teacher_name']) ?></span>
                    <?php endif; ?>
                    <span>Frequência registrada: <?= e((string) ($certificateStatus['frequency'] ?? 0)) ?>%</span>
                </div>
                <p><?= e($legalText) ?></p>
            </div>
            <figure class="education-certificate-qr">
                <img src="<?= e($verificationQrUrl) ?>" alt="QR Code para verificar o certificado">
                <figcaption>Verifique a autenticidade em: <?= e($certificatePublicVerify) ?></figcaption>
            </figure>
        </footer>
    </article>
    <?php if ($programEnabled): ?>
        <article class="education-certificate-sheet education-certificate-program-sheet education-certificate-program-columns-<?= e((string) $programColumns) ?><?= $programColumns >= 2 ? ' is-multi-column' : '' ?><?= $programBackground !== '' ? ' has-background' : '' ?>" style="--certificate-program-columns: <?= e((string) $programColumns) ?>;<?= $programBackground !== '' ? ' background-image: url(\'' . e(media_url($programBackground)) . '\');' : '' ?>">
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

            <?php if ($hasProgramSummary): ?>
                <section class="education-certificate-program-summary" aria-label="Informações institucionais do curso">
                    <?php if ($courseObjectives !== ''): ?>
                        <article>
                            <h3>Objetivos do curso</h3>
                            <p><?= nl2br(e($courseObjectives)) ?></p>
                        </article>
                    <?php endif; ?>
                    <?php if ($courseCompetencies): ?>
                        <article>
                            <h3>Competências desenvolvidas</h3>
                            <ul>
                                <?php foreach ($courseCompetencies as $competency): ?>
                                    <li><?= e($competency) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endif; ?>
                    <?php if ($courseResponsible !== '' || $courseResponsibleCredential !== ''): ?>
                        <article>
                            <h3>Responsável pelo curso</h3>
                            <?php if ($courseResponsible !== ''): ?>
                                <p>Professor Responsável: <?= e($courseResponsible) ?></p>
                            <?php endif; ?>
                            <?php if ($courseResponsibleCredential !== ''): ?>
                                <p><?= e($courseResponsibleCredential) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endif; ?>
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

<section class="panel education-certificate-name-panel">
    <div class="section-heading">
        <h2>Nome no certificado</h2>
        <span><?= e($certificate['student_name'] ?? '') ?></span>
    </div>
    <?php if (($certificate['name_change_status'] ?? '') === 'pending'): ?>
        <p class="field-hint mb-0">Solicitação pendente para: <strong><?= e($certificate['requested_student_name'] ?? '') ?></strong></p>
    <?php else: ?>
        <form method="post" action="<?= e(url('/admin/education/certificate/name-change?id=' . $course['id'])) ?>" class="education-certificate-name-form">
            <?= csrf_field() ?>
            <label>
                <span class="form-label">Solicitar alteração do nome completo</span>
                <input class="form-control" name="requested_student_name" maxlength="180" value="<?= e($certificate['student_name'] ?? '') ?>" required>
            </label>
            <button class="btn btn-outline-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar para autorização</button>
        </form>
        <small class="field-hint">A alteração precisa ser autorizada por professor, diretor ou master do curso.</small>
    <?php endif; ?>
</section>
