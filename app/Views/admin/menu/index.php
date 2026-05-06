<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Navegação pública</p>
        <h1>Menu do site</h1>
    </div>
</div>

<section class="panel">
    <h2><?= $isEdit ? 'Editar item' : 'Novo item' ?></h2>
    <form method="post" action="<?= e($isEdit ? url('/admin/menu/update?id=' . $editing['id']) : url('/admin/menu')) ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <label class="form-label">Nome no menu</label>
            <input class="form-control" name="label" value="<?= e($editing['label'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-3">
            <label class="form-label">Link</label>
            <input class="form-control" name="url" value="<?= e($editing['url'] ?? '') ?>" placeholder="/categoria/bairro" required>
        </div>
        <div class="col-md-1">
            <label class="form-label">Ordem</label>
            <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editing['sort_order'] ?? 10)) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="visible" <?= checked((bool) ($editing['visible'] ?? true)) ?>>
                <span class="form-check-label">Visível</span>
            </label>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary"><?= $isEdit ? 'Atualizar' : 'Criar' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('/admin/menu')) ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Itens cadastrados</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Nome</th>
                    <th>Link</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e((string) $item['sort_order']) ?></td>
                        <td><?= e($item['label']) ?></td>
                        <td><?= e($item['url']) ?></td>
                        <td><?= e($item['category_name'] ?? '-') ?></td>
                        <td><?= $item['visible'] ? 'Visível' : 'Oculto' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/menu/edit?id=' . $item['id'])) ?>">Editar</a>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/menu/delete?id=' . $item['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Nenhum item de menu cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
