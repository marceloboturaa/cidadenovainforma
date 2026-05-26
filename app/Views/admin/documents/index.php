<?php
$totalDocuments = count($documents);
$linkedDocuments = count(array_filter($documents, fn (array $document): bool => \App\Models\Document::isExternalLink($document)));
$documentsOnServer = count(array_filter($documents, fn (array $document): bool => \App\Models\Document::fileExistsOnServer($document)));
$publicDocuments = count(array_filter($documents, fn (array $document): bool => !empty($document['is_public'])));
$restrictedDocuments = $totalDocuments - $publicDocuments;
?>

<div class="page-heading">
    <div>
        <p>Equipe</p>
        <h1>Documentos internos</h1>
    </div>
</div>

<section class="document-summary-grid" aria-label="Resumo dos documentos">
    <article>
        <i class="bi bi-files" aria-hidden="true"></i>
        <span>Total</span>
        <strong><?= e((string) $totalDocuments) ?></strong>
        <small>documento(s) cadastrado(s)</small>
    </article>
    <article>
        <i class="bi bi-hdd-network" aria-hidden="true"></i>
        <span>No servidor</span>
        <strong><?= e((string) $documentsOnServer) ?></strong>
        <small>arquivo(s) confirmado(s)</small>
    </article>
    <article>
        <i class="bi bi-link-45deg" aria-hidden="true"></i>
        <span>Links</span>
        <strong><?= e((string) $linkedDocuments) ?></strong>
        <small>atalho(s) externo(s)</small>
    </article>
    <article>
        <i class="bi bi-lock" aria-hidden="true"></i>
        <span>Restritos</span>
        <strong><?= e((string) $restrictedDocuments) ?></strong>
        <small>vis&iacute;veis apenas para liberados</small>
    </article>
</section>

