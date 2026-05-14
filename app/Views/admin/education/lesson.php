<div class="page-heading">
    <div>
        <p><?= e($course['title'] ?? 'Curso') ?></p>
        <h1><?= e($lesson['title']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $lesson['course_id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
</div>

<section class="education-watch-layout">
    <article class="panel education-watch-panel">
        <div class="education-video-frame">
            <?php if (!empty($videoEmbedUrl)): ?>
                <?php if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $videoEmbedUrl)): ?>
                    <video src="<?= e(media_url($videoEmbedUrl)) ?>" controls></video>
                <?php else: ?>
                    <iframe src="<?= e($videoEmbedUrl) ?>" title="<?= e($lesson['title']) ?>" allowfullscreen></iframe>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">Esta aula ainda não tem vídeo cadastrado.</div>
            <?php endif; ?>
        </div>
        <?php if (!empty($lesson['description'])): ?>
            <div class="education-lesson-description">
                <h2>Sobre esta aula</h2>
                <p><?= nl2br(e($lesson['description'])) ?></p>
            </div>
        <?php endif; ?>
    </article>

    <aside class="panel education-progress-panel">
        <h2>Progresso</h2>
        <p>Marque a aula quando terminar de assistir.</p>
        <form method="post" action="<?= e(url('/admin/education/progress?id=' . $lesson['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="completed" value="1">
            <button class="btn btn-success icon-btn w-100"><i class="bi bi-check2-circle" aria-hidden="true"></i>Marcar como concluída</button>
        </form>
        <form method="post" action="<?= e(url('/admin/education/progress?id=' . $lesson['id'])) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="completed" value="0">
            <button class="btn btn-outline-secondary icon-btn w-100"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Marcar como pendente</button>
        </form>
    </aside>
</section>
