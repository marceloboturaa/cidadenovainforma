<div class="page-heading institution-edit-heading">
    <div>
        <p>Instituição</p>
        <h1>Editar página institucional</h1>
    </div>
    <div class="split-actions">
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/institution-pages')) ?>">Voltar</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/instituicao')) ?>" target="_blank" rel="noopener">Ver pública</a>
    </div>
</div>

<nav class="institution-editor-nav" aria-label="Seções da página institucional">
    <a href="#capa">Capa</a>
    <a href="#quem-somos">Quem somos</a>
    <a href="#galeria">Galeria</a>
    <a href="#impacto">Impacto</a>
    <a href="#apoio">Apoie</a>
    <a href="#seo">SEO</a>
</nav>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="institution-editor">
    <?= csrf_field() ?>

    <section class="panel institution-editor-section" id="capa">
        <div class="institution-editor-section-head">
            <div>
                <span>01</span>
                <h2>Hero / capa</h2>
                <p>Primeiro bloco da página institucional. A imagem deve mostrar comunidade, território e acolhimento.</p>
            </div>
        </div>
        <div class="institution-field-grid">
            <div>
                <label class="form-label">Selo acima do título</label>
                <input class="form-control" name="hero[eyebrow]" value="<?= e($landing['hero']['eyebrow']) ?>">
            </div>
            <div>
                <label class="form-label">Título</label>
                <input class="form-control" name="hero[title]" value="<?= e($landing['hero']['title']) ?>">
            </div>
            <div>
                <label class="form-label">Subtítulo</label>
                <textarea class="form-control" name="hero[subtitle]" rows="3"><?= e($landing['hero']['subtitle']) ?></textarea>
            </div>
            <div>
                <label class="form-label">Imagem de capa</label>
                <input class="form-control" name="hero[image]" value="<?= e($landing['hero']['image']) ?>">
                <p class="form-text">Aceita URL externa, Google Drive ou imagem do site. O upload abaixo substitui este campo.</p>
            </div>
            <div>
                <label class="form-label">Substituir capa por upload</label>
                <input class="form-control" type="file" name="hero_image_file" accept="image/*">
            </div>
            <div>
                <label class="form-label">Texto do botão</label>
                <input class="form-control" name="hero[button_label]" value="<?= e($landing['hero']['button_label']) ?>">
            </div>
            <div>
                <label class="form-label">Link do botão</label>
                <input class="form-control" name="hero[button_url]" value="<?= e($landing['hero']['button_url']) ?>">
            </div>
        </div>
    </section>

    <section class="panel institution-editor-section" id="quem-somos">
        <div class="institution-editor-section-head">
            <div>
                <span>02</span>
                <h2>Quem somos</h2>
                <p>Texto institucional sobre origem, jornalismo comunitário, ações sociais, educação, sustentabilidade e fortalecimento comunitário.</p>
            </div>
        </div>
        <div class="institution-field-grid">
            <div>
                <label class="form-label">Selo</label>
                <input class="form-control" name="about[eyebrow]" value="<?= e($landing['about']['eyebrow']) ?>">
            </div>
            <div>
                <label class="form-label">Título da seção</label>
                <input class="form-control" name="about[title]" value="<?= e($landing['about']['title']) ?>">
            </div>
            <div>
                <label class="form-label">Texto</label>
                <textarea class="form-control" name="about[body]" rows="9" data-tinymce><?= e($landing['about']['body']) ?></textarea>
            </div>
        </div>
    </section>

    <section class="panel institution-editor-section" id="galeria">
        <div class="institution-editor-section-head">
            <div>
                <span>03</span>
                <h2>Galeria</h2>
                <p>Gerencie fotos, vídeos, eventos, oficinas, rádio, biblioteca, horta e registros da comunidade.</p>
            </div>
            <button class="btn btn-outline-primary" type="button" data-gallery-add>Adicionar item</button>
        </div>

        <div class="institution-field-grid">
            <div>
                <label class="form-label">Selo</label>
                <input class="form-control" name="gallery_meta[eyebrow]" value="<?= e($landing['gallery']['eyebrow']) ?>">
            </div>
            <div>
                <label class="form-label">Título da seção</label>
                <input class="form-control" name="gallery_meta[title]" value="<?= e($landing['gallery']['title']) ?>">
            </div>
            <div>
                <label class="form-label">Texto de apoio</label>
                <textarea class="form-control" name="gallery_meta[intro]" rows="3"><?= e($landing['gallery']['intro']) ?></textarea>
            </div>
        </div>

        <div class="gallery-editor-list" data-gallery-list>
            <?php foreach (($landing['gallery']['items'] ?? []) as $item): ?>
                <article class="gallery-editor-card" data-gallery-card>
                    <div class="gallery-editor-number" aria-hidden="true"></div>
                    <div class="gallery-editor-fields gallery-editor-fields-wide">
                        <div>
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="gallery[type][]">
                                <?php foreach (['fotos', 'vídeos', 'eventos', 'oficinas', 'rádio', 'biblioteca', 'horta', 'comunidade'] as $type): ?>
                                    <option value="<?= e($type) ?>" <?= selected($item['type'] ?? '', $type) ?>><?= e(ucfirst($type)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Título</label>
                            <input class="form-control" name="gallery[title][]" value="<?= e($item['title'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label">Descrição</label>
                            <input class="form-control" name="gallery[description][]" value="<?= e($item['description'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label">Link opcional</label>
                            <input class="form-control" name="gallery[url][]" value="<?= e($item['url'] ?? '') ?>" placeholder="https://...">
                        </div>
                        <div>
                            <label class="form-label">Imagem atual</label>
                            <input class="form-control" name="gallery[cover][]" value="<?= e($item['cover'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label">Substituir imagem</label>
                            <input class="form-control" type="file" name="gallery_cover_file[]" accept="image/*">
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-gallery-remove>Remover</button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel institution-editor-section" id="impacto">
        <div class="institution-editor-section-head">
            <div>
                <span>04</span>
                <h2>Impacto social</h2>
                <p>Números e estatísticas editáveis exibidos na página institucional.</p>
            </div>
        </div>
        <div class="institution-field-grid">
            <div>
                <label class="form-label">Selo</label>
                <input class="form-control" name="impact_meta[eyebrow]" value="<?= e($landing['impact']['eyebrow']) ?>">
            </div>
            <div>
                <label class="form-label">Título da seção</label>
                <input class="form-control" name="impact_meta[title]" value="<?= e($landing['impact']['title']) ?>">
            </div>
        </div>
        <div class="institution-repeat-grid">
            <?php foreach (($landing['impact']['stats'] ?? []) as $stat): ?>
                <article>
                    <label class="form-label">Número</label>
                    <input class="form-control" name="impact[value][]" value="<?= e($stat['value'] ?? '') ?>">
                    <label class="form-label">Indicador</label>
                    <input class="form-control" name="impact[label][]" value="<?= e($stat['label'] ?? '') ?>">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="impact[description][]" rows="3"><?= e($stat['description'] ?? '') ?></textarea>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel institution-editor-section" id="apoio">
        <div class="institution-editor-section-head">
            <div>
                <span>05</span>
                <h2>Apoie o projeto</h2>
                <p>Campos de voluntariado, doações, parcerias e contato.</p>
            </div>
        </div>
        <div class="institution-field-grid">
            <div>
                <label class="form-label">Selo</label>
                <input class="form-control" name="support_meta[eyebrow]" value="<?= e($landing['support']['eyebrow']) ?>">
            </div>
            <div>
                <label class="form-label">Título da seção</label>
                <input class="form-control" name="support_meta[title]" value="<?= e($landing['support']['title']) ?>">
            </div>
            <div>
                <label class="form-label">Texto de apoio</label>
                <textarea class="form-control" name="support_meta[body]" rows="3"><?= e($landing['support']['body']) ?></textarea>
            </div>
        </div>
        <div class="institution-repeat-grid">
            <?php foreach (($landing['support']['items'] ?? []) as $item): ?>
                <article>
                    <label class="form-label">Título</label>
                    <input class="form-control" name="support[title][]" value="<?= e($item['title'] ?? '') ?>">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="support[description][]" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                    <label class="form-label">Texto do botão</label>
                    <input class="form-control" name="support[button_label][]" value="<?= e($item['button_label'] ?? '') ?>">
                    <label class="form-label">Link</label>
                    <input class="form-control" name="support[url][]" value="<?= e($item['url'] ?? '') ?>" placeholder="https://..., mailto:... ou #contato">
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel institution-editor-section" id="seo">
        <div class="institution-editor-section-head">
            <div>
                <span>06</span>
                <h2>SEO básico</h2>
                <p>Título e descrição usados no navegador, Google e redes sociais.</p>
            </div>
        </div>
        <div class="institution-field-grid">
            <div>
                <label class="form-label">Título SEO</label>
                <input class="form-control" name="seo[title]" value="<?= e($landing['seo']['title']) ?>">
            </div>
            <div>
                <label class="form-label">Descrição SEO</label>
                <textarea class="form-control" name="seo[description]" rows="3"><?= e($landing['seo']['description']) ?></textarea>
            </div>
        </div>
    </section>

    <div class="institution-save-bar">
        <button class="btn btn-primary">Salvar página institucional</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/instituicao')) ?>" target="_blank" rel="noopener">Ver página pública</a>
    </div>
</form>
