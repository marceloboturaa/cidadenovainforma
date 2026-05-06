<?php $isEdit = (bool) $editing; ?>

<div class="page-heading">
    <div>
        <p>Organização editorial</p>
        <h1>Tags</h1>
    </div>
</div>

<section class="panel">
    <h2><?= $isEdit ? 'Editar tag' : 'Nova tag' ?></h2>
    <form method="post" action="<?= e($isEdit ? url('/admin/tags/update?id=' . $editing['id']) : url('/admin/tags')) ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2">
            <button class="btn btn-primary"><?= $isEdit ? 'Atualizar' : 'Criar' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('/admin/tags')) ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Tags cadastradas</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Notícias</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tags as $tag): ?>
                    <tr>
                        <td><?= e($tag['name']) ?></td>
                        <td><?= e($tag['slug']) ?></td>
                        <td><?= e((string) $tag['news_count']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/tags/edit?id=' . $tag['id'])) ?>">Editar</a>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/tags/delete?id=' . $tag['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tags): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Nenhuma tag cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
