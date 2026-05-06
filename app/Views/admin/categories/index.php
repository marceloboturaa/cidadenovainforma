<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Organização editorial</p>
        <h1>Categorias</h1>
    </div>
</div>

<section class="panel">
    <h2><?= $isEdit ? 'Editar categoria' : 'Nova categoria' ?></h2>
    <form method="post" action="<?= e($isEdit ? url('/admin/categories/update?id=' . $editing['id']) : url('/admin/categories')) ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoria pai</label>
            <select class="form-select" name="parent_id">
                <option value="">Nenhuma</option>
                <?php foreach ($parents as $parent): ?>
                    <?php if ($isEdit && (int) $parent['id'] === (int) $editing['id']) continue; ?>
                    <option value="<?= e((string) $parent['id']) ?>" <?= selected((string) ($editing['parent_id'] ?? ''), (string) $parent['id']) ?>>
                        <?= e($parent['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Descrição</label>
            <input class="form-control" name="description" value="<?= e($editing['description'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="active" <?= checked((bool) ($editing['active'] ?? true)) ?>>
                <span class="form-check-label">Ativa</span>
            </label>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary"><?= $isEdit ? 'Atualizar' : 'Criar' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('/admin/categories')) ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Categorias cadastradas</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Pai</th>
                    <th>Notícias</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e($category['name']) ?></td>
                        <td><?= e($category['slug']) ?></td>
                        <td><?= e($category['parent_name'] ?? '-') ?></td>
                        <td><?= e((string) $category['news_count']) ?></td>
                        <td><?= $category['active'] ? 'Ativa' : 'Inativa' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/categories/edit?id=' . $category['id'])) ?>">Editar</a>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/categories/delete?id=' . $category['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
