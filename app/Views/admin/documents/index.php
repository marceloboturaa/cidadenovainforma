<div class="page-heading">
    <div>
        <p>Equipe</p>
        <h1>Documentos</h1>
    </div>
</div>

<?php if ($canManage): ?>
    <section class="panel document-upload-panel">
        <div class="section-heading">
            <h2>Novo documento</h2>
            <span>Envie o arquivo e defina quem pode acessar</span>
        </div>
        <form method="post" action="<?= e(url('/admin/documents')) ?>" enctype="multipart/form-data" class="document-upload-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Título</label>
                <input class="form-control" name="title" maxlength="180" placeholder="Ex.: Ata da reunião">
            </div>
            <div>
                <label class="form-label">Arquivo</label>
                <input class="form-control" name="document" type="file" accept="<?= e($allowedAccept ?? '') ?>" required>
                <small class="form-text">Formatos liberados: <?= e($allowedExtensionsText ?? '') ?></small>
            </div>
            <div>
                <label class="form-label">Visibilidade</label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_public" value="1">
                    <span class="form-check-label">Documento público no site</span>
                </label>
            </div>
            <div class="form-action-cell">
                <button class="btn btn-primary w-100">Enviar</button>
            </div>
            <details class="document-access-details">
                <summary>Liberar para usuários específicos</summary>
                <div class="document-access-options">
                    <?php foreach ($users as $user): ?>
                        <label>
                            <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>">
                            <span><?= e($user['name']) ?> <small><?= e($user['role_name']) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </details>
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
                Extensões liberadas para upload
                <textarea class="form-control" name="allowed_extensions" rows="3" placeholder="pdf, ai, cdr, eps, png, svg, mov, xlsx, rar"><?= e($allowedExtensionsText ?? '') ?></textarea>
            </label>
            <p class="form-text">Separe por vírgula ou espaço. Exemplos: pdf, ai, cdr, eps, png, svg, mov, xlsx, rar. Extensões executáveis e scripts são bloqueados.</p>
            <button class="btn btn-outline-primary">Salvar formatos</button>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="section-heading">
        <h2>Arquivos disponíveis</h2>
        <span><?= e((string) count($documents)) ?> documento(s)</span>
    </div>

    <div class="document-list">
        <?php foreach ($documents as $document): ?>
            <?php $accessUserIds = $canManage ? \App\Models\Document::accessUserIds((int) $document['id']) : []; ?>
            <article class="document-row">
                <div class="document-file-icon">
                    <?= e(strtoupper(pathinfo($document['original_name'], PATHINFO_EXTENSION) ?: 'ARQ')) ?>
                </div>
                <div class="document-main">
                    <div class="document-title-line">
                        <h3><?= e($document['title']) ?></h3>
                        <span class="state-pill <?= !empty($document['is_public']) ? 'is-active' : 'is-muted' ?>"><?= !empty($document['is_public']) ? 'Público' : 'Restrito' ?></span>
                    </div>
                    <p><?= e($document['original_name']) ?></p>
                    <small>
                        <?= e(number_format(((int) $document['size_bytes']) / 1024, 1, ',', '.')) ?> KB
                        · Enviado por <?= e($document['uploader_name']) ?>
                        <?php if ($canManage && !$document['is_public']): ?>
                            · <?= e((string) count($accessUserIds)) ?> usuário(s) liberado(s)
                        <?php endif; ?>
                    </small>
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
                <?php if ($canManage): ?>
                    <details class="document-row-access">
                        <summary>Editar acesso</summary>
                        <form method="post" action="<?= e(url('/admin/documents/access?id=' . $document['id'])) ?>">
                            <?= csrf_field() ?>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_public" value="1" <?= checked((bool) $document['is_public']) ?>>
                                <span class="form-check-label">Documento público no site</span>
                            </label>
                            <div class="document-access-options compact">
                                <?php foreach ($users as $user): ?>
                                    <label>
                                        <input type="checkbox" name="user_ids[]" value="<?= e((string) $user['id']) ?>" <?= checked(in_array((int) $user['id'], $accessUserIds, true)) ?>>
                                        <span><?= e($user['name']) ?> <small><?= e($user['role_name']) ?></small></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-sm btn-outline-primary">Salvar acesso</button>
                        </form>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$documents): ?>
            <div class="empty-state">Nenhum documento disponível.</div>
        <?php endif; ?>
    </div>
</section>
