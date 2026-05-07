<div class="page-heading">
    <div>
        <p>Painel administrativo</p>
        <h1>Visão geral</h1>
    </div>
</div>

<div class="metric-grid">
    <article class="metric-card">
        <span>Usuários</span>
        <strong><?= e((string) $stats['users']) ?></strong>
    </article>
    <article class="metric-card">
        <span>Notícias</span>
        <strong><?= e((string) $stats['news']) ?></strong>
    </article>
    <article class="metric-card">
        <span>Pendentes</span>
        <strong><?= e((string) $stats['pending_news']) ?></strong>
    </article>
    <article class="metric-card">
        <span>Comentários</span>
        <strong><?= e((string) $stats['comments']) ?></strong>
    </article>
</div>

<div class="dashboard-grid">
    <section class="panel">
        <h2>Fluxo de publicação</h2>
        <div class="status-board">
            <?php foreach (\App\Models\News::STATUS_LABELS as $status => $label): ?>
                <div>
                    <span><?= e($label) ?></span>
                    <strong><?= e((string) ($stats['status_counts'][$status] ?? 0)) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <h2>Acessos dos últimos dias</h2>
        <div class="mini-chart">
            <?php foreach ($stats['access_days'] as $day): ?>
                <?php $height = max(10, min(100, (int) $day['total'] * 12)); ?>
                <div>
                    <span style="height: <?= e((string) $height) ?>px"></span>
                    <small><?= e(date('d/m', strtotime($day['day']))) ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (!$stats['access_days']): ?>
                <p class="text-muted m-0">Os acessos públicos serão exibidos quando a home estiver ativa.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<section class="panel">
    <div class="section-heading">
        <h2>Notícias recentes</h2>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/news')) ?>">Ver todas</a>
    </div>
    <div class="admin-card-list">
        <?php foreach ($stats['recent_news'] as $item): ?>
            <article class="admin-list-card">
                <div class="admin-list-main">
                    <a class="admin-list-title" href="<?= e(url('/admin/news/edit?id=' . $item['id'])) ?>"><?= e($item['title']) ?></a>
                    <dl class="admin-list-meta">
                        <div>
                            <dt>Autor</dt>
                            <dd><?= e($item['author_name']) ?></dd>
                        </div>
                        <div>
                            <dt>Atualização</dt>
                            <dd><?= e($item['updated_at']) ?></dd>
                        </div>
                    </dl>
                </div>
                <span class="status-pill status-<?= e($item['status']) ?>"><?= e(\App\Models\News::STATUS_LABELS[$item['status']] ?? $item['status']) ?></span>
            </article>
        <?php endforeach; ?>
        <?php if (!$stats['recent_news']): ?>
            <div class="empty-state">Nenhuma notícia cadastrada ainda.</div>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <h2>Logs recentes</h2>
    <div class="admin-card-list compact-list">
        <?php foreach ($stats['logs'] as $log): ?>
            <article class="admin-list-card">
                <div class="admin-list-main">
                    <strong class="admin-list-title"><?= e($log['action']) ?></strong>
                    <p class="admin-list-description"><?= e($log['description']) ?></p>
                    <dl class="admin-list-meta">
                        <div>
                            <dt>Data</dt>
                            <dd><?= e($log['created_at']) ?></dd>
                        </div>
                        <div>
                            <dt>Usuário</dt>
                            <dd><?= e($log['user_name'] ?? 'Sistema') ?></dd>
                        </div>
                    </dl>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$stats['logs']): ?>
            <div class="empty-state">Nenhum log recente.</div>
        <?php endif; ?>
    </div>
</section>
