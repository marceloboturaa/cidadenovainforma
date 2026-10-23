<div class="page-heading"><div><p>Conteúdo</p><h1>Links quebrados</h1></div></div>
<section class="panel">
    <h2>Vincular link a uma matéria</h2>
    <p>Informe um endereço antigo deste site e escolha a matéria publicada que deve abrir. Páginas que ainda existem continuam abrindo normalmente.</p>
    <form method="post" action="<?= e(url('/admin/news-redirects')) ?>">
        <?= csrf_field() ?>
        <label class="form-label" for="source-path">Link antigo ou quebrado</label>
        <input class="form-control" id="source-path" name="source_path" maxlength="1000" placeholder="/noticia/link-antigo" value="<?= e(is_string($_GET['source'] ?? null) ? $_GET['source'] : '') ?>" required>
        <p class="field-hint">Cole o link completo deste site ou o caminho. Use endereços sem ? ou #.</p>
        <label class="form-label mt-3" for="redirect-news">Matéria publicada de destino</label>
        <select class="form-select" id="redirect-news" name="news_id" required>
            <option value="">Selecione a matéria</option>
            <?php foreach ($articles as $article): ?>
                <option value="<?= e((string) $article['id']) ?>"><?= e($article['title'] . ' — /noticia/' . $article['slug']) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="field-hint">Para trocar o destino de um link cadastrado, informe o mesmo link e escolha outra matéria. O destino acompanha futuras alterações no link da matéria.</p>
        <button class="btn btn-primary mt-3" <?= !$articles ? 'disabled' : '' ?>>Salvar redirecionamento</button>
        <?php if (!$articles): ?><p>Não há matérias publicadas disponíveis.</p><?php endif; ?>
    </form>
</section>
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
