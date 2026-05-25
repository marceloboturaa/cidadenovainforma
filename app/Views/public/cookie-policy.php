<section class="cookie-policy-page">
    <header class="cookie-policy-header">
        <span>LGPD Brasil</span>
        <h1><?= e($settings['policy_title'] ?? 'Política de Cookies') ?></h1>
        <p>Versão <?= e($settings['policy_version'] ?? '1.0') ?>. Você pode alterar ou revogar seu consentimento a qualquer momento.</p>
        <button class="cookie-policy-action" type="button" data-cookie-preferences>Editar preferências</button>
    </header>

    <article class="cookie-policy-content">
        <?= nl2br(e($settings['policy_text'] ?? '')) ?>
    </article>

    <section class="cookie-policy-categories" aria-label="Categorias de cookies">
        <?php foreach ($categories as $category): ?>
            <article>
                <span><?= !empty($category['required']) ? 'Sempre ativo' : 'Opcional' ?></span>
                <h2><?= e($category['name']) ?></h2>
                <p><?= e($category['description'] ?? '') ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</section>
