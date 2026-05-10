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
            <label class="form-label">Título</label>
            <input class="form-control form-control-lg" name="title" value="<?= e($newsItem['title'] ?? '') ?>" required maxlength="220" placeholder="Título principal da matéria">

            <label class="form-label mt-3">Resumo</label>
            <textarea class="form-control" name="summary" rows="3" maxlength="320" placeholder="Resumo curto para a capa e compartilhamento"><?= e($newsItem['summary'] ?? '') ?></textarea>
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
                    <button type="button" class="toolbar-text-button" data-block-format="P">Normal</button>
                    <button type="button" class="toolbar-text-button" data-block-format="H2">Título</button>
                    <button type="button" class="toolbar-text-button" data-block-format="H3">Subtítulo</button>
                    <button type="button" class="toolbar-text-button" data-block-format="BLOCKQUOTE">Citação</button>
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
                            <button type="button" data-upload-target="content-media-input"><i class="bi bi-cloud-upload" aria-hidden="true"></i>Fazer upload do computador</button>
                            <button type="button" data-action="image"><i class="bi bi-link-45deg" aria-hidden="true"></i>Por URL</button>
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
                    <button type="button" class="toolbar-icon" data-command="justifyLeft" title="Alinhar à esquerda"><i class="bi bi-text-left" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="justifyCenter" title="Centralizar"><i class="bi bi-text-center" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="justifyRight" title="Alinhar à direita"><i class="bi bi-text-right" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="justifyFull" title="Justificar"><i class="bi bi-justify" aria-hidden="true"></i></button>
                </div>

                <div class="toolbar-group">
                    <button type="button" class="toolbar-icon" data-command="insertUnorderedList" title="Lista"><i class="bi bi-list-ul" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="insertOrderedList" title="Lista numerada"><i class="bi bi-list-ol" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="formatBlock" data-value="blockquote" title="Citação"><i class="bi bi-quote" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-command="insertHorizontalRule" title="Linha divisória"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                    <button type="button" class="toolbar-icon" data-action="clear-format" title="Limpar formatação"><i class="bi bi-eraser" aria-hidden="true"></i></button>
                </div>
            </div>
            <input type="hidden" name="content" id="news-content" value="<?= e($newsItem['content'] ?? '') ?>">
            <div class="rich-editor" contenteditable="true" data-target="news-content">
                <?= article_html($newsItem['content'] ?? '') ?>
            </div>
            <textarea class="html-editor" data-html-editor hidden spellcheck="false"></textarea>
        </div>
    </section>

    <aside class="panel editor-side">
        <h2>Configuração</h2>

        <div class="config-block">
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
            <label class="form-label">Imagem de capa</label>
            <input class="form-control" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp">
            <label class="form-label mt-3">Ou link externo da capa</label>
            <input class="form-control" name="cover_image_url" value="<?= !empty($newsItem['cover_image']) && preg_match('#^https?://#i', $newsItem['cover_image']) ? e($newsItem['cover_image']) : '' ?>" placeholder="https://site.com/imagem.jpg">
            <?php if (!empty($newsItem['cover_image'])): ?>
                <img class="cover-preview" src="<?= e(media_url($newsItem['cover_image'])) ?>" alt="" onerror="this.remove()">
            <?php endif; ?>
        </div>

        <div class="config-block">
            <label class="form-label">Mídias para inserir no corpo</label>
            <input class="form-control" id="content-media-input" name="content_media[]" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,audio/mpeg,audio/mp3,audio/ogg,audio/wav" multiple>
            <p class="field-hint">Imagens, vídeos e áudios enviados aqui entram no fim do texto ao salvar. Links externos podem ser inseridos pelos botões do editor.</p>
        </div>

        <?php if (\App\Core\Auth::can('news.manage')): ?>
            <div class="config-block compact-checks">
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

        <?php if ($isEdit): ?>
            <div class="current-status">
                Status atual: <strong><?= e(\App\Models\News::STATUS_LABELS[$status] ?? $status) ?></strong>
            </div>
        <?php endif; ?>

        <div class="editor-actions">
            <button class="btn btn-outline-secondary w-100" name="intent" value="draft">Salvar rascunho</button>
            <button class="btn btn-primary w-100" name="intent" value="submit">
                <?= \App\Core\Auth::can('news.approve') ? 'Publicar' : 'Enviar para aprovação' ?>
            </button>
        </div>
    </aside>
</form>
<script src="<?= e(url('/public/assets/js/editor.js')) ?>"></script>
