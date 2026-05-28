<section class="listing-head public-document-view-head">
    <div>
        <h1><?= e($document['title'] ?? 'Documento') ?></h1>
        <p><?= e($document['original_name'] ?? 'Documento publico') ?></p>
    </div>
    <div class="public-document-view-actions">
        <a href="<?= e(url('/documentos')) ?>">Voltar</a>
        <?php if (!empty($downloadUrl)): ?>
            <a href="<?= e($downloadUrl) ?>"<?= \App\Models\Document::isExternalLink($document) ? ' target="_blank" rel="noopener"' : '' ?>>Baixar</a>
        <?php endif; ?>
    </div>
</section>

<section class="public-document-viewer">
    <?php if (($viewerType ?? '') === 'image'): ?>
        <img src="<?= e($documentSrc) ?>" alt="<?= e($document['title'] ?? 'Documento') ?>">
    <?php elseif (($viewerType ?? '') === 'text'): ?>
        <pre><?= e($documentText ?? '') ?></pre>
    <?php elseif (($viewerType ?? '') === 'pdf'): ?>
        <div class="document-pdf-viewer" data-pdf-viewer data-pdf-url="<?= e($documentSrc) ?>" data-pdf-start-page="<?= e((string) ($pdfStartPage ?? 1)) ?>">
            <div class="document-pdf-toolbar">
                <div class="document-pdf-toolbar-group">
                    <button type="button" data-pdf-prev>Anterior</button>
                    <button type="button" data-pdf-next>Proxima</button>
                </div>
                <span data-pdf-status>Carregando PDF...</span>
                <div class="document-pdf-toolbar-group document-pdf-mode-group">
                    <button type="button" data-pdf-mode-page>Pagina</button>
                    <button type="button" data-pdf-mode-scroll>Rolagem</button>
                </div>
                <div class="document-pdf-toolbar-group">
                    <button type="button" data-pdf-zoom-out>Menos zoom</button>
                    <button type="button" data-pdf-fit>Ajustar</button>
                    <button type="button" data-pdf-zoom-in>Mais zoom</button>
                </div>
            </div>
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
        <div class="document-google-viewer">
            <iframe src="<?= e($documentSrc) ?>" title="<?= e($document['title'] ?? 'Documento') ?>" referrerpolicy="no-referrer"></iframe>
        </div>
    <?php elseif (($viewerType ?? '') === 'external'): ?>
        <div class="public-document-notice">
            <strong>Documento externo</strong>
            <p>Por seguranca, links externos nao sao carregados dentro da pagina.</p>
            <a href="<?= e($externalUrl ?? '#') ?>" target="_blank" rel="noopener">Abrir documento</a>
        </div>
    <?php else: ?>
        <div class="public-document-notice">
            <strong>Visualizacao protegida indisponivel</strong>
            <p>Este formato nao pode ser exibido com seguranca no site sem liberar o arquivo para download.</p>
        </div>
    <?php endif; ?>
</section>

<?php if (($viewerType ?? '') === 'pdf'): ?>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
    <script src="<?= e(versioned_asset_url('/public/assets/js/document-pdf-viewer.js')) ?>"></script>
<?php endif; ?>
