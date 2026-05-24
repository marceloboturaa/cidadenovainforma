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
        <div class="public-document-notice">
            <strong>PDF disponivel para visualizacao</strong>
            <p>Para evitar bloqueio do navegador, o PDF abre em uma aba protegida do proprio site.</p>
            <a href="<?= e($documentSrc) ?>" target="_blank" rel="noopener">Abrir PDF</a>
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
