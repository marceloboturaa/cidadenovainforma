<?php
$profileUser = $user ?? current_user();
$requiredFields = !empty($profileUser['profile_update_required'])
    ? ($requiredFields ?? ['name', 'email'])
    : ['name', 'email'];
$needsName = in_array('name', $requiredFields, true);
$needsEmail = in_array('email', $requiredFields, true);
$needsAddress = in_array('address', $requiredFields, true);
$needsPassword = in_array('password', $requiredFields, true);
$birthDate = '';
if (!empty($person['birth_date'])) {
    $birthDate = date('d/m/Y', strtotime((string) $person['birth_date']));
}
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

    <form method="post" action="<?= e(url('/admin/profile')) ?>" class="profile-complete-form" data-person-form>
        <?= csrf_field() ?>
        <div class="profile-form-section">
            <div class="profile-section-heading">
                <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                <div>
                    <strong>Dados pessoais</strong>
                    <span>Identificação e contatos principais</span>
                </div>
            </div>
        <div class="profile-field-grid">
            <label>
                <span>Nome</span>
                <input class="form-control" name="name" value="<?= e($profileUser['name'] ?? '') ?>" required autocomplete="name">
            </label>
            <label>
                <span>E-mail</span>
                <input class="form-control" name="email" type="email" value="<?= e($profileUser['email'] ?? '') ?>" required autocomplete="email">
            </label>
            <label>
                <span>CPF</span>
                <input class="form-control" name="cpf" value="<?= e($person['cpf'] ?? '') ?>" data-cpf-input inputmode="numeric">
            </label>
            <label>
                <span>Nascimento</span>
                <input class="form-control" name="birth_date" value="<?= e($birthDate) ?>" data-birth-date-input placeholder="dd/mm/aaaa">
            </label>
            <label>
                <span>Telefone</span>
                <input class="form-control" name="phone" value="<?= e($person['phone'] ?? '') ?>" data-phone-input autocomplete="tel">
            </label>
            <label>
                <span>WhatsApp</span>
                <input class="form-control" name="whatsapp" value="<?= e($person['whatsapp'] ?? '') ?>" data-phone-input autocomplete="tel">
            </label>
        </div>
        </div>

        <div class="profile-form-section">
            <div class="profile-section-heading">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <div>
                    <strong>Endereço</strong>
                    <span>Dados de residência e localização</span>
                </div>
            </div>
        <div class="profile-field-grid profile-address-grid">
            <label>
                <span>CEP</span>
                <input class="form-control" name="cep" value="<?= e($person['cep'] ?? '') ?>" data-cep-input autocomplete="postal-code">
            </label>
            <label>
                <span>Endereço</span>
                <input class="form-control" name="address" value="<?= e($person['address'] ?? '') ?>" <?= $needsAddress ? 'required' : '' ?> data-address-input autocomplete="street-address">
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
                <input class="form-control" name="district" value="<?= e($person['district'] ?? '') ?>" data-district-input>
            </label>
            <label>
                <span>Cidade</span>
                <input class="form-control" name="city" value="<?= e($person['city'] ?? '') ?>" <?= $needsAddress ? 'required' : '' ?> data-city-input autocomplete="address-level2">
            </label>
            <label>
                <span>UF</span>
                <input class="form-control" name="state" value="<?= e($person['state'] ?? '') ?>" maxlength="2" <?= $needsAddress ? 'required' : '' ?> data-state-input autocomplete="address-level1">
            </label>
            <button class="btn btn-outline-secondary icon-btn" type="button" data-cep-search><i class="bi bi-search" aria-hidden="true"></i>Buscar CEP</button>
        </div>
        </div>

        <label class="profile-check-line">
            <input class="form-check-input" type="checkbox" name="is_minor" value="1" <?= checked(!empty($person['is_minor'])) ?> data-minor-toggle>
            <span class="form-check-label">Sou menor de idade ou preciso informar responsável</span>
        </label>

        <div class="profile-form-section profile-guardian-section" data-guardian-fields>
            <div class="profile-section-heading">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <div>
                    <strong>Responsável</strong>
                    <span>Preencha quando houver menor de idade ou representante legal</span>
                </div>
            </div>
        <div class="profile-field-grid">
            <label>
                <span>Responsável</span>
                <input class="form-control" name="guardian_name" value="<?= e($person['guardian_name'] ?? '') ?>">
            </label>
            <label>
                <span>Parentesco</span>
                <input class="form-control" name="guardian_relation" value="<?= e($person['guardian_relation'] ?? '') ?>">
            </label>
            <label>
                <span>CPF do responsável</span>
                <input class="form-control" name="guardian_cpf" value="<?= e($person['guardian_cpf'] ?? '') ?>" data-cpf-input>
            </label>
            <label>
                <span>Telefone do responsável</span>
                <input class="form-control" name="guardian_phone" value="<?= e($person['guardian_phone'] ?? '') ?>" data-phone-input>
            </label>
            <label>
                <span>E-mail do responsável</span>
                <input class="form-control" name="guardian_email" type="email" value="<?= e($person['guardian_email'] ?? '') ?>">
            </label>
        </div>
        </div>

        <div class="profile-form-section">
            <div class="profile-section-heading">
                <i class="bi bi-check2-square" aria-hidden="true"></i>
                <div>
                    <strong>Autorizações</strong>
                    <span>Preferências de contato e observações</span>
                </div>
            </div>
        <div class="profile-consent-grid">
            <label class="profile-check-card">
                <input class="form-check-input" type="checkbox" name="contact_authorized" value="1" <?= checked(!empty($person['contact_authorized'])) ?>>
                <span class="form-check-label">Autorizo contato por e-mail, telefone ou WhatsApp</span>
            </label>
            <label class="profile-check-card">
                <input class="form-check-input" type="checkbox" name="image_authorized" value="1" <?= checked(!empty($person['image_authorized'])) ?>>
                <span class="form-check-label">Autorizo uso de imagem em registros institucionais</span>
            </label>
            <label class="profile-notes-field">
                <span>Observações</span>
                <textarea class="form-control" name="notes" rows="3"><?= e($person['notes'] ?? '') ?></textarea>
            </label>
        </div>
        </div>

        <?php if ($needsPassword): ?>
            <div class="profile-form-section">
                <div class="profile-section-heading">
                    <i class="bi bi-key" aria-hidden="true"></i>
                    <div>
                        <strong>Nova senha</strong>
                        <span>Obrigatório para concluir esta atualização</span>
                    </div>
                </div>
            <div class="profile-field-grid profile-password-grid">
            <label>
                <span>Nova senha</span>
                <input class="form-control" name="password" type="password" minlength="8" required autocomplete="new-password">
            </label>
            <label>
                <span>Confirmar nova senha</span>
                <input class="form-control" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
            </label>
            </div>
            </div>
        <?php endif; ?>
        <div class="profile-actions">
            <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar cadastro</button>
        </div>
    </form>
</section>
