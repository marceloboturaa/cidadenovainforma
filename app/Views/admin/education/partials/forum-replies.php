<?php
$topicReplies = $topicReplies ?? [];
$topic = $topic ?? [];
$course = $course ?? [];
$canManage = $canManage ?? false;
$studentPreview = $studentPreview ?? false;
$currentUserId = (int) (current_user()['id'] ?? 0);
$courseTeacherUserId = (int) ($course['teacher_user_id'] ?? 0);
$knownReplyIds = [];
$repliesByParent = [];

foreach ($topicReplies as $reply) {
    $knownReplyIds[(int) ($reply['id'] ?? 0)] = true;
}

foreach ($topicReplies as $reply) {
    $parentId = (int) ($reply['parent_reply_id'] ?? 0);
    if ($parentId > 0 && !isset($knownReplyIds[$parentId])) {
        $parentId = 0;
    }
    $repliesByParent[$parentId][] = $reply;
}

$replyCounter = 0;
$renderForumReplies = function (int $parentId = 0, int $depth = 0) use (&$renderForumReplies, &$replyCounter, $repliesByParent, $topic, $canManage, $studentPreview, $currentUserId, $courseTeacherUserId): void {
    foreach ($repliesByParent[$parentId] ?? [] as $reply) {
        $replyCounter++;
        $replyHidden = empty($reply['active']);
        $canEditReply = !$studentPreview && !$replyHidden && ($canManage || (int) ($reply['user_id'] ?? 0) === $currentUserId);
        $isCourseTeacherReply = $courseTeacherUserId > 0 && (int) ($reply['user_id'] ?? 0) === $courseTeacherUserId;
        ?>
        <div class="education-forum-reply-thread <?= $depth > 0 ? 'is-child-reply' : '' ?>">
            <div class="education-forum-reply-card reply-tone-<?= e((string) ((((int) $replyCounter - 1) % 6) + 1)) ?> <?= $replyHidden ? 'is-hidden-reply' : '' ?>">
                <div class="education-forum-reply-head">
                    <strong>
                        <?= e($reply['user_name'] ?? 'Usuario') ?>
                        <?php if ($isCourseTeacherReply): ?>
                            <span class="education-teacher-reply-badge">Professor do curso respondendo</span>
                        <?php endif; ?>
                        <?php if ($replyHidden): ?>
                            <span class="education-hidden-badge">Oculto para estudantes</span>
                        <?php endif; ?>
                        <?php if (!empty($reply['parent_user_name'])): ?>
                            <span class="education-reply-parent">Em resposta a <?= e($reply['parent_user_name']) ?></span>
                        <?php endif; ?>
                    </strong>
                    <div class="education-forum-reply-actions">
                    <?php if (!$studentPreview && !$replyHidden): ?>
                        <details class="education-forum-reply-interaction">
                            <summary class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-reply" aria-hidden="true"></i>Responder</summary>
                            <form method="post" action="<?= e(url('/admin/education/forum/reply?topic_id=' . $topic['id'])) ?>" class="education-forum-reply-form is-inline-reply">
                                <?= csrf_field() ?>
                                <input type="hidden" name="parent_reply_id" value="<?= e((string) $reply['id']) ?>">
                                <textarea class="form-control" name="body" rows="2" placeholder="Responder <?= e($reply['user_name'] ?? 'este comentario') ?>" required></textarea>
                                <button class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar</button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($canEditReply): ?>
                        <details class="education-forum-reply-interaction">
                            <summary class="btn btn-sm btn-outline-secondary icon-btn"><i class="bi bi-pencil-square" aria-hidden="true"></i>Editar</summary>
                            <form method="post" action="<?= e(url('/admin/education/forum/reply/update?reply_id=' . $reply['id'])) ?>" class="education-forum-reply-form is-inline-reply">
                                <?= csrf_field() ?>
                                <textarea class="form-control" name="body" rows="2" required><?= e($reply['body'] ?? '') ?></textarea>
                                <button class="btn btn-sm btn-outline-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar</button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                        <?php if ($replyHidden): ?>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/forum/reply/restore?reply_id=' . $reply['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success icon-btn"><i class="bi bi-eye" aria-hidden="true"></i>Restaurar</button>
                            </form>
                        <?php else: ?>
                            <form class="inline-form" method="post" action="<?= e(url('/admin/education/forum/reply/delete?reply_id=' . $reply['id'])) ?>" onsubmit="return confirm('Ocultar este comentario para estudantes?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger icon-btn"><i class="bi bi-eye-slash" aria-hidden="true"></i>Ocultar</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div>
                </div>
                <div><?= article_html($reply['body'] ?? '') ?></div>
            </div>
            <?php if (!empty($repliesByParent[(int) ($reply['id'] ?? 0)])): ?>
                <div class="education-forum-reply-children">
                    <?php $renderForumReplies((int) $reply['id'], $depth + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
};
?>

<?php if ($topicReplies): ?>
    <div class="education-forum-replies">
        <?php $renderForumReplies(); ?>
    </div>
<?php endif; ?>
