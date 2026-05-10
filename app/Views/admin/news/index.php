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
    <div class="section-heading">
        <h2>Matérias cadastradas</h2>
        <span><?= e((string) count($news)) ?> resultado(s)</span>
    </div>

    <div class="news-admin-list">
        <?php foreach ($news as $item): ?>
            <?php
                $canEditItem = \App\Core\Auth::can('news.manage') || ((int) $item['author_id'] === (int) current_user()['id'] && $item['status'] !== 'archived');
                $canArchiveItem = \App\Core\Auth::can('news.manage') || ((int) $item['author_id'] === (int) current_user()['id'] && !in_array($item['status'], ['published', 'archived'], true));
                $canDeleteItem = (current_user()['role_slug'] ?? '') === 'master';
            ?>
            <article class="news-admin-item">
                <div class="news-admin-body">
                    <div class="news-admin-title">
                        <strong><?= e($item['title']) ?></strong>
                        <span class="status-pill status-<?= e($item['status']) ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span>
                    </div>
                    <div class="news-admin-badges">
                        <?php if ($item['urgent']): ?>
                            <span class="badge text-bg-danger">Urgente</span>
                        <?php endif; ?>
                        <?php if ($item['featured']): ?>
                            <span class="badge text-bg-warning">Destaque</span>
                        <?php endif; ?>
                        <?php if ($item['is_archive']): ?>
                            <span class="archive-admin-badge"><i class="bi bi-archive" aria-hidden="true"></i>Acervo</span>
                        <?php endif; ?>
                    </div>
                    <dl class="news-admin-meta">
                        <div>
                            <dt>Categoria</dt>
                            <dd><?= e($item['category_name'] ?? 'Sem categoria') ?></dd>
                        </div>
                        <div>
                            <dt>Autor</dt>
                            <dd><?= e($item['author_name']) ?></dd>
                        </div>
                        <div>
                            <dt>Atualização</dt>
                            <dd><?= e($item['updated_at'] ?? $item['created_at']) ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="news-admin-actions">
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

                    <?php if ($canArchiveItem): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/news/archive?id=' . $item['id'])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-dark">Arquivar</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($canDeleteItem): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/news/delete?id=' . $item['id'])) ?>" onsubmit="return confirm('Excluir esta notícia permanentemente? Esta ação não pode ser desfeita.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3" aria-hidden="true"></i> Excluir</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$news): ?>
            <div class="empty-state">Nenhuma matéria encontrada.</div>
        <?php endif; ?>
    </div>
</section>
