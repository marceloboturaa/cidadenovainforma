<section class="listing-head">
    <h1>Documentos</h1>
    <p>Arquivos públicos disponibilizados pela equipe do Cidade Nova Informa.</p>
</section>

<section class="public-document-list">
    <?php foreach ($documents as $document): ?>
        <article class="public-document-card">
            <div>
                <span><?= e(strtoupper(pathinfo($document['original_name'], PATHINFO_EXTENSION) ?: 'ARQ')) ?></span>
                <h2><?= e($document['title']) ?></h2>
                <p><?= e($document['original_name']) ?> · <?= e(number_format(((int) $document['size_bytes']) / 1024, 1, ',', '.')) ?> KB</p>
            </div>
            <a href="<?= e(url('/documentos/download?id=' . $document['id'])) ?>">Baixar</a>
        </article>
    <?php endforeach; ?>

    <?php if (!$documents): ?>
        <article class="empty-public">
            <h2>Nenhum documento público</h2>
            <p>Quando houver documentos liberados ao público, eles aparecerão aqui.</p>
        </article>
    <?php endif; ?>
</section>
