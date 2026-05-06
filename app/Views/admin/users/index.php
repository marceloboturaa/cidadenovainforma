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
            <input class="form-control" name="password" type="password" minlength="8" required>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
