<section class="auth-card">
    <div class="auth-title">
        <span>Acesso restrito</span>
        <h1>Entrar no painel</h1>
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
            <div class="password-field">
                <input class="form-control" type="password" name="password" required autocomplete="current-password">
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </label>
        <button class="btn btn-primary w-100 auth-submit">Entrar</button>
    </form>

    <div class="auth-actions">
        <a class="auth-link" href="<?= e(url('/forgot-password')) ?>">Esqueci minha senha</a>
        <a class="auth-link secondary-auth-link" href="<?= e(url('/register')) ?>">Criar novo cadastro</a>
    </div>
    <a class="auth-link auth-back-link" href="<?= e(url('/')) ?>">Voltar para página inicial</a>
</section>
