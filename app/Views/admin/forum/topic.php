<div class="forum-heading">
    <div>
        <a class="forum-back-link" href="<?= e(url('/admin/forum/area?area=' . $topic['area_slug'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e($topic['area_name']) ?></a>
        <p><?= e($topic['status'] === 'closed' ? 'Tópico fechado' : 'Tópico aberto') ?></p>
        <h1><?= e($topic['title']) ?></h1>
    </div>
    <div class="forum-heading-actions">
        <?php if ($canModerate): ?>
            <form method="post" action="<?= e(url('/admin/forum/moderate?id=' . $topic['id'])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= $topic['status'] === 'closed' ? 'open' : 'closed' ?>">
                <button class="btn btn-outline-secondary icon-btn">
                    <i class="bi bi-lock<?= $topic['status'] === 'closed' ? '-fill' : '' ?>" aria-hidden="true"></i>
                    <?= $topic['status'] === 'closed' ? 'Reabrir' : 'Fechar' ?>
                </button>
            </form>
        <?php endif; ?>
        <?php if ($canPost): ?>
            <button class="btn btn-primary icon-btn" type="button" data-modal-open="forum-reply-modal">
                <i class="bi bi-reply" aria-hidden="true"></i>
                Responder
            </button>
        <?php endif; ?>
    </div>
</div>

<section class="forum-thread">
    <article class="forum-message forum-message-topic">
        <div class="forum-avatar"><?= e(strtoupper(substr((string) ($topic['user_name'] ?? 'U'), 0, 1))) ?></div>
        <div class="forum-message-body">
            <header>
                <div>
                    <strong><?= e($topic['user_name']) ?></strong>
                    <span>Criado em <?= e((string) $topic['created_at']) ?></span>
                </div>
                <span class="state-pill <?= !empty($topic['is_public']) ? 'is-active' : 'is-muted' ?>"><?= !empty($topic['is_public']) ? 'Público autorizado' : 'Restrito' ?></span>
            </header>
            <div class="forum-message-text"><?= nl2br(e($topic['body'])) ?></div>
            <?php if (!empty($attachments['topic'])): ?>
                <div class="forum-attachments">
                    <?php foreach ($attachments['topic'] as $attachment): ?>
                        <a href="<?= e(url('/admin/forum/attachment?id=' . $attachment['id'])) ?>">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>
                            <?= e($attachment['original_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>

    <div class="forum-reply-divider">
        <span><?= e((string) count($replies)) ?> resposta(s)</span>
    </div>

    <?php foreach ($replies as $reply): ?>
        <article class="forum-message">
            <div class="forum-avatar"><?= e(strtoupper(substr((string) ($reply['user_name'] ?? 'U'), 0, 1))) ?></div>
            <div class="forum-message-body">
                <header>
                    <div>
                        <strong><?= e($reply['user_name']) ?></strong>
                        <span><?= e((string) $reply['created_at']) ?></span>
                    </div>
                    <?php if ($canModerate): ?>
                        <form method="post" action="<?= e(url('/admin/forum/reply/delete?id=' . $topic['id'] . '&reply_id=' . $reply['id'])) ?>" onsubmit="return confirm('Remover esta resposta?');">
                            <?= csrf_field() ?>
                            <button class="forum-text-button">Remover</button>
                        </form>
                    <?php endif; ?>
                </header>
                <div class="forum-message-text"><?= nl2br(e($reply['body'])) ?></div>
                <?php if (!empty($attachments['replies'][(int) $reply['id']])): ?>
                    <div class="forum-attachments">
                        <?php foreach ($attachments['replies'][(int) $reply['id']] as $attachment): ?>
                            <a href="<?= e(url('/admin/forum/attachment?id=' . $attachment['id'])) ?>">
                                <i class="bi bi-paperclip" aria-hidden="true"></i>
                                <?= e($attachment['original_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$replies): ?>
        <div class="forum-empty">
            <i class="bi bi-reply" aria-hidden="true"></i>
            <strong>Nenhuma resposta ainda</strong>
            <span>Use o botão Responder para continuar a conversa.</span>
        </div>
    <?php endif; ?>
</section>

<?php if ($canPost): ?>
    <div class="forum-reply-dock">
        <button class="btn btn-primary icon-btn" type="button" data-modal-open="forum-reply-modal">
            <i class="bi bi-reply" aria-hidden="true"></i>
            Responder tópico
        </button>
    </div>

    <div class="forum-modal" id="forum-reply-modal" aria-hidden="true">
        <div class="forum-modal-backdrop" data-modal-close></div>
        <section class="forum-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="forum-reply-title">
            <header>
                <div>
                    <span>Responder</span>
                    <h2 id="forum-reply-title"><?= e($topic['title']) ?></h2>
                </div>
                <button type="button" class="forum-icon-button" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <form method="post" action="<?= e(url('/admin/forum/reply?id=' . $topic['id'])) ?>" enctype="multipart/form-data" class="forum-compose-form">
                <?= csrf_field() ?>
                <label class="forum-compose-wide">
                    <span>Mensagem</span>
                    <textarea class="form-control" name="body" rows="7" required autofocus></textarea>
                </label>
                <label class="forum-compose-wide">
                    <span>Anexos</span>
                    <input class="form-control" name="attachments[]" type="file" multiple>
                </label>
                <footer>
                    <button class="btn btn-outline-secondary" type="button" data-modal-close>Cancelar</button>
                    <button class="btn btn-primary">Enviar resposta</button>
                </footer>
            </form>
        </section>
    </div>
<?php endif; ?>
