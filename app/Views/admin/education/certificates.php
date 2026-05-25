<div class="page-heading">
    <div>
        <p>Ensino</p>
        <h1>Meus certificados</h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao ensino</a>
</div>

<section class="panel education-my-certificates-panel">
    <div class="section-heading">
        <h2><i class="bi bi-award" aria-hidden="true"></i>Certificados emitidos</h2>
        <span><?= e((string) count($certificates ?? [])) ?> emitido(s)</span>
    </div>

    <?php if (!empty($certificates)): ?>
        <div class="education-certificate-list">
            <?php foreach ($certificates as $certificate): ?>
                <?php
                    $issuedAt = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string) $certificate['issued_at'])) : '';
                    $isRecognitionCertificate = ($certificate['certificate_activity_type'] ?? '') === 'reconhecimento';
                    $certificateTitle = trim((string) ($certificate['certificate_title'] ?? ''));
                    if ($certificateTitle === '') {
                        $certificateTitle = $isRecognitionCertificate ? 'Certificado de reconhecimento' : 'Certificado de conclusao';
                    }
                    $viewUrl = $isRecognitionCertificate
                        ? url('/admin/education/certificate?certificate_id=' . ($certificate['id'] ?? ''))
                        : url('/admin/education/certificate?id=' . ($certificate['course_id'] ?? ''));
                ?>
                <article class="education-certificate-card">
                    <div class="education-certificate-icon">
                        <i class="bi bi-award" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span><?= e($certificateTitle) ?></span>
                        <h3><?= e($certificate['course_title'] ?? 'Curso') ?></h3>
                        <p>
                            Emitido em <?= e($issuedAt) ?>
                            <?php if (!empty($certificate['teacher_name'])): ?>
                                &middot; Professor: <?= e($certificate['teacher_name']) ?>
                            <?php endif; ?>
                        </p>
                        <small>C&oacute;digo <?= e($certificate['verification_code'] ?? '') ?></small>
                    </div>
                    <div class="education-certificate-card-actions">
                        <a class="btn btn-sm btn-primary icon-btn" href="<?= e($viewUrl) ?>">
                            <i class="bi bi-eye" aria-hidden="true"></i>Ver
                        </a>
                        <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/certificado/' . $certificate['verification_code'])) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-patch-check" aria-hidden="true"></i>Validar
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Quando um curso for conclu&iacute;do e o certificado for emitido, ele aparecer&aacute; aqui.</div>
    <?php endif; ?>
</section>
