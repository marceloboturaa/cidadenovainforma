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
        <div class="document-pdf-viewer" data-pdf-viewer data-pdf-url="<?= e($documentSrc) ?>">
            <div class="document-pdf-toolbar">
                <button type="button" data-pdf-prev>Anterior</button>
                <span data-pdf-status>Carregando PDF...</span>
                <button type="button" data-pdf-next>Pr&oacute;xima</button>
                <button type="button" data-pdf-zoom-out>Menos zoom</button>
                <button type="button" data-pdf-zoom-in>Mais zoom</button>
            </div>
            <div class="document-pdf-canvas-shell">
                <canvas data-pdf-canvas></canvas>
            </div>
            <p data-pdf-error hidden>N&atilde;o foi poss&iacute;vel carregar o PDF dentro da p&aacute;gina.</p>
        </div>
    <?php elseif (($viewerType ?? '') === 'google'): ?>
        <div class="document-google-viewer">
            <iframe src="<?= e($documentSrc) ?>" title="<?= e($document['title'] ?? 'Documento') ?>" referrerpolicy="no-referrer"></iframe>
        </div>
    <?php else: ?>
        <div class="document-view-notice">
            <strong>Visualizacao protegida indisponivel</strong>
            <p>Este formato nao pode ser exibido com seguranca no painel sem liberar o arquivo para download.</p>
        </div>
    <?php endif; ?>
</section>

<?php if (($viewerType ?? '') === 'pdf'): ?>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
    <script src="<?= e(versioned_asset_url('/public/assets/js/document-pdf-viewer.js')) ?>"></script>
<?php endif; ?>
