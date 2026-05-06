<section class="auth-card">
    <div class="auth-title">
        <span>Portal Jornalístico</span>
        <h1>Cidade Nova Informa</h1>
    </div>

    <?php if ($message = flash('success')): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" class="stack-form">
        <?= csrf_field() ?>
        <label>
            E-mail
            <input class="form-control" type="email" name="email" required autocomplete="email">
        </label>
        <label>
            Senha
            <input class="form-control" type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn-primary w-100">Entrar</button>
    </form>

    <a class="auth-link" href="<?= e(url('/forgot-password')) ?>">Esqueci minha senha</a>
</section>
