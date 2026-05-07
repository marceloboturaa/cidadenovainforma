<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Organização editorial</p>
        <h1>Tags</h1>
    </div>
</div>

<section class="panel tag-editor-panel">
    <div>
        <h2><?= $isEdit ? 'Editar tag' : 'Nova tag' ?></h2>
        <p><i class="bi bi-info-circle" aria-hidden="true"></i> Use nomes curtos para organizar matérias por assunto.</p>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/tags/update?id=' . $editing['id']) : url('/admin/tags')) ?>" class="tag-editor-form">
        <?= csrf_field() ?>
        <label class="form-label">
            <span>Nome</span>
            <input class="form-control" name="name" value="<?= e($editing['name'] ?? '') ?>" placeholder="Ex.: Saúde, Educação, Bairro" required>
        </label>
        <div class="split-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar tag' : 'Criar tag' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/tags')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-tags" aria-hidden="true"></i> Tags cadastradas</h2>
        <span><?= e((string) count($tags)) ?> tag(s)</span>
    </div>
    <div class="tag-card-grid">
        <?php foreach ($tags as $tag): ?>
            <article class="tag-card">
                <div class="tag-card-main">
                    <div>
                        <strong><?= e($tag['name']) ?></strong>
                        <span><?= e($tag['slug']) ?></span>
                    </div>
                    <div class="tag-count">
                        <strong><?= e((string) $tag['news_count']) ?></strong>
                        <span>notícia(s)</span>
                    </div>
                </div>
                <div class="tag-card-actions">
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/tags/edit?id=' . $tag['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/tags/delete?id=' . $tag['id'])) ?>">
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
