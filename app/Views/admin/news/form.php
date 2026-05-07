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
                <button type="button" data-command="formatBlock" data-value="h2">Título</button>
                <button type="button" data-command="bold"><strong>B</strong></button>
                <button type="button" data-command="italic"><em>I</em></button>
                <button type="button" data-command="underline"><u>U</u></button>
                <button type="button" data-command="insertUnorderedList">Lista</button>
                <button type="button" data-command="formatBlock" data-value="blockquote">Citação</button>
                <button type="button" data-action="link">Link</button>
                <button type="button" data-action="image">Imagem</button>
                <button type="button" data-command="removeFormat">Limpar</button>
            </div>
            <input type="hidden" name="content" id="news-content" value="<?= e($newsItem['content'] ?? '') ?>">
            <div class="rich-editor" contenteditable="true" data-target="news-content">
                <?= article_html($newsItem['content'] ?? '') ?>
            </div>
        </div>
    </section>

    <aside class="panel editor-side">
        <h2>Configuração</h2>

        <div class="config-block">
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
        </div>

        <div class="config-block">
            <label class="form-label">Imagem de capa</label>
            <input class="form-control" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($newsItem['cover_image'])): ?>
                <img class="cover-preview" src="<?= e(url($newsItem['cover_image'])) ?>" alt="">
            <?php endif; ?>
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
