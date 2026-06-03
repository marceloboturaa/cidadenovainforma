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
$hasCertificateProgramBack = $programExtra !== '' || $hasProgramSummary || !empty($certificateProgram);
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
    <?php if ($programEnabled && $hasCertificateProgramBack): ?>
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

    const pageWidth = 842;
    const pageHeight = 595;
    const renderWidth = 1600;
    const renderHeight = Math.round(renderWidth * 210 / 297);

    const blobToDataUrl = (blob) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });

    const inlineImages = async (node) => {
        const images = Array.from(node.querySelectorAll('img'));
        await Promise.all(images.map(async (image) => {
            const source = image.currentSrc || image.src;
            if (!source || source.startsWith('data:')) {
                return;
            }

            try {
                const response = await fetch(source, { credentials: 'same-origin', mode: 'cors' });
                if (!response.ok) {
                    return;
                }
                image.src = await blobToDataUrl(await response.blob());
            } catch (error) {
                image.removeAttribute('crossorigin');
            }
        }));
    };

    const stylesheetText = () => Array.from(document.styleSheets).map((sheet) => {
        try {
            return Array.from(sheet.cssRules).map((rule) => rule.cssText).join('\n');
        } catch (error) {
            return '';
        }
    }).join('\n');

    const canvasFromSheet = async (sheet) => {
        const clone = sheet.cloneNode(true);
        clone.style.width = renderWidth + 'px';
        clone.style.height = renderHeight + 'px';
        clone.style.maxWidth = 'none';
        clone.style.margin = '0';
        clone.style.border = '0';
        clone.style.borderRadius = '0';
        clone.style.boxShadow = 'none';
        clone.style.transform = 'none';
        clone.style.overflow = 'hidden';

        await inlineImages(clone);

        const html = `
            <div xmlns="http://www.w3.org/1999/xhtml">
                <style>
                    * { box-sizing: border-box; }
                    body { margin: 0; }
                    ${stylesheetText()}
                    .education-certificate-sheet {
                        width: ${renderWidth}px !important;
                        height: ${renderHeight}px !important;
                        max-width: none !important;
                        border: 0 !important;
                        border-radius: 0 !important;
                        box-shadow: none !important;
                        transform: none !important;
                    }
                </style>
                ${clone.outerHTML}
            </div>
        `;
        const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="${renderWidth}" height="${renderHeight}" viewBox="0 0 ${renderWidth} ${renderHeight}">
                <foreignObject width="100%" height="100%">${html}</foreignObject>
            </svg>
        `;
        const url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));

        try {
            const image = await new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = url;
            });
            const canvas = document.createElement('canvas');
            canvas.width = renderWidth;
            canvas.height = renderHeight;
            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0);
            return canvas;
        } finally {
            URL.revokeObjectURL(url);
        }
    };

    const bytesFromDataUrl = (dataUrl) => {
        const binary = atob(dataUrl.split(',')[1] || '');
        const bytes = new Uint8Array(binary.length);
        for (let index = 0; index < binary.length; index++) {
            bytes[index] = binary.charCodeAt(index);
        }
        return bytes;
    };

    const buildPdf = (jpegDataUrls) => {
        const encoder = new TextEncoder();
        const writers = [];
        const pageIds = [];

        const addTextObject = (value) => {
            const id = writers.length + 1;
            writers.push((writeText) => writeText(value));
            return id;
        };

        const addImageObject = (bytes) => {
            const id = writers.length + 1;
            writers.push((writeText, writeBytes) => {
                writeText(`<< /Type /XObject /Subtype /Image /Width ${renderWidth} /Height ${renderHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${bytes.length} >>\nstream\n`);
                writeBytes(bytes);
                writeText('\nendstream');
            });
            return id;
        };

        addTextObject('<< /Type /Catalog /Pages 2 0 R >>');
        addTextObject('');

        jpegDataUrls.forEach((dataUrl, index) => {
            const imageId = addImageObject(bytesFromDataUrl(dataUrl));
            const content = `q\n${pageWidth} 0 0 ${pageHeight} 0 0 cm\n/Im${index} Do\nQ\n`;
            const contentId = addTextObject(`<< /Length ${content.length} >>\nstream\n${content}endstream`);
            const pageId = addTextObject(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${pageWidth} ${pageHeight}] /Resources << /XObject << /Im${index} ${imageId} 0 R >> >> >> /Contents ${contentId} 0 R >>`);
            pageIds.push(pageId);
        });

        writers[1] = (writeText) => writeText(`<< /Type /Pages /Kids [${pageIds.map((id) => `${id} 0 R`).join(' ')}] /Count ${pageIds.length} >>`);
        const chunks = [];
        const offsets = [0];
        let length = 0;

        const writeBytes = (bytes) => {
            chunks.push(bytes);
            length += bytes.length;
        };
        const writeText = (value) => writeBytes(encoder.encode(value));

        writeText('%PDF-1.4\n');
        writers.forEach((writer, index) => {
            offsets[index + 1] = length;
            writeText(`${index + 1} 0 obj\n`);
            writer(writeText, writeBytes);
            writeText('\nendobj\n');
        });

        const xref = length;
        writeText(`xref\n0 ${writers.length + 1}\n0000000000 65535 f \n`);
        for (let i = 1; i <= writers.length; i++) {
            writeText(String(offsets[i]).padStart(10, '0') + ' 00000 n \n');
        }
        writeText(`trailer\n<< /Size ${writers.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`);

        return new Blob(chunks, { type: 'application/pdf' });
    };

    downloadButton.addEventListener('click', async () => {
        const originalHtml = downloadButton.innerHTML;
        downloadButton.disabled = true;
        downloadButton.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Gerando PDF';

        try {
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            const sheets = Array.from(sourcePanel.querySelectorAll('.education-certificate-sheet'));
            const images = [];
            for (const sheet of sheets) {
                const canvas = await canvasFromSheet(sheet);
                images.push(canvas.toDataURL('image/jpeg', 0.96));
            }

            const pdfUrl = URL.createObjectURL(buildPdf(images));
            const link = document.createElement('a');
            link.href = pdfUrl;
            link.download = downloadButton.dataset.filename || 'certificado.pdf';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(pdfUrl), 1000);
        } catch (error) {
            alert('Nao foi possivel gerar o PDF visual do certificado. Use o botao Imprimir e escolha "Salvar como PDF".');
        } finally {
            downloadButton.disabled = false;
            downloadButton.innerHTML = originalHtml;
        }
    });
})();
</script>
