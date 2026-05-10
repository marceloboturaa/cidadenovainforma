<div class="page-heading">
    <div>
        <p>Fluxo editorial</p>
        <h1>Notícias</h1>
    </div>
    <?php if (\App\Core\Auth::can('news.create')): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/news/create')) ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Nova notícia
        </a>
    <?php endif; ?>
</div>

<section class="panel news-filter-panel">
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
            <button class="btn btn-outline-secondary w-100">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                Filtrar
            </button>
        </div>
    </form>
</section>

<section class="panel news-management-panel">
    <div class="section-heading">
        <h2><i class="bi bi-newspaper" aria-hidden="true"></i>Matérias cadastradas</h2>
        <span><?= e((string) count($news)) ?> resultado(s)</span>
    </div>

    <?php if ($news): ?>
        <form id="news-bulk-form" class="news-bulk-form" method="post" action="<?= e(url('/admin/news/bulk')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="export_scope" value="selected">
            <div class="bulk-select">
                <label>
                    <input type="checkbox" data-news-select-all>
                    Selecionar todas
                </label>
                <span data-news-selected-count>Nenhuma selecionada</span>
            </div>
            <div class="bulk-actions">
                <?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
                    <button class="btn btn-sm btn-outline-secondary" formaction="<?= e(url('/admin/backups/news/export')) ?>">
                        <i class="bi bi-box-arrow-down" aria-hidden="true"></i>
                        Exportar
                    </button>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('news.manage') || \App\Core\Auth::can('news.create')): ?>
                    <button class="btn btn-sm btn-outline-dark" name="bulk_action" value="archive" onclick="return confirm('Arquivar as notícias selecionadas?');">
                        <i class="bi bi-archive" aria-hidden="true"></i>
                        Arquivar
                    </button>
                <?php endif; ?>
                <?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
                    <button class="btn btn-sm btn-outline-danger" name="bulk_action" value="delete" onclick="return confirm('Excluir permanentemente as notícias selecionadas? Esta ação não pode ser desfeita.');">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                        Excluir
                    </button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>

    <div class="news-admin-list">
        <?php foreach ($news as $item): ?>
            <?php
                $canEditItem = \App\Core\Auth::can('news.manage') || ((int) $item['author_id'] === (int) current_user()['id'] && $item['status'] !== 'archived');
                $canArchiveItem = \App\Core\Auth::can('news.manage') || ((int) $item['author_id'] === (int) current_user()['id'] && !in_array($item['status'], ['published', 'archived'], true));
                $canDeleteItem = (current_user()['role_slug'] ?? '') === 'master';
            ?>
            <article class="news-admin-item">
                <label class="news-select-check" title="Selecionar notícia">
                    <input type="checkbox" name="news_ids[]" value="<?= e((string) $item['id']) ?>" form="news-bulk-form" data-news-select-item>
                </label>
                <div class="news-admin-body">
                    <div class="news-admin-title">
                        <strong>
                            <?php if ($item['is_archive']): ?>
                                <i class="bi bi-archive" aria-hidden="true"></i>
                            <?php else: ?>
                                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                            <?php endif; ?>
                            <?= e($item['title']) ?>
                        </strong>
                        <span class="status-pill status-<?= e($item['status']) ?>"><?= e($statuses[$item['status']] ?? $item['status']) ?></span>
                    </div>
                    <div class="news-admin-badges">
                        <?php if ($item['urgent']): ?>
                            <span class="badge text-bg-danger"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>Urgente</span>
                        <?php endif; ?>
                        <?php if ($item['featured']): ?>
                            <span class="badge text-bg-warning"><i class="bi bi-star" aria-hidden="true"></i>Destaque</span>
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
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/news/edit?id=' . $item['id'])) ?>">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                            Editar
                        </a>
                    <?php endif; ?>

                    <?php if ($canApprove && $item['status'] === 'pending'): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/news/approve?id=' . $item['id'])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-success">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                Aprovar
                            </button>
                        </form>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/news/reject?id=' . $item['id'])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                Rejeitar
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($canArchiveItem): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/news/archive?id=' . $item['id'])) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-archive" aria-hidden="true"></i>
                                Arquivar
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($canDeleteItem): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/news/delete?id=' . $item['id'])) ?>" onsubmit="return confirm('Excluir esta notícia permanentemente? Esta ação não pode ser desfeita.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                Excluir
                            </button>
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

<script>
(() => {
    const form = document.getElementById('news-bulk-form');
    if (!form) {
        return;
    }

    const selectAll = form.querySelector('[data-news-select-all]');
    const counter = form.querySelector('[data-news-selected-count]');
    const items = Array.from(document.querySelectorAll('[data-news-select-item]'));

    const update = () => {
        const selected = items.filter((item) => item.checked).length;
        items.forEach((item) => {
            item.closest('.news-admin-item')?.classList.toggle('is-selected', item.checked);
        });
        if (counter) {
            counter.textContent = selected === 0
                ? 'Nenhuma selecionada'
                : selected + ' selecionada(s)';
        }
        if (selectAll) {
            selectAll.checked = selected > 0 && selected === items.length;
            selectAll.indeterminate = selected > 0 && selected < items.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        items.forEach((item) => {
            item.checked = selectAll.checked;
        });
        update();
    });

    items.forEach((item) => item.addEventListener('change', update));
    form.addEventListener('submit', (event) => {
        if (!items.some((item) => item.checked)) {
            event.preventDefault();
            window.alert('Selecione pelo menos uma notícia.');
        }
    });
})();
</script>
