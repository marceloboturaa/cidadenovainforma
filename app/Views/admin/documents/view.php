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
        <div class="document-view-notice">
            <strong>PDF disponivel para visualizacao</strong>
            <p>Para evitar bloqueio do navegador, o PDF abre em uma aba protegida do proprio painel.</p>
            <a class="btn btn-sm btn-outline-primary" href="<?= e($documentSrc) ?>" target="_blank" rel="noopener">Abrir PDF</a>
        </div>
    <?php else: ?>
        <div class="document-view-notice">
            <strong>Visualizacao protegida indisponivel</strong>
            <p>Este formato nao pode ser exibido com seguranca no painel sem liberar o arquivo para download.</p>
        </div>
    <?php endif; ?>
</section>
