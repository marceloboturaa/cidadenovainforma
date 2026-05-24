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
    <iframe src="<?= e($documentSrc) ?>" title="<?= e($document['title'] ?? 'Documento') ?>"></iframe>
    <?php if (\App\Models\Document::isExternalLink($document)): ?>
        <p>Se o conteudo nao aparecer, o site de origem bloqueou a visualizacao incorporada.</p>
    <?php endif; ?>
</section>
