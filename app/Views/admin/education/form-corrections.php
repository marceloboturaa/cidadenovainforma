<?php
$responses = $responses ?? [];
$responseDetails = $responseDetails ?? [];
$status = $status ?? 'pending';
$page = max(1, (int) ($page ?? 1));
$hasMore = !empty($hasMore);
$statusLabels = [
    'pending' => 'Pendente',
    'corrected' => 'Corrigido',
    'redo' => 'Refazer',
];
$statusFilterLabels = [
    'pending' => 'Pendentes',
    'redo' => 'Para refazer',
    'corrected' => 'Corrigidos',
    'all' => 'Todos',
];
?>

<div class="page-heading">
    <div>
        <p>Espaço do professor</p>
        <h1>Corrigir formulários</h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-secondary icon-btn" href="<?= e(url('/admin/education')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Meu ensino</a>
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/manage')) ?>"><i class="bi bi-house-door" aria-hidden="true"></i>Escola</a>
    </div>
</div>

<section class="panel education-form-corrections-panel">
    <div class="education-correction-tabs" role="list" aria-label="Filtros de correção">
        <?php foreach ($statusFilterLabels as $value => $label): ?>
            <a class="<?= $status === $value ? 'active' : '' ?>" href="<?= e(url('/admin/education/form-corrections?status=' . $value)) ?>" role="listitem"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="education-correction-list">
        <?php foreach ($responses as $response): ?>
            <?php
                $responseId = (int) ($response['id'] ?? 0);
                $details = $responseDetails[$responseId] ?? ['questions' => [], 'answers' => []];
                $questions = $details['questions'] ?? [];
                $answers = $details['answers'] ?? [];
                $currentStatus = (string) ($response['correction_status'] ?? 'pending');
                $courseUrl = !empty($response['lesson_id'])
                    ? url('/admin/education/lesson?id=' . $response['lesson_id'] . '#lesson-forms')
                    : url('/admin/education/course?id=' . $response['course_id'] . '#course-forms');
                $returnTo = '/admin/education/form-corrections?status=' . $status . ($status === 'all' ? '&page=' . $page : '') . '#response-' . $responseId;
            ?>
            <article class="education-correction-card status-<?= e($currentStatus) ?>" id="response-<?= e((string) $responseId) ?>">
                <header>
                    <div>
                        <span class="education-kicker"><?= e($response['course_title'] ?? 'Curso') ?><?= !empty($response['lesson_title']) ? ' / ' . e($response['lesson_title']) : '' ?></span>
                        <h2><?= e($response['form_title'] ?? 'Formulário') ?></h2>
                        <?php if (!empty($response['form_description'])): ?><p><?= e($response['form_description']) ?></p><?php endif; ?>
                    </div>
                    <div class="education-correction-meta">
                        <span class="state-pill <?= $currentStatus === 'corrected' ? 'is-active' : 'is-muted' ?>"><?= e($statusLabels[$currentStatus] ?? 'Pendente') ?></span>
                        <small>Enviado em <?= e($response['updated_at'] ?? $response['created_at'] ?? '') ?></small>
                    </div>
                </header>

                <div class="education-correction-student">
                    <div>
                        <strong><?= e($response['student_name'] ?? 'Estudante') ?></strong>
                        <?php if (!empty($response['student_email'])): ?><span><?= e($response['student_email']) ?></span><?php endif; ?>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary icon-btn" href="<?= e($courseUrl) ?>"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Abrir no curso</a>
                </div>

                <div class="education-correction-answers">
                    <?php foreach ($questions as $question): ?>
                        <section>
                            <strong><?= e($question['question'] ?? '') ?></strong>
                            <p><?= nl2br(e($answers[(int) ($question['id'] ?? 0)] ?? '-')) ?></p>
                        </section>
                    <?php endforeach; ?>
                    <?php if (!$questions): ?>
                        <div class="empty-state">Nenhuma pergunta encontrada para este formulário.</div>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?= e(url('/admin/education/form/grade?response_id=' . $responseId)) ?>" class="education-correction-form education-correction-panel-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <label class="form-label">
                        Situação
                        <select class="form-control" name="correction_status">
                            <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Pendente</option>
                            <option value="corrected" <?= $currentStatus === 'corrected' ? 'selected' : '' ?>>Corrigido</option>
                            <option value="redo" <?= $currentStatus === 'redo' ? 'selected' : '' ?>>Refazer</option>
                        </select>
                    </label>
                    <label class="form-label">
                        Nota do aluno
                        <input class="form-control" name="grade" maxlength="40" value="<?= e($response['grade'] ?? '') ?>" placeholder="Ex.: 8,5 ou Aprovado">
                    </label>
                    <label class="form-label grid-span-2">
                        Resposta/comentário do professor
                        <textarea class="form-control" name="feedback" rows="4" placeholder="Comentário que aparecerá para o aluno"><?= e($response['feedback'] ?? '') ?></textarea>
                    </label>
                    <div class="education-correction-form-footer grid-span-2">
                        <small>
                            <?php if (!empty($response['corrector_name'])): ?>
                                Última correção: <?= e($response['corrector_name']) ?><?= !empty($response['corrected_at']) ? ' em ' . e($response['corrected_at']) : '' ?>
                            <?php else: ?>
                                Ainda sem correção salva.
                            <?php endif; ?>
                        </small>
                        <button class="btn btn-primary icon-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i>Salvar correção</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>

        <?php if (!$responses): ?>
            <div class="empty-state">Nenhum formulário encontrado neste filtro.</div>
        <?php endif; ?>

        <?php if ($status === 'all' && $hasMore): ?>
            <div class="education-correction-more">
                <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/admin/education/form-corrections?status=all&page=' . ($page + 1))) ?>"><i class="bi bi-plus-circle" aria-hidden="true"></i>Mostrar mais 10</a>
            </div>
        <?php endif; ?>
    </div>
</section>
