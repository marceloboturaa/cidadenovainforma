<div class="page-heading">
    <div>
        <p>Comunidade interna</p>
        <h1>Fóruns</h1>
    </div>
    <?php if (!empty($unreadCount)): ?>
        <span class="state-pill is-active"><?= e((string) $unreadCount) ?> nova(s)</span>
    <?php endif; ?>
</div>

<section class="education-grid">
    <?php foreach ($areas as $area): ?>
        <article class="education-course-card">
            <div class="education-course-body">
                <div>
                    <span class="education-kicker"><?= !empty($area['is_public']) ? 'Público autorizado' : 'Área privada' ?></span>
                    <h2><?= e($area['name']) ?></h2>
                    <p><?= e(text_excerpt($area['description'] ?? '', 150)) ?></p>
                </div>
                <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/forum/area?area=' . $area['slug'])) ?>">
                    <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                    Acessar
                </a>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$areas): ?>
        <div class="empty-state">Nenhum fórum liberado para seu cargo.</div>
    <?php endif; ?>
</section>
