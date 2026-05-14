<div class="forum-heading">
    <div>
        <a class="forum-back-link" href="<?= e(url('/admin/forum')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Fóruns</a>
        <p><?= !empty($area['is_public']) ? 'Público autorizado' : 'Área privada' ?></p>
        <h1><?= e($area['name']) ?></h1>
        <?php if (!empty($area['description'])): ?>
            <span><?= e($area['description']) ?></span>
        <?php endif; ?>
    </div>
    <div class="forum-heading-actions">
        <?php if ($canModerate): ?>
            <button class="btn btn-outline-secondary icon-btn" type="button" data-modal-open="forum-category-modal">
                <i class="bi bi-folder-plus" aria-hidden="true"></i>
                Categoria
            </button>
        <?php endif; ?>
        <?php if ($canPost): ?>
            <button class="btn btn-primary icon-btn" type="button" data-modal-open="forum-topic-modal">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                Novo tópico
            </button>
        <?php endif; ?>
    </div>
</div>

<section class="forum-summary-strip" aria-label="Resumo do fórum">
    <article>
        <span>Tópicos</span>
        <strong><?= e((string) count($topics)) ?></strong>
    </article>
    <article>
        <span>Categorias</span>
        <strong><?= e((string) count($categories)) ?></strong>
    </article>
    <article>
        <span>Acesso</span>
        <strong><?= !empty($area['is_public']) ? 'Público' : 'Privado' ?></strong>
    </article>
</section>

<section class="forum-topic-board">
    <div class="forum-board-head">
        <div>
            <h2>Conversas</h2>
            <p>Tópicos recentes desta área</p>
        </div>
    </div>

    <div class="forum-topic-list">
        <?php foreach ($topics as $topic): ?>
            <article class="forum-topic-item">
                <a class="forum-topic-main" href="<?= e(url('/admin/forum/topic?id=' . $topic['id'])) ?>">
                    <span class="forum-topic-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                    <span>
                        <strong><?= e($topic['title']) ?></strong>
                        <small><?= e(text_excerpt($topic['body'] ?? '', 150)) ?></small>
                    </span>
                </a>
                <div class="forum-topic-meta">
                    <span><?= e($topic['category_name'] ?? 'Sem categoria') ?></span>
                    <span><?= e($topic['user_name']) ?></span>
                    <span><?= e((string) $topic['reply_count']) ?> resposta(s)</span>
                </div>
                <div class="forum-topic-actions">
                    <a class="btn btn-sm btn-primary icon-btn" href="<?= e(url('/admin/forum/topic?id=' . $topic['id'])) ?>">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        Abrir
                    </a>
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
            <div class="forum-empty">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                <strong>Nenhum tópico ainda</strong>
                <span>Quando alguém iniciar uma conversa, ela aparecerá aqui.</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($canPost): ?>
    <div class="forum-modal" id="forum-topic-modal" aria-hidden="true">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="forum-topic-title">
            <header>
                <div>
                    <span>Novo tópico</span>
                    <h2 id="forum-topic-title"><?= e($area['name']) ?></h2>
                </div>
                <button type="button" class="forum-icon-button" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <form method="post" action="<?= e(url('/admin/forum/topic?area=' . $area['slug'])) ?>" enctype="multipart/form-data" class="forum-compose-form">
                <?= csrf_field() ?>
                <label>
                    <span>Título</span>
                    <input class="form-control" name="title" maxlength="180" required>
                </label>
                <label>
                    <span>Categoria</span>
                    <select class="form-select" name="category_id">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="forum-compose-wide">
                    <span>Mensagem</span>
                    <textarea class="form-control" name="body" rows="6" data-tinymce required></textarea>
                </label>
                <label>
                    <span>Anexos</span>
                    <input class="form-control" name="attachments[]" type="file" multiple>
                </label>
                <?php if ($canModerate): ?>
                    <label class="forum-check-line">
                        <input type="checkbox" name="is_public" value="1">
                        <span>Marcar como público autorizado</span>
                    </label>
                <?php endif; ?>
                <footer>
                    <button class="btn btn-outline-secondary" type="button" data-modal-close>Cancelar</button>
                    <button class="btn btn-primary">Publicar tópico</button>
                </footer>
            </form>
        </section>
    </div>
<?php endif; ?>

<?php if ($canModerate): ?>
    <div class="forum-modal" id="forum-category-modal" aria-hidden="true">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog forum-modal-small" role="dialog" aria-modal="true" aria-labelledby="forum-category-title">
            <header>
                <div>
                    <span>Moderação</span>
                    <h2 id="forum-category-title">Nova categoria</h2>
                </div>
                <button type="button" class="forum-icon-button" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <form method="post" action="<?= e(url('/admin/forum/category?area=' . $area['slug'])) ?>" class="forum-compose-form">
                <?= csrf_field() ?>
                <label class="forum-compose-wide">
                    <span>Nome</span>
                    <input class="form-control" name="name" maxlength="120" required>
                </label>
                <footer>
                    <button class="btn btn-outline-secondary" type="button" data-modal-close>Cancelar</button>
                    <button class="btn btn-primary">Adicionar</button>
                </footer>
            </form>
        </section>
    </div>
<?php endif; ?>
