<section class="auth-card">
    <div class="auth-title">
        <span>Nova senha</span>
        <h1>Atualizar acesso</h1>
    </div>

    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/reset-password')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>
            Nova senha
            <input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>
            Confirmar senha
            <input class="form-control" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn btn-primary w-100">Alterar senha</button>
    </form>
</section>
