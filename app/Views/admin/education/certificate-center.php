<?php
$stats = $stats ?? [];
$recent = $stats['recent'] ?? [];
$byStatus = $stats['by_status'] ?? [];
$institutions = $institutions ?? [];
$statusLabels = [
    'issued' => 'Emitidos',
    'pending' => 'Pendentes',
    'revoked' => 'Revogados',
    'draft' => 'Rascunhos',
    'deleted' => 'Excluídos',
];
?>

<div class="page-heading">
    <div>
        <p>Central institucional</p>
        <h1>Certificados digitais</h1>
    </div>
    <div class="heading-actions">
        <?php if (!empty($canIssueCertificates)): ?>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-journal-plus" aria-hidden="true"></i>Certificado de curso</a>
            <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/recognitions')) ?>"><i class="bi bi-stars" aria-hidden="true"></i>Reconhecimentos</a>
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

    <?php if (!empty($canManageInstitutions)): ?>
        <section class="panel">
            <div class="section-heading">
                <h2>Instituições emissoras</h2>
                <span><?= e((string) count($institutions)) ?> cadastrada(s)</span>
            </div>
            <form method="post" action="<?= e(url('/admin/education/certificate-institution')) ?>" class="education-certificate-settings">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Nome da instituição</label>
                    <input class="form-control" name="name" maxlength="180" placeholder="Ex.: Associação, Instituto, Projeto Social" required>
                </div>
                <div>
                    <label class="form-label">CNPJ</label>
                    <input class="form-control" name="cnpj" maxlength="32" placeholder="Opcional">
                </div>
                <div>
                    <label class="form-label">Cidade</label>
                    <input class="form-control" name="city" maxlength="120">
                </div>
                <div>
                    <label class="form-label">UF</label>
                    <input class="form-control" name="state" maxlength="2">
                </div>
                <div class="grid-span-2">
                    <label class="form-label">Site oficial</label>
                    <input class="form-control" name="site" maxlength="180" placeholder="www.exemplo.org.br">
                </div>
                <div class="form-action-cell">
                    <button class="btn btn-primary icon-btn"><i class="bi bi-building-add" aria-hidden="true"></i>Criar instituição</button>
                </div>
            </form>
            <div class="certificate-status-list mt-3">
                <?php foreach ($institutions as $institution): ?>
                    <div>
                        <span>
                            <strong><?= e($institution['name'] ?? '') ?></strong>
                            <?php if (!empty($institution['cnpj'])): ?> · CNPJ <?= e($institution['cnpj']) ?><?php endif; ?>
                        </span>
                        <strong><?= e(trim(($institution['city'] ?? '') . '/' . ($institution['state'] ?? ''), '/')) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

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
                            <div><dt>Curso certificado</dt><dd><?= e($certificate['course_title'] ?? '') ?></dd></div>
                            <div><dt>Código</dt><dd><?= e($certificate['verification_code'] ?? '') ?></dd></div>
                            <div><dt>Status</dt><dd><?= e($statusLabels[$certificate['status'] ?? ''] ?? ($certificate['status'] ?? '')) ?></dd></div>
                        </dl>
                    </div>
                    <div class="admin-list-actions">
                        <?php if (!empty($canIssueCertificates)): ?>
                            <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/certificate?certificate_id=' . ($certificate['id'] ?? ''))) ?>"><i class="bi bi-eye" aria-hidden="true"></i><?= ($certificate['status'] ?? '') === 'pending' ? 'Pre-visualizar' : 'Ver' ?></a>
                            <?php if (in_array(($certificate['status'] ?? ''), ['pending', 'revoked'], true)): ?>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/education/certificate/status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="certificate_id" value="<?= e((string) ($certificate['id'] ?? 0)) ?>">
                                    <input type="hidden" name="action" value="issue">
                                    <button class="btn btn-sm btn-outline-success icon-btn"><i class="bi <?= ($certificate['status'] ?? '') === 'pending' ? 'bi-check2-circle' : 'bi-arrow-counterclockwise' ?>" aria-hidden="true"></i><?= ($certificate['status'] ?? '') === 'pending' ? 'Liberar' : 'Reativar' ?></button>
                                </form>
                            <?php else: ?>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/education/certificate/status')) ?>" onsubmit="return confirm('Revogar este certificado?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="certificate_id" value="<?= e((string) ($certificate['id'] ?? 0)) ?>">
                                    <input type="hidden" name="action" value="revoke">
                                    <button class="btn btn-sm btn-outline-warning icon-btn"><i class="bi bi-slash-circle" aria-hidden="true"></i>Revogar</button>
                                </form>
                            <?php endif; ?>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/certificate/status')) ?>" onsubmit="return confirm('Excluir este certificado? Ele deixara de aparecer nas listas.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="certificate_id" value="<?= e((string) ($certificate['id'] ?? 0)) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash" aria-hidden="true"></i>Excluir</button>
                            </form>
                        <?php endif; ?>
                        <?php if (($certificate['status'] ?? '') === 'issued'): ?>
                            <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/certificado/' . ($certificate['verification_code'] ?? ''))) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
                <div class="empty-state">Os certificados emitidos aparecerão aqui.</div>
            <?php endif; ?>
        </div>
    </section>
</section>
