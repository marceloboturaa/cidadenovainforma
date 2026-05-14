<div class="forum-heading">
    <div>
        <p>Comunidade interna</p>
        <h1>Fóruns</h1>
        <span>Escolha a área de conversa liberada para o seu cargo.</span>
    </div>
    <?php if (!empty($unreadCount)): ?>
        <span class="state-pill is-active"><?= e((string) $unreadCount) ?> nova(s)</span>
    <?php endif; ?>
</div>

<section class="forum-area-grid">
    <?php foreach ($areas as $area): ?>
        <a class="forum-area-card" href="<?= e(url('/admin/forum/area?area=' . $area['slug'])) ?>">
            <span class="forum-area-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
            <span class="forum-area-content">
                <small><?= !empty($area['is_public']) ? 'Público autorizado' : 'Área privada' ?></small>
                <strong><?= e($area['name']) ?></strong>
                <em><?= e(text_excerpt($area['description'] ?? '', 130)) ?></em>
            </span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    <?php endforeach; ?>

    <?php if (!$areas): ?>
        <div class="forum-empty">
            <i class="bi bi-shield-lock" aria-hidden="true"></i>
            <strong>Nenhum fórum liberado</strong>
            <span>O acesso aos fóruns depende do cargo e das autorizações do sistema.</span>
        </div>
    <?php endif; ?>
</section>
