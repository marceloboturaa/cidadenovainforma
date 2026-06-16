<div class="communication-shell">
    <section class="communication-hero">
        <div>
            <span>Comunicacao interna</span>
            <h1>Conversas de eventos</h1>
            <p>Fale com o responsavel do evento e acompanhe as conversas abertas por usuarios logados.</p>
        </div>
        <form class="communication-start-form" method="post" action="<?= e(url('/admin/communication/start')) ?>">
            <?= csrf_field() ?>
            <label>
                <span>Evento</span>
                <select class="form-select" name="event_id" required>
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
                <a class="communication-thread <?= !empty($selectedConversation) && (int) $selectedConversation['id'] === (int) $conversation['id'] ? 'is-active' : '' ?>" href="<?= e(url('/admin/communication/show?id=' . $conversation['id'])) ?>">
                    <strong><?= e($conversation['event_title']) ?></strong>
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
                        <span><?= e($selectedConversation['event_title']) ?></span>
                        <h2><?= e($selectedConversation['participant_name']) ?> com <?= e($selectedConversation['responsible_name'] ?: 'responsavel nao definido') ?></h2>
                    </div>
                    <?php if (!empty($selectedConversation['event_starts_at'])): ?>
                        <strong><?= e(date('d/m/Y H:i', strtotime((string) $selectedConversation['event_starts_at']))) ?></strong>
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
                    <textarea class="form-control" name="body" rows="3" maxlength="3000" placeholder="Digite sua mensagem" required></textarea>
                    <button class="btn btn-primary icon-btn"><i class="bi bi-send" aria-hidden="true"></i>Enviar</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>
