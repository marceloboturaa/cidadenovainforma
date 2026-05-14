<div class="page-heading">
    <div>
        <p><?= e($topic['area_name']) ?> · <?= e($topic['status'] === 'closed' ? 'Fechado' : 'Aberto') ?></p>
        <h1><?= e($topic['title']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/forum/area?area=' . $topic['area_slug'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar</a>
</div>

<section class="panel">
    <div class="document-row">
        <div class="document-file-icon"><i class="bi bi-person" aria-hidden="true"></i></div>
        <div class="document-main">
            <div class="document-title-line">
                <h3><?= e($topic['user_name']) ?></h3>
                <span class="state-pill <?= !empty($topic['is_public']) ? 'is-active' : 'is-muted' ?>"><?= !empty($topic['is_public']) ? 'Público autorizado' : 'Restrito' ?></span>
            </div>
            <p><?= nl2br(e($topic['body'])) ?></p>
            <small>Criado em <?= e((string) $topic['created_at']) ?></small>
            <?php if (!empty($attachments['topic'])): ?>
                <div class="document-actions mt-3">
                    <?php foreach ($attachments['topic'] as $attachment): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/forum/attachment?id=' . $attachment['id'])) ?>">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>
                            <?= e($attachment['original_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-heading">
        <h2>Respostas</h2>
        <span><?= e((string) count($replies)) ?> resposta(s)</span>
    </div>
    <div class="education-lesson-list">
        <?php foreach ($replies as $reply): ?>
            <article class="education-lesson-row">
                <div class="education-lesson-number"><i class="bi bi-reply" aria-hidden="true"></i></div>
                <div>
                    <h3><?= e($reply['user_name']) ?></h3>
                    <p><?= nl2br(e($reply['body'])) ?></p>
                    <small><?= e((string) $reply['created_at']) ?></small>
                    <?php foreach (($attachments['replies'][(int) $reply['id']] ?? []) as $attachment): ?>
                        <div class="mt-2">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/forum/attachment?id=' . $attachment['id'])) ?>">
                                <i class="bi bi-paperclip" aria-hidden="true"></i>
                                <?= e($attachment['original_name']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($canModerate): ?>
                    <div class="education-lesson-actions">
                        <form class="inline-form" method="post" action="<?= e(url('/admin/forum/reply/delete?id=' . $topic['id'] . '&reply_id=' . $reply['id'])) ?>" onsubmit="return confirm('Remover esta resposta?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Remover</button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$replies): ?>
            <div class="empty-state">Nenhuma resposta ainda.</div>
        <?php endif; ?>
    </div>
</section>

<?php if ($canPost): ?>
    <section class="panel">
        <div class="section-heading">
            <h2>Responder</h2>
            <span>Participe da conversa</span>
        </div>
        <form method="post" action="<?= e(url('/admin/forum/reply?id=' . $topic['id'])) ?>" enctype="multipart/form-data" class="education-lesson-form">
            <?= csrf_field() ?>
            <div class="grid-span-2">
                <label class="form-label">Mensagem</label>
                <textarea class="form-control" name="body" rows="4" required></textarea>
            </div>
            <div>
                <label class="form-label">Anexos</label>
                <input class="form-control" name="attachments[]" type="file" multiple>
            </div>
            <div class="form-action-cell">
                <button class="btn btn-primary w-100">Enviar resposta</button>
            </div>
        </form>
    </section>
<?php endif; ?>
