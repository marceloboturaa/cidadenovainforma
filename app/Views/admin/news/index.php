<div class="page-heading">
    <div>
        <p>Fluxo editorial</p>
        <h1>Notícias</h1>
    </div>
    <?php if (\App\Core\Auth::can('news.create')): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/news/create')) ?>">Nova notícia</a>
    <?php endif; ?>
</div>

<section class="panel">
    <form class="row g-3" method="get" action="<?= e(url('/admin/news')) ?>">
        <div class="col-md-5">
            <label class="form-label">Busca</label>
            <input class="form-control" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Título, resumo ou conteúdo">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">Todos</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= selected($filters['status'] ?? '', $key) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Tipo editorial</label>
            <select class="form-select" name="is_archive">
                <option value="">Todos</option>
                <option value="1" <?= selected((string) ($filters['is_archive'] ?? ''), '1') ?>>Acervo</option>
                <option value="0" <?= selected((string) ($filters['is_archive'] ?? ''), '0') ?>>Atual</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100">Filtrar</button>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Matérias cadastradas</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Autor</th>
                    <th>Status</th>
                    <th>Atualização</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($news as $item): ?>
                    <tr>
                        <td>
                            <strong><?= e($item['title']) ?></strong>
                            <?php if ($item['urgent']): ?>
                                <span class="badge text-bg-danger ms-1">Urgente</span>
                            <?php endif; ?>
                            <?php if ($item['featured']): ?>
                                <span class="badge text-bg-warning ms-1">Destaque</span>
                            <?php endif; ?>
                            <?php if ($item['is_archive']): ?>
                                <span class="badge text-bg-secondary ms-1">Acervo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($item['category_name'] ?? 'Sem categoria') ?></td>
                        <td><?= e($item['author_name']) ?></td>
                        <td><span class="status-pill status-<?= e($item['status']) ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span></td>
                        <td><?= e($item['updated_at'] ?? $item['created_at']) ?></td>
                        <td class="text-end">
                            <?php $canEditItem = \App\Core\Auth::can('news.manage') || ((int) $item['author_id'] === (int) current_user()['id'] && !in_array($item['status'], ['published', 'archived'], true)); ?>
                            <?php if ($canEditItem): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/news/edit?id=' . $item['id'])) ?>">Editar</a>
                            <?php endif; ?>

                            <?php if ($canApprove && $item['status'] === 'pending'): ?>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/news/approve?id=' . $item['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-success">Aprovar</button>
                                </form>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/news/reject?id=' . $item['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger">Rejeitar</button>
                                </form>
                            <?php endif; ?>

                            <?php $canArchiveItem = \App\Core\Auth::can('news.manage') || ((int) $item['author_id'] === (int) current_user()['id'] && !in_array($item['status'], ['published', 'archived'], true)); ?>
                            <?php if ($canArchiveItem): ?>
                                <form class="inline-form" method="post" action="<?= e(url('/admin/news/archive?id=' . $item['id'])) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-dark">Arquivar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$news): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Nenhuma matéria encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