<?php if ($canManage): ?>
    <section class="panel document-rule-panel">
        <div class="section-heading">
            <h2>Permiss&otilde;es desta &aacute;rea</h2>
            <span>Acesso e envio s&atilde;o controles separados</span>
        </div>
        <div class="document-rule-grid">
            <article>
                <strong>Quem pode ver</strong>
                <p>&Eacute; definido em cada documento, na op&ccedil;&atilde;o <b>Usu&aacute;rios com acesso</b>. Ver e baixar s&atilde;o permiss&otilde;es separadas.</p>
            </article>
            <article>
                <strong>Quem pode enviar arquivos</strong>
                <p>Administradores desta &aacute;rea j&aacute; podem enviar. Al&eacute;m deles, os usu&aacute;rios marcados abaixo tamb&eacute;m poder&atilde;o subir novos documentos para o servidor.</p>
            </article>
        </div>
        <form method="post" action="<?= e(url('/admin/documents/uploaders')) ?>" class="document-uploaders-form">
            <?= csrf_field() ?>
            <span class="form-label">Usu&aacute;rios autorizados a enviar documentos</span>
            <div class="document-access-tools" data-document-access-tools>
                <label>
                    <span>Pesquisar usu&aacute;rio</span>
                    <input class="form-control form-control-sm" type="search" placeholder="Nome ou perfil" data-document-access-search>
                </label>
                <div>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-document-access-select>Marcar todos</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-document-access-clear>Limpar todos</button>
                </div>
            </div>
            <div class="document-access-options compact" data-document-access-list>
                <?php foreach ($users as $user): ?>
                    <?php
                    $roleSlugs = array_filter(explode(',', (string) ($user['role_slugs'] ?? $user['role_slug'] ?? '')));
                    if (in_array('master', $roleSlugs, true)) {
                        continue;
                    }
                    $userSearchText = mb_strtolower(($user['name'] ?? '') . ' ' . ($user['role_names'] ?? $user['role_name'] ?? ''), 'UTF-8');
                    ?>
                    <label data-document-access-item="<?= e($userSearchText) ?>">
                        <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>" <?= checked(in_array((int) $user['id'], $documentUploadUserIds ?? [], true)) ?>>
                        <span><?= e($user['name']) ?> <small><?= e($user['role_names'] ?? $user['role_name']) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-sm btn-outline-primary">Salvar quem pode enviar</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($canUpload): ?>
    <section class="panel document-upload-panel">
        <div class="section-heading">
            <h2>Novo documento</h2>
            <span>Esta parte s&oacute; aparece para quem tem permiss&atilde;o de envio</span>
        </div>
        <form method="post" action="<?= e(url('/admin/documents')) ?>" enctype="multipart/form-data" class="document-upload-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label" for="document-title">T&iacute;tulo</label>
                <input class="form-control" id="document-title" name="title" maxlength="180" placeholder="Ex.: Ata da reuni&atilde;o">
            </div>
            <div>
                <label class="form-label" for="document-file">Arquivo</label>
                <input class="form-control" id="document-file" name="document" type="file" accept="<?= e($allowedAccept ?? '') ?>">
                <small class="form-text">Formatos permitidos: <?= e($allowedExtensionsText ?? '') ?></small>
            </div>
            <div>
                <label class="form-label" for="document-url">Link do documento</label>
                <input class="form-control" id="document-url" name="document_url" type="url" maxlength="255" placeholder="https://...">
                <small class="form-text">Use arquivo ou link. Se os dois forem preenchidos, o arquivo será usado.</small>
            </div>
            <?php if ($canManage): ?>
                <div class="document-visibility-box">
                    <span class="form-label">Visibilidade</span>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_public" value="1">
                        <span class="form-check-label">Publicar tamb&eacute;m no site</span>
                    </label>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="allow_download" value="1" checked>
                        <span class="form-check-label">Permitir baixar o documento</span>
                    </label>
                </div>
            <?php endif; ?>
            <div class="form-action-cell">
                <button class="btn btn-primary w-100">Enviar</button>
            </div>
            <?php if ($canManage): ?>
                <details class="document-access-details">
                    <summary>Quem poder&aacute; ver este documento</summary>
                    <div class="document-access-tools" data-document-access-tools>
                        <label>
                            <span>Pesquisar usu&aacute;rio</span>
                            <input class="form-control form-control-sm" type="search" placeholder="Nome ou perfil" data-document-access-search>
                        </label>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-document-access-select>Marcar todos</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-document-access-clear>Limpar todos</button>
                        </div>
                    </div>
                    <div class="document-access-options" data-document-access-list>
                        <?php foreach ($users as $user): ?>
                            <?php $userSearchText = mb_strtolower(($user['name'] ?? '') . ' ' . ($user['role_names'] ?? $user['role_name'] ?? ''), 'UTF-8'); ?>
                            <label data-document-access-item="<?= e($userSearchText) ?>">
                                <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>">
                                <span><?= e($user['name']) ?> <small><?= e($user['role_names'] ?? $user['role_name']) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </form>
    </section>
<?php endif; ?>

<?php if (!empty($canManageFormats)): ?>
    <section class="panel document-formats-panel">
        <div class="section-heading">
            <h2>Formatos permitidos</h2>
            <span>Controle exclusivo do MASTER</span>
        </div>
        <form method="post" action="<?= e(url('/admin/documents/formats')) ?>" class="document-formats-form">
            <?= csrf_field() ?>
            <label class="form-label">
                Extens&otilde;es liberadas para upload
                <textarea class="form-control" name="allowed_extensions" rows="3" placeholder="pdf, docx, xlsx, png, zip"><?= e($allowedExtensionsText ?? '') ?></textarea>
            </label>
            <p class="form-text">Separe por v&iacute;rgula ou espa&ccedil;o. Extens&otilde;es execut&aacute;veis e scripts continuam bloqueados.</p>
            <button class="btn btn-outline-primary">Salvar formatos</button>
        </form>
    </section>
<?php endif; ?>

