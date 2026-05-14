<?php
$blocks = $blocks ?? [];
$editingBlock = $editingBlock ?? null;
$canManage = $canManage ?? false;
$playlist = $playlist ?? [];
$modules = $modules ?? [];
$playlistByModule = [];
$moduleIds = array_map(fn (array $module): int => (int) $module['id'], $modules);
$currentIndex = null;

foreach ($playlist as $index => $playlistLesson) {
    if ((int) $playlistLesson['id'] === (int) $lesson['id']) {
        $currentIndex = $index;
    }

    $key = !empty($playlistLesson['module_id']) && in_array((int) $playlistLesson['module_id'], $moduleIds, true) ? (string) $playlistLesson['module_id'] : 'none';
    $playlistByModule[$key][] = $playlistLesson;
}

$previousLesson = $currentIndex !== null && isset($playlist[$currentIndex - 1]) ? $playlist[$currentIndex - 1] : null;
$nextLesson = $currentIndex !== null && isset($playlist[$currentIndex + 1]) ? $playlist[$currentIndex + 1] : null;
$blockAction = $editingBlock
    ? url('/admin/education/block/update?id=' . $editingBlock['id'])
    : url('/admin/education/block?id=' . $lesson['id']);

$embed = function (?string $url): ?string {
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }

    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $match)) {
        return 'https://www.youtube.com/embed/' . $match[1];
    }

    return $url;
};
?>

