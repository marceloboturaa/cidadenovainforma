<?php
$startsAt = !empty($event['starts_at']) ? date('d/m/Y H:i', strtotime($event['starts_at'])) : 'Data a definir';
$endsAt = !empty($event['ends_at']) ? date('d/m/Y H:i', strtotime($event['ends_at'])) : null;
$startTime = !empty($event['starts_at']) ? strtotime($event['starts_at']) : null;
$endTime = !empty($event['ends_at']) ? strtotime($event['ends_at']) : null;
$isHappening = ($event['status'] ?? '') === 'aberto' && $startTime && $endTime && $startTime <= time() && $endTime >= time();
$isPast = ($event['status'] ?? '') === 'encerrado' || (($endTime ?: $startTime) && ($endTime ?: $startTime) < time());
$statusText = $isHappening ? 'Acontecendo' : ($isPast ? 'Evento realizado' : 'Próximo evento');
$canRegister = ($event['status'] ?? '') === 'aberto' && !$isPast && !empty($event['registration_enabled']);
$remainingSlots = $remainingSlots ?? null;
$capacity = !empty($event['capacity']) ? (int) $event['capacity'] : null;
$occupiedSlots = $capacity ? max(0, $capacity - (int) ($remainingSlots ?? $capacity)) : null;
$showLocation = !empty($event['public_show_location']);
$showAddress = !empty($event['public_show_address']);
$showCapacity = !empty($event['public_show_capacity']);
$showResponsible = !empty($event['public_show_responsible']);
$showHeroCapacity = $showCapacity && $capacity;
$shareUrl = url('/evento/' . $event['id']);
$shareText = 'Confira este evento: ' . ($event['title'] ?? 'Evento');
$shareLinks = [
    'whatsapp' => 'https://wa.me/?text=' . rawurlencode($shareText . ' ' . $shareUrl),
    'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl),
    'email' => 'mailto:?subject=' . rawurlencode((string) ($event['title'] ?? 'Evento')) . '&body=' . rawurlencode($shareText . "\n" . $shareUrl),
];
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
            <?php if ($showHeroCapacity): ?>
                <div class="event-hero-slots" aria-label="Vagas do evento">
                    <strong><?= e((string) $remainingSlots) ?></strong>
                    <span>vaga(s) restante(s) de <?= e((string) $capacity) ?> liberada(s)</span>
                    <small><?= e((string) $occupiedSlots) ?> inscrição(ões) em análise ou confirmada(s)</small>
                </div>
            <?php endif; ?>
            <div class="event-show-actions">
                <?php if ($canRegister && $remainingSlots !== 0): ?>
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

            <?php if (!empty($event['event_course_id']) && !empty($event['course_title'])): ?>
                <section class="event-linked-course">
                    <div>
                        <span>Curso online vinculado</span>
                        <h2><?= e($event['course_title']) ?></h2>
                        <?php if (!empty($event['course_summary'])): ?>
                            <p><?= e(text_excerpt($event['course_summary'], 220)) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($event['course_cover_image'])): ?>
                        <img src="<?= e(media_url($event['course_cover_image'])) ?>" alt="" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                    <a class="events-card-link" href="<?= e(url('/admin/education/course?id=' . $event['event_course_id'])) ?>">Acessar curso online</a>
                </section>
            <?php endif; ?>

            <?php if ($canRegister && $remainingSlots !== 0): ?>
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
                        <label class="event-registration-check event-minor-toggle field-wide">
                            <input type="checkbox" name="is_minor" value="1" data-public-minor-toggle>
                            <span>Sou menor de idade ou estou inscrevendo menor de idade</span>
                        </label>
                        <section class="event-guardian-panel field-wide" data-public-guardian-panel hidden>
                            <div class="event-guardian-heading">
                                <strong>Dados do responsável</strong>
                                <span>Obrigatório quando a inscrição for de menor de idade.</span>
                            </div>
                            <div>
                                <label>Nome do responsável</label>
                                <input name="guardian_name">
                            </div>
                            <div>
                                <label>Parentesco</label>
                                <input name="guardian_relation">
                            </div>
                            <div>
                                <label>CPF do responsável</label>
                                <input name="guardian_cpf">
                            </div>
                            <div>
                                <label>Telefone do responsável</label>
                                <input name="guardian_phone">
                            </div>
                            <div>
                                <label>E-mail do responsável</label>
                                <input name="guardian_email" type="email">
                            </div>
                        </section>
                        <label class="event-registration-check field-wide">
                            <input type="checkbox" name="contact_authorized" value="1" required>
                            <span>Autorizo o uso dos contatos informados para comunicação sobre esta inscrição.</span>
                        </label>
                        <label class="event-registration-check event-image-consent field-wide">
                            <input type="checkbox" name="image_authorized" value="1">
                            <span>Autorizo, nos termos da LGPD, o uso gratuito da minha imagem em fotos e vídeos deste evento para divulgação institucional do Cidade Nova Informa.</span>
                        </label>
                        <label class="event-registration-check event-login-option field-wide">
                            <input type="checkbox" name="create_login" value="1" data-public-login-toggle>
                            <span>Também quero criar login com este e-mail. O acesso só será liberado após aprovação do administrador.</span>
                        </label>
                        <section class="event-login-panel field-wide" data-public-login-panel hidden>
                            <div class="event-login-heading">
                                <strong>Dados de acesso</strong>
                                <span>O login fica bloqueado até aprovação do administrador.</span>
                            </div>
                            <div>
                                <label>Senha do login</label>
                                <input name="login_password" type="password" minlength="8" autocomplete="new-password">
                            </div>
                            <div>
                                <label>Confirmar senha</label>
                                <input name="login_password_confirmation" type="password" minlength="8" autocomplete="new-password">
                            </div>
                        </section>
                        <div class="field-wide">
                            <label>Observações</label>
                            <textarea name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit">Enviar inscrição para confirmação</button>
                    </form>
                </section>
            <?php endif; ?>
            <?php if ($canRegister && $remainingSlots === 0): ?>
                <section class="event-registration-panel" id="inscricao">
                    <div class="public-form-alert is-error">As vagas deste evento estão esgotadas no momento.</div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="event-show-sidebar">
            <h2>Informações</h2>
            <dl class="event-info-color-list">
                <div class="info-date"><dt>Data e horário</dt><dd><?= e($startsAt) ?></dd></div>
                <?php if ($endsAt): ?><div class="info-date"><dt>Encerramento</dt><dd><?= e($endsAt) ?></dd></div><?php endif; ?>
                <?php if ($showLocation && !empty($event['location'])): ?><div class="info-place"><dt>Local</dt><dd><?= e($event['location']) ?></dd></div><?php endif; ?>
                <?php if ($showAddress && !empty($event['event_cep'])): ?><div class="info-address"><dt>CEP</dt><dd><?= e($event['event_cep']) ?></dd></div><?php endif; ?>
                <?php if ($showAddress && !empty($event['event_address'])): ?><div class="info-address"><dt>Endereço</dt><dd><?= e($event['event_address']) ?></dd></div><?php endif; ?>
                <?php if ($showCapacity && $capacity): ?><div class="info-slots"><dt>Vagas</dt><dd><strong><?= e((string) $remainingSlots) ?> livre(s)</strong><span><?= e((string) $occupiedSlots) ?> ocupada(s) de <?= e((string) $capacity) ?> total</span></dd></div><?php endif; ?>
                <?php if ($showResponsible && !empty($event['responsible_name'])): ?><div class="info-person"><dt>Responsável</dt><dd><?= e($event['responsible_name']) ?></dd></div><?php endif; ?>
                <div class="info-status"><dt>Status</dt><dd><?= e($statusText) ?></dd></div>
            </dl>

            <section class="event-share-box" aria-label="Compartilhar evento">
                <h3>Compartilhar</h3>
                <div class="event-share-actions">
                    <a href="<?= e($shareLinks['whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    <a href="<?= e($shareLinks['facebook']) ?>" target="_blank" rel="noopener">Facebook</a>
                    <a href="<?= e($shareLinks['email']) ?>">E-mail</a>
                    <button type="button" data-copy-share="<?= e($shareUrl) ?>">Copiar link</button>
                </div>
            </section>

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
