<section class="auth-card">
    <div class="auth-title">
        <span>Nova senha</span>
        <h1>Atualizar acesso</h1>
        <p>Crie uma senha nova com pelo menos 8 caracteres.</p>
    </div>

    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/reset-password')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>
            Nova senha
            <div class="password-field">
                <input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password">
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </label>
        <label>
            Confirmar senha
            <div class="password-field">
                <input class="form-control" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </label>
        <button class="btn btn-primary w-100 auth-submit">Alterar senha</button>
    </form>
</section>
