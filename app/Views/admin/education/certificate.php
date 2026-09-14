<?php
$issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : date('d/m/Y');
$heading = trim((string) ($course['certificate_heading'] ?? '')) ?: 'Certificado';
$title = trim((string) ($course['certificate_title'] ?? ''));
if ($title === '') {
    $title = 'Certificado de conclusão';
}
$background = trim((string) ($course['certificate_background'] ?? ''));
$readyImage = trim((string) ($course['certificate_ready_image'] ?? ''));
$programBackground = trim((string) ($course['certificate_program_background'] ?? ''));
$programBackgroundColor = trim((string) ($course['certificate_program_background_color'] ?? ''));
$footerOnBack = (int) ($course['certificate_footer_on_back'] ?? 0) === 1 && $readyImage === '';
$programBackgroundEnabled = (int) ($course['certificate_program_background_enabled'] ?? 1) === 1;
$programTextColor = trim((string) ($course['certificate_program_text_color'] ?? ''));
$programEnabled = (int) ($course['certificate_program_enabled'] ?? 1) === 1 && $readyImage === '';
$isRecognitionCertificate = ($course['certificate_activity_type'] ?? '') === 'reconhecimento';
$certificateFont = trim((string) ($course['certificate_font_family'] ?? ''));
$fontClass = in_array($certificateFont, ['serif', 'georgia', 'garamond', 'playfair', 'montserrat'], true) ? ' certificate-font-' . $certificateFont : '';
$textColor = trim((string) ($course['certificate_text_color'] ?? ''));
$bodyBackgroundColor = trim((string) ($course['certificate_body_background_color'] ?? ''));
$bodyBackgroundEnabled = (int) ($course['certificate_body_background_enabled'] ?? 0) === 1;
$footerTextColor = trim((string) ($course['certificate_footer_text_color'] ?? ''));
$footerBackgroundColor = trim((string) ($course['certificate_footer_background_color'] ?? ''));
$footerBackgroundEnabled = (int) ($course['certificate_footer_background_enabled'] ?? 1) === 1;
$footerRounded = (int) ($course['certificate_footer_rounded'] ?? 0) === 1;
$certificateStyle = [];
if (preg_match('/^#[0-9a-fA-F]{6}$/', $textColor)) {
    $certificateStyle[] = '--certificate-text-color: ' . $textColor;
}
if ($bodyBackgroundEnabled && preg_match('/^#[0-9a-fA-F]{6}$/', $bodyBackgroundColor)) {
    $certificateStyle[] = '--certificate-body-background: ' . $bodyBackgroundColor;
}
if (preg_match('/^#[0-9a-fA-F]{6}$/', $footerTextColor)) {
    $certificateStyle[] = '--certificate-footer-text-color: ' . $footerTextColor;
}
if (preg_match('/^#[0-9a-fA-F]{6}$/', $footerBackgroundColor)) {
    $certificateStyle[] = '--certificate-footer-background: ' . $footerBackgroundColor;
}
if ($footerRounded) {
    $certificateStyle[] = '--certificate-footnote-radius: 8mm';
}
if (preg_match('/^#[0-9a-fA-F]{6}$/', $programBackgroundColor)) {
    $certificateStyle[] = '--certificate-program-background: ' . $programBackgroundColor;
}
$programStyle = $certificateStyle;
if (preg_match('/^#[0-9a-fA-F]{6}$/', $programTextColor)) {
    $programStyle[] = '--certificate-text-color: ' . $programTextColor;
}
$certificateStyleAttr = $certificateStyle ? ' style="' . e(implode('; ', $certificateStyle)) . ';"' : '';
$programColumns = max(1, min(4, (int) ($course['certificate_program_columns'] ?? 2)));
$programExtra = trim((string) ($course['certificate_program_extra'] ?? ''));
$certificateProgram = $certificateProgram ?? [];
$certificatePeriod = $certificatePeriod ?? [];
$isCertificatePreview = !empty($isCertificatePreview);
$certificateRecordStatus = (string) ($certificate['status'] ?? 'issued');
$certificatePendingReview = !$isCertificatePreview && $certificateRecordStatus === 'pending';
$certificateTextSearch = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', strip_tags((string) ($certificateText ?? ''))) ?: strip_tags((string) ($certificateText ?? '')));
$formatCertificateDate = static function (?string $value) use ($issuedAt): string {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d/m/Y', $timestamp) : $issuedAt;
};
$periodStart = $formatCertificateDate($certificatePeriod['start'] ?? null);
$periodEnd = $formatCertificateDate($certificatePeriod['end'] ?? ($certificate['issued_at'] ?? null));
$minimumFrequency = max(75, (int) ($certificateStatus['minimum_frequency'] ?? 0));
$courseNature = trim((string) ($course['certificate_course_nature'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Curso Livre de Capacitação Profissional - Formação Continuada');
$courseModality = trim((string) ($course['certificate_modality'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Online');
$approvalCriteria = trim((string) ($course['certificate_approval_criteria'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Certificado concedido mediante frequência mínima de ' . $minimumFrequency . '% e aproveitamento satisfatório.');
$legalText = trim((string) ($course['certificate_legal_text'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Curso Livre de Capacitação Profissional ofertado nos termos da Lei nº 9.394/96 (LDB) e Decreto nº 5.154/04.');
$courseModalitySearch = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $courseModality) ?: $courseModality);
$textHasModality = $courseModalitySearch !== '' && str_contains($certificateTextSearch, $courseModalitySearch);
$textHasPeriod = str_contains($certificateTextSearch, 'periodo') || str_contains($certificateTextSearch, 'realizado em') || str_contains($certificateTextSearch, 'realizado no');
$textHasApproval = str_contains($certificateTextSearch, 'aproveitamento') || str_contains($certificateTextSearch, 'rendimento') || str_contains($certificateTextSearch, 'frequencia minima') || str_contains($certificateTextSearch, 'frequencia de');
$textHasCode = str_contains($certificateTextSearch, 'codigo') || str_contains($certificateTextSearch, strtolower((string) ($certificate['verification_code'] ?? '')));
$textHasFrequency = str_contains($certificateTextSearch, 'frequencia') || str_contains($certificateTextSearch, '100%') || str_contains($certificateTextSearch, '75%');
$showNature = (int) ($course['certificate_show_nature'] ?? 1) === 1 && $courseNature !== '';
$showModality = (int) ($course['certificate_show_modality'] ?? 1) === 1 && $courseModality !== '' && !$textHasModality;
$showPeriod = (int) ($course['certificate_show_period'] ?? 1) === 1 && !$textHasPeriod;
$showApproval = (int) ($course['certificate_show_approval'] ?? 1) === 1 && $approvalCriteria !== '' && !$textHasApproval;
$showInstitution = (int) ($course['certificate_show_institution'] ?? 1) === 1;
$showMeta = (int) ($course['certificate_show_meta'] ?? 1) === 1;
$showLegal = (int) ($course['certificate_show_legal'] ?? 1) === 1 && $legalText !== '';
$showRecipient = (int) ($course['certificate_show_recipient'] ?? 1) === 1;
$showHeading = (int) ($course['certificate_show_heading'] ?? 1) === 1;
$showText = (int) ($course['certificate_show_text'] ?? 1) === 1;
$showQr = (int) ($course['certificate_show_qr'] ?? 1) === 1;
$showIssuedMeta = $showMeta && !$textHasCode && !$textHasPeriod;
$showCodeMeta = $showMeta && !$textHasCode;
$hideResponsible = (int) ($course['certificate_hide_responsible'] ?? 0) === 1;
$showTeacherMeta = !$hideResponsible && $showMeta && !$textHasCode && !$isRecognitionCertificate && !empty($course['teacher_name']);
$showFrequencyMeta = $showMeta && !$textHasCode && !$isRecognitionCertificate && !$textHasFrequency;
$officialCity = trim((string) ($course['certificate_institution_official_city'] ?? ''));
$officialState = trim((string) ($course['certificate_institution_official_state'] ?? ''));
$institutionName = trim((string) ($course['certificate_institution_name'] ?? '')) ?: trim((string) ($course['certificate_institution_official_name'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_NAME') ?: 'Cidade Nova Informa - CNI');
$institutionCity = trim((string) ($course['certificate_institution_city'] ?? '')) ?: trim($officialCity . ($officialState !== '' ? ' - ' . $officialState : '')) ?: (getenv('INSTITUTION_CERTIFICATE_CITY') ?: 'Foz do Iguaçu - PR');
$institutionCnpj = trim((string) ($course['certificate_institution_cnpj'] ?? '')) ?: trim((string) ($course['certificate_institution_official_cnpj'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_CNPJ') ?: '');
$institutionSite = trim((string) ($course['certificate_institution_site'] ?? '')) ?: trim((string) ($course['certificate_institution_official_site'] ?? '')) ?: (getenv('INSTITUTION_CERTIFICATE_SITE') ?: 'www.cidadenovainforma.com.br');
$certificatePublicVerify = $institutionSite . '/certificados';
$courseObjectives = trim((string) ($course['certificate_objectives'] ?? ''));
$courseCompetencies = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($course['certificate_competencies'] ?? '')) ?: [])));
$courseResponsible = trim((string) ($course['certificate_responsible_name'] ?? '')) ?: trim((string) ($course['teacher_name'] ?? ''));
$courseResponsibleCredential = trim((string) ($course['certificate_responsible_credential'] ?? ''));
if ($hideResponsible) {
    $courseResponsible = '';
    $courseResponsibleCredential = '';
}
$hasProgramSummary = $courseObjectives !== '' || $courseCompetencies || $courseResponsible !== '' || $courseResponsibleCredential !== '';
$hasCertificateProgramBack = $programBackground !== '' || $programExtra !== '' || $hasProgramSummary || !empty($certificateProgram);
$verificationUrl = $isCertificatePreview ? url('/certificados') : url('/certificado/' . ($certificate['verification_code'] ?? ''));
$verificationQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=8&data=' . rawurlencode($verificationUrl);
$backUrl = $isRecognitionCertificate
    ? url(!empty($isManagedCertificate) ? '/admin/education/recognitions' : '/admin/education/certificates')
    : url('/admin/education/course?id=' . $course['id'] . '#course-certificate');
$backLabel = $isRecognitionCertificate
    ? (!empty($isManagedCertificate) ? 'Voltar aos reconhecimentos' : 'Voltar aos meus certificados')
    : 'Voltar ao curso';
$hasBottomInfo = $showInstitution || $showIssuedMeta || $showCodeMeta || $showTeacherMeta || $showFrequencyMeta || $showLegal;
?>

<div class="page-heading certificate-toolbar">
    <div>
        <p><?= $isCertificatePreview ? 'Previa administrativa' : ($certificatePendingReview ? 'Pre-visualizacao do certificado' : 'Certificado emitido') ?></p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="certificate-toolbar-actions">
        <div class="certificate-toolbar-primary">
            <button class="btn btn-primary icon-btn" type="button" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i>Imprimir</button>
            <?php if ($isCertificatePreview): ?>
                <span class="btn btn-outline-secondary icon-btn disabled" aria-disabled="true"><i class="bi bi-eye" aria-hidden="true"></i>Somente visualizacao</span>
            <?php elseif ($certificatePendingReview && !empty($isManagedCertificate)): ?>
                <form class="inline-form" method="post" action="<?= e(url('/admin/education/certificate/status')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="certificate_id" value="<?= e((string) ($certificate['id'] ?? 0)) ?>">
                    <input type="hidden" name="action" value="issue">
                    <button class="btn btn-outline-success icon-btn" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>Liberar para estudante</button>
                </form>
            <?php elseif (!$certificatePendingReview): ?>
                <a class="btn btn-outline-primary icon-btn" href="<?= e($verificationUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar certificado</a>
            <?php endif; ?>
            <?php if (!$isCertificatePreview && !empty($isManagedCertificate) && !$isRecognitionCertificate && ($certificate['status'] ?? 'issued') !== 'deleted'): ?>
                <form class="inline-form" method="post" action="<?= e(url('/admin/education/certificate/status')) ?>" onsubmit="return confirm('Excluir este certificado? Ele deixara de aparecer nas listas.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="certificate_id" value="<?= e((string) ($certificate['id'] ?? 0)) ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-outline-danger icon-btn" type="submit"><i class="bi bi-trash" aria-hidden="true"></i>Excluir</button>
                </form>
            <?php endif; ?>
        </div>
        <a class="btn btn-outline-secondary icon-btn certificate-toolbar-back" href="<?= e($backUrl) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i><?= e($backLabel) ?></a>
    </div>
</div>

<?php if ($certificatePendingReview && !empty($isManagedCertificate)): ?>
    <section class="panel">
        <div class="section-heading">
            <h2>Conferencia antes da liberacao</h2>
            <span>Aguardando aprovacao</span>
        </div>
        <p class="field-hint mb-0">Revise nome, curso, datas, texto e layout. Depois de clicar em Liberar para estudante, o certificado ficara disponivel em Meus certificados e no verificador publico.</p>
    </section>
<?php endif; ?>

<?php if ($isCertificatePreview): ?>
    <section class="panel">
        <div class="section-heading">
            <h2>Previa do modelo</h2>
            <span>Master/Admin</span>
        </div>
        <p class="field-hint mb-0">Esta visualizacao usa dados de exemplo e nao libera certificado para estudante.</p>
    </section>
<?php endif; ?>

<section class="panel education-certificate-sheet-panel">
    <article class="education-certificate-sheet<?= $background !== '' || $readyImage !== '' ? ' has-background' : '' ?><?= $readyImage !== '' ? ' has-ready-image' : '' ?><?= e($fontClass) ?>"<?= $certificateStyleAttr ?>>
        <?php if ($readyImage !== ''): ?>
            <img class="education-certificate-ready-image" src="<?= e(media_url($readyImage)) ?>" alt="Certificado pronto">
        <?php elseif ($background !== ''): ?>
            <img class="education-certificate-background" src="<?= e(media_url($background)) ?>" alt="" aria-hidden="true">
        <?php endif; ?>
        <?php if ($readyImage === ''): ?>
            <div class="education-certificate-copy">
                <header class="education-certificate-heading">
                    <?php if ($showHeading): ?><span><?= e($heading) ?></span><?php endif; ?>
                    <h2><?= e($title) ?></h2>
                </header>
                <?php if ($showNature): ?>
                    <p class="education-certificate-nature"><?= e($courseNature) ?></p>
                <?php endif; ?>
                <?php if ($showText && trim($certificateText) !== ''): ?>
                    <div class="education-certificate-body"><?= nl2br(e(trim($certificateText))) ?></div>
                <?php endif; ?>
                <?php if ($showModality || $showPeriod || $showApproval): ?>
                    <section class="education-certificate-details" aria-label="Detalhes do certificado">
                        <?php if ($showModality): ?><span>Modalidade: <?= e($courseModality) ?></span><?php endif; ?>
                        <?php if ($showPeriod): ?><span>Realizado de <?= e($periodStart) ?> até <?= e($periodEnd) ?></span><?php endif; ?>
                        <?php if ($showApproval): ?><span><?= e($approvalCriteria) ?></span><?php endif; ?>
                    </section>
                <?php endif; ?>
                <?php if ($showRecipient): ?>
                    <footer>
                        <strong><?= e($certificate['student_name'] ?? '') ?></strong>
                    </footer>
                <?php endif; ?>
            </div>
            <?php if ($hasBottomInfo && !$footerOnBack): ?>
            <?php require __DIR__ . '/certificate-footnote.php'; ?>
            <?php endif; ?>
            <?php if ($showQr): ?>
                <figure class="education-certificate-qr">
                    <img src="<?= e($verificationQrUrl) ?>" alt="QR Code para verificar o certificado" crossorigin="anonymous">
                    <figcaption>Verifique a autenticidade</figcaption>
                </figure>
            <?php endif; ?>
        <?php endif; ?>
    </article>
    <?php if (($programEnabled && $hasCertificateProgramBack) || ($footerOnBack && $hasBottomInfo)): ?>
        <article class="education-certificate-sheet education-certificate-program-sheet<?= !$programBackgroundEnabled ? ' is-overlay-hidden' : '' ?><?= $footerOnBack && $hasBottomInfo ? ' has-back-footer' : '' ?><?= e($fontClass) ?> education-certificate-program-columns-<?= e((string) $programColumns) ?><?= $programColumns >= 2 ? ' is-multi-column' : '' ?><?= $programBackground !== '' ? ' has-background' : '' ?>" style="--certificate-program-columns: <?= e((string) $programColumns) ?>;<?= $programStyle ? ' ' . e(implode('; ', $programStyle)) . ';' : '' ?>">
            <?php if ($programBackground !== ''): ?>
                <img class="education-certificate-background" src="<?= e(media_url($programBackground)) ?>" alt="" aria-hidden="true">
            <?php endif; ?>
            <?php if ($programEnabled && $hasCertificateProgramBack): ?>
            <header class="education-certificate-program-header">
                <span>Verso do certificado</span>
                <h2><?= $isRecognitionCertificate ? 'Informações do reconhecimento' : 'Programação cursada' ?></h2>
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
                                <p><?= $isRecognitionCertificate ? 'Responsável: ' : 'Professor Responsável: ' ?><?= e($courseResponsible) ?></p>
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
            </section>
            <?php endif; ?>
            <?php if ($footerOnBack && $hasBottomInfo): ?>
                <?php require __DIR__ . '/certificate-footnote.php'; ?>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</section>

<?php if (empty($isManagedCertificate) && !$isRecognitionCertificate): ?>
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
<?php endif; ?>
