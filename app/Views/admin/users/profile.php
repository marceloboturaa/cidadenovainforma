<?php $profileUser = $user ?? current_user(); ?>

<div class="page-heading">
    <div>
        <p>Conta</p>
        <h1>Meu cadastro</h1>
    </div>
</div>

<?php if (!empty($profileUser['profile_update_required'])): ?>
    <div class="alert alert-warning">Atualize e confirme seu cadastro para continuar usando o painel.</div>
<?php endif; ?>

<section class="panel person-register-panel">
    <div class="person-register-head">
        <div>
            <span>Dados de acesso</span>
            <h2>Atualizar cadastro</h2>
        </div>
        <strong>Obrigatório quando solicitado</strong>
    </div>

    <form method="post" action="<?= e(url('/admin/profile')) ?>" class="user-stacked-form">
        <?= csrf_field() ?>
        <label>
            <span>Nome</span>
            <input class="form-control" name="name" value="<?= e($profileUser['name'] ?? '') ?>" required autocomplete="name">
        </label>
        <label>
            <span>E-mail</span>
            <input class="form-control" name="email" type="email" value="<?= e($profileUser['email'] ?? '') ?>" required autocomplete="email">
        </label>
        <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar cadastro</button>
    </form>
</section>
