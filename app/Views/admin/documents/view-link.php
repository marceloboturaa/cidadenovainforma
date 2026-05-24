<div class="page-heading">
    <div>
        <p>Documento interno</p>
        <h1><?= e($document['title'] ?? 'Documento') ?></h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/documents')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
    </div>
</div>

<section class="panel document-inline-panel">
    <iframe src="<?= e($document['path'] ?? '') ?>" title="<?= e($document['title'] ?? 'Documento') ?>"></iframe>
    <p class="form-text mb-0">Se o conteúdo não aparecer, o site de origem bloqueou a visualização incorporada.</p>
</section>
