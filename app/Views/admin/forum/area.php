<div class="page-heading">
    <div>
        <p><?= !empty($area['is_public']) ? 'Público autorizado' : 'Área privada' ?></p>
        <h1><?= e($area['name']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/forum')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
</div>

<?php if ($canPost): ?>
    <section class="panel">
        <div class="section-heading">
            <h2>Novo tópico</h2>
            <span>Crie uma conversa nesta área</span>
        </div>
        <form method="post" action="<?= e(url('/admin/forum/topic?area=' . $area['slug'])) ?>" enctype="multipart/form-data" class="education-lesson-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Título</label>
                <input class="form-control" name="title" maxlength="180" required>
            </div>
            <div>
                <label class="form-label">Categoria</label>
                <select class="form-select" name="category_id">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid-span-2">
                <label class="form-label">Mensagem</label>
                <textarea class="form-control" name="body" rows="4" required></textarea>
            </div>
            <div>
                <label class="form-label">Anexos</label>
                <input class="form-control" name="attachments[]" type="file" multiple>
            </div>
            <?php if ($canModerate): ?>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_public" value="1">
                    <span class="form-check-label">Marcar como público autorizado</span>
                </label>
            <?php endif; ?>
            <div class="form-action-cell">
                <button class="btn btn-primary w-100">Publicar tópico</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if ($canModerate): ?>
    <section class="panel">
        <div class="section-heading">
            <h2>Categorias</h2>
            <span>Moderação</span>
        </div>
        <form method="post" action="<?= e(url('/admin/forum/category?area=' . $area['slug'])) ?>" class="education-lesson-form">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Nova categoria</label>
                <input class="form-control" name="name" maxlength="120" required>
            </div>
            <div class="form-action-cell">
                <button class="btn btn-outline-primary w-100">Adicionar</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="section-heading">
        <h2>Tópicos</h2>
        <span><?= e((string) count($topics)) ?> conversa(s)</span>
    </div>
    <div class="education-lesson-list">
        <?php foreach ($topics as $topic): ?>
            <article class="education-lesson-row">
                <div class="education-lesson-number"><i class="bi bi-chat-dots" aria-hidden="true"></i></div>
                <div>
                    <h3><?= e($topic['title']) ?></h3>
                    <p><?= e(text_excerpt($topic['body'] ?? '', 150)) ?></p>
                    <small>
                        <?= e($topic['category_name'] ?? 'Sem categoria') ?>
                        · <?= e($topic['user_name']) ?>
                        · <?= e((string) $topic['reply_count']) ?> resposta(s)
                    </small>
                </div>
                <div class="education-lesson-actions">
                    <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/forum/topic?id=' . $topic['id'])) ?>"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Abrir</a>
                    <?php if ($canModerate): ?>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/forum/moderate?id=' . $topic['id'])) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= $topic['status'] === 'closed' ? 'open' : 'closed' ?>">
                            <button class="btn btn-sm btn-outline-secondary"><?= $topic['status'] === 'closed' ? 'Reabrir' : 'Fechar' ?></button>
                        </form>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/forum/moderate?id=' . $topic['id'])) ?>" onsubmit="return confirm('Ocultar este tópico?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="hidden">
                            <button class="btn btn-sm btn-outline-danger">Ocultar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$topics): ?>
            <div class="empty-state">Nenhum tópico criado nesta área.</div>
        <?php endif; ?>
    </div>
</section>
