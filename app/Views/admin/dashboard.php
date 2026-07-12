<div class="page-heading">
    <div>
        <p>Painel administrativo</p>
        <h1>Visão geral</h1>
    </div>
</div>

<?php if ($canViewEditorialDashboard ?? false): ?>
<div class="metric-grid">
    <?php if ($canViewSensitiveDashboard ?? false): ?>
    <article class="metric-card">
        <span>Usuários</span>
        <strong><?= e((string) $stats['users']) ?></strong>
    </article>
    <?php endif; ?>
    <article class="metric-card">
        <span>Notícias</span>
        <strong><?= e((string) $stats['news']) ?></strong>
    </article>
    <article class="metric-card">
        <span>Pendentes</span>
        <strong><?= e((string) $stats['pending_news']) ?></strong>
    </article>
    <?php if ($canViewSensitiveDashboard ?? false): ?>
    <article class="metric-card">
        <span>Comentários</span>
        <strong><?= e((string) $stats['comments']) ?></strong>
    </article>
    <?php endif; ?>
    <?php if ($canViewSensitiveDashboard ?? false): ?>
        <article class="metric-card">
            <span>Online agora</span>
            <strong><?= e((string) ($stats['online_users_count'] ?? 0)) ?></strong>
        </article>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!($isStudent ?? false) && ($canManageHomeNotice ?? false)): ?>
<section class="panel">
    <div class="section-heading">
        <h2>Aviso no topo do site</h2>
        <span>Independente dos cursos públicos</span>
    </div>
    <form method="post" action="<?= e(url('/admin/home-notice')) ?>" class="home-notice-form">
        <?= csrf_field() ?>
        <label class="form-check home-notice-toggle">
            <input class="form-check-input" type="checkbox" name="home_notice_enabled" value="1" <?= checked(($homeNotice['enabled'] ?? '0') === '1') ?>>
            <span class="form-check-label">Mostrar aviso na página inicial</span>
        </label>
        <div class="home-notice-grid">
            <label class="form-label">Título
                <input class="form-control" name="home_notice_title" maxlength="120" value="<?= e($homeNotice['title'] ?? '') ?>" placeholder="Ex.: Inscrições abertas">
            </label>
            <label class="form-label">Texto
                <textarea class="form-control" name="home_notice_text" maxlength="260" rows="3" placeholder="Ex.: Garanta sua vaga nas atividades desta semana."><?= e($homeNotice['text'] ?? '') ?></textarea>
            </label>
            <label class="form-label">Link
                <input class="form-control" name="home_notice_url" maxlength="255" value="<?= e($homeNotice['url'] ?? '') ?>" placeholder="/evento/1, /admin/education/course?id=1 ou https://...">
            </label>
            <label class="form-label">Texto do botão
                <input class="form-control" name="home_notice_label" maxlength="60" value="<?= e($homeNotice['label'] ?? '') ?>" placeholder="Saiba mais">
            </label>
        </div>
        <button class="btn btn-primary icon-btn"><i class="bi bi-megaphone" aria-hidden="true"></i>Salvar aviso</button>
    </form>
</section>
<?php endif; ?>

<?php if (!($isStudent ?? false) && ($canManageAnnouncements ?? false)): ?>
<section class="panel">
    <div class="section-heading">
        <h2>Aviso interno para todos</h2>
        <span>Notificação no painel</span>
    </div>
    <form method="post" action="<?= e(url('/admin/announcement')) ?>" class="home-notice-form">
        <?= csrf_field() ?>
        <div class="home-notice-grid">
            <label class="form-label">Título
                <input class="form-control" name="announcement_title" maxlength="160" required placeholder="Ex.: Reunião geral">
            </label>
            <label class="form-label">Mensagem
                <textarea class="form-control" name="announcement_body" maxlength="2000" rows="4" required placeholder="Escreva o aviso que aparecerá para todos no painel."></textarea>
            </label>
            <label class="form-label">Link opcional
                <input class="form-control" name="announcement_url" maxlength="255" placeholder="/admin/education, /admin/library-events ou https://...">
            </label>
            <label class="form-label">Texto do botão
                <input class="form-control" name="announcement_button_label" maxlength="80" placeholder="Abrir">
            </label>
        </div>
        <label class="form-check home-notice-toggle">
            <input class="form-check-input" type="checkbox" name="send_whatsapp" value="1">
            <span class="form-check-label">Enviar também por WhatsApp para usuários com contato autorizado</span>
        </label>
        <button class="btn btn-primary icon-btn"><i class="bi bi-bell" aria-hidden="true"></i>Enviar aviso</button>
    </form>
</section>
<?php endif; ?>

<?php if ($canViewEditorialDashboard ?? false): ?>
<div class="<?= ($canViewSensitiveDashboard ?? false) ? 'dashboard-grid' : '' ?>">
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

    <?php if ($canViewSensitiveDashboard ?? false): ?>
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
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!($isStudent ?? false) && ($canViewEditorialDashboard ?? false)): ?>
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
<?php endif; ?>