<div class="page-heading">
    <div>
        <p><?= e($course['title'] ?? 'Curso') ?></p>
        <h1><?= e($lesson['title']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education/course?id=' . $lesson['course_id'])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Voltar ao curso</a>
</div>

<section class="education-player-layout">
    <aside class="education-playlist-sidebar">
        <div class="education-playlist-title">
            <span>Playlist</span>
            <strong><?= e($course['title'] ?? 'Curso') ?></strong>
        </div>
        <div class="education-sidebar-scroll">
            <?php foreach ($modules as $module): ?>
                <?php $moduleLessons = $playlistByModule[(string) $module['id']] ?? []; ?>
                <section class="education-sidebar-module">
                    <h2><?= e($module['title']) ?></h2>
                    <?php foreach ($moduleLessons as $playlistLesson): ?>
                        <a class="<?= (int) $playlistLesson['id'] === (int) $lesson['id'] ? 'active' : '' ?>" href="<?= e(url('/admin/education/lesson?id=' . $playlistLesson['id'])) ?>">
                            <i class="bi <?= !empty($playlistLesson['completed_at']) ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                            <span><?= e($playlistLesson['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$moduleLessons): ?>
                        <small>Nenhuma aula neste módulo.</small>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <?php if (!empty($playlistByModule['none'])): ?>
                <section class="education-sidebar-module">
                    <h2>Sem módulo</h2>
                    <?php foreach ($playlistByModule['none'] as $playlistLesson): ?>
                        <a class="<?= (int) $playlistLesson['id'] === (int) $lesson['id'] ? 'active' : '' ?>" href="<?= e(url('/admin/education/lesson?id=' . $playlistLesson['id'])) ?>">
                            <i class="bi <?= !empty($playlistLesson['completed_at']) ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                            <span><?= e($playlistLesson['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </aside>

    <article class="education-content-stack">
        <?php if (!empty($lesson['description'])): ?>
            <section class="panel education-lesson-description">
                <h2>Sobre esta aula</h2>
                <p><?= nl2br(e($lesson['description'])) ?></p>
            </section>
        <?php endif; ?>

        <?php if (!empty($videoEmbedUrl)): ?>
            <section class="panel education-block-card">
                <div class="education-block-heading">
                    <span class="education-block-type"><i class="bi bi-play-circle" aria-hidden="true"></i> Vídeo principal</span>
                    <strong><?= e($lesson['title']) ?></strong>
                </div>
                <div class="education-video-frame">
                    <?php if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $videoEmbedUrl)): ?>
                        <video src="<?= e(media_url($videoEmbedUrl)) ?>" controls></video>
                    <?php else: ?>
                        <iframe src="<?= e($videoEmbedUrl) ?>" title="<?= e($lesson['title']) ?>" allowfullscreen></iframe>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php foreach ($blocks as $block): ?>
            <?php
            $type = (string) ($block['type'] ?? 'text');
            $blockTitle = $block['title'] ?: match ($type) {
                'video' => 'Vídeo da aula',
                'file' => 'Arquivo para baixar',
                default => 'Material da aula',
            };
            $media = $embed($block['media_url'] ?? '');
            ?>
            <section class="panel education-block-card">
                <div class="education-block-heading">
                    <span class="education-block-type">
                        <?php if ($type === 'video'): ?>
                            <i class="bi bi-play-circle" aria-hidden="true"></i> Vídeo
                        <?php elseif ($type === 'file'): ?>
                            <i class="bi bi-download" aria-hidden="true"></i> Arquivo
                        <?php else: ?>
                            <i class="bi bi-card-text" aria-hidden="true"></i> Texto
                        <?php endif; ?>
                    </span>
                    <strong><?= e($blockTitle) ?></strong>
                </div>

                <?php if ($type === 'video' && $media): ?>
                    <div class="education-video-frame">
                        <?php if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $media)): ?>
                            <video src="<?= e(media_url($media)) ?>" controls></video>
                        <?php else: ?>
                            <iframe src="<?= e($media) ?>" title="<?= e($blockTitle) ?>" allowfullscreen></iframe>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($block['content'])): ?>
                    <div class="education-block-text"><?= nl2br(e($block['content'])) ?></div>
                <?php endif; ?>

                <?php if ($type === 'file' && !empty($block['file_path'])): ?>
                    <a class="btn btn-outline-primary icon-btn education-download-btn" href="<?= e(url('/admin/education/block/download?id=' . $block['id'])) ?>">
                        <i class="bi bi-download" aria-hidden="true"></i>
                        Baixar arquivo
                    </a>
                <?php elseif ($type === 'file' && !empty($block['media_url'])): ?>
                    <a class="btn btn-outline-primary icon-btn education-download-btn" href="<?= e(media_url($block['media_url'])) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        Abrir arquivo
                    </a>
                <?php endif; ?>

                <?php if ($canManage): ?>
                    <div class="education-block-actions">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'] . '&block_id=' . $block['id'])) ?>">Editar</a>
                        <form class="inline-form" method="post" action="<?= e(url('/admin/education/block/delete?id=' . $block['id'])) ?>" onsubmit="return confirm('Remover este material?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Remover</button>
                        </form>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <?php if (!$blocks && empty($videoEmbedUrl) && empty($lesson['description'])): ?>
            <div class="empty-state">Esta aula ainda não tem materiais cadastrados.</div>
        <?php endif; ?>

        <nav class="education-player-nav" aria-label="Navegação da playlist">
            <?php if ($previousLesson): ?>
                <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $previousLesson['id'])) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i>Aula anterior</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <a href="<?= e(url('/admin/education/course?id=' . $lesson['course_id'])) ?>">Voltar para o curso</a>
            <?php if ($nextLesson): ?>
                <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/education/lesson?id=' . $nextLesson['id'])) ?>">Próxima aula<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </nav>
    </article>

    <aside class="education-side-stack">
        <?php if ($canManage): ?>
            <section class="panel education-material-form-panel">
                <div class="section-heading">
                    <h2><?= $editingBlock ? 'Editar material' : 'Adicionar material' ?></h2>
                    <span>Texto, vídeo ou arquivo para baixar</span>
                </div>
                <form method="post" action="<?= e($blockAction) ?>" enctype="multipart/form-data" class="education-material-form">
                    <?= csrf_field() ?>
                    <label>
                        <span>Tipo</span>
                        <select class="form-select" name="type">
                            <option value="text" <?= selected((string) ($editingBlock['type'] ?? 'text'), 'text') ?>>Texto</option>
                            <option value="video" <?= selected((string) ($editingBlock['type'] ?? ''), 'video') ?>>Vídeo</option>
                            <option value="file" <?= selected((string) ($editingBlock['type'] ?? ''), 'file') ?>>Arquivo</option>
                        </select>
                    </label>
                    <label>
                        <span>Título</span>
                        <input class="form-control" name="title" maxlength="180" value="<?= e($editingBlock['title'] ?? '') ?>" placeholder="Ex.: Aula 1 - Introdução">
                    </label>
                    <label>
                        <span>Ordem</span>
                        <input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingBlock['sort_order'] ?? 0)) ?>">
                    </label>
                    <label>
                        <span>Link do vídeo ou arquivo</span>
                        <input class="form-control" name="media_url" value="<?= e($editingBlock['media_url'] ?? '') ?>" placeholder="YouTube, Vimeo ou link direto">
                    </label>
                    <label>
                        <span>Enviar arquivo</span>
                        <input class="form-control" name="block_file" type="file">
                    </label>
                    <?php if ($editingBlock && !empty($editingBlock['file_path'])): ?>
                        <div class="education-current-file">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>
                            Arquivo atual mantido se nenhum novo for enviado.
                        </div>
                    <?php endif; ?>
                    <label>
                        <span>Texto / descrição</span>
                        <textarea class="form-control" name="content" rows="5" placeholder="Escreva o texto, instruções ou descrição do material"><?= e($editingBlock['content'] ?? '') ?></textarea>
                    </label>
                    <button class="btn btn-primary icon-btn w-100">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <?= $editingBlock ? 'Atualizar material' : 'Adicionar material' ?>
                    </button>
                    <?php if ($editingBlock): ?>
                        <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/education/lesson?id=' . $lesson['id'])) ?>">Cancelar edição</a>
                    <?php endif; ?>
                </form>
            </section>
        <?php endif; ?>

        <section class="panel education-progress-panel">
            <h2>Progresso</h2>
            <p>Marque a aula quando terminar de estudar.</p>
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
        </section>
    </aside>
</section>
