<?php
$issued = [];
foreach ($certificates as $certificate) $issued[(int) $certificate['person_id']][$certificate['certificate_type']] = $certificate;
?>
<div class="page-heading">
    <div><p>Participação e coordenação</p><h1>Certificados de <?= e($event['title']) ?></h1></div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/library-events/participants?id=' . $event['id'])) ?>">Voltar aos inscritos</a>
</div>
<div class="dashboard-grid">
    <article class="metric-card"><span>Inscritos cadastrados</span><strong><?= count($participants) ?></strong></article>
    <article class="metric-card"><span>Certificados de participação</span><strong><?= count(array_filter($certificates, fn ($c) => $c['certificate_type'] === 'participacao')) ?></strong></article>
    <article class="metric-card"><span>Certificados de coordenação</span><strong><?= count(array_filter($certificates, fn ($c) => $c['certificate_type'] === 'coordenacao')) ?></strong></article>
</div>
<section class="panel">
    <h2>Inscritos e certificados</h2>
    <p>A equipe atribui a coordenação. Uma pessoa pode receber participação e coordenação no mesmo evento. Selecione os tipos e clique em Emitir.</p>
    <?php if (!$participants): ?><div class="empty-state">Cadastre os inscritos do evento para emitir certificados.</div><?php endif; ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th scope="col">Pessoa inscrita</th><th scope="col">Coordenação</th><th scope="col">Emitir certificados</th><th scope="col">Documentos emitidos</th></tr></thead>
            <tbody>
            <?php foreach ($participants as $participant): ?>
                <?php
                $personId = (int) $participant['person_id'];
                $canIssue = in_array($participant['status'], ['inscrito', 'presente'], true) && $event['status'] !== 'cancelado';
                ?>
                <tr>
                    <td><strong><?= e($participant['full_name']) ?></strong><br><small><?= e(ucfirst($participant['status'])) ?></small></td>
                    <td>
                        <form method="post" action="<?= e(url('/admin/library-events/certificates/coordinator?id=' . $event['id'])) ?>">
                            <?= csrf_field() ?><input type="hidden" name="person_id" value="<?= $personId ?>">
                            <label><input type="checkbox" name="is_coordinator" value="1" <?= !empty($participant['is_coordinator']) ? 'checked' : '' ?>> Coordenador(a)</label>
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Salvar função</button>
                        </form>
                    </td>
                    <td>
                        <?php if ($canIssue): ?>
                        <form method="post" action="<?= e(url('/admin/library-events/certificates/issue?id=' . $event['id'])) ?>">
                            <?= csrf_field() ?><input type="hidden" name="person_id" value="<?= $personId ?>">
                            <label class="d-block"><input type="checkbox" name="types[]" value="participacao"> Participação</label>
                            <?php if (!empty($participant['is_coordinator'])): ?>
                            <label class="d-block"><input type="checkbox" name="types[]" value="coordenacao"> Coordenação</label>
                            <?php else: ?><small class="d-block">Salve a função de coordenador para liberar o segundo tipo.</small><?php endif; ?>
                            <button class="btn btn-sm btn-primary" type="submit">Emitir selecionados</button>
                        </form>
                        <?php else: ?><small>Exige inscrição aprovada e evento não cancelado.</small><?php endif; ?>
                    </td>
                    <td>
                        <?php foreach ($issued[$personId] ?? [] as $document): ?>
                            <a class="btn btn-sm btn-outline-primary mb-1" href="<?= e(url('/admin/library-events/certificate?certificate_id=' . $document['id'])) ?>"><?= e($document['certificate_title']) ?></a><br>
                        <?php endforeach; ?>
                        <?php if (empty($issued[$personId])): ?>Nenhum certificado emitido.<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
