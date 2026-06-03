<?php
$issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : date('d/m/Y');
$title = trim((string) ($course['certificate_title'] ?? ''));
if ($title === '') {
    $title = 'Certificado de conclusão';
}
$background = trim((string) ($course['certificate_background'] ?? ''));
$programBackground = trim((string) ($course['certificate_program_background'] ?? ''));
$programEnabled = (int) ($course['certificate_program_enabled'] ?? 1) === 1;
$isRecognitionCertificate = ($course['certificate_activity_type'] ?? '') === 'reconhecimento';
$certificateFont = trim((string) ($course['certificate_font_family'] ?? ''));
$fontClass = in_array($certificateFont, ['serif', 'georgia', 'garamond', 'playfair', 'montserrat'], true) ? ' certificate-font-' . $certificateFont : '';
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
$courseNature = trim((string) ($course['certificate_course_nature'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Curso Livre de Capacitação Profissional - Formação Continuada');
$courseModality = trim((string) ($course['certificate_modality'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Online');
$approvalCriteria = trim((string) ($course['certificate_approval_criteria'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Certificado concedido mediante frequência mínima de ' . $minimumFrequency . '% e aproveitamento satisfatório.');
$legalText = trim((string) ($course['certificate_legal_text'] ?? '')) ?: ($isRecognitionCertificate ? '' : 'Curso Livre de Capacitação Profissional ofertado nos termos da Lei nº 9.394/96 (LDB) e Decreto nº 5.154/04.');
$showNature = (int) ($course['certificate_show_nature'] ?? 1) === 1 && $courseNature !== '';
$showModality = (int) ($course['certificate_show_modality'] ?? 1) === 1 && $courseModality !== '';
$showPeriod = (int) ($course['certificate_show_period'] ?? 1) === 1;
$showApproval = (int) ($course['certificate_show_approval'] ?? 1) === 1 && $approvalCriteria !== '';
$showInstitution = (int) ($course['certificate_show_institution'] ?? 1) === 1;
$showMeta = (int) ($course['certificate_show_meta'] ?? 1) === 1;
$showLegal = (int) ($course['certificate_show_legal'] ?? 1) === 1 && $legalText !== '';
$showRecipient = (int) ($course['certificate_show_recipient'] ?? 1) === 1;
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
$hasProgramSummary = $courseObjectives !== '' || $courseCompetencies || $courseResponsible !== '' || $courseResponsibleCredential !== '';
$verificationUrl = url('/certificado/' . ($certificate['verification_code'] ?? ''));
$verificationQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=8&data=' . rawurlencode($verificationUrl);
$downloadFilename = 'certificado-' . slugify((string) ($course['title'] ?? 'curso')) . '-' . slugify((string) ($certificate['student_name'] ?? 'aluno')) . '.pdf';
$backUrl = $isRecognitionCertificate
    ? url(!empty($isManagedCertificate) ? '/admin/education/recognitions' : '/admin/education/certificates')
    : url('/admin/education/course?id=' . $course['id'] . '#course-certificate');
$backLabel = $isRecognitionCertificate
    ? (!empty($isManagedCertificate) ? 'Voltar aos reconhecimentos' : 'Voltar aos meus certificados')
    : 'Voltar ao curso';
?>

<div class="page-heading certificate-toolbar">
    <div>
        <p>Certificado emitido</p>
        <h1><?= e($course['title']) ?></h1>
    </div>
    <div class="certificate-toolbar-actions">
        <div class="certificate-toolbar-primary">
            <button class="btn btn-primary icon-btn" type="button" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i>Imprimir</button>
            <button class="btn btn-outline-primary icon-btn" type="button" data-certificate-download data-filename="<?= e($downloadFilename) ?>"><i class="bi bi-download" aria-hidden="true"></i>Baixar PDF</button>
            <a class="btn btn-outline-primary icon-btn" href="<?= e($verificationUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar certificado</a>
            <?php if (!empty($isManagedCertificate) && !$isRecognitionCertificate && ($certificate['status'] ?? 'issued') !== 'deleted'): ?>
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

<section class="panel education-certificate-sheet-panel">
    <article class="education-certificate-sheet<?= $background !== '' ? ' has-background' : '' ?><?= e($fontClass) ?>">
        <?php if ($background !== ''): ?>
            <img class="education-certificate-background" src="<?= e(media_url($background)) ?>" alt="" aria-hidden="true">
        <?php endif; ?>
        <div class="education-certificate-copy">
            <span>Certificado</span>
            <h2><?= e($title) ?></h2>
            <?php if ($showNature): ?>
                <p class="education-certificate-nature"><?= e($courseNature) ?></p>
            <?php endif; ?>
            <?php if (trim($certificateText) !== ''): ?>
                <div><?= nl2br(e($certificateText)) ?></div>
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
        <footer class="education-certificate-footnote">
            <div class="education-certificate-footnote-text">
                <?php if ($showInstitution): ?>
                    <div class="education-certificate-institution">
                        <?php if ($institutionName !== ''): ?><strong><?= e($institutionName) ?></strong><?php endif; ?>
                        <?php if ($institutionCity !== ''): ?><span><?= e($institutionCity) ?></span><?php endif; ?>
                        <?php if ($institutionCnpj !== ''): ?><span>CNPJ: <?= e($institutionCnpj) ?></span><?php endif; ?>
                        <?php if ($institutionSite !== ''): ?><span><?= e($institutionSite) ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($showMeta): ?>
                    <div class="education-certificate-footnote-meta">
                        <span>Emitido em <?= e($issuedAt) ?></span>
                        <span>Código <?= e($certificate['verification_code'] ?? '') ?></span>
                        <?php if (!$isRecognitionCertificate && !empty($course['teacher_name'])): ?><span>Professor: <?= e($course['teacher_name']) ?></span><?php endif; ?>
                        <?php if (!$isRecognitionCertificate): ?><span>Frequência registrada: <?= e((string) ($certificateStatus['frequency'] ?? 0)) ?>%</span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($showLegal): ?><p><?= e($legalText) ?></p><?php endif; ?>
            </div>
            <figure class="education-certificate-qr">
                <img src="<?= e($verificationQrUrl) ?>" alt="QR Code para verificar o certificado" crossorigin="anonymous">
                <figcaption>Verifique a autenticidade</figcaption>
            </figure>
        </footer>
    </article>
    <?php if ($programEnabled): ?>
        <article class="education-certificate-sheet education-certificate-program-sheet education-certificate-program-columns-<?= e((string) $programColumns) ?><?= $programColumns >= 2 ? ' is-multi-column' : '' ?><?= $programBackground !== '' ? ' has-background' : '' ?>" style="--certificate-program-columns: <?= e((string) $programColumns) ?>;">
            <?php if ($programBackground !== ''): ?>
                <img class="education-certificate-background" src="<?= e(media_url($programBackground)) ?>" alt="" aria-hidden="true">
            <?php endif; ?>
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
                <?php if (!$certificateProgram): ?>
                    <p class="education-certificate-program-empty"><?= $isRecognitionCertificate ? 'Nenhuma informação complementar cadastrada para este reconhecimento.' : 'Nenhuma aula cadastrada para este curso.' ?></p>
                <?php endif; ?>
            </section>
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

<script>
(() => {
    const downloadButton = document.querySelector('[data-certificate-download]');
    const sourcePanel = document.querySelector('.education-certificate-sheet-panel');

    if (!downloadButton || !sourcePanel) {
        return;
    }

    const loadHtml2Pdf = () => new Promise((resolve, reject) => {
        if (window.html2pdf) {
            resolve(window.html2pdf);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.crossOrigin = 'anonymous';
        script.referrerPolicy = 'no-referrer';
        script.onload = () => resolve(window.html2pdf);
        script.onerror = () => reject(new Error('Nao foi possivel carregar o gerador de PDF.'));
        document.head.appendChild(script);
    });

    const waitForImages = async (container) => {
        const images = Array.from(container.querySelectorAll('img'));
        await Promise.all(images.map((image) => {
            if (image.complete && image.naturalWidth > 0) {
                return Promise.resolve();
            }

            return new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            });
        }));
    };

    const buildExportNode = () => {
        const exportNode = document.createElement('div');
        exportNode.className = 'education-certificate-pdf-export';

        sourcePanel.querySelectorAll('.education-certificate-sheet').forEach((sheet, index, sheets) => {
            const clone = sheet.cloneNode(true);
            clone.style.width = '297mm';
            clone.style.height = '210mm';
            clone.style.maxWidth = 'none';
            clone.style.margin = '0';
            clone.style.border = '0';
            clone.style.borderRadius = '0';
            clone.style.boxShadow = 'none';
            clone.style.pageBreakAfter = index === sheets.length - 1 ? 'auto' : 'always';
            clone.style.breakAfter = index === sheets.length - 1 ? 'auto' : 'page';
            exportNode.appendChild(clone);
        });

        Object.assign(exportNode.style, {
            position: 'fixed',
            left: '-10000px',
            top: '0',
            width: '297mm',
            background: '#ffffff',
            zIndex: '-1',
        });

        return exportNode;
    };

    downloadButton.addEventListener('click', async () => {
        const originalHtml = downloadButton.innerHTML;
        downloadButton.disabled = true;
        downloadButton.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Gerando PDF';

        let exportNode = null;

        try {
            const html2pdf = await loadHtml2Pdf();
            exportNode = buildExportNode();
            document.body.appendChild(exportNode);
            await waitForImages(exportNode);

            await html2pdf().set({
                filename: downloadButton.dataset.filename || 'certificado.pdf',
                margin: 0,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: 1123,
                    windowHeight: 794,
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
                pagebreak: { mode: ['css', 'legacy'] },
            }).from(exportNode).save();
        } catch (error) {
            alert(error.message || 'Nao foi possivel baixar o PDF agora.');
        } finally {
            if (exportNode) {
                exportNode.remove();
            }
            downloadButton.disabled = false;
            downloadButton.innerHTML = originalHtml;
        }
    });
})();
</script>
