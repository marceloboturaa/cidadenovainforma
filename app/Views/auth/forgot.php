<section class="auth-card">
    <div class="auth-title">
        <span>Recuperação</span>
        <h1>Redefinir senha</h1>
        <p>Informe o e-mail cadastrado para receber o link de alteração de senha.</p>
    </div>

    <?php if ($message = flash('success')): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($link = flash('reset_link')): ?>
        <div class="alert alert-info small">
            Link local de teste: <a href="<?= e($link) ?>"><?= e($link) ?></a>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/forgot-password')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <label>
            E-mail cadastrado
            <input class="form-control" type="email" name="email" required autocomplete="email">
        </label>
        <button class="btn btn-primary w-100 auth-submit">Enviar link</button>
    </form>

    <a class="auth-link auth-back-link" href="<?= e(url('/login')) ?>">Voltar ao login</a>
</section>
