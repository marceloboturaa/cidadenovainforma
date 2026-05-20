<?php
$totalDocuments = count($documents);
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
        <span>Total</span>
        <strong><?= e((string) $totalDocuments) ?></strong>
        <small>documento(s) cadastrado(s)</small>
    </article>
    <article>
        <span>No servidor</span>
        <strong><?= e((string) $documentsOnServer) ?></strong>
        <small>arquivo(s) confirmado(s)</small>
    </article>
    <article>
        <span>Restritos</span>
        <strong><?= e((string) $restrictedDocuments) ?></strong>
        <small>vis&iacute;veis apenas para liberados</small>
    </article>
</section>

<?php if ($canUpload): ?>
    <section class="panel document-upload-panel">
        <div class="section-heading">
            <h2>Novo documento</h2>
            <span>Envie o arquivo e escolha a visibilidade</span>
        </div>
        <form method="post" action="<?= e(url('/admin/documents')) ?>" enctype="multipart/form-data" class="document-upload-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label" for="document-title">T&iacute;tulo</label>
                <input class="form-control" id="document-title" name="title" maxlength="180" placeholder="Ex.: Ata da reuni&atilde;o">
            </div>
            <div>
                <label class="form-label" for="document-file">Arquivo</label>
                <input class="form-control" id="document-file" name="document" type="file" accept="<?= e($allowedAccept ?? '') ?>" required>
                <small class="form-text">Formatos permitidos: <?= e($allowedExtensionsText ?? '') ?></small>
            </div>
            <?php if ($canManage): ?>
                <div class="document-visibility-box">
                    <span class="form-label">Visibilidade</span>
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_public" value="1">
                        <span class="form-check-label">Publicar tamb&eacute;m no site</span>
                    </label>
                </div>
            <?php endif; ?>
            <div class="form-action-cell">
                <button class="btn btn-primary w-100">Enviar</button>
            </div>
            <?php if ($canManage): ?>
                <details class="document-access-details">
                    <summary>Liberar para usu&aacute;rios espec&iacute;ficos</summary>
                    <div class="document-access-options">
                        <?php foreach ($users as $user): ?>
                            <label>
                                <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>">
                                <span><?= e($user['name']) ?> <small><?= e($user['role_name']) ?></small></span>
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
        <span><?= e((string) $totalDocuments) ?> documento(s)</span>
    </div>

    <div class="document-list">
        <?php foreach ($documents as $document): ?>
            <?php
            $accessUserIds = $canManage ? \App\Models\Document::accessUserIds((int) $document['id']) : [];
            $fileExists = \App\Models\Document::fileExistsOnServer($document);
            $canEditDocument = $canManage || ($canUpload && (int) $document['uploaded_by'] === (int) (current_user()['id'] ?? 0));
            $documentType = strtoupper(pathinfo($document['original_name'], PATHINFO_EXTENSION) ?: 'ARQ');
            ?>
            <article class="document-row">
                <div class="document-file-icon"><?= e($documentType) ?></div>

                <div class="document-main">
                    <div class="document-title-line">
                        <h3><?= e($document['title']) ?></h3>
                        <span class="state-pill <?= $fileExists ? 'is-active' : 'is-pending' ?>"><?= $fileExists ? 'Arquivo no servidor' : 'Arquivo ausente' ?></span>
                        <span class="state-pill <?= !empty($document['is_public']) ? 'is-active' : 'is-muted' ?>"><?= !empty($document['is_public']) ? 'P&uacute;blico' : 'Restrito' ?></span>
                    </div>
                    <div class="document-meta-grid">
                        <span><?= e($document['original_name']) ?></span>
                        <span><?= e(number_format(((int) $document['size_bytes']) / 1024, 1, ',', '.')) ?> KB</span>
                        <span>Enviado por <?= e($document['uploader_name']) ?></span>
                        <?php if ($canManage && !$document['is_public']): ?>
                            <span><?= e((string) count($accessUserIds)) ?> usu&aacute;rio(s) liberado(s)</span>
                        <?php endif; ?>
                    </div>
                    <code class="document-storage-path"><?= e($document['path']) ?></code>
                </div>

                <div class="document-actions">
                    <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/documents/download?id=' . $document['id'])) ?>">Baixar</a>
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
                            </div>

                            <?php if ($canManage): ?>
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_public" value="1" <?= checked((bool) $document['is_public']) ?>>
                                    <span class="form-check-label">Publicar tamb&eacute;m no site</span>
                                </label>
                                <details class="document-access-details compact">
                                    <summary>Usu&aacute;rios com acesso</summary>
                                    <div class="document-access-options compact">
                                        <?php foreach ($users as $user): ?>
                                            <label>
                                                <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>" <?= checked(in_array((int) $user['id'], $accessUserIds, true)) ?>>
                                                <span><?= e($user['name']) ?> <small><?= e($user['role_name']) ?></small></span>
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
    </div>
</section>
