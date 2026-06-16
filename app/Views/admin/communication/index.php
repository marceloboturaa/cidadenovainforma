<div class="communication-shell">
    <section class="communication-hero">
        <div>
            <span>Comunicacao interna</span>
            <h1>Conversas com professores</h1>
            <p>Alunos podem falar com o professor do curso. Professores e gestores acompanham as conversas dos seus estudantes e tambem mantem os canais de eventos.</p>
        </div>
        <form class="communication-start-form" method="post" action="<?= e(url('/admin/communication/start')) ?>">
            <?= csrf_field() ?>
            <div class="communication-channel-tabs" role="group" aria-label="Tipo de conversa">
                <label>
                    <input type="radio" name="channel" value="education" checked data-communication-channel="education">
                    <span><i class="bi bi-mortarboard" aria-hidden="true"></i>Curso</span>
                </label>
                <label>
                    <input type="radio" name="channel" value="event" data-communication-channel="event">
                    <span><i class="bi bi-calendar-event" aria-hidden="true"></i>Evento</span>
                </label>
            </div>
            <label data-communication-panel="education">
                <span><?= !empty($canStartCourseContact) ? 'Aluno e curso' : 'Curso' ?></span>
                <?php if (!empty($canStartCourseContact)): ?>
                    <select class="form-select" name="course_contact">
                        <option value="">Selecionar aluno</option>
                        <?php foreach ($courseContacts as $contact): ?>
                            <option value="<?= e((string) $contact['course_id'] . ':' . (string) $contact['student_user_id']) ?>">
                                <?= e($contact['student_name']) ?> - <?= e($contact['course_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <select class="form-select" name="course_id">
                        <option value="">Selecionar curso</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= e((string) $course['id']) ?>"><?= e($course['title']) ?><?= !empty($course['teacher_name']) ? ' - ' . e($course['teacher_name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </label>
            <label data-communication-panel="event">
                <span>Evento</span>
                <select class="form-select" name="event_id">
                    <option value="">Selecionar evento</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= e((string) $event['id']) ?>"><?= e($event['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-primary icon-btn"><i class="bi bi-chat-dots" aria-hidden="true"></i>Iniciar</button>
        </form>
    </section>

    <section class="communication-board">
        <aside class="communication-list">
            <div class="communication-panel-head">
                <h2>Conversas</h2>
                <span><?= count($conversations) ?></span>
            </div>
            <?php if (!$conversations): ?>
                <div class="communication-empty">
                    <i class="bi bi-inbox" aria-hidden="true"></i>
                    <strong>Nenhuma conversa ainda</strong>
                    <span>Escolha um evento acima para abrir o canal.</span>
                </div>
            <?php endif; ?>
            <?php foreach ($conversations as $conversation): ?>
                <?php $threadType = (string) ($conversation['conversation_type'] ?? 'event'); ?>
                <a class="communication-thread <?= !empty($selectedConversation) && (string) ($selectedConversation['conversation_type'] ?? $selectedType ?? 'event') === $threadType && (int) $selectedConversation['id'] === (int) $conversation['id'] ? 'is-active' : '' ?>" href="<?= e(url('/admin/communication/show?type=' . $threadType . '&id=' . $conversation['id'])) ?>">
                    <em><?= $threadType === 'education' ? 'Curso' : 'Evento' ?></em>
                    <strong><?= e($conversation['context_title'] ?? $conversation['event_title'] ?? $conversation['course_title'] ?? '') ?></strong>
                    <span><?= e($conversation['participant_name']) ?><?= !empty($conversation['responsible_name']) ? ' -> ' . e($conversation['responsible_name']) : '' ?></span>
                    <small><?= e(text_excerpt($conversation['last_message_body'] ?? 'Sem mensagens ainda.', 82)) ?></small>
                </a>
            <?php endforeach; ?>
        </aside>

        <div class="communication-chat">
            <?php if (!$selectedConversation): ?>
                <div class="communication-empty communication-empty-large">
                    <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                    <strong>Selecione uma conversa</strong>
                    <span>As mensagens do evento aparecem aqui.</span>
                </div>
            <?php else: ?>
                <header class="communication-chat-head">
                    <div>
                        <span><?= ($selectedType ?? 'event') === 'education' ? 'Curso' : 'Evento' ?> - <?= e($selectedConversation['context_title'] ?? $selectedConversation['event_title'] ?? $selectedConversation['course_title'] ?? '') ?></span>
                        <h2><?= e($selectedConversation['participant_name']) ?> com <?= e($selectedConversation['responsible_name'] ?: 'responsavel nao definido') ?></h2>
                    </div>
                    <?php if (!empty($selectedConversation['context_at'])): ?>
                        <strong><?= e(date('d/m/Y', strtotime((string) $selectedConversation['context_at']))) ?></strong>
                    <?php endif; ?>
                </header>

                <div class="communication-messages">
                    <?php if (!$messages): ?>
                        <div class="communication-empty">
                            <i class="bi bi-send" aria-hidden="true"></i>
                            <strong>Comece a conversa</strong>
                            <span>Envie a primeira mensagem para alinhar duvidas do evento.</span>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($messages as $message): ?>
                        <?php $mine = (int) $message['sender_user_id'] === (int) (current_user()['id'] ?? 0); ?>
                        <article class="communication-message <?= $mine ? 'is-mine' : '' ?>">
                            <div>
                                <strong><?= e($message['sender_name']) ?></strong>
                                <span><?= e($message['sender_role_name'] ?? '') ?> - <?= e(date('d/m/Y H:i', strtotime((string) $message['created_at']))) ?></span>
                            </div>
                            <p><?= nl2br(e($message['body'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <form class="communication-reply" method="post" action="<?= e(url('/admin/communication/send')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="conversation_id" value="<?= e((string) $selectedConversation['id']) ?>">
                    <input type="hidden" name="conversation_type" value="<?= e((string) ($selectedType ?? $selectedConversation['conversation_type'] ?? 'event')) ?>">
                    <textarea class="form-control" name="body" rows="3" maxlength="3000" placeholder="Digite sua mensagem" required></textarea>
                    <button class="btn btn-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
document.querySelectorAll('[data-communication-channel]').forEach((input) => {
    const sync = () => {
        const active = document.querySelector('[data-communication-channel]:checked')?.value || 'education';
        document.querySelectorAll('[data-communication-panel]').forEach((panel) => {
            const visible = panel.dataset.communicationPanel === active;
            panel.hidden = !visible;
            panel.querySelectorAll('select').forEach((select) => {
                select.required = visible;
            });
            if (panel.matches('select')) {
                panel.required = visible;
            }
        });
    };

    input.addEventListener('change', sync);
    sync();
});
</script>
