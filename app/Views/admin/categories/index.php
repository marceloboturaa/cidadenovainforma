<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Organização editorial</p>
        <h1>Categorias</h1>
    </div>
</div>

<section class="panel category-editor-panel">
    <div>
        <h2><?= $isEdit ? 'Editar categoria' : 'Nova categoria' ?></h2>
        <p><i class="bi bi-info-circle" aria-hidden="true"></i> Categorias aparecem no site e ajudam a organizar as matérias.</p>
    </div>
    <form method="post" action="<?= e($isEdit ? url('/admin/categories/update?id=' . $editing['id']) : url('/admin/categories')) ?>" class="category-editor-form">
        <?= csrf_field() ?>
        <label class="form-label">
            <span>Nome</span>
            <input class="form-control" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
        </label>
        <label class="form-label">
            <span>Categoria pai</span>
            <select class="form-select" name="parent_id">
                <option value="">Nenhuma</option>
                <?php foreach ($parents as $parent): ?>
                    <?php if ($isEdit && (int) $parent['id'] === (int) $editing['id']) continue; ?>
                    <option value="<?= e((string) $parent['id']) ?>" <?= selected((string) ($editing['parent_id'] ?? ''), (string) $parent['id']) ?>>
                        <?= e($parent['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="form-label category-description-field">
            <span>Descrição</span>
            <input class="form-control" name="description" value="<?= e($editing['description'] ?? '') ?>">
        </label>
        <div class="category-check-cell">
            <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="active" <?= checked((bool) ($editing['active'] ?? true)) ?>>
                <span class="form-check-label">Ativa</span>
            </label>
        </div>
        <div class="split-actions category-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i><?= $isEdit ? 'Atualizar categoria' : 'Criar categoria' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/categories')) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i>Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2><i class="bi bi-folder2-open" aria-hidden="true"></i> Categorias cadastradas</h2>
        <span><?= e((string) count($categories)) ?> categoria(s)</span>
    </div>
    <div class="category-card-grid">
        <?php foreach ($categories as $category): ?>
            <article class="category-card">
                <div class="category-card-main">
                    <div>
                        <div class="admin-list-title-row">
                            <strong><?= e($category['name']) ?></strong>
                            <span class="state-pill <?= $category['active'] ? 'is-active' : 'is-muted' ?>"><?= $category['active'] ? 'Ativa' : 'Inativa' ?></span>
                        </div>
                        <span><?= e($category['slug']) ?></span>
                    </div>
                    <div class="tag-count">
                        <strong><?= e((string) $category['news_count']) ?></strong>
                        <span>notícia(s)</span>
                    </div>
                </div>
                <dl class="category-card-meta">
                    <div>
                        <dt>Categoria pai</dt>
                        <dd><?= e($category['parent_name'] ?? '-') ?></dd>
                    </div>
                </dl>
                <div class="tag-card-actions">
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e(url('/admin/categories/edit?id=' . $category['id'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</a>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/categories/delete?id=' . $category['id'])) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-trash3" aria-hidden="true"></i>Remover</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
