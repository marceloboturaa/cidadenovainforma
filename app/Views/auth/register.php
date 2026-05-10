<section class="auth-card">
    <div class="auth-title">
        <span>Solicitar acesso</span>
        <h1>Criar cadastro</h1>
        <p>Preencha seus dados para que o master avalie a liberação do painel.</p>
    </div>

    <?php if ($message = flash('success')): ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($registrationEnabled ?? true): ?>
        <form method="post" action="<?= e(url('/register')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label>
                Nome
                <input class="form-control" name="name" required autocomplete="name">
            </label>
            <label>
                E-mail
                <input class="form-control" type="email" name="email" required autocomplete="email">
            </label>
            <label>
                Senha
                <div class="password-field">
                    <input class="form-control" type="password" name="password" minlength="8" required autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
                </div>
            </label>
            <label>
                Confirmar senha
                <div class="password-field">
                    <input class="form-control" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                    <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
                </div>
            </label>
            <button class="btn btn-primary w-100 auth-submit">Enviar cadastro</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">Novos cadastros estão bloqueados no momento.</div>
    <?php endif; ?>

    <a class="auth-link auth-back-link" href="<?= e(url('/login')) ?>">Já tenho cadastro</a>
</section>
