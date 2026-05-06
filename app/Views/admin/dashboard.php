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
    <h2>Notícias recentes</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Status</th>
                    <th>Atualização</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['recent_news'] as $item): ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/news/edit?id=' . $item['id'])) ?>"><?= e($item['title']) ?></a></td>
                        <td><?= e($item['author_name']) ?></td>
                        <td><span class="status-pill status-<?= e($item['status']) ?>"><?= e(\App\Models\News::STATUS_LABELS[$item['status']] ?? $item['status']) ?></span></td>
                        <td><?= e($item['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$stats['recent_news']): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Nenhuma notícia cadastrada ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2>Logs recentes</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['logs'] as $log): ?>
                    <tr>
                        <td><?= e($log['created_at']) ?></td>
                        <td><?= e($log['user_name'] ?? 'Sistema') ?></td>
                        <td><?= e($log['action']) ?></td>
                        <td><?= e($log['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