<section class="panel document-list-panel">
    <div class="section-heading">
        <h2>Arquivos dispon&iacute;veis</h2>
        <span><span data-document-visible-count><?= e((string) $totalDocuments) ?></span> de <?= e((string) $totalDocuments) ?> documento(s)</span>
    </div>

    <div class="document-list-search">
        <label>
            <span>Pesquisar documentos</span>
            <input class="form-control" type="search" placeholder="Nome, arquivo, tipo ou respons&aacute;vel" data-document-search>
        </label>
    </div>

    <div class="document-list" data-document-list>
        <?php foreach ($documents as $document): ?>
            <?php
            $accessUserIds = $canManage ? \App\Models\Document::accessUserIds((int) $document['id']) : [];
            $isLink = \App\Models\Document::isExternalLink($document);
            $isGoogleDocument = \App\Models\Document::googlePreviewUrl($document) !== null;
            $fileExists = \App\Models\Document::fileExistsOnServer($document);
            $canEditDocument = $canManage || ($canUpload && (int) $document['uploaded_by'] === (int) (current_user()['id'] ?? 0));
            $documentType = \App\Models\Document::typeLabel($document);
            $canDownloadDocument = !$isGoogleDocument && (!$isLink || $canManage) && ($canManage || !empty($document['allow_download']));
            $displayOriginalName = $isGoogleDocument ? 'Documento Google vinculado' : (string) $document['original_name'];
            $documentIcon = match (true) {
                $isGoogleDocument => 'bi-google',
                $documentType === 'PDF' => 'bi-file-earmark-pdf',
                in_array($documentType, ['JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'SVG'], true) => 'bi-file-earmark-image',
                in_array($documentType, ['DOC', 'DOCX', 'ODT', 'RTF'], true) => 'bi-file-earmark-word',
                in_array($documentType, ['XLS', 'XLSX', 'ODS', 'CSV'], true) => 'bi-file-earmark-spreadsheet',
                in_array($documentType, ['PPT', 'PPTX', 'ODP'], true) => 'bi-file-earmark-slides',
                in_array($documentType, ['ZIP', 'RAR', '7Z'], true) => 'bi-file-earmark-zip',
                $isLink => 'bi-link-45deg',
                default => 'bi-file-earmark-text',
            };
            $storageLabel = $isGoogleDocument ? 'Google Drive' : ($isLink ? 'Link externo' : ($fileExists ? 'Servidor' : 'Ausente'));
            $storageIcon = $isGoogleDocument ? 'bi-google' : ($isLink ? 'bi-link-45deg' : ($fileExists ? 'bi-hdd-network' : 'bi-exclamation-triangle'));
            $documentSearchText = mb_strtolower(implode(' ', [
                $document['title'] ?? '',
                $displayOriginalName,
                $documentType,
                $storageLabel,
                $document['uploader_name'] ?? '',
                !empty($document['is_public']) ? 'publico' : 'restrito',
            ]), 'UTF-8');
            ?>
            <article class="document-row" data-document-row data-document-search-text="<?= e($documentSearchText) ?>">
                <div class="document-file-icon <?= $isGoogleDocument ? 'is-google' : '' ?>">
                    <i class="bi <?= e($documentIcon) ?>" aria-hidden="true"></i>
                    <span><?= e($documentType) ?></span>
                </div>

                <div class="document-main">
                    <div class="document-title-line">
                        <h3><?= e($document['title']) ?></h3>
                        <span class="state-pill document-state <?= ($fileExists || $isLink) ? 'is-active' : 'is-pending' ?>"><i class="bi <?= e($storageIcon) ?>" aria-hidden="true"></i><?= e($storageLabel) ?></span>
                        <span class="state-pill document-state <?= !empty($document['is_public']) ? 'is-active' : 'is-muted' ?>"><i class="bi <?= !empty($document['is_public']) ? 'bi-globe2' : 'bi-lock' ?>" aria-hidden="true"></i><?= !empty($document['is_public']) ? 'P&uacute;blico' : 'Restrito' ?></span>
                        <span class="state-pill document-state <?= !empty($document['allow_download']) && !$isGoogleDocument ? 'is-active' : 'is-muted' ?>"><i class="bi <?= !empty($document['allow_download']) && !$isGoogleDocument ? 'bi-download' : 'bi-eye' ?>" aria-hidden="true"></i><?= !empty($document['allow_download']) && !$isGoogleDocument ? 'Baixar' : 'Somente ver' ?></span>
                    </div>
                    <div class="document-meta-grid">
                        <?php if ($canManage): ?>
                            <span><i class="bi bi-file-text" aria-hidden="true"></i><?= e($displayOriginalName) ?></span>
                            <span><i class="bi bi-database" aria-hidden="true"></i><?= $isLink ? e($storageLabel) : e(number_format(((int) $document['size_bytes']) / 1024, 1, ',', '.')) . ' KB' ?></span>
                        <?php else: ?>
                            <span><i class="bi <?= e($storageIcon) ?>" aria-hidden="true"></i><?= e($storageLabel) ?></span>
                        <?php endif; ?>
                        <span><i class="bi bi-person" aria-hidden="true"></i>Enviado por <?= e($document['uploader_name']) ?></span>
                        <?php if ($canManage && !$document['is_public']): ?>
                            <span><?= e((string) count($accessUserIds)) ?> usu&aacute;rio(s) liberado(s)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="document-actions">
                    <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/documents/visualizar?id=' . $document['id'])) ?>">Ver</a>
                    <?php if ($canDownloadDocument): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/documents/download?id=' . $document['id'])) ?>"<?= $isLink ? ' target="_blank" rel="noopener"' : '' ?>><?= $isLink ? 'Abrir' : 'Baixar' ?></a>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/documents/delete?id=' . $document['id'])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Remover</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($canEditDocument): ?>
                    <details class="document-row-access">
                        <summary>Editar documento</summary>
                        <form method="post" action="<?= e(url('/admin/documents/update?id=' . $document['id'])) ?>" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="document-edit-grid">
                                <label class="form-label">
                                    T&iacute;tulo
                                    <input class="form-control form-control-sm" name="title" value="<?= e($document['title']) ?>" maxlength="180" required>
                                </label>
                                <label class="form-label">
                                    Trocar arquivo
                                    <input class="form-control form-control-sm" name="document" type="file" accept="<?= e($allowedAccept ?? '') ?>">
                                </label>
                                <?php if ($canManage): ?>
                                    <label class="form-label">
                                        Trocar por link
                                        <input class="form-control form-control-sm" name="document_url" type="url" maxlength="255" value="<?= $isLink ? e($document['path']) : '' ?>" placeholder="https://...">
                                    </label>
                                <?php endif; ?>
                            </div>

                            <?php if ($canManage): ?>
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_public" value="1" <?= checked((bool) $document['is_public']) ?>>
                                    <span class="form-check-label">Publicar tamb&eacute;m no site</span>
                                </label>
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_download" value="1" <?= checked((bool) ($document['allow_download'] ?? true)) ?>>
                                    <span class="form-check-label">Permitir baixar o documento</span>
                                </label>
                                <details class="document-access-details compact">
                                    <summary>Usu&aacute;rios que podem ver</summary>
                                    <div class="document-access-tools" data-document-access-tools>
                                        <label>
                                            <span>Pesquisar usu&aacute;rio</span>
                                            <input class="form-control form-control-sm" type="search" placeholder="Nome ou perfil" data-document-access-search>
                                        </label>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-document-access-select>Marcar todos</button>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-document-access-clear>Limpar todos</button>
                                        </div>
                                    </div>
                                    <div class="document-access-options compact" data-document-access-list>
                                        <?php foreach ($users as $user): ?>
                                            <?php $userSearchText = mb_strtolower(($user['name'] ?? '') . ' ' . ($user['role_names'] ?? $user['role_name'] ?? ''), 'UTF-8'); ?>
                                            <label data-document-access-item="<?= e($userSearchText) ?>">
                                                <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>" <?= checked(in_array((int) $user['id'], $accessUserIds, true)) ?>>
                                                <span><?= e($user['name']) ?> <small><?= e($user['role_names'] ?? $user['role_name']) ?></small></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php endif; ?>

                            <button class="btn btn-sm btn-outline-primary">Salvar altera&ccedil;&otilde;es</button>
                        </form>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$documents): ?>
            <div class="empty-state">Nenhum documento dispon&iacute;vel.</div>
        <?php endif; ?>
        <div class="empty-state" data-document-empty hidden>Nenhum documento encontrado.</div>
    </div>
</section>
