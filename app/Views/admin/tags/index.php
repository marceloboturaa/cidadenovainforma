<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Organização editorial</p>
        <h1>Tags</h1>
    </div>
</div>

<section class="panel taxonomy-editor-panel">
    <div class="taxonomy-editor-copy">
        <h2><?= $isEdit ? 'Editar tag' : 'Nova tag' ?></h2>
        <p>Tags agrupam assuntos específicos. Prefira termos curtos como bairro, saúde ou educação.</p>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/tags/update?id=' . $editing['id']) : url('/admin/tags')) ?>" class="taxonomy-editor-form taxonomy-tag-form">
        <?= csrf_field() ?>
        <label class="form-label">
            <span>Nome</span>
            <input class="form-control" name="name" value="<?= e($editing['display_name'] ?? $editing['name'] ?? '') ?>" placeholder="Ex.: Saúde, Educação, Bairro" required>
        </label>
        <div class="split-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar tag' : 'Criar tag' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/tags')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel taxonomy-list-panel">
    <div class="section-heading">
        <h2><i class="bi bi-tags" aria-hidden="true"></i> Tags cadastradas</h2>
        <span><?= e((string) count($tags)) ?> tag(s)</span>
    </div>
    <div class="taxonomy-table taxonomy-tag-table" role="table" aria-label="Tags cadastradas">
        <div class="taxonomy-row taxonomy-row-head" role="row">
            <div role="columnheader">Tag</div>
            <div role="columnheader">Notícias</div>
            <div role="columnheader">Ações</div>
        </div>
        <?php foreach ($tags as $tag): ?>
            <article class="taxonomy-row" role="row">
                <div class="taxonomy-name-cell" role="cell">
                    <strong><?= e($tag['display_name'] ?? $tag['name']) ?></strong>
                    <span><?= e($tag['slug']) ?></span>
                </div>
                <div role="cell"><span class="taxonomy-count"><?= e((string) $tag['news_count']) ?></span></div>
                <div class="taxonomy-row-actions" role="cell">
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/tags/edit?id=' . $tag['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/tags/delete?id=' . $tag['id'])) ?>" onsubmit="return confirm('Remover esta tag? Ela será desvinculada das notícias.');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$tags): ?>
            <div class="empty-state">Nenhuma tag cadastrada.</div>
        <?php endif; ?>
    </div>
</section>
