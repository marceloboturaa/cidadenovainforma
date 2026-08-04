<?php
$currentUser = current_user();
$isMaster = ($currentUser['role_slug'] ?? '') === 'master';
$canRequestProfileUpdate = in_array($currentUser['role_slug'] ?? '', ['master', 'admin'], true);
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn (array $user): bool => (bool) ($user['active'] ?? false)));
$pendingCount = count($pendingUsers ?? []);
$onlineCount = count($onlineUserIds ?? []);
?>

<div class="users-shell">
    <div class="users-hero">
        <div>
            <span class="eyebrow">Controle de acesso</span>
            <h1>Usuários e acessos</h1>
            <p>Gerencie logins, cargos, páginas institucionais e recuperação de senha.</p>
        </div>
        <?php if ($isMaster): ?>
            <a class="btn btn-primary" href="#novo-usuario">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Novo usuário
            </a>
        <?php endif; ?>
    </div>

    <section class="users-metrics" aria-label="Resumo de usuários">
        <article>
            <span>Usuários</span>
            <strong><?= e((string) $totalUsers) ?></strong>
            <small><?= e((string) $activeUsers) ?> ativo(s)</small>
        </article>
        <article>
            <span>Online agora</span>
            <strong><?= e((string) $onlineCount) ?></strong>
            <small>Visível para master</small>
        </article>
        <article>
            <span>Cadastros</span>
            <strong><?= e((string) $pendingCount) ?></strong>
            <small>aguardando aprovação</small>
        </article>
        <article>
            <span>Novos cadastros</span>
            <strong><?= ($registrationEnabled ?? true) ? 'On' : 'Off' ?></strong>
            <small><?= ($registrationEnabled ?? true) ? 'autorizados' : 'bloqueados' ?></small>
        </article>
    </section>

    <?php if ($isMaster): ?>
        <section class="users-command-grid">
            <article class="users-panel users-create-panel" id="novo-usuario">
                <div class="users-panel-heading">
                    <div>
                        <span class="eyebrow">Adicionar</span>
                        <h2>Novo usuário</h2>
                    </div>
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                </div>

                <form method="post" action="<?= e(url('/admin/users')) ?>" class="users-create-form">
                    <?= csrf_field() ?>
                    <div>
                        <label class="form-label">Nome</label>
                        <input class="form-control" name="name" autocomplete="name" required>
                    </div>
                    <div>
                        <label class="form-label">E-mail</label>
                        <input class="form-control" name="email" type="email" autocomplete="email" required>
                    </div>
                    <div>
                        <label class="form-label">Cargo principal</label>
                        <select class="form-select" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= e((string) $role['id']) ?>"><?= e($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-hint">Para aluno de curso, use ESTUDANTE.</small>
                    </div>
                    <div>
                        <label class="form-label">Senha inicial</label>
                        <div class="password-field">
                            <input class="form-control" name="password" type="password" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <details class="users-check-options">
                        <summary>Cargos acumulativos</summary>
                        <div>
                            <?php foreach ($roles as $role): ?>
                                <label>
                                    <input type="checkbox" name="role_ids[]" value="<?= e((string) $role['id']) ?>">
                                    <span><?= e($role['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        Criar usuário
                    </button>
                </form>
            </article>

            <article class="users-panel">
                <div class="users-panel-heading">
                    <div>
                        <span class="eyebrow">Entrada</span>
                        <h2>Cadastros públicos</h2>
                    </div>
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                </div>
                <div class="registration-status">
                    <span class="state-pill <?= ($registrationEnabled ?? true) ? 'is-active' : 'is-muted' ?>">
                        <?= ($registrationEnabled ?? true) ? 'Autorizados' : 'Bloqueados' ?>
                    </span>
                    <p>Controle se visitantes podem solicitar acesso pelo formulário de cadastro.</p>
                </div>
                <form method="post" action="<?= e(url('/admin/users/registrations')) ?>">
                    <?= csrf_field() ?>
                    <?php if ($registrationEnabled ?? true): ?>
                        <input type="hidden" name="enabled" value="0">
                        <button class="btn btn-outline-danger w-100">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            Bloquear novos cadastros
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="enabled" value="1">
                        <button class="btn btn-success w-100">
                            <i class="bi bi-unlock" aria-hidden="true"></i>
                            Autorizar novos cadastros
                        </button>
                    <?php endif; ?>
                </form>
            </article>
        </section>

        <section class="users-panel">
            <div class="users-panel-heading">
                <div>
                    <span class="eyebrow">Fila</span>
                    <h2>Aguardando aprovação</h2>
                </div>
                <span class="users-count-badge"><?= e((string) $pendingCount) ?></span>
            </div>
            <?php if (!empty($pendingUsers)): ?>
                <form id="pending-users-bulk-form" class="participant-status-bulk-form" method="post" action="<?= e(url('/admin/users/bulk-approval')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-success" name="bulk_action" value="approve_selected" type="submit"><i class="bi bi-check2-square" aria-hidden="true"></i>Aprovar marcados</button>
                    <button class="btn btn-sm btn-outline-danger" name="bulk_action" value="deny_selected" type="submit" onclick="return confirm('Negar os logins marcados? As inscricoes de evento ligadas a estes e-mails ficarao como inscritas.');"><i class="bi bi-x-square" aria-hidden="true"></i>Negar marcados</button>
                    <button class="btn btn-sm btn-outline-success" name="bulk_action" value="approve_all" type="submit" onclick="return confirm('Aprovar todos os cadastros pendentes?');"><i class="bi bi-check2-all" aria-hidden="true"></i>Aprovar tudo</button>
                    <button class="btn btn-sm btn-outline-danger" name="bulk_action" value="deny_all" type="submit" onclick="return confirm('Negar todos os logins pendentes? As inscricoes de evento ligadas a estes e-mails ficarao como inscritas.');"><i class="bi bi-x-lg" aria-hidden="true"></i>Negar tudo</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-pending-users-select-all><i class="bi bi-ui-checks" aria-hidden="true"></i>Selecionar todos</button>
                </form>
                <div class="users-directory-toolbar pending-users-toolbar">
                    <label class="users-search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Pesquisar cadastros pendentes</span>
                        <input class="form-control" type="search" placeholder="Buscar por nome, e-mail ou cargo" data-pending-users-search autocomplete="off">
                    </label>
                    <div class="users-filter-tabs" role="group" aria-label="Filtrar cadastros pendentes">
                        <button type="button" class="is-active" data-pending-users-filter="all">Todos</button>
                        <button type="button" data-pending-users-filter="estudante">Estudante</button>
                        <button type="button" data-pending-users-filter="jornalista">Jornalista</button>
                        <button type="button" data-pending-users-filter="outros">Outros</button>
                    </div>
                    <div class="users-filter-summary">
                        <strong data-pending-users-visible-label><?= e((string) $pendingCount) ?></strong>
                        <span>pendente(s)</span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="pending-user-list" data-pending-users-list>
                <?php foreach (($pendingUsers ?? []) as $item): ?>
                    <?php
                    $pendingRole = strtolower((string) ($item['role_name'] ?? ''));
                    $pendingFilter = str_contains($pendingRole, 'estudante')
                        ? 'estudante'
                        : (str_contains($pendingRole, 'jornalista') ? 'jornalista' : 'outros');
                    $originLabel = ($item['registration_origin'] ?? '') === 'event'
                        ? 'Evento: ' . ($item['registration_event_title'] ?? 'evento') . (!empty($item['registration_course_title']) ? ' / Curso: ' . $item['registration_course_title'] : '')
                        : ((($item['registration_origin'] ?? '') === 'login') ? 'Cadastro pelo login' : 'Cadastro manual');
                    $pendingSearchText = implode(' ', [$item['name'] ?? '', $item['email'] ?? '', $item['role_name'] ?? '', $item['created_at'] ?? '', $originLabel]);
                    ?>
                    <article class="pending-user-row" data-pending-user-card data-pending-user-role="<?= e($pendingFilter) ?>" data-pending-user-search="<?= e($pendingSearchText) ?>">
                        <label class="participant-select-box" title="Selecionar cadastro">
                            <input type="checkbox" form="pending-users-bulk-form" name="user_ids[]" value="<?= e((string) $item['id']) ?>" data-pending-user-select>
                            <span>Selecionar</span>
                        </label>
                        <div>
                            <strong><?= e($item['name']) ?></strong>
                            <span><?= e($item['email']) ?></span>
                        </div>
                        <small><?= e($item['role_name']) ?> · <?= e($item['created_at']) ?></small>
                        <small><?= e($originLabel) ?></small>
                        <div class="participant-bulk-actions">
                            <form method="post" action="<?= e(url('/admin/users/approve?id=' . $item['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-success">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                                    Aprovar
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/users/deny?id=' . $item['id'])) ?>" onsubmit="return confirm('Negar este login? Se houver inscricao de evento com este e-mail, ela ficara como inscrita.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    Negar
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($pendingUsers)): ?>
                    <div class="empty-state">Nenhum cadastro aguardando aprovação.</div>
                <?php endif; ?>
                <div class="empty-state" data-pending-users-empty hidden>Nenhum cadastro pendente encontrado com este filtro.</div>
            </div>
        </section>

        <details class="users-panel users-collapsible-panel">
            <summary class="users-panel-heading users-collapsible-heading">
                <div>
                    <span class="eyebrow">Documentos</span>
                    <h2>Quem pode enviar</h2>
                </div>
                <span class="users-collapsible-icons">
                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                    <i class="bi bi-chevron-down users-collapsible-caret" aria-hidden="true"></i>
                </span>
            </summary>
            <form method="post" action="<?= e(url('/admin/users/document-uploads')) ?>" class="user-stacked-form users-collapsible-body">
                <?= csrf_field() ?>
                <p class="field-hint">Usu&aacute;rios marcados podem enviar documentos internos. A gest&atilde;o completa continua restrita a quem gerencia documentos.</p>
                <div class="responsibility-options">
                    <?php foreach ($users as $item): ?>
                        <?php
                        $itemRoleSlugs = array_filter(explode(',', (string) ($item['role_slugs'] ?? $item['role_slug'] ?? '')));
                        if (in_array('master', $itemRoleSlugs, true)) {
                            continue;
                        }
                        ?>
                        <label>
                            <input type="checkbox" name="user_ids[]" value="<?= e((string) $item['id']) ?>" <?= checked(in_array((int) $item['id'], $documentUploadUserIds ?? [], true)) ?>>
                            <span><?= e($item['name']) ?> <small><?= e($item['role_names'] ?? $item['role_name']) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-outline-primary">Salvar permiss&otilde;es de envio</button>
            </form>
        </details>
    <?php endif; ?>

    <section class="users-panel">
        <div class="users-panel-heading">
            <div>
                <span class="eyebrow">Diretório</span>
                <h2>Usuários cadastrados</h2>
            </div>
            <span class="users-count-badge" data-users-visible-count><?= e((string) $totalUsers) ?></span>
        </div>

        <div class="users-directory-toolbar">
            <label class="users-search-field">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="visually-hidden">Pesquisar usuários</span>
                <input class="form-control" type="search" placeholder="Pesquisar por nome, e-mail ou cargo" data-users-search autocomplete="off">
            </label>
            <div class="users-filter-tabs" role="group" aria-label="Filtrar usuários">
                <button type="button" class="is-active" data-users-filter="all">Todos</button>
                <button type="button" data-users-filter="active">Ativos</button>
                <button type="button" data-users-filter="inactive">Inativos</button>
            </div>
            <div class="users-filter-summary">
                <strong data-users-visible-label><?= e((string) $totalUsers) ?></strong>
                <span>pessoa(s) na lista</span>
            </div>
        </div>

        <div class="user-directory modern" data-users-directory>
            <?php foreach ($users as $item): ?>
                <?php
                $itemRoleSlugs = array_filter(explode(',', (string) ($item['role_slugs'] ?? $item['role_slug'] ?? '')));
                $selectedRoleIds = $isMaster ? \App\Models\User::roleIds((int) $item['id']) : [];
                $selectedPages = $userResponsibilities[(int) $item['id']] ?? [];
                $canManageAccess = $isMaster && !in_array('master', $itemRoleSlugs, true) && (int) $item['id'] !== (int) ($currentUser['id'] ?? 0);
                $initial = strtoupper(substr((string) ($item['name'] ?? 'U'), 0, 1));
                $searchText = implode(' ', [
                    $item['name'] ?? '',
                    $item['email'] ?? '',
                    $item['role_names'] ?? $item['role_name'] ?? '',
                    $item['active'] ? 'ativo' : 'inativo',
                    in_array((int) $item['id'], $onlineUserIds ?? [], true) ? 'online' : '',
                ]);
                ?>
                <article class="user-row-card" data-user-card data-user-status="<?= $item['active'] ? 'active' : 'inactive' ?>" data-user-search-text="<?= e($searchText) ?>">
                    <header class="user-row-header">
                        <div class="user-avatar"><?= e($initial) ?></div>
                        <div class="user-identity">
                            <strong><?= e($item['name']) ?></strong>
                            <span><?= e($item['email']) ?></span>
                        </div>
                        <div class="user-role-summary">
                            <span><?= e($item['role_names'] ?? $item['role_name']) ?></span>
                            <small>Criado em <?= e($item['created_at']) ?></small>
                        </div>
                        <div class="user-status-stack">
                            <span class="state-pill <?= $item['active'] ? 'is-active' : 'is-muted' ?>"><?= $item['active'] ? 'Ativo' : 'Inativo' ?></span>
                            <?php if (!empty($item['profile_update_required'])): ?>
                                <span class="state-pill is-muted">Atualização pendente</span>
                            <?php endif; ?>
                            <?php if (in_array((int) $item['id'], $onlineUserIds ?? [], true)): ?>
                                <span class="state-pill is-online">Online</span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <?php if ($isMaster || $canRequestProfileUpdate): ?>
                        <details class="user-manage-drawer">
                            <summary>
                                <span><i class="bi bi-sliders" aria-hidden="true"></i> Gerenciar</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </summary>

                            <div class="user-access-grid">
                                    <section class="user-action-panel">
                                        <h3>Dados</h3>
                                        <?php if ($isMaster): ?>
                                            <form method="post" action="<?= e(url('/admin/users/update?id=' . $item['id'])) ?>" class="user-stacked-form">
                                                <?= csrf_field() ?>
                                                <label>
                                                    <span>Nome</span>
                                                    <input class="form-control form-control-sm" name="name" value="<?= e($item['name']) ?>" required>
                                                </label>
                                                <label>
                                                    <span>E-mail</span>
                                                    <input class="form-control form-control-sm" name="email" type="email" value="<?= e($item['email']) ?>" required>
                                                </label>
                                                <button class="btn btn-sm btn-outline-primary">Salvar dados</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($canRequestProfileUpdate && (int) $item['id'] !== (int) ($currentUser['id'] ?? 0)): ?>
                                            <form method="post" action="<?= e(url('/admin/users/request-profile-update?id=' . $item['id'])) ?>" class="user-stacked-form" onsubmit="return confirm('Solicitar atualização de cadastro para este usuário? Ele ficará bloqueado até concluir.');">
                                                <?= csrf_field() ?>
                                                <span class="field-hint">Escolha o que este usuário precisa atualizar no próximo login.</span>
                                                <div class="responsibility-options compact">
                                                    <label><input type="checkbox" name="profile_update_fields[]" value="name" checked><span>Nome</span></label>
                                                    <label><input type="checkbox" name="profile_update_fields[]" value="email" checked><span>E-mail</span></label>
                                                    <label><input type="checkbox" name="profile_update_fields[]" value="address"><span>Endereço</span></label>
                                                    <label><input type="checkbox" name="profile_update_fields[]" value="password"><span>Nova senha</span></label>
                                                    <label><input type="checkbox" name="profile_update_fields[]" value="all"><span>Tudo</span></label>
                                                </div>
                                                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-person-vcard" aria-hidden="true"></i>Solicitar atualização</button>
                                            </form>
                                        <?php endif; ?>
                                    </section>

                                <?php if ($canManageAccess): ?>
                                    <section class="user-action-panel">
                                        <h3>Cargos</h3>
                                        <form method="post" action="<?= e(url('/admin/users/role?id=' . $item['id'])) ?>" class="user-stacked-form">
                                            <?= csrf_field() ?>
                                            <label>
                                                <span>Cargo principal</span>
                                                <select class="form-select form-select-sm" name="role_id" required>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?= e((string) $role['id']) ?>" <?= selected((string) $role['id'], (string) $item['role_id']) ?>>
                                                            <?= e($role['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <details class="users-check-options compact">
                                                <summary>Cargos extras</summary>
                                                <div>
                                                    <?php foreach ($roles as $role): ?>
                                                        <label>
                                                            <input type="checkbox" name="role_ids[]" value="<?= e((string) $role['id']) ?>" <?= checked(in_array((int) $role['id'], $selectedRoleIds, true)) ?>>
                                                            <span><?= e($role['name']) ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </details>
                                            <button class="btn btn-sm btn-outline-primary">Salvar cargos</button>
                                        </form>
                                    </section>

                                    <section class="user-action-panel">
                                        <h3>Status</h3>
                                        <p><?= $item['active'] ? 'Este usuário pode acessar o painel.' : 'Este usuário está bloqueado no login.' ?></p>
                                        <form method="post" action="<?= e(url('/admin/users/status?id=' . $item['id'])) ?>" class="user-status-form" onsubmit="return confirm('<?= $item['active'] ? 'Inativar este usuário?' : 'Ativar este usuário?' ?>');">
                                            <?= csrf_field() ?>
                                            <?php if ($item['active']): ?>
                                                <input type="hidden" name="active" value="0">
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash" aria-hidden="true"></i> Inativar</button>
                                            <?php else: ?>
                                                <input type="hidden" name="active" value="1">
                                                <button class="btn btn-sm btn-success"><i class="bi bi-person-check" aria-hidden="true"></i> Ativar</button>
                                            <?php endif; ?>
                                        </form>
                                    </section>
                                <?php endif; ?>

                                <?php if ($institutionPages ?? []): ?>
                                    <section class="user-action-panel user-action-panel-wide">
                                        <h3>Páginas institucionais</h3>
                                        <form method="post" action="<?= e(url('/admin/users/responsibilities?id=' . $item['id'])) ?>" class="user-stacked-form">
                                            <?= csrf_field() ?>
                                            <span class="field-hint"><?= e((string) count($selectedPages)) ?> selecionada(s)</span>
                                            <div class="responsibility-options">
                                                <?php foreach (($institutionPages ?? []) as $page): ?>
                                                    <label>
                                                        <input type="checkbox" name="pages[]" value="<?= e($page['slug']) ?>" <?= checked(in_array($page['slug'], $selectedPages, true)) ?>>
                                                        <span><?= e($page['name']) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary">Salvar páginas</button>
                                        </form>
                                    </section>
                                <?php endif; ?>

                                <section class="user-action-panel">
                                    <h3>Senha</h3>
                                    <form method="post" action="<?= e(url('/admin/users/reset-password?id=' . $item['id'])) ?>" class="user-stacked-form user-temporary-password-form" data-temporary-password-form>
                                        <?= csrf_field() ?>
                                        <span class="field-hint">Gere uma senha provisória ou informe uma senha manual. A senha atual só muda ao confirmar.</span>
                                        <label>
                                            <span>Nova senha</span>
                                            <div class="password-field">
                                                <input class="form-control form-control-sm" name="password" type="password" minlength="8" placeholder="Nova senha" autocomplete="new-password" data-temporary-password required>
                                                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                            </div>
                                        </label>
                                        <label>
                                            <span>Confirmar senha</span>
                                            <div class="password-field">
                                                <input class="form-control form-control-sm" name="password_confirmation" type="password" minlength="8" placeholder="Confirmar senha" autocomplete="new-password" data-temporary-password-confirmation required>
                                                <button type="button" class="password-toggle" aria-label="Mostrar senha" title="Mostrar senha"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                            </div>
                                        </label>
                                        <div class="temporary-password-actions">
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-generate-temporary-password>
                                                <i class="bi bi-shuffle" aria-hidden="true"></i>
                                                Gerar provisória
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-temporary-password>
                                                <i class="bi bi-clipboard" aria-hidden="true"></i>
                                                Copiar
                                            </button>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Definir esta senha provisória para este usuário?');">
                                            <i class="bi bi-key" aria-hidden="true"></i>
                                            Definir senha provisória
                                        </button>
                                    </form>
                                </section>
                            </div>
                        </details>
                    <?php else: ?>
                        <div class="user-readonly-note">Somente master pode alterar acessos.</div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <div class="empty-state users-search-empty" data-users-empty hidden>Nenhum usuário encontrado com esse termo.</div>
        </div>
    </section>
</div>
