<div class="page-heading">
    <div>
        <p>Controle de acesso</p>
        <h1>Equipe e acessos</h1>
    </div>
</div>

<section class="panel user-create-panel">
    <div class="section-heading">
        <h2>Novo membro da equipe</h2>
        <span>Crie o login e defina o cargo inicial</span>
    </div>
    <form method="post" action="<?= e(url('/admin/users')) ?>" class="admin-form-grid users-form-grid">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" required>
        </div>
        <div>
            <label class="form-label">E-mail</label>
            <input class="form-control" name="email" type="email" required>
        </div>
        <div>
            <label class="form-label">Cargo</label>
            <select class="form-select" name="role_id" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e((string) $role['id']) ?>"><?= e($role['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Senha</label>
            <div class="password-field">
                <input class="form-control" name="password" type="password" minlength="8" required>
                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
            </div>
        </div>
        <div class="form-action-cell">
            <button class="btn btn-primary w-100">Criar</button>
        </div>
    </form>
</section>

<?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
    <section class="panel">
        <div class="registration-control">
            <div>
                <h2>Novos cadastros</h2>
                <p>Status atual: <strong><?= ($registrationEnabled ?? true) ? 'autorizados' : 'bloqueados' ?></strong></p>
            </div>
            <form method="post" action="<?= e(url('/admin/users/registrations')) ?>">
                <?= csrf_field() ?>
                <?php if ($registrationEnabled ?? true): ?>
                    <input type="hidden" name="enabled" value="0">
                    <button class="btn btn-outline-danger">Bloquear novos cadastros</button>
                <?php else: ?>
                    <input type="hidden" name="enabled" value="1">
                    <button class="btn btn-success">Autorizar novos cadastros</button>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="section-heading">
            <h2>Cadastros aguardando aprovação</h2>
            <span><?= e((string) count($pendingUsers ?? [])) ?> pendente(s)</span>
        </div>
        <div class="admin-card-list">
            <?php foreach (($pendingUsers ?? []) as $item): ?>
                <article class="admin-list-card user-card">
                    <div class="admin-list-main">
                        <div class="admin-list-title-row">
                            <strong class="admin-list-title"><?= e($item['name']) ?></strong>
                            <span class="state-pill is-pending">Aguardando</span>
                        </div>
                        <dl class="admin-list-meta">
                            <div>
                                <dt>E-mail</dt>
                                <dd><?= e($item['email']) ?></dd>
                            </div>
                            <div>
                                <dt>Cargo inicial</dt>
                                <dd><?= e($item['role_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Solicitado em</dt>
                                <dd><?= e($item['created_at']) ?></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="admin-list-actions">
                        <form method="post" action="<?= e(url('/admin/users/approve?id=' . $item['id'])) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-success">Aprovar cadastro</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (empty($pendingUsers)): ?>
                <div class="empty-state">Nenhum cadastro aguardando aprovação.</div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="section-heading">
        <h2>Equipe cadastrada</h2>
        <span><?= e((string) count($users)) ?> membro(s)</span>
    </div>
    <div class="user-directory">
        <?php foreach ($users as $item): ?>
            <article class="user-directory-card">
                <div class="user-profile-block">
                    <div class="admin-list-title-row">
                        <strong class="admin-list-title"><?= e($item['name']) ?></strong>
                        <span class="state-pill <?= $item['active'] ? 'is-active' : 'is-muted' ?>"><?= $item['active'] ? 'Ativo' : 'Inativo' ?></span>
                        <?php if (in_array((int) $item['id'], $onlineUserIds ?? [], true)): ?>
                            <span class="state-pill is-online">Online</span>
                        <?php endif; ?>
                    </div>
                    <p><?= e($item['email']) ?></p>
                    <div class="user-role-line">
                        <span><?= e($item['role_name']) ?></span>
                        <?php if (($item['role_slug'] ?? '') === 'equipe'): ?>
                            <strong>Acesso a documentos liberados</strong>
                        <?php endif; ?>
                    </div>
                    <small>Criado em <?= e($item['created_at']) ?></small>
                </div>
                <div class="user-management-block">
                    <?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
                        <?php if ($institutionPages ?? []): ?>
                            <details>
                                <summary>Páginas institucionais</summary>
                                <form method="post" action="<?= e(url('/admin/users/responsibilities?id=' . $item['id'])) ?>" class="responsibility-form">
                                    <?= csrf_field() ?>
                                    <div class="responsibility-options">
                                        <?php foreach (($institutionPages ?? []) as $page): ?>
                                            <label>
                                                <input type="checkbox" name="pages[]" value="<?= e($page['slug']) ?>" <?= checked(in_array($page['slug'], $userResponsibilities[(int) $item['id']] ?? [], true)) ?>>
                                                <span><?= e($page['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary">Salvar páginas</button>
                                </form>
                            </details>
                        <?php endif; ?>
                        <details>
                            <summary>Redefinir senha</summary>
                            <form method="post" action="<?= e(url('/admin/users/reset-password?id=' . $item['id'])) ?>" class="password-reset-row">
                                <?= csrf_field() ?>
                                <div class="password-field">
                                    <input class="form-control form-control-sm" name="password" type="password" minlength="8" placeholder="Nova senha" required>
                                    <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
                                </div>
                                <div class="password-field">
                                    <input class="form-control form-control-sm" name="password_confirmation" type="password" minlength="8" placeholder="Confirmar senha" required>
                                    <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha">&#128065;</button>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Resetar senha</button>
                            </form>
                        </details>
                    <?php else: ?>
                        <span class="text-muted">Somente master</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
