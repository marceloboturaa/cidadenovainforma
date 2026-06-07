<?php
$profileUser = $user ?? current_user();
$requiredFields = !empty($profileUser['profile_update_required'])
    ? ($requiredFields ?? ['name', 'email'])
    : ['name', 'email'];
$needsName = in_array('name', $requiredFields, true);
$needsEmail = in_array('email', $requiredFields, true);
$needsAddress = in_array('address', $requiredFields, true);
$needsPassword = in_array('password', $requiredFields, true);
?>

<div class="page-heading">
    <div>
        <p>Conta</p>
        <h1>Meu cadastro</h1>
    </div>
</div>

<?php if (!empty($profileUser['profile_update_required'])): ?>
    <div class="alert alert-warning">Atualize os dados solicitados para continuar usando o painel.</div>
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
        <?php if ($needsName): ?>
            <label>
                <span>Nome</span>
                <input class="form-control" name="name" value="<?= e($profileUser['name'] ?? '') ?>" required autocomplete="name">
            </label>
        <?php endif; ?>
        <?php if ($needsEmail): ?>
            <label>
                <span>E-mail</span>
                <input class="form-control" name="email" type="email" value="<?= e($profileUser['email'] ?? '') ?>" required autocomplete="email">
            </label>
        <?php endif; ?>
        <?php if ($needsAddress): ?>
            <div class="person-field-grid">
                <label>
                    <span>CEP</span>
                    <input class="form-control" name="cep" value="<?= e($person['cep'] ?? '') ?>" autocomplete="postal-code">
                </label>
                <label>
                    <span>Endereço</span>
                    <input class="form-control" name="address" value="<?= e($person['address'] ?? '') ?>" required autocomplete="street-address">
                </label>
                <label>
                    <span>Número</span>
                    <input class="form-control" name="address_number" value="<?= e($person['address_number'] ?? '') ?>">
                </label>
                <label>
                    <span>Complemento</span>
                    <input class="form-control" name="address_complement" value="<?= e($person['address_complement'] ?? '') ?>">
                </label>
                <label>
                    <span>Bairro</span>
                    <input class="form-control" name="district" value="<?= e($person['district'] ?? '') ?>">
                </label>
                <label>
                    <span>Cidade</span>
                    <input class="form-control" name="city" value="<?= e($person['city'] ?? '') ?>" required autocomplete="address-level2">
                </label>
                <label>
                    <span>UF</span>
                    <input class="form-control" name="state" value="<?= e($person['state'] ?? '') ?>" maxlength="2" required autocomplete="address-level1">
                </label>
            </div>
        <?php endif; ?>
        <?php if ($needsPassword): ?>
            <label>
                <span>Nova senha</span>
                <input class="form-control" name="password" type="password" minlength="8" required autocomplete="new-password">
            </label>
            <label>
                <span>Confirmar nova senha</span>
                <input class="form-control" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
            </label>
        <?php endif; ?>
        <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar cadastro</button>
    </form>
</section>
