<?php
$recognitions = $recognitions ?? [];
$recognitionPeople = $recognitionPeople ?? [];
$institutions = $institutions ?? [];
$editingRecognition = $editingRecognition ?? null;
$isEditingRecognition = !empty($editingRecognition);
$recognitionFormAction = $isEditingRecognition ? '/admin/education/recognition-certificate/update' : '/admin/education/recognition-certificate';
$statusLabels = [
    'issued' => 'Emitido',
    'pending' => 'Pendente',
    'revoked' => 'Revogado',
    'draft' => 'Rascunho',
    'locked' => 'Travado',
];
$recognitionValue = static function (string $key, string $default = '') use ($editingRecognition): string {
    if (!$editingRecognition) {
        return $default;
    }

    return (string) ($editingRecognition[$key] ?? $default);
};
$recognitionChecked = static function (string $key, bool $default = true) use ($editingRecognition): string {
    if (!$editingRecognition) {
        return $default ? ' checked' : '';
    }

    return !empty($editingRecognition[$key]) ? ' checked' : '';
};
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
                <h2><?= $isEditingRecognition ? 'Editar certificado de reconhecimento' : 'Novo certificado de reconhecimento' ?></h2>
                <span><?= $isEditingRecognition ? 'Ajuste texto, imagens, campos e travas' : 'Voluntariado, homenagem e participação comunitária' ?></span>
            </div>
            <?php if ($recognitionPeople || $isEditingRecognition): ?>
                <form method="post" action="<?= e(url($recognitionFormAction)) ?>" class="education-certificate-settings" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <?php if ($isEditingRecognition): ?>
                        <input type="hidden" name="certificate_id" value="<?= e((string) ($editingRecognition['certificate_id'] ?? '')) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Pessoa reconhecida</label>
                        <?php if ($isEditingRecognition): ?>
                            <input class="form-control" value="<?= e(($editingRecognition['recipient_name'] ?? '') . ' - ' . ($editingRecognition['recipient_kind'] ?? '')) ?>" readonly>
                        <?php else: ?>
                            <select class="form-select" name="recipient_key" required>
                                <option value="">Selecione usuário ou pessoa cadastrada</option>
                                <?php foreach ($recognitionPeople as $person): ?>
                                    <option value="<?= e((string) $person['recipient_key']) ?>">
                                        <?= e($person['display_name'] ?? '') ?>
                                        - <?= e(($person['recipient_type'] ?? '') === 'user' ? 'Usuário logado' : 'Pessoa cadastrada') ?>
                                        <?php if (!empty($person['email'])): ?> - <?= e($person['email']) ?><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">Instituição emissora</label>
                        <select class="form-select" name="institution_id">
                            <option value="">Cidade Nova Informa / padrão do certificado</option>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?= e((string) $institution['id']) ?>"<?= (string) ($editingRecognition['certificate_institution_id'] ?? '') === (string) ($institution['id'] ?? '') ? ' selected' : '' ?>><?= e($institution['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Reconhecimento</label>
                        <input class="form-control" name="activity_title" maxlength="180" value="<?= e($recognitionValue('title', 'Reconhecimento por atuação voluntária')) ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Título no certificado</label>
                        <input class="form-control" name="certificate_title" maxlength="180" value="<?= e($recognitionValue('certificate_title', 'Certificado de reconhecimento')) ?>">
                    </div>
                    <div>
                        <label class="form-label">Fonte do certificado</label>
                        <select class="form-select" name="certificate_font_family">
                            <?php $selectedFont = $recognitionValue('certificate_font_family', 'system'); ?>
                            <option value="system"<?= $selectedFont === 'system' ? ' selected' : '' ?>>Padrão moderna</option>
                            <option value="serif"<?= $selectedFont === 'serif' ? ' selected' : '' ?>>Serif clássica</option>
                            <option value="georgia"<?= $selectedFont === 'georgia' ? ' selected' : '' ?>>Georgia</option>
                            <option value="garamond"<?= $selectedFont === 'garamond' ? ' selected' : '' ?>>Garamond</option>
                            <option value="playfair"<?= $selectedFont === 'playfair' ? ' selected' : '' ?>>Playfair</option>
                            <option value="montserrat"<?= $selectedFont === 'montserrat' ? ' selected' : '' ?>>Montserrat</option>
                        </select>
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Texto editável do certificado</label>
                        <textarea class="form-control" name="certificate_text" rows="4"><?= e($recognitionValue('certificate_text', 'Certificamos que {student_name} recebeu este certificado de reconhecimento por sua contribuição voluntária em ações institucionais e comunitárias.')) ?></textarea>
                        <small class="form-text">Use {student_name} para inserir automaticamente o nome da pessoa reconhecida.</small>
                    </div>
                    <div class="grid-span-2">
                        <span class="form-label">Exibir no certificado</span>
                        <div class="education-certificate-toggle-grid">
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_recipient" value="1"<?= $recognitionChecked('certificate_show_recipient', true) ?>><span class="form-check-label">Nome do recebedor</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_nature" value="1"<?= $recognitionChecked('certificate_show_nature', true) ?>><span class="form-check-label">Natureza</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_modality" value="1"<?= $recognitionChecked('certificate_show_modality', false) ?>><span class="form-check-label">Modalidade/contexto</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_period" value="1"<?= $recognitionChecked('certificate_show_period', true) ?>><span class="form-check-label">Período/data</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_approval" value="1"<?= $recognitionChecked('certificate_show_approval', true) ?>><span class="form-check-label">Justificativa</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_institution" value="1"<?= $recognitionChecked('certificate_show_institution', true) ?>><span class="form-check-label">Instituição</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_meta" value="1"<?= $recognitionChecked('certificate_show_meta', true) ?>><span class="form-check-label">Emissão/código</span></label>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="certificate_show_legal" value="1"<?= $recognitionChecked('certificate_show_legal', false) ?>><span class="form-check-label">Texto legal</span></label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Imagem de fundo da frente por link</label>
                        <input class="form-control" name="certificate_background" value="<?= e($recognitionValue('certificate_background')) ?>" placeholder="/public/uploads/education/fundo.jpg ou URL">
                    </div>
                    <div>
                        <label class="form-label">Enviar fundo da frente</label>
                        <input class="form-control" type="file" name="certificate_background_upload" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <div>
                        <label class="form-label">Natureza do certificado</label>
                        <input class="form-control" name="certificate_course_nature" maxlength="180" value="<?= e($recognitionValue('certificate_course_nature', 'Certificado de Reconhecimento Institucional')) ?>">
                    </div>
                    <div>
                        <label class="form-label">Modalidade / contexto</label>
                        <input class="form-control" name="certificate_modality" maxlength="80" value="<?= e($recognitionValue('certificate_modality')) ?>" placeholder="Ex.: Voluntariado, Presencial, Online">
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Critério / justificativa</label>
                        <input class="form-control" name="certificate_approval_criteria" maxlength="255" value="<?= e($recognitionValue('certificate_approval_criteria', 'Certificado concedido por reconhecimento institucional de participação voluntária.')) ?>">
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Texto legal ou observação institucional</label>
                        <textarea class="form-control" name="certificate_legal_text" rows="2" placeholder="Opcional"><?= e($recognitionValue('certificate_legal_text')) ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Nome da instituição no certificado</label>
                        <input class="form-control" name="certificate_institution_name" maxlength="180" value="<?= e($recognitionValue('certificate_institution_name')) ?>" placeholder="Opcional, sobrescreve a instituição selecionada">
                    </div>
                    <div>
                        <label class="form-label">Cidade/UF</label>
                        <input class="form-control" name="certificate_institution_city" maxlength="120" value="<?= e($recognitionValue('certificate_institution_city')) ?>" placeholder="Foz do Iguaçu - PR">
                    </div>
                    <div>
                        <label class="form-label">CNPJ</label>
                        <input class="form-control" name="certificate_institution_cnpj" maxlength="32" value="<?= e($recognitionValue('certificate_institution_cnpj')) ?>" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="form-label">Site oficial</label>
                        <input class="form-control" name="certificate_institution_site" maxlength="180" value="<?= e($recognitionValue('certificate_institution_site')) ?>" placeholder="www.exemplo.org.br">
                    </div>
                    <div>
                        <label class="form-label">Responsável / assinatura</label>
                        <input class="form-control" name="certificate_responsible_name" maxlength="180" value="<?= e($recognitionValue('certificate_responsible_name')) ?>" placeholder="Nome do responsável">
                    </div>
                    <div>
                        <label class="form-label">Cargo / credencial</label>
                        <input class="form-control" name="certificate_responsible_credential" maxlength="180" value="<?= e($recognitionValue('certificate_responsible_credential')) ?>" placeholder="Coordenação, Presidência, Organização">
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Objetivo do reconhecimento</label>
                        <textarea class="form-control" name="certificate_objectives" rows="2" placeholder="Opcional"><?= e($recognitionValue('certificate_objectives')) ?></textarea>
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Competências / contribuições reconhecidas</label>
                        <textarea class="form-control" name="certificate_competencies" rows="3" placeholder="Uma por linha, opcional"><?= e($recognitionValue('certificate_competencies')) ?></textarea>
                    </div>
                    <div class="grid-span-2">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="certificate_program_enabled" value="1"<?= $recognitionChecked('certificate_program_enabled', true) ?>>
                            <span class="form-check-label">Gerar verso com informações complementares</span>
                        </label>
                    </div>
                    <div class="grid-span-2">
                        <label class="form-label">Texto complementar do verso</label>
                        <textarea class="form-control" name="certificate_program_extra" rows="4" placeholder="Histórico, descrição da ação voluntária, agradecimento ou observações."><?= e($recognitionValue('certificate_program_extra')) ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Imagem de fundo do verso por link</label>
                        <input class="form-control" name="certificate_program_background" value="<?= e($recognitionValue('certificate_program_background')) ?>" placeholder="/public/uploads/education/verso.jpg ou URL">
                    </div>
                    <div>
                        <label class="form-label">Enviar fundo do verso</label>
                        <input class="form-control" type="file" name="certificate_program_background_upload" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <div>
                        <label class="form-label">Colunas do verso</label>
                        <select class="form-select" name="certificate_program_columns">
                            <?php $programColumns = (int) $recognitionValue('certificate_program_columns', '2'); ?>
                            <option value="1"<?= $programColumns === 1 ? ' selected' : '' ?>>1 coluna</option>
                            <option value="2"<?= $programColumns === 2 ? ' selected' : '' ?>>2 colunas</option>
                            <option value="3"<?= $programColumns === 3 ? ' selected' : '' ?>>3 colunas</option>
                        </select>
                    </div>
                    <div class="form-action-cell">
                        <button class="btn btn-primary icon-btn"><i class="bi bi-award" aria-hidden="true"></i><?= $isEditingRecognition ? 'Salvar reconhecimento' : 'Emitir reconhecimento' ?></button>
                        <?php if ($isEditingRecognition): ?>
                            <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/recognitions')) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i>Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    Cadastre pessoas em <a href="<?= e(url('/admin/people')) ?>">Pessoas</a> ou usuários ativos para emitir reconhecimentos.
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
                            <div><dt>Tipo</dt><dd><?= e($recognition['recipient_kind'] ?? '') ?></dd></div>
                            <div><dt>Instituição</dt><dd><?= e($recognition['institution_name'] ?? 'Padrão institucional') ?></dd></div>
                            <div><dt>Código</dt><dd><?= e($recognition['verification_code'] ?? '') ?></dd></div>
                            <div><dt>Status</dt><dd><?= e($statusLabels[$recognition['status'] ?? ''] ?? ($recognition['status'] ?? '')) ?></dd></div>
                        </dl>
                    </div>
                    <div class="admin-list-actions">
                        <?php if (!empty($canIssueCertificates)): ?>
                            <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/recognitions?edit=' . ($recognition['id'] ?? ''))) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                            <form method="post" action="<?= e(url('/admin/education/recognition-certificate/status')) ?>" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="certificate_id" value="<?= e((string) ($recognition['id'] ?? '')) ?>">
                                <input type="hidden" name="action" value="<?= ($recognition['status'] ?? '') === 'locked' ? 'unlock' : 'lock' ?>">
                                <button class="btn btn-sm btn-outline-secondary icon-btn" type="submit"><i class="bi <?= ($recognition['status'] ?? '') === 'locked' ? 'bi-unlock' : 'bi-lock' ?>" aria-hidden="true"></i><?= ($recognition['status'] ?? '') === 'locked' ? 'Destravar' : 'Travar' ?></button>
                            </form>
                            <?php if (($recognition['status'] ?? '') !== 'revoked'): ?>
                                <form method="post" action="<?= e(url('/admin/education/recognition-certificate/status')) ?>" class="inline-form" onsubmit="return confirm('Revogar este reconhecimento?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="certificate_id" value="<?= e((string) ($recognition['id'] ?? '')) ?>">
                                    <input type="hidden" name="action" value="revoke">
                                    <button class="btn btn-sm btn-outline-danger icon-btn" type="submit"><i class="bi bi-slash-circle" aria-hidden="true"></i>Revogar</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/education/recognition-certificate/status')) ?>" class="inline-form" onsubmit="return confirm('Remover este reconhecimento da lista?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="certificate_id" value="<?= e((string) ($recognition['id'] ?? '')) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-sm btn-outline-danger icon-btn" type="submit"><i class="bi bi-trash" aria-hidden="true"></i>Excluir</button>
                            </form>
                        <?php endif; ?>
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
