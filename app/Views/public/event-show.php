<?php
$startsAt = !empty($event['starts_at']) ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Data a definir';
$endsAt = !empty($event['ends_at']) ? date('d/m/Y H:i', strtotime($event['ends_at'])) : null;
$startTime = !empty($event['starts_at']) ? strtotime($event['starts_at']) : null;
$endTime = !empty($event['ends_at']) ? strtotime($event['ends_at']) : null;
$isHappening = ($event['status'] ?? '') === 'aberto' && $startTime && $endTime && $startTime <= time() && $endTime >= time();
$isPast = ($event['status'] ?? '') === 'encerrado' || (($endTime ?: $startTime) && ($endTime ?: $startTime) < time());
$statusText = $isHappening ? 'Acontecendo' : ($isPast ? 'Evento realizado' : 'Próximo evento');
$canRegister = ($event['status'] ?? '') === 'aberto' && !$isPast;
$relatedLinks = [];
foreach (preg_split('/\R+/', (string) ($event['related_links'] ?? '')) ?: [] as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }

    $parts = array_map('trim', explode('|', $line, 2));
    $label = count($parts) === 2 ? $parts[0] : 'Ler matéria';
    $href = count($parts) === 2 ? $parts[1] : $parts[0];

    if ($href === '' || (!preg_match('#^https?://#i', $href) && !str_starts_with($href, '/'))) {
        continue;
    }

    $relatedLinks[] = [
        'label' => $label !== '' ? $label : 'Ler matéria',
        'href' => preg_match('#^https?://#i', $href) ? $href : url($href),
        'external' => (bool) preg_match('#^https?://#i', $href),
    ];
}
?>

