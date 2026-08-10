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
$eventImage = event_public_image($event);
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
            <?php if ($canRegister && $remainingSlots !== 0): ?>
                <span class="registration-open-badge">Inscrições abertas</span>
            <?php endif; ?>
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
                    <a class="public-event-more registration-action-link" href="#inscricao">Fazer inscrição</a>
                <?php endif; ?>
                <a class="public-event-more" href="<?= e($isPast ? url('/eventos/realizados') : url('/eventos/futuros')) ?>">
                    <?= $isPast ? 'Ver eventos realizados' : 'Ver eventos futuros' ?>
                </a>
                <a class="events-card-link" href="<?= e(url('/eventos')) ?>">Agenda completa</a>
            </div>
        </div>
        <div class="event-show-media<?= $eventImage ? '' : ' is-empty' ?>">
            <?php if ($eventImage): ?>
                <img src="<?= e(media_url($eventImage)) ?>" alt="<?= e($event['title']) ?>" onerror="this.parentElement.classList.add('is-empty'); this.remove()">
            <?php endif; ?>
            <i class="bi bi-calendar-event" aria-hidden="true"></i>
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

            <?php if (!empty($event['linked_courses'])): ?>
                <section class="event-linked-course-list">
                    <span>Cursos online vinculados</span>
                    <h2><?= count($event['linked_courses']) > 1 ? 'Cursos deste evento' : 'Curso deste evento' ?></h2>
                    <div>
                        <?php foreach ($event['linked_courses'] as $course): ?>
                            <?php
                            $courseHref = url('/curso/' . $course['id']);
                            $courseActionLabel = !empty($course['public_access_enabled']) ? 'Acessar curso' : 'Entrar para acessar';
                            ?>
                            <article class="event-linked-course">
                                <div>
                                    <h3><?= e($course['title']) ?></h3>
                                    <?php if (!empty($course['summary'])): ?>
                                        <p><?= e(text_excerpt($course['summary'], 180)) ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($course['cover_image'])): ?>
                                    <img src="<?= e(media_url($course['cover_image'])) ?>" alt="" loading="lazy" onerror="this.remove()">
                                <?php endif; ?>
                                <a class="events-card-link" href="<?= e($courseHref) ?>"><?= e($courseActionLabel) ?></a>
                            </article>
                        <?php endforeach; ?>
                    </div>
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

                    <form class="event-registration-form" method="post" action="<?= e(url('/evento/' . $event['id'] . '/inscricao')) ?>" data-public-event-registration-form>
                        <?= csrf_field() ?>
                        <div class="field-wide">
                            <label>Nome completo</label>
                            <input name="full_name" required>
                        </div>
                        <div>
                            <label>CPF</label>
                            <input name="cpf" data-public-cpf-input inputmode="numeric" autocomplete="off">
                        </div>
                        <div>
                            <label>Nascimento</label>
                            <input name="birth_date" type="text" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="bday" data-public-birth-date-input>
                        </div>
                        <div>
                            <label>WhatsApp</label>
                            <input name="whatsapp" inputmode="tel" autocomplete="tel" data-public-phone-input required>
                        </div>
                        <div>
                            <label>Telefone</label>
                            <input name="phone" inputmode="tel" autocomplete="tel" data-public-phone-input>
                        </div>
                        <div class="field-wide">
                            <label>E-mail</label>
                            <input name="email" type="email">
                        </div>
                        <div>
                            <label>CEP</label>
                            <div class="event-registration-input-action">
                                <input name="cep" data-public-cep-input inputmode="numeric" autocomplete="postal-code">
                                <button type="button" data-public-cep-search aria-label="Buscar CEP" title="Buscar CEP">
                                    <span class="search-icon" aria-hidden="true"></span>
                                </button>
                            </div>
                            <small class="event-registration-field-hint" data-public-cep-status></small>
                        </div>
                        <div class="field-wide">
                            <label>Endereço</label>
                            <input name="address" data-public-address-input>
                        </div>
                        <div>
                            <label>Número</label>
                            <input name="address_number">
                        </div>
                        <div>
                            <label>Bairro</label>
                            <input name="district" data-public-district-input>
                        </div>
                        <div>
                            <label>Cidade</label>
                            <input name="city" data-public-city-input>
                        </div>
                        <div>
                            <label>UF</label>
                            <input name="state" maxlength="2" data-public-state-input>
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
                                <input name="guardian_cpf" data-public-cpf-input inputmode="numeric" autocomplete="off">
                            </div>
                            <div>
                                <label>Telefone do responsável</label>
                                <input name="guardian_phone" inputmode="tel" autocomplete="tel" data-public-phone-input>
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
                        <div class="field-wide">
                            <label>Como você ficou sabendo deste evento?</label>
                            <select name="heard_about">
                                <option value="">Selecione uma opção</option>
                                <option value="Internet">Internet</option>
                                <option value="Redes sociais">Redes sociais</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Indicação de amigo/familiar">Indicação de amigo/familiar</option>
                                <option value="Escola ou instituição">Escola ou instituição</option>
                                <option value="Comunidade/igreja">Comunidade/igreja</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div class="field-wide">
                            <label>O que você espera deste evento?</label>
                            <textarea name="event_expectations" rows="3"></textarea>
                        </div>
                        <?php if (!empty($event['registration_question_label'])): ?>
                            <?php
                            $questionType = (string) ($event['registration_question_type'] ?? 'text');
                            $questionOptions = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) ($event['registration_question_options'] ?? '')) ?: [])));
                            ?>
                            <div class="field-wide">
                                <label><?= e($event['registration_question_label']) ?></label>
                                <?php if ($questionType === 'select' && $questionOptions): ?>
                                    <select name="registration_extra_answer" <?= !empty($event['registration_question_required']) ? 'required' : '' ?>>
                                        <option value="">Selecione uma opção</option>
                                        <?php foreach ($questionOptions as $option): ?>
                                            <option value="<?= e($option) ?>"><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($questionType === 'checkboxes' && $questionOptions): ?>
                                    <div class="event-topic-options">
                                        <?php foreach ($questionOptions as $option): ?>
                                            <label class="event-registration-check">
                                                <input type="checkbox" name="registration_extra_answer[]" value="<?= e($option) ?>">
                                                <span><?= e($option) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <textarea name="registration_extra_answer" rows="3" <?= !empty($event['registration_question_required']) ? 'required' : '' ?>></textarea>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                <?php if ($showCapacity && $capacity): ?><div class="info-slots"><dt>Vagas</dt><dd><strong class="slot-free"><?= e((string) $remainingSlots) ?> livre(s)</strong><span class="slot-occupied"><?= e((string) $occupiedSlots) ?> ocupada(s)</span><span class="slot-total"><?= e((string) $capacity) ?> total</span></dd></div><?php endif; ?>
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
