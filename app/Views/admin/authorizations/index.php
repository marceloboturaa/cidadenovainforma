<div class="page-heading">
    <div>
        <p>Controle de acesso</p>
        <h1>Painel de autorizações</h1>
    </div>
</div>

<div class="dashboard-grid">
    <section class="panel">
        <h2>Cargos</h2>
        <div class="admin-card-list compact-list">
            <?php foreach ($roles as $role): ?>
                <a class="admin-list-card <?= (int) ($selectedRole['id'] ?? 0) === (int) $role['id'] ? 'is-active' : '' ?>" href="<?= e(url('/admin/authorizations?role_id=' . $role['id'])) ?>">
                    <div class="admin-list-main">
                        <strong class="admin-list-title"><?= e($role['name']) ?></strong>
                        <p class="admin-list-description"><?= e($role['slug']) ?></p>
                    </div>
                    <span class="state-pill <?= ($role['slug'] ?? '') === 'master' ? 'is-active' : 'is-muted' ?>">Nível <?= e((string) $role['level']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="section-heading">
            <h2><?= e($selectedRole['name'] ?? 'Cargo') ?></h2>
            <span><?= e($selectedRole['slug'] ?? '') ?></span>
        </div>

        <?php if (($selectedRole['slug'] ?? '') === 'master'): ?>
            <div class="empty-state">O cargo MASTER tem acesso total e não pode ser alterado por este painel.</div>
        <?php elseif ($selectedRole): ?>
            <form method="post" action="<?= e(url('/admin/authorizations')) ?>" class="user-stacked-form">
                <?= csrf_field() ?>
                <input type="hidden" name="role_id" value="<?= e((string) $selectedRole['id']) ?>">

                <div class="responsibility-options">
                    <?php foreach ($permissions as $permission): ?>
                        <label>
                            <input type="checkbox" name="permission_ids[]" value="<?= e((string) $permission['id']) ?>" <?= checked(in_array((int) $permission['id'], $selectedPermissionIds, true)) ?>>
                            <span>
                                <?= e($permission['name']) ?>
                                <small><?= e($permission['slug']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button class="btn btn-primary">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    Salvar autorizações
                </button>
            </form>
        <?php endif; ?>
    </section>
</div>
