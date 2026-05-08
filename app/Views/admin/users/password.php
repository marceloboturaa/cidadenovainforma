<div class="page-heading">
    <div>
        <p>Conta</p>
        <h1>Minha senha</h1>
    </div>
</div>

<section class="panel">
    <h2>Alterar senha</h2>
    <form method="post" action="<?= e(url('/admin/password')) ?>" class="admin-form-grid users-form-grid">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Senha atual</label>
            <div class="password-field">
                <input class="form-control" name="current_password" type="password" required autocomplete="current-password">
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </div>
        <div>
            <label class="form-label">Nova senha</label>
            <div class="password-field">
                <input class="form-control" name="password" type="password" minlength="8" required autocomplete="new-password">
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </div>
        <div>
            <label class="form-label">Confirmar nova senha</label>
            <div class="password-field">
                <input class="form-control" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </div>
        <div class="form-action-cell">
            <button class="btn btn-primary w-100">Alterar senha</button>
        </div>
    </form>
</section>
