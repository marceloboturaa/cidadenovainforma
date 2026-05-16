<div class="page-heading">
    <div>
        <p>Instituição</p>
        <h1>Minhas páginas</h1>
    </div>
    <?php if (!empty($canManageLanding)): ?>
        <div class="split-actions">
            <a class="btn btn-primary" href="<?= e(url('/admin/institution-pages/landing')) ?>">Editar página institucional</a>
            <a class="btn btn-outline-secondary" href="<?= e(url('/instituicao')) ?>" target="_blank" rel="noopener">Ver pública</a>
        </div>
    <?php endif; ?>
</div>

<section class="institution-admin-home">
    <div class="institution-admin-intro">
        <span>Painel institucional</span>
        <h2>Escolha uma área para atualizar</h2>
        <p>Atualize a página principal da instituição e os projetos sociais, culturais e comunitários ligados ao Cidade Nova Informa.</p>
    </div>

    <div class="institution-admin-grid">
        <?php foreach ($pages as $page): ?>
            <article class="institution-admin-card">
                <div class="institution-admin-card-top">
                    <span><?= e($page['kicker']) ?></span>
                    <h3><?= e($page['name']) ?></h3>
                    <p><?= e($page['summary']) ?></p>
                </div>
                <dl>
                    <div>
                        <dt>Equipe</dt>
                        <dd><?= e((string) count($page['team'])) ?></dd>
                    </div>
                    <div>
                        <dt>Galerias</dt>
                        <dd><?= e((string) count($page['galleries'] ?? [])) ?></dd>
                    </div>
                    <div>
                        <dt>Tags</dt>
                        <dd><?= e((string) count($page['related_tags'] ?? [])) ?></dd>
                    </div>
                </dl>
                <p class="institution-admin-card-status"><?= !empty($page['show_on_landing']) ? 'Visível nos cards da página institucional.' : 'Oculto dos cards da página institucional.' ?></p>
                <div class="institution-admin-actions">
                    <a class="btn btn-primary" href="<?= e(url('/admin/institution-pages/edit?slug=' . $page['slug'])) ?>">Editar informações</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/instituicao/' . $page['slug'])) ?>" target="_blank" rel="noopener">Ver pública</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($pages)): ?>
            <div class="empty-state">Seu acesso permite editar a página institucional principal. Nenhum projeto específico foi vinculado ao seu usuário.</div>
        <?php endif; ?>
    </div>
</section>
