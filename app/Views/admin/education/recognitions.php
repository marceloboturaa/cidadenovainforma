<?php
$recognitions = $recognitions ?? [];
$recognitionPeople = $recognitionPeople ?? [];
$institutions = $institutions ?? [];
$statusLabels = [
    'issued' => 'Emitido',
    'pending' => 'Pendente',
    'revoked' => 'Revogado',
    'draft' => 'Rascunho',
];
?>

<div class="page-heading">
    <div>
        <p>Certificados institucionais</p>
        <h1>Reconhecimentos</h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/certificate-center')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Central</a>
    </div>
</div>

<section class="education-certificate-center">
    <?php if (!empty($canIssueCertificates)): ?>
        <section class="panel">
            <div class="section-heading">
                <h2>Novo certificado de reconhecimento</h2>
                <span>Voluntariado, homenagem e participação comunitária</span>
            </div>
            <?php if ($recognitionPeople): ?>
                <form method="post" action="<?= e(url('/admin/education/recognition-certificate')) ?>" class="education-certificate-settings">
                    <?= csrf_field() ?>
                    <div>
                        <label class="form-label">Pessoa reconhecida</label>
                        <select class="form-select" name="person_id" required>
                            <option value="">Selecione uma pessoa cadastrada</option>
                            <?php foreach ($recognitionPeople as $person): ?>
                                <option value="<?= e((string) $person['id']) ?>">
                                    <?= e($person['full_name'] ?? '') ?>
                                    <?php if (!empty($person['email'])): ?> - <?= e($person['email']) ?><?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Instituição emissora</label>
                        <select class="form-select" name="institution_id">
                            <option value="">Cidade Nova Informa / padrão do certificado</option>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?= e((string) $institution['id']) ?>"><?= e($institution['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Reconhecimento</label>
                        <input class="form-control" name="activity_title" maxlength="180" value="Reconhecimento por atuação voluntária" required>
                    </div>
                    <div>
                        <label class="form-label">Título no certificado</label>
                        <input class="form-control" name="certificate_title" maxlength="180" value="Certificado de reconhecimento">
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Texto editável do certificado</label>
                        <textarea class="form-control" name="certificate_text" rows="4">Certificamos que {student_name} recebeu este certificado de reconhecimento por sua contribuição voluntária em ações institucionais e comunitárias.</textarea>
                        <small class="form-text">Use {student_name} para inserir automaticamente o nome da pessoa reconhecida.</small>
                    </div>
                    <div class="form-action-cell">
                        <button class="btn btn-primary icon-btn"><i class="bi bi-award" aria-hidden="true"></i>Emitir reconhecimento</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    Cadastre voluntários em <a href="<?= e(url('/admin/people')) ?>">Pessoas</a> para emitir reconhecimentos.
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="section-heading">
            <h2>Reconhecimentos emitidos</h2>
            <span><?= e((string) count($recognitions)) ?> registro(s)</span>
        </div>
        <div class="admin-card-list compact-list">
            <?php foreach ($recognitions as $recognition): ?>
                <article class="admin-list-card">
                    <div class="admin-list-main">
                        <strong class="admin-list-title"><?= e($recognition['recipient_name'] ?? '') ?></strong>
                        <dl class="admin-list-meta">
                            <div><dt>Reconhecimento</dt><dd><?= e($recognition['recognition_title'] ?? '') ?></dd></div>
                            <div><dt>Instituição</dt><dd><?= e($recognition['institution_name'] ?? 'Padrão institucional') ?></dd></div>
                            <div><dt>Código</dt><dd><?= e($recognition['verification_code'] ?? '') ?></dd></div>
                            <div><dt>Status</dt><dd><?= e($statusLabels[$recognition['status'] ?? ''] ?? ($recognition['status'] ?? '')) ?></dd></div>
                        </dl>
                    </div>
                    <div class="admin-list-actions">
                        <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/certificate?certificate_id=' . ($recognition['id'] ?? ''))) ?>"><i class="bi bi-printer" aria-hidden="true"></i>Imprimir</a>
                        <a class="btn btn-sm btn-outline-primary icon-btn" href="<?= e(url('/certificado/' . ($recognition['verification_code'] ?? ''))) ?>" target="_blank" rel="noopener"><i class="bi bi-patch-check" aria-hidden="true"></i>Verificar</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$recognitions): ?>
                <div class="empty-state">Nenhum reconhecimento emitido ainda.</div>
            <?php endif; ?>
        </div>
    </section>
</section>
