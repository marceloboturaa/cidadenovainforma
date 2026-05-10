<?php
$isEdit = (bool) $newsItem;
$status = $newsItem['status'] ?? 'draft';
?>

<div class="page-heading">
    <div>
        <p>Publicação</p>
        <h1><?= e($title) ?></h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/news')) ?>">Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="editor-grid">
    <?= csrf_field() ?>

    <section class="panel editor-main">
        <div class="editor-section">
            <h2>Texto da matéria</h2>
            <div class="editor-title-row">
                <label class="form-label" for="news-title">Título</label>
                <input class="form-control form-control-lg" id="news-title" name="title" value="<?= e($newsItem['title'] ?? '') ?>" required maxlength="220" placeholder="Título principal da matéria">
            </div>

            <div class="editor-summary-row">
                <label class="form-label mt-3" for="news-summary">Resumo</label>
                <textarea class="form-control" id="news-summary" name="summary" rows="5" maxlength="1200" placeholder="Resumo para a capa, compartilhamento e contexto da matéria"><?= e($newsItem['summary'] ?? '') ?></textarea>
                <p class="field-hint">O resumo completo aparece na notícia; capas e listas usam uma versão curta automaticamente.</p>
            </div>
        </div>

        <div class="editor-section">
            <div class="editor-label-row">
                <label class="form-label">Conteúdo</label>
                <button class="btn btn-sm btn-outline-secondary editor-focus-toggle" type="button" data-editor-focus>Foco</button>
            </div>
            <div class="rich-toolbar" aria-label="Ferramentas do editor">
                <div class="toolbar-group">
                    <button type="button" class="toolbar-icon" data-action="html-toggle" title="Visualização em HTML"><i class="bi bi-code" aria-hidden="true"></i></button>
                </div>

                <div class="toolbar-group">
                    <button type="button" class="toolbar-icon" data-command="undo" title="Desfazer"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="redo" title="Refazer"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
                </div>

                <div class="toolbar-group">
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Estilo do texto"><i class="bi bi-type" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover">
                            <span class="rich-menu-label">Estilo do texto</span>
                            <button type="button" data-block-format="P"><i class="bi bi-text-paragraph" aria-hidden="true"></i>Normal</button>
                            <button type="button" data-block-format="H2"><i class="bi bi-type-h2" aria-hidden="true"></i>Título</button>
                            <button type="button" data-block-format="H3"><i class="bi bi-type-h3" aria-hidden="true"></i>Subtítulo</button>
                            <button type="button" data-block-format="BLOCKQUOTE"><i class="bi bi-quote" aria-hidden="true"></i>Citação</button>
                        </div>
                    </div>
                </div>

                <div class="toolbar-group">
                    <button type="button" class="toolbar-icon" data-command="bold" title="Negrito"><strong>B</strong></button>
                    <button type="button" class="toolbar-icon" data-command="italic" title="Itálico"><em>I</em></button>
                    <button type="button" class="toolbar-icon" data-command="underline" title="Sublinhado"><u>U</u></button>
                    <button type="button" class="toolbar-icon" data-command="strikeThrough" title="Riscado"><i class="bi bi-type-strikethrough" aria-hidden="true"></i></button>
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Cor do texto"><i class="bi bi-palette" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover color-popover">
                            <button type="button" class="color-swatch" data-color="#111827" style="--swatch:#111827" title="Preto"></button>
                            <button type="button" class="color-swatch" data-color="#6b7280" style="--swatch:#6b7280" title="Cinza"></button>
                            <button type="button" class="color-swatch" data-color="#b91c1c" style="--swatch:#b91c1c" title="Vermelho"></button>
                            <button type="button" class="color-swatch" data-color="#c2410c" style="--swatch:#c2410c" title="Laranja"></button>
                            <button type="button" class="color-swatch" data-color="#a16207" style="--swatch:#a16207" title="Dourado"></button>
                            <button type="button" class="color-swatch" data-color="#15803d" style="--swatch:#15803d" title="Verde"></button>
                            <button type="button" class="color-swatch" data-color="#0f766e" style="--swatch:#0f766e" title="Verde azulado"></button>
                            <button type="button" class="color-swatch" data-color="#2563eb" style="--swatch:#2563eb" title="Azul"></button>
                        </div>
                    </div>
                </div>

                <div class="toolbar-group">
                    <button type="button" class="toolbar-icon" data-action="link" title="Link"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Imagem"><i class="bi bi-image" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover">
                            <span class="rich-menu-label">Inserir imagem</span>
                            <button type="button" data-upload-target="content-media-input"><i class="bi bi-cloud-upload" aria-hidden="true"></i>Fazer upload do computador</button>
                            <button type="button" data-action="image"><i class="bi bi-link-45deg" aria-hidden="true"></i>Por URL</button>
                            <span class="rich-menu-label">Tamanho</span>
                            <button type="button" data-image-size="small"><i class="bi bi-arrows-angle-contract" aria-hidden="true"></i>Pequena</button>
                            <button type="button" data-image-size="medium"><i class="bi bi-aspect-ratio" aria-hidden="true"></i>Média</button>
                            <button type="button" data-image-size="large"><i class="bi bi-arrows-angle-expand" aria-hidden="true"></i>Grande</button>
                            <button type="button" data-image-size="full"><i class="bi bi-fullscreen" aria-hidden="true"></i>Largura total</button>
                            <span class="rich-menu-label">Posição</span>
                            <button type="button" data-image-align="left"><i class="bi bi-text-left" aria-hidden="true"></i>À esquerda</button>
                            <button type="button" data-image-align="center"><i class="bi bi-text-center" aria-hidden="true"></i>Centralizada</button>
                            <button type="button" data-image-align="right"><i class="bi bi-text-right" aria-hidden="true"></i>À direita</button>
                            <button type="button" data-image-align="justify"><i class="bi bi-justify" aria-hidden="true"></i>Justificada</button>
                        </div>
                    </div>
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Vídeo"><i class="bi bi-camera-video" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover">
                            <button type="button" data-upload-target="content-media-input"><i class="bi bi-cloud-upload" aria-hidden="true"></i>Fazer upload do computador</button>
                            <button type="button" data-action="video"><i class="bi bi-youtube" aria-hidden="true"></i>YouTube, Vimeo ou URL</button>
                        </div>
                    </div>
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Áudio"><i class="bi bi-volume-up" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover">
                            <button type="button" data-upload-target="content-media-input"><i class="bi bi-cloud-upload" aria-hidden="true"></i>Fazer upload do computador</button>
                            <button type="button" data-action="audio"><i class="bi bi-link-45deg" aria-hidden="true"></i>Por URL</button>
                        </div>
                    </div>
                </div>

                <div class="toolbar-group">
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Alinhamento"><i class="bi bi-text-left" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover">
                            <span class="rich-menu-label">Alinhamento</span>
                            <button type="button" data-command="justifyLeft"><i class="bi bi-text-left" aria-hidden="true"></i>À esquerda</button>
                            <button type="button" data-command="justifyCenter"><i class="bi bi-text-center" aria-hidden="true"></i>Centralizado</button>
                            <button type="button" data-command="justifyRight"><i class="bi bi-text-right" aria-hidden="true"></i>À direita</button>
                            <button type="button" data-command="justifyFull"><i class="bi bi-justify" aria-hidden="true"></i>Justificado</button>
                        </div>
                    </div>
                </div>

                <div class="toolbar-group">
                    <div class="rich-menu">
                        <button type="button" class="toolbar-icon" data-menu-toggle title="Listas"><i class="bi bi-list-ul" aria-hidden="true"></i><i class="bi bi-caret-down-fill" aria-hidden="true"></i></button>
                        <div class="rich-menu-popover">
                            <span class="rich-menu-label">Listas</span>
                            <button type="button" data-command="insertUnorderedList"><i class="bi bi-list-ul" aria-hidden="true"></i>Lista com pontos</button>
                            <button type="button" data-command="insertOrderedList"><i class="bi bi-list-ol" aria-hidden="true"></i>Lista numerada</button>
                        </div>
                    </div>
                    <button type="button" class="toolbar-icon" data-command="insertHorizontalRule" title="Linha divisória"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-action="clear-format" title="Limpar formatação"><i class="bi bi-eraser" aria-hidden="true"></i></button>
                </div>
            </div>
            <input type="hidden" name="content" id="news-content" value="<?= e($newsItem['content'] ?? '') ?>">
            <div class="rich-editor" contenteditable="true" data-target="news-content">
                <?= article_html($newsItem['content'] ?? '') ?>
            </div>
            <textarea class="html-editor" data-html-editor hidden spellcheck="false"></textarea>
            <input class="content-media-hidden" id="content-media-input" name="content_media[]" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,audio/mpeg,audio/mp3,audio/ogg,audio/wav" multiple>
        </div>
    </section>

    <aside class="panel editor-side">
        <div class="editor-side-head">
            <div>
                <span>Configuração</span>
                <h2>Publicação</h2>
            </div>
            <?php if ($isEdit): ?>
                <div class="current-status">
                    <?= e(\App\Models\News::STATUS_LABELS[$status] ?? $status) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="config-block">
            <h3>Organização</h3>
            <?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
                <label class="form-label">Criador da notícia</label>
                <select class="form-select" name="author_id">
                    <?php foreach (($users ?? []) as $user): ?>
                        <option value="<?= e((string) $user['id']) ?>" <?= selected((string) ($newsItem['author_id'] ?? current_user()['id']), (string) $user['id']) ?>>
                            <?= e($user['name']) ?> · <?= e($user['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <label class="form-label">Categoria</label>
            <select class="form-select" name="category_id">
                <option value="">Sem categoria</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= selected((string) ($newsItem['category_id'] ?? ''), (string) $category['id']) ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="form-label mt-3">Tipo</label>
            <select class="form-select" name="type">
                <?php foreach (['noticia' => 'Notícia', 'reportagem' => 'Reportagem', 'artigo' => 'Artigo', 'coluna' => 'Coluna'] as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= selected($newsItem['type'] ?? 'noticia', $key) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="form-label mt-3">Tags</label>
            <input class="form-control" name="tags" value="<?= e($tags) ?>" placeholder="cidade, saúde, bairro">

            <label class="form-label mt-3">Data de publicação</label>
            <input class="form-control" type="datetime-local" name="published_at" value="<?= !empty($newsItem['published_at']) ? e(date('Y-m-d\TH:i', strtotime($newsItem['published_at']))) : '' ?>">
        </div>

        <div class="config-block">
            <h3>Imagem de capa</h3>
            <label class="form-label">Imagem de capa</label>
            <input class="form-control" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp">
            <label class="form-label mt-3">Ou link externo da capa</label>
            <input class="form-control" name="cover_image_url" value="<?= !empty($newsItem['cover_image']) && preg_match('#^https?://#i', $newsItem['cover_image']) ? e($newsItem['cover_image']) : '' ?>" placeholder="https://site.com/imagem.jpg">
            <p class="field-hint">A capa aparece nas listagens e no compartilhamento. Imagens do corpo ficam no menu Imagem do editor.</p>
            <?php if (!empty($newsItem['cover_image'])): ?>
                <img class="cover-preview" src="<?= e(media_url($newsItem['cover_image'])) ?>" alt="" onerror="this.remove()">
            <?php endif; ?>
        </div>

        <?php if (\App\Core\Auth::can('news.manage')): ?>
            <div class="config-block compact-checks">
                <h3>Destaques</h3>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="featured" id="featured" <?= checked((bool) ($newsItem['featured'] ?? false)) ?>>
                    <label class="form-check-label" for="featured">Destaque na home</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="urgent" id="urgent" <?= checked((bool) ($newsItem['urgent'] ?? false)) ?>>
                    <label class="form-check-label" for="urgent">Notícia urgente</label>
                </div>
            </div>
        <?php endif; ?>

        <div class="archive-box">
            <h3>Acervo</h3>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_archive" id="is_archive" <?= checked((bool) ($newsItem['is_archive'] ?? false)) ?>>
                <label class="form-check-label" for="is_archive">Matéria de acervo</label>
            </div>

            <label class="form-label mt-3">Data original</label>
            <input class="form-control" type="date" name="original_published_at" value="<?= e($newsItem['original_published_at'] ?? '') ?>">

            <label class="form-label mt-3">Autor original</label>
            <input class="form-control" name="original_author" value="<?= e($newsItem['original_author'] ?? '') ?>" placeholder="Nome de quem fez a reportagem na época">

            <label class="form-label mt-3">Fonte original</label>
            <input class="form-control" name="original_source" value="<?= e($newsItem['original_source'] ?? '') ?>" placeholder="Blog Cidade Nova Informa">

            <label class="form-label mt-3">Link original</label>
            <input class="form-control" name="original_url" value="<?= e($newsItem['original_url'] ?? '') ?>" placeholder="https://...">

            <label class="form-label mt-3">Observação editorial</label>
            <textarea class="form-control" name="archive_note" rows="3" placeholder="Ex.: As informações refletem o contexto da época."><?= e($newsItem['archive_note'] ?? '') ?></textarea>
        </div>

        <div class="editor-actions">
            <button class="btn btn-outline-secondary w-100" name="intent" value="draft">Salvar rascunho</button>
            <button class="btn btn-primary w-100" name="intent" value="submit">
                <?= \App\Core\Auth::can('news.approve') ? 'Publicar' : 'Enviar para aprovação' ?>
            </button>
        </div>
    </aside>
</form>
<?php $editorJsVersion = file_exists(dirname(__DIR__, 4) . '/public/assets/js/editor.js') ? filemtime(dirname(__DIR__, 4) . '/public/assets/js/editor.js') : time(); ?>
<script src="<?= e(url('/public/assets/js/editor.js') . '?v=' . $editorJsVersion) ?>"></script>
