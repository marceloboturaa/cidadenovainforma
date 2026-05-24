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
    <iframe src="<?= e(url('/admin/documents/visualizar?id=' . $document['id'] . '&inline=1')) ?>" title="<?= e($document['title'] ?? 'Documento') ?>"></iframe>
</section>
