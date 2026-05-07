<div class="page-heading">
    <div>
        <p>Instituição</p>
        <h1>Minhas páginas</h1>
    </div>
</div>

<section class="institution-admin-home">
    <div class="institution-admin-intro">
        <span>Painel institucional</span>
        <h2>Escolha uma área para atualizar</h2>
        <p>Biblioteca, Horta e Rádio têm páginas próprias. Cada card mostra o que já foi preenchido e leva direto para a edição.</p>
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
                <div class="institution-admin-actions">
                    <a class="btn btn-primary" href="<?= e(url('/admin/institution-pages/edit?slug=' . $page['slug'])) ?>">Editar informações</a>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/instituicao/' . $page['slug'])) ?>" target="_blank" rel="noopener">Ver pública</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
