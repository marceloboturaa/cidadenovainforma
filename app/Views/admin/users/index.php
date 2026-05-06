<div class="page-heading">
    <div>
        <p>Controle de acesso</p>
        <h1>Usuários e cargos</h1>
    </div>
</div>

<section class="panel">
    <h2>Novo usuário</h2>
    <form method="post" action="<?= e(url('/admin/users')) ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">E-mail</label>
            <input class="form-control" name="email" type="email" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Cargo</label>
            <select class="form-select" name="role_id" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e((string) $role['id']) ?>"><?= e($role['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Senha</label>
            <div class="password-field">
                <input class="form-control" name="password" type="password" minlength="8" required>
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">Criar</button>
        </div>
    </form>
</section>

<section class="panel">
    <h2>Usuários cadastrados</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Cargo</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Resetar senha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= e($item['email']) ?></td>
                        <td><?= e($item['role_name']) ?></td>
                        <td><?= $item['active'] ? 'Ativo' : 'Inativo' ?></td>
                        <td><?= e($item['created_at']) ?></td>
                        <td>
                            <?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
                                <form method="post" action="<?= e(url('/admin/users/reset-password?id=' . $item['id'])) ?>" class="password-reset-row">
                                    <?= csrf_field() ?>
                                    <div class="password-field">
                                        <input class="form-control form-control-sm" name="password" type="password" minlength="8" placeholder="Nova senha" required>
                                        <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary">Resetar</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Somente master</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
