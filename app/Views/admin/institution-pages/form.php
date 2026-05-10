<div class="page-heading institution-edit-heading">
    <div>
        <p>Instituição</p>
        <h1>Editar <?= e($page['name']) ?></h1>
    </div>
    <div class="split-actions">
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/institution-pages')) ?>">Voltar</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/instituicao/' . $page['slug'])) ?>" target="_blank" rel="noopener">Ver pública</a>
    </div>
</div>

<nav class="institution-editor-nav" aria-label="Seções da edição institucional">
    <a href="#apresentacao">Apresentação</a>
    <a href="#galerias">Galerias</a>
    <a href="#organizacao">Organização</a>
    <a href="#noticias">Notícias</a>
</nav>

<form method="post" action="<?= e($action) ?>" class="institution-editor">
    <?= csrf_field() ?>

    <section class="panel institution-editor-section" id="apresentacao">
        <div class="institution-editor-section-head">
            <div>
                <span>01</span>
                <h2>Apresentação da página</h2>
                <p>Texto principal que aparece para o visitante em <?= e($page['name']) ?>.</p>
            </div>
        </div>

        <div class="institution-field-grid">
            <div>
                <label class="form-label">Nome da área</label>
                <input class="form-control form-control-lg" name="name" value="<?= e($page['name']) ?>" required>
            </div>
            <div>
                <label class="form-label">Chamada curta</label>
                <input class="form-control" name="kicker" value="<?= e($page['kicker']) ?>" required>
            </div>
            <div>
                <label class="form-label">Resumo</label>
                <textarea class="form-control" name="summary" rows="3" required><?= e($page['summary']) ?></textarea>
            </div>
            <div>
                <label class="form-label">Descrição do projeto</label>
                <textarea class="form-control" name="description" rows="7" required><?= e($page['description']) ?></textarea>
            </div>
        </div>
    </section>

    <section class="panel institution-editor-section" id="galerias">
        <div class="institution-editor-section-head">
            <div>
                <span>02</span>
                <h2>Galerias de fotos</h2>
                <p>Adicione álbuns do Google Photos como cards. O link fica escondido no botão da página pública.</p>
            </div>
            <button class="btn btn-outline-primary" type="button" data-gallery-add>Adicionar galeria</button>
        </div>

        <div class="gallery-editor-list" data-gallery-list>
            <?php $galleries = $page['galleries'] ?: [['title' => '', 'description' => '', 'url' => '', 'cover' => '']]; ?>
            <?php foreach ($galleries as $gallery): ?>
                <article class="gallery-editor-card" data-gallery-card>
                    <div class="gallery-editor-number" aria-hidden="true"></div>
                    <div class="gallery-editor-fields">
                        <div>
                            <label class="form-label">Nome do álbum</label>
                            <input class="form-control" name="galleries[title][]" value="<?= e($gallery['title'] ?? '') ?>" placeholder="Ex.: Atividades da biblioteca">
                        </div>
                        <div>
                            <label class="form-label">Descrição curta</label>
                            <input class="form-control" name="galleries[description][]" value="<?= e($gallery['description'] ?? '') ?>" placeholder="Ex.: Registros das ações com a comunidade">
                        </div>
                        <div>
                            <label class="form-label">Link do álbum</label>
                            <input class="form-control" name="galleries[url][]" value="<?= e($gallery['url'] ?? '') ?>" placeholder="https://photos.app.goo.gl/...">
                        </div>
                        <div>
                            <label class="form-label">Imagem de capa</label>
                            <input class="form-control" name="galleries[cover][]" value="<?= e($gallery['cover'] ?? '') ?>" placeholder="Opcional: link da imagem de capa">
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-gallery-remove>Remover</button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel institution-editor-section" id="organizacao">
        <div class="institution-editor-section-head">
            <div>
                <span>03</span>
                <h2>Equipe e informações</h2>
                <p>Organize responsáveis, informações úteis e fotos avulsas sem expor links longos na página.</p>
            </div>
        </div>

        <div class="institution-field-grid">
            <div>
                <label class="form-label">Equipe responsável</label>
                <textarea class="form-control" name="team" rows="5" placeholder="Um responsável por linha"><?= e(implode(PHP_EOL, $page['team'])) ?></textarea>
            </div>
            <div>
                <label class="form-label">Informações da área</label>
                <textarea class="form-control" name="materials" rows="5" placeholder="Uma informação por linha&#10;Exemplo com link: Regulamento | https://site.com/arquivo.pdf"><?= e(implode(PHP_EOL, $page['materials'])) ?></textarea>
                <p class="form-text">Para criar um botão/link, escreva: Nome que aparece | https://link.com</p>
            </div>
        </div>

        <details class="institution-advanced">
            <summary>Fotos avulsas</summary>
            <textarea class="form-control" name="photos" rows="6" placeholder="https://site.com/foto.jpg&#10;https://drive.google.com/file/d/ID_DA_IMAGEM/view&#10;/public/uploads/news/foto.jpg"><?= e(implode(PHP_EOL, $page['photos'])) ?></textarea>
            <p class="form-text">Use esta área só quando precisar destacar imagens fora de uma galeria. Aceita links externos, Google Drive e imagens do site.</p>
        </details>
    </section>

    <section class="panel institution-editor-section" id="noticias">
        <div class="institution-editor-section-head">
            <div>
                <span>04</span>
                <h2>Matérias relacionadas</h2>
                <p>Selecione as tags que devem alimentar a seção de notícias desta página.</p>
            </div>
        </div>

        <div class="institution-tag-picker">
            <?php foreach (($tags ?? []) as $tag): ?>
                <label>
                    <input type="checkbox" name="related_tags[]" value="<?= e($tag['slug']) ?>" <?= checked(in_array($tag['slug'], $page['related_tags'] ?? [], true)) ?>>
                    <span><?= e($tag['display_name'] ?? $tag['name']) ?></span>
                </label>
            <?php endforeach; ?>
            <?php if (empty($tags)): ?>
                <div class="empty-state">Cadastre tags no site para vincular notícias a esta página.</div>
            <?php endif; ?>
        </div>

        <input type="hidden" name="search_terms" value="<?= e($page['search_terms'] ?? '') ?>">
    </section>

    <div class="institution-save-bar">
        <button class="btn btn-primary">Salvar alterações</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/instituicao/' . $page['slug'])) ?>" target="_blank" rel="noopener">Ver página pública</a>
    </div>
</form>
