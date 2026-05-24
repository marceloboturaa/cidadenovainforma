<div class="page-heading">
    <div>
        <p>Documento interno</p>
        <h1><?= e($document['title'] ?? 'Documento') ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/documents')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
        <?php if (!empty($canDownload)): ?>
            <a class="btn btn-primary icon-btn" href="<?= e($document['path'] ?? '#') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Abrir</a>
        <?php endif; ?>
    </div>
</div>

<section class="panel document-inline-panel">
    <div class="document-view-notice">
        <strong>Documento externo</strong>
        <p>Por seguranca, links externos nao sao carregados dentro do painel.</p>
        <a class="btn btn-sm btn-outline-primary" href="<?= e($document['path'] ?? '#') ?>" target="_blank" rel="noopener">Abrir documento</a>
    </div>
</section>
