<?php
$stats = $stats ?? [];
$recent = $stats['recent'] ?? [];
$byStatus = $stats['by_status'] ?? [];
$statusLabels = [
    'issued' => 'Emitidos',
    'pending' => 'Pendentes',
    'revoked' => 'Revogados',
    'draft' => 'Rascunhos',
];
?>

<div class="page-heading">
    <div>
        <p>Central institucional</p>
        <h1>Certificados digitais</h1>
    </div>
    <div class="heading-actions">
        <?php if (!empty($canIssueCertificates)): ?>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-plus-circle" aria-hidden="true"></i>Novo certificado</a>
        <?php endif; ?>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/certificados')) ?>" target="_blank" rel="noopener"><i class="bi bi-qr-code-scan" aria-hidden="true"></i>Verificador público</a>
    </div>
</div>

<section class="education-certificate-center">
    <div class="dashboard-grid">
        <article class="metric-card">
            <span>Certificados</span>
            <strong><?= e((string) ($stats['total_certificates'] ?? 0)) ?></strong>
            <small>Total emitido na base</small>
        </article>
        <article class="metric-card">
            <span>Válidos</span>
            <strong><?= e((string) ($stats['issued_certificates'] ?? 0)) ?></strong>
            <small>Status emitido</small>
        </article>
        <article class="metric-card">
            <span>Verificações</span>
            <strong><?= e((string) ($stats['verified_total'] ?? 0)) ?></strong>
            <small>Consultas públicas registradas</small>
        </article>
        <article class="metric-card">
            <span>Instituições</span>
            <strong><?= e((string) ($stats['institutions'] ?? 0)) ?></strong>
            <small>Emissoras ativas</small>
        </article>
    </div>

    <div class="education-certificate-center-grid">
        <section class="panel">
            <div class="section-heading">
                <h2>Estrutura implantada</h2>
                <span>Base profissional</span>
            </div>
            <div class="certificate-capability-grid">
                <article>
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <strong>Multi-instituição</strong>
                    <span>Instituições, logos, assinaturas e administradores locais.</span>
                </article>
                <article>
                    <i class="bi bi-file-earmark-richtext" aria-hidden="true"></i>
                    <strong>Modelos reutilizáveis</strong>
                    <span>Templates, categorias e histórico de versões.</span>
                </article>
                <article>
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <strong>Autenticidade</strong>
                    <span>Código único, QR Code, hash, status e verificação pública.</span>
                </article>
                <article>
                    <i class="bi bi-list-check" aria-hidden="true"></i>
                    <strong>Emissão em massa</strong>
                    <span>Lotes preparados para CSV/Excel, aprovação e processamento.</span>
                </article>
                <article>
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    <strong>Auditoria</strong>
                    <span>Registro de emissão, autorização, revogação, IP e alterações.</span>
                </article>
                <article>
                    <i class="bi bi-person-lock" aria-hidden="true"></i>
                    <strong>Permissões</strong>
                    <span>Master, Admin, Admin Local, Delegado Emissor, Professor e Participante.</span>
                </article>
            </div>
        </section>

        <aside class="panel">
            <div class="section-heading">
                <h2>Status</h2>
                <span><?= e((string) count($byStatus)) ?> tipo(s)</span>
            </div>
            <div class="certificate-status-list">
                <?php foreach ($byStatus as $status => $total): ?>
                    <div>
                        <span><?= e($statusLabels[$status] ?? ucfirst((string) $status)) ?></span>
                        <strong><?= e((string) $total) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (!$byStatus): ?>
                    <div class="empty-state">Nenhum certificado emitido ainda.</div>
                <?php endif; ?>
            </div>
        </aside>
    </div>

    <section class="panel">
        <div class="section-heading">
            <h2>Últimos certificados</h2>
            <span><?= e((string) count($recent)) ?> registro(s)</span>
        </div>
        <div class="admin-card-list compact-list">
            <?php foreach ($recent as $certificate): ?>
                <article class="admin-list-card">
                    <div class="admin-list-main">
                        <strong class="admin-list-title"><?= e($certificate['student_name'] ?? '') ?></strong>
                        <dl class="admin-list-meta">
                            <div><dt>Curso</dt><dd><?= e($certificate['course_title'] ?? '') ?></dd></div>
                            <div><dt>Código</dt><dd><?= e($certificate['verification_code'] ?? '') ?></dd></div>
                            <div><dt>Status</dt><dd><?= e($statusLabels[$certificate['status'] ?? ''] ?? ($certificate['status'] ?? '')) ?></dd></div>
                        </dl>
                    </div>
                    <div class="admin-list-actions">
                        <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/certificado/' . ($certificate['verification_code'] ?? ''))) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
                <div class="empty-state">Os certificados emitidos aparecerão aqui.</div>
            <?php endif; ?>
        </div>
    </section>
</section>
