<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Navegação pública</p>
        <h1>Menu do site</h1>
    </div>
</div>

<section class="panel">
    <h2><?= $isEdit ? 'Editar item' : 'Novo item' ?></h2>
    <form method="post" action="<?= e($isEdit ? url('/admin/menu/update?id=' . $editing['id']) : url('/admin/menu')) ?>" class="admin-form-grid menu-form-grid">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Nome no menu</label>
            <input class="form-control" name="label" value="<?= e($editing['label'] ?? '') ?>" required>
        </div>
        <div>
            <label class="form-label">Categoria vinculada</label>
            <select class="form-select" name="category_id">
                <option value="">Nenhuma</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= selected((string) ($editing['category_id'] ?? ''), (string) $category['id']) ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Link</label>
            <input class="form-control" name="url" value="<?= e($editing['url'] ?? '') ?>" placeholder="/categoria/bairro" required>
        </div>
        <div>
            <label class="form-label">Ordem</label>
            <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editing['sort_order'] ?? 10)) ?>">
        </div>
        <div class="form-check-cell">
            <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="visible" <?= checked((bool) ($editing['visible'] ?? true)) ?>>
                <span class="form-check-label">Visível</span>
            </label>
        </div>
        <div class="form-action-cell split-actions">
            <button class="btn btn-primary"><?= $isEdit ? 'Atualizar' : 'Criar' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('/admin/menu')) ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h2>Itens cadastrados</h2>
        <span><?= e((string) count($items)) ?> item(ns)</span>
    </div>
    <div class="admin-card-list compact-list">
        <?php foreach ($items as $item): ?>
            <article class="admin-list-card menu-card">
                <div class="order-badge"><?= e((string) $item['sort_order']) ?></div>
                <div class="admin-list-main">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($item['label']) ?></strong>
                        <span class="state-pill <?= $item['visible'] ? 'is-active' : 'is-muted' ?>"><?= $item['visible'] ? 'Visível' : 'Oculto' ?></span>
                    </div>
                    <dl class="admin-list-meta">
                        <div>
                            <dt>Link</dt>
                            <dd><?= e($item['url']) ?></dd>
                        </div>
                        <div>
                            <dt>Categoria</dt>
                            <dd><?= e($item['category_name'] ?? '-') ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="admin-list-actions">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/menu/edit?id=' . $item['id'])) ?>">Editar</a>
                    <form class="inline-form" method="post" action="<?= e(url('/admin/menu/delete?id=' . $item['id'])) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger">Remover</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <div class="empty-state">Nenhum item de menu cadastrado.</div>
        <?php endif; ?>
    </div>
</section>
