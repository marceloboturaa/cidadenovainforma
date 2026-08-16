<?php
$isEdit = (bool) $newsItem;
$status = $newsItem['status'] ?? 'draft';
?>

<div class="page-heading">
    <div>
        <p>Publicacao</p>
        <h1><?= e($title) ?></h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/news')) ?>">Voltar</a>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="editor-grid">
    <?= csrf_field() ?>

    <section class="panel editor-main">
        <div class="editor-section">
            <h2>Texto da materia</h2>
            <div class="editor-title-row">
                <label class="form-label" for="news-title">Titulo</label>
                <input class="form-control form-control-lg" id="news-title" name="title" value="<?= e($newsItem['title'] ?? '') ?>" required maxlength="220" placeholder="Titulo principal da materia">
            </div>

            <div class="editor-summary-row">
                <label class="form-label mt-3" for="news-summary">Resumo</label>
                <textarea class="form-control" id="news-summary" name="summary" rows="5" maxlength="1200" placeholder="Resumo para a capa, compartilhamento e contexto da materia"><?= e($newsItem['summary'] ?? '') ?></textarea>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="hide_summary_in_body" id="hide_summary_in_body" <?= checked((bool) ($newsItem['hide_summary_in_body'] ?? false)) ?>>
                    <label class="form-check-label" for="hide_summary_in_body">Ocultar resumo dentro da reportagem</label>
                </div>
                <p class="field-hint">O resumo continua sendo usado em capas, listas e compartilhamentos. Marque a opção acima quando não quiser mostrar o resumo no topo da matéria.</p>
            </div>
        </div>

        <div class="editor-section">
            <div class="editor-label-row">
                <label class="form-label" for="news-content">Conteudo</label>
                <button class="btn btn-sm btn-outline-secondary editor-focus-toggle" type="button" data-editor-focus>Foco</button>
            </div>
            <textarea class="form-control tinymce-textarea" id="news-content" name="content" rows="18" data-tinymce required><?= e($newsItem['content'] ?? '') ?></textarea>
            <p class="field-hint">Use a barra do editor para inserir imagens, videos, links, tabelas, codigo, espacamento de linha e espaco entre paragrafos. Para formulas, escreva \(a^2+b^2=c^2\) no texto ou $$E=mc^2$$ em uma linha separada.</p>
        </div>
    </section>

    <aside class="panel editor-side">
        <div class="editor-side-head">
            <div>
                <span>Configuracao</span>
                <h2>Publicacao</h2>
            </div>
            <?php if ($isEdit): ?>
                <div class="current-status">
                    <?= e(\App\Models\News::STATUS_LABELS[$status] ?? $status) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="config-block">
            <h3>Organizacao</h3>
            <?php if ((current_user()['role_slug'] ?? '') === 'master'): ?>
                <label class="form-label">Criador da noticia</label>
                <select class="form-select" name="author_id">
                    <?php foreach (($users ?? []) as $user): ?>
                        <option value="<?= e((string) $user['id']) ?>" <?= selected((string) ($newsItem['author_id'] ?? current_user()['id']), (string) $user['id']) ?>>
                            <?= e($user['name']) ?> - <?= e($user['role_name'] ?? $user['role_names'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <label class="form-label mt-3">Coautor exibido</label>
            <input class="form-control" name="co_author_name" value="<?= e($newsItem['co_author_name'] ?? '') ?>" maxlength="160" placeholder="Nome da segunda pessoa autora, se houver">

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
                <?php foreach (['noticia' => 'Noticia', 'reportagem' => 'Reportagem', 'artigo' => 'Artigo', 'coluna' => 'Coluna'] as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= selected($newsItem['type'] ?? 'noticia', $key) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="form-label mt-3">Tags</label>
            <input class="form-control" name="tags" value="<?= e($tags) ?>" placeholder="cidade, saude, bairro">

            <label class="form-label mt-3">Data de publicacao</label>
            <input class="form-control" type="datetime-local" name="published_at" value="<?= !empty($newsItem['published_at']) ? e(date('Y-m-d\TH:i', strtotime($newsItem['published_at']))) : '' ?>">
        </div>

        <?php if (\App\Core\Auth::can('news.manage') || \App\Core\Auth::can('news.approve')): ?>
            <div class="config-block">
                <h3>Modo de visualizacao</h3>
                <label class="form-label" for="public_visibility">Visibilidade publica</label>
                <select class="form-select" id="public_visibility" name="public_visibility">
                    <?php foreach (($visibilityLabels ?? \App\Models\News::VISIBILITY_LABELS) as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= selected($newsItem['public_visibility'] ?? \App\Models\News::VISIBILITY_LISTED, $key) ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field-hint">"Somente por link" publica a materia, mas remove da home, busca, categorias, tags, reprise, relacionados e sitemap.</p>
            </div>
        <?php endif; ?>

        <div class="config-block">
            <h3>Imagem de capa</h3>
            <label class="form-label">Imagem de capa</label>
            <input class="form-control" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <label class="form-label mt-3">Ou link externo da capa</label>
            <input class="form-control" name="cover_image_url" value="<?= !empty($newsItem['cover_image']) && preg_match('#^https?://#i', $newsItem['cover_image']) ? e($newsItem['cover_image']) : '' ?>" placeholder="https://site.com/imagem.jpg">
            <label class="form-label mt-3">Legenda da capa</label>
            <input class="form-control" name="cover_caption" value="<?= e($newsItem['cover_caption'] ?? '') ?>" maxlength="255" placeholder="Opcional; aparece abaixo da imagem de capa">
            <label class="form-label mt-3">Tamanho da capa na reportagem</label>
            <select class="form-select" name="cover_size">
                <?php foreach (($coverSizeLabels ?? \App\Models\News::COVER_SIZE_LABELS) as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= selected($newsItem['cover_size'] ?? \App\Models\News::COVER_SIZE_FULL, $key) ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="hide_cover_in_body" id="hide_cover_in_body" <?= checked((bool) ($newsItem['hide_cover_in_body'] ?? false)) ?>>
                <label class="form-check-label" for="hide_cover_in_body">Ocultar capa dentro da notícia</label>
            </div>
            <p class="field-hint">Use quando a capa for a mesma imagem do vídeo. Ela continua aparecendo na home, listas, cards e compartilhamentos.</p>
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
                    <label class="form-check-label" for="urgent">Noticia urgente</label>
                </div>
            </div>
        <?php endif; ?>

        <div class="archive-box">
            <h3>Reprise</h3>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_archive" id="is_archive" <?= checked((bool) ($newsItem['is_archive'] ?? false)) ?>>
                <label class="form-check-label" for="is_archive">Matéria de reprise</label>
            </div>

            <label class="form-label mt-3">Data original</label>
            <input class="form-control" type="date" name="original_published_at" value="<?= e($newsItem['original_published_at'] ?? '') ?>">

            <label class="form-label mt-3">Autor original</label>
            <input class="form-control" name="original_author" value="<?= e($newsItem['original_author'] ?? '') ?>" placeholder="Nome de quem fez a reportagem na epoca">

            <label class="form-label mt-3">Fonte original</label>
            <input class="form-control" name="original_source" value="<?= e($newsItem['original_source'] ?? '') ?>" placeholder="Blog Cidade Nova Informa">

            <label class="form-label mt-3">Link original</label>
            <input class="form-control" name="original_url" value="<?= e($newsItem['original_url'] ?? '') ?>" placeholder="https://...">

            <label class="form-label mt-3">Observacao editorial</label>
            <textarea class="form-control" name="archive_note" rows="3" placeholder="Ex.: As informacoes refletem o contexto da epoca."><?= e($newsItem['archive_note'] ?? '') ?></textarea>
        </div>

        <div class="editor-actions">
            <?php if ($isEdit && $status === 'published' && !empty($newsItem['slug'])): ?>
                <a class="btn btn-outline-primary w-100" href="<?= e(url('/noticia/' . $newsItem['slug'])) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    Ver no site
                </a>
            <?php endif; ?>
            <button class="btn btn-outline-secondary w-100" name="intent" value="draft">Salvar rascunho</button>
            <button class="btn btn-primary w-100" name="intent" value="submit">
                <?= \App\Core\Auth::can('news.approve') ? 'Publicar' : 'Enviar para aprovacao' ?>
            </button>
        </div>
    </aside>
</form>
