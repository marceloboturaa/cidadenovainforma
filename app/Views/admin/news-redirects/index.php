<link rel="stylesheet" href="<?= e(versioned_asset_url('/public/assets/css/admin/news-redirects.css')) ?>">
<div class="page-heading"><div><p>Conteúdo</p><h1>Links quebrados</h1></div></div>
<section class="panel">
    <h2>Vincular link a uma matéria</h2>
    <p>Informe um endereço antigo deste site e escolha a matéria publicada que deve abrir. Páginas que ainda existem continuam abrindo normalmente.</p>
    <form method="post" action="<?= e(url('/admin/news-redirects')) ?>">
        <?= csrf_field() ?>
        <label class="form-label" for="source-path">Link antigo ou quebrado</label>
        <input class="form-control" id="source-path" name="source_path" maxlength="1000" placeholder="/noticia/link-antigo" value="<?= e(is_string($_GET['source'] ?? null) ? $_GET['source'] : '') ?>" required>
        <p class="field-hint">Cole o link completo deste site ou o caminho. Use endereços sem ? ou #.</p>
        <fieldset class="redirect-picker mt-3" data-article-picker>
            <legend>Matéria publicada de destino</legend>
            <div data-article-search-box hidden>
                <label class="form-label" for="article-search">Pesquisar matéria</label>
                <input class="form-control" type="search" id="article-search" placeholder="Digite o título, número ou link da matéria" autocomplete="off" aria-controls="article-results">
                <p class="field-hint" data-result-count role="status" aria-live="polite"></p>
            </div>
            <div class="redirect-picker-results" id="article-results">
            <?php foreach ($articles as $article): ?>
                <label class="redirect-article" data-article-option>
                    <input type="radio" name="news_id" value="<?= e((string) $article['id']) ?>" required <?= checked((int) ($selectedNewsId ?? 0) === (int) $article['id']) ?>>
                    <span>
                        <strong><?= e($article['title']) ?></strong>
                        <small>#<?= e((string) $article['id']) ?> · <?= e(url('/noticia/' . $article['slug'])) ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
            </div>
            <p data-no-articles hidden>Nenhuma matéria encontrada. Tente outra palavra do título ou do link.</p>
            <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-more-articles hidden>Mostrar mais matérias</button>
            <div class="redirect-selected mt-3" data-selected-article hidden>
                <span>Destino selecionado</span>
                <strong data-selected-title></strong>
                <a data-selected-link target="_blank" rel="noopener">Abrir matéria <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        </fieldset>
        <p class="field-hint">Para trocar o destino de um link cadastrado, informe o mesmo link e escolha outra matéria. O destino acompanha futuras alterações no link da matéria.</p>
        <button class="btn btn-primary mt-3" <?= !$articles ? 'disabled' : '' ?>>Salvar redirecionamento</button>
        <?php if (!$articles): ?><p>Não há matérias publicadas disponíveis.</p><?php endif; ?>
    </form>
</section>
<script src="<?= e(versioned_asset_url('/public/assets/js/news-redirects.js')) ?>" defer></script>
<section class="panel mt-3">
    <h2>Links cadastrados</h2>
    <?php if (!$redirects): ?><p>Nenhum redirecionamento cadastrado.</p><?php endif; ?>
    <?php foreach ($redirects as $item): ?>
        <article class="border-bottom py-3">
            <a href="<?= e(url($item['source_path'])) ?>" target="_blank" rel="noopener"><?= e($item['source_path']) ?></a>
            <p>Destino: <?= e($item['title'] ?? 'Matéria excluída') ?>
                <?php if (($item['status'] ?? '') !== 'published'): ?><strong> — Inativo: destino não publicado</strong><?php endif; ?>
            </p>
            <div class="split-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/news-redirects?source=' . rawurlencode($item['source_path']))) ?>">Alterar destino</a>
                <form method="post" action="<?= e(url('/admin/news-redirects/delete')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                    <button class="btn btn-sm btn-outline-danger">Remover</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</section>