<?php if ($isStudent ?? false): ?>
    <?php
    $responseStatusLabels = ['pending' => 'Aguardando correção', 'corrected' => 'Corrigido', 'redo' => 'Refazer'];
    $studentForms = $studentResponses['forms'] ?? [];
    $studentAssignments = $studentResponses['assignments'] ?? [];
    ?>
    <section class="panel student-response-panel">
        <div class="section-heading">
            <h2>Minhas respostas</h2>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/education')) ?>">Meus cursos</a>
        </div>
        <div class="student-response-grid">
            <div>
                <h3>Formulários enviados</h3>
                <div class="admin-card-list compact-list">
                    <?php foreach ($studentForms as $response): ?>
                        <?php $responseHref = !empty($response['lesson_id']) ? '/admin/education/lesson?id=' . $response['lesson_id'] . '#lesson-forms' : '/admin/education/course?id=' . $response['course_id'] . '#course-forms'; ?>
                        <article class="admin-list-card student-response-card">
                            <div class="admin-list-main">
                                <a class="admin-list-title" href="<?= e(url($responseHref)) ?>"><?= e($response['item_title']) ?></a>
                                <p class="admin-list-description"><?= e($response['course_title']) ?><?= !empty($response['lesson_title']) ? ' / ' . e($response['lesson_title']) : '' ?></p>
                                <?php if (!empty($response['grade'])): ?><p class="student-response-feedback"><b>Nota:</b> <?= e($response['grade']) ?></p><?php endif; ?>
                                <?php if (!empty($response['feedback'])): ?><p class="student-response-feedback"><?= e($response['feedback']) ?></p><?php endif; ?>
                            </div>
                            <span class="state-pill <?= ($response['correction_status'] ?? 'pending') === 'corrected' ? 'is-active' : 'is-pending' ?>"><?= e($responseStatusLabels[$response['correction_status'] ?? 'pending'] ?? $responseStatusLabels['pending']) ?></span>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$studentForms): ?><div class="empty-state">Você ainda não enviou formulários.</div><?php endif; ?>
                </div>
            </div>
            <div>
                <h3>Tarefas entregues</h3>
                <div class="admin-card-list compact-list">
                    <?php foreach ($studentAssignments as $submission): ?>
                        <article class="admin-list-card student-response-card">
                            <div class="admin-list-main">
                                <a class="admin-list-title" href="<?= e(url('/admin/education/lesson?id=' . $submission['lesson_id'])) ?>"><?= e($submission['item_title'] ?: 'Tarefa enviada') ?></a>
                                <p class="admin-list-description"><?= e($submission['course_title']) ?> / <?= e($submission['lesson_title']) ?></p>
                                <?php if (!empty($submission['grade'])): ?><p class="student-response-feedback"><b>Nota:</b> <?= e($submission['grade']) ?></p><?php endif; ?>
                                <?php if (!empty($submission['feedback'])): ?><p class="student-response-feedback"><?= e($submission['feedback']) ?></p><?php endif; ?>
                            </div>
                            <span class="state-pill <?= ($submission['correction_status'] ?? 'pending') === 'corrected' ? 'is-active' : 'is-pending' ?>"><?= e($responseStatusLabels[$submission['correction_status'] ?? 'pending'] ?? $responseStatusLabels['pending']) ?></span>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$studentAssignments): ?><div class="empty-state">Você ainda não entregou tarefas.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($canViewSensitiveDashboard ?? false): ?>
    <section class="panel">
        <div class="section-heading">
            <h2>Usuários online</h2>
            <span>Ativos nos últimos <?= e((string) ($stats['online_window_minutes'] ?? 5)) ?> minutos</span>
        </div>
        <div class="admin-card-list compact-list">
            <?php foreach (($stats['online_users'] ?? []) as $onlineUser): ?>
                <article class="admin-list-card online-user-card">
                    <div class="admin-list-main">
                        <strong class="admin-list-title"><?= e($onlineUser['name']) ?></strong>
                        <dl class="admin-list-meta">
                            <div>
                                <dt>E-mail</dt>
                                <dd><?= e($onlineUser['email']) ?></dd>
                            </div>
                            <div>
                                <dt>Cargo</dt>
                                <dd><?= e($onlineUser['role_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Última atividade</dt>
                                <dd><?= e(date('d/m/Y H:i:s', strtotime($onlineUser['last_seen_at']))) ?></dd>
                            </div>
                        </dl>
                    </div>
                    <span class="state-pill is-online">Online</span>
                </article>
            <?php endforeach; ?>
            <?php if (empty($stats['online_users'])): ?>
                <div class="empty-state">Nenhum usuário online agora.</div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!($isStudent ?? false) && ($canViewSensitiveDashboard ?? false)): ?>
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
<?php endif; ?>