<article class="event-show-page">
    <nav class="institution-breadcrumb" aria-label="Caminho">
        <a href="<?= e(url('/eventos')) ?>">Eventos</a>
        <span><?= e($event['title']) ?></span>
    </nav>

    <header class="event-show-hero">
        <div class="event-show-copy">
            <span class="event-status-badge"><?= e($statusText) ?></span>
            <h1><?= e($event['title']) ?></h1>
            <?php if (!empty($event['description'])): ?>
                <p><?= e(text_excerpt($event['description'], 240)) ?></p>
            <?php endif; ?>
            <div class="event-show-actions">
                <?php if ($canRegister): ?>
                    <a class="public-event-more" href="#inscricao">Fazer inscrição</a>
                <?php endif; ?>
                <a class="public-event-more" href="<?= e($isPast ? url('/eventos/realizados') : url('/eventos/futuros')) ?>">
                    <?= $isPast ? 'Ver eventos realizados' : 'Ver eventos futuros' ?>
                </a>
                <a class="events-card-link" href="<?= e(url('/eventos')) ?>">Agenda completa</a>
            </div>
        </div>
        <div class="event-show-media">
            <?php if (!empty($event['cover_image'])): ?>
                <img src="<?= e(media_url($event['cover_image'])) ?>" alt="<?= e($event['title']) ?>" onerror="this.parentElement.classList.add('is-empty'); this.remove()">
            <?php else: ?>
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
            <?php endif; ?>
        </div>
    </header>

    <section class="event-show-layout">
        <div class="event-show-content">
            <h2>Sobre o evento</h2>
            <?php if (!empty($event['description'])): ?>
                <div class="event-detail-text">
                    <?= article_html($event['description']) ?>
                </div>
            <?php else: ?>
                <p class="event-detail-text">Mais informações serão divulgadas em breve.</p>
            <?php endif; ?>

            <?php if ($canRegister): ?>
                <section class="event-registration-panel" id="inscricao">
                    <div class="event-registration-heading">
                        <span>Inscrição online</span>
                        <h2>Preencha seus dados</h2>
                        <p>A inscrição será enviada para conferência. A equipe confirma pelo painel antes de validar a participação.</p>
                    </div>

                    <?php if (!empty($registrationSuccess)): ?>
                        <div class="public-form-alert is-success"><?= e($registrationSuccess) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($registrationError)): ?>
                        <div class="public-form-alert is-error"><?= e($registrationError) ?></div>
                    <?php endif; ?>

                    <form class="event-registration-form" method="post" action="<?= e(url('/evento/' . $event['id'] . '/inscricao')) ?>">
                        <?= csrf_field() ?>
                        <div class="field-wide">
                            <label>Nome completo</label>
                            <input name="full_name" required>
                        </div>
                        <div>
                            <label>CPF</label>
                            <input name="cpf">
                        </div>
                        <div>
                            <label>Nascimento</label>
                            <input name="birth_date" type="date">
                        </div>
                        <div>
                            <label>WhatsApp</label>
                            <input name="whatsapp" required>
                        </div>
                        <div>
                            <label>Telefone</label>
                            <input name="phone">
                        </div>
                        <div class="field-wide">
                            <label>E-mail</label>
                            <input name="email" type="email">
                        </div>
                        <div>
                            <label>CEP</label>
                            <input name="cep">
                        </div>
                        <div class="field-wide">
                            <label>Endereço</label>
                            <input name="address">
                        </div>
                        <div>
                            <label>Número</label>
                            <input name="address_number">
                        </div>
                        <div>
                            <label>Bairro</label>
                            <input name="district">
                        </div>
                        <div>
                            <label>Cidade</label>
                            <input name="city">
                        </div>
                        <div>
                            <label>UF</label>
                            <input name="state" maxlength="2">
                        </div>
                        <label class="event-registration-check field-wide">
                            <input type="checkbox" name="is_minor" value="1">
                            <span>Sou menor de idade ou estou inscrevendo menor de idade</span>
                        </label>
                        <div class="field-wide">
                            <label>Nome do responsável</label>
                            <input name="guardian_name">
                        </div>
                        <div>
                            <label>Parentesco</label>
                            <input name="guardian_relation">
                        </div>
                        <div>
                            <label>Telefone do responsável</label>
                            <input name="guardian_phone">
                        </div>
                        <label class="event-registration-check field-wide">
                            <input type="checkbox" name="contact_authorized" value="1" required>
                            <span>Autorizo o uso dos contatos informados para comunicação sobre esta inscrição.</span>
                        </label>
                        <label class="event-registration-check field-wide">
                            <input type="checkbox" name="create_login" value="1">
                            <span>Também quero criar login com este e-mail. O acesso só será liberado após aprovação do administrador.</span>
                        </label>
                        <div>
                            <label>Senha do login</label>
                            <input name="login_password" type="password" minlength="8" autocomplete="new-password">
                        </div>
                        <div>
                            <label>Confirmar senha</label>
                            <input name="login_password_confirmation" type="password" minlength="8" autocomplete="new-password">
                        </div>
                        <div class="field-wide">
                            <label>Observações</label>
                            <textarea name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit">Enviar inscrição para confirmação</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <aside class="event-show-sidebar">
            <h2>Informações</h2>
            <dl>
                <div><dt>Data e horário</dt><dd><?= e($startsAt) ?></dd></div>
                <?php if ($endsAt): ?><div><dt>Encerramento</dt><dd><?= e($endsAt) ?></dd></div><?php endif; ?>
                <?php if (!empty($event['location'])): ?><div><dt>Local</dt><dd><?= e($event['location']) ?></dd></div><?php endif; ?>
                <?php if (!empty($event['capacity'])): ?><div><dt>Vagas</dt><dd><?= e((string) $event['capacity']) ?></dd></div><?php endif; ?>
                <?php if (!empty($event['responsible_name'])): ?><div><dt>Responsável</dt><dd><?= e($event['responsible_name']) ?></dd></div><?php endif; ?>
                <div><dt>Status</dt><dd><?= e($statusText) ?></dd></div>
            </dl>

            <?php if ($relatedLinks): ?>
                <section class="event-related-news" aria-label="Matérias sobre o evento">
                    <h3>Matérias sobre o evento</h3>
                    <ul>
                        <?php foreach ($relatedLinks as $link): ?>
                            <li>
                                <a href="<?= e($link['href']) ?>"<?= $link['external'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                    <?= e($link['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </aside>
    </section>
</article>
