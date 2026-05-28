<div class="page-heading">
    <div>
        <p>Documento interno</p>
        <h1><?= e($document['title'] ?? 'Documento') ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/documents')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
        <?php if (!empty($canDownload)): ?>
            <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/documents/download?id=' . $document['id'])) ?>"><i class="bi bi-download" aria-hidden="true"></i>Baixar</a>
        <?php endif; ?>
    </div>
</div>

<section class="panel document-inline-panel">
    <?php if (($viewerType ?? '') === 'image'): ?>
        <img src="<?= e($documentSrc) ?>" alt="<?= e($document['title'] ?? 'Documento') ?>">
    <?php elseif (($viewerType ?? '') === 'text'): ?>
        <pre><?= e($documentText ?? '') ?></pre>
    <?php elseif (($viewerType ?? '') === 'pdf'): ?>
        <?php $annotationEndpoints = $annotationEndpoints ?? null; ?>
        <div class="document-pdf-viewer"
            data-pdf-viewer
            data-pdf-url="<?= e($documentSrc) ?>"
            data-pdf-start-page="<?= e((string) ($pdfStartPage ?? 1)) ?>"
            <?php if ($annotationEndpoints): ?>
                data-pdf-annotations-url="<?= e($annotationEndpoints['list']) ?>"
                data-pdf-annotations-store-url="<?= e($annotationEndpoints['store']) ?>"
                data-pdf-annotations-delete-url="<?= e($annotationEndpoints['delete']) ?>"
            <?php endif; ?>
        >
            <div class="document-pdf-toolbar">
                <div class="document-pdf-toolbar-group">
                    <button type="button" title="P&aacute;gina anterior" aria-label="P&aacute;gina anterior" data-pdf-prev><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" title="Pr&oacute;xima p&aacute;gina" aria-label="Pr&oacute;xima p&aacute;gina" data-pdf-next><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                </div>
                <span data-pdf-status>Carregando PDF...</span>
                <div class="document-pdf-toolbar-group document-pdf-mode-group">
                    <button type="button" title="Ver uma p&aacute;gina por vez" aria-label="Ver uma p&aacute;gina por vez" data-pdf-mode-page><i class="bi bi-file-earmark" aria-hidden="true"></i></button>
                    <button type="button" title="Rolar documento inteiro" aria-label="Rolar documento inteiro" data-pdf-mode-scroll><i class="bi bi-layout-three-columns" aria-hidden="true"></i></button>
                </div>
                <div class="document-pdf-toolbar-group">
                    <button type="button" title="Reduzir zoom" aria-label="Reduzir zoom" data-pdf-zoom-out><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                    <button type="button" title="Ajustar largura" aria-label="Ajustar largura" data-pdf-fit><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i></button>
                    <button type="button" title="Aumentar zoom" aria-label="Aumentar zoom" data-pdf-zoom-in><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                </div>
                <?php if ($annotationEndpoints): ?>
                    <div class="document-pdf-toolbar-group">
                        <button type="button" title="Marcar trecho" aria-label="Marcar trecho" data-pdf-tool="highlight"><i class="bi bi-highlighter" aria-hidden="true"></i></button>
                        <button type="button" title="Comentar" aria-label="Comentar" data-pdf-tool="comment"><i class="bi bi-chat-left-text" aria-hidden="true"></i></button>
                        <button type="button" title="Mover/selecionar" aria-label="Mover/selecionar" data-pdf-tool="select"><i class="bi bi-cursor" aria-hidden="true"></i></button>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($annotationEndpoints): ?>
                <div class="document-pdf-annotation-bar">
                    <label>
                        <span>Cor</span>
                        <select data-pdf-annotation-color>
                            <option value="#facc15">Amarelo</option>
                            <option value="#38bdf8">Azul</option>
                            <option value="#4ade80">Verde</option>
                            <option value="#fb7185">Rosa</option>
                        </select>
                    </label>
                    <label class="document-pdf-note-field">
                        <span>Coment&aacute;rio</span>
                        <input type="text" maxlength="1200" placeholder="Use a ferramenta de coment&aacute;rio e clique no PDF" data-pdf-annotation-note>
                    </label>
                    <small data-pdf-annotation-hint>Use Marcar para arrastar uma &aacute;rea ou Comentar para clicar em um ponto do PDF.</small>
                </div>
            <?php endif; ?>
            <div class="document-pdf-canvas-shell">
                <div class="document-pdf-page-frame" data-pdf-page-frame>
                    <canvas data-pdf-canvas></canvas>
                    <div class="document-pdf-annotation-layer" data-pdf-annotation-layer></div>
                </div>
                <div class="document-pdf-pages" data-pdf-pages hidden></div>
            </div>
            <p data-pdf-error hidden>N&atilde;o foi poss&iacute;vel carregar o PDF dentro da p&aacute;gina.</p>
        </div>
    <?php elseif (($viewerType ?? '') === 'google'): ?>
        <div class="document-google-viewer document-modern-viewer">
            <iframe src="<?= e($documentSrc) ?>" title="<?= e($document['title'] ?? 'Documento') ?>" referrerpolicy="no-referrer"></iframe>
        </div>
    <?php else: ?>
        <div class="document-view-notice document-external-notice">
            <i class="bi bi-file-earmark-lock" aria-hidden="true"></i>
            <strong>Visualiza&ccedil;&atilde;o interna indispon&iacute;vel</strong>
            <p>Este formato n&atilde;o pode ser exibido com seguran&ccedil;a dentro do painel.</p>
            <?php if (!empty($canDownload)): ?>
                <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/documents/download?id=' . $document['id'])) ?>"><i class="bi bi-download" aria-hidden="true"></i>Baixar arquivo</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (($viewerType ?? '') === 'pdf'): ?>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
    <script src="<?= e(versioned_asset_url('/public/assets/js/document-pdf-viewer.js')) ?>"></script>
<?php endif; ?>
