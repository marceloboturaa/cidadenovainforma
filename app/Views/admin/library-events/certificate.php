<?php
$verificationUrl = url('/certificado/' . $certificate['verification_code']);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=8&data=' . rawurlencode($verificationUrl);
$roleText = $certificate['certificate_type'] === 'coordenacao' ? 'atuou na coordenação do evento' : 'participou do evento';
$period = '';
if (!empty($certificate['event_starts_at'])) {
    $period = ', realizado em ' . date('d/m/Y', strtotime($certificate['event_starts_at']));
    if (!empty($certificate['event_ends_at']) && substr($certificate['event_starts_at'], 0, 10) !== substr($certificate['event_ends_at'], 0, 10)) {
        $period .= ' a ' . date('d/m/Y', strtotime($certificate['event_ends_at']));
    }
}
?>
<div class="page-heading certificate-toolbar">
    <div><p>Certificado emitido</p><h1><?= e($certificate['event_title']) ?></h1></div>
    <div class="certificate-toolbar-actions">
        <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir / salvar PDF</button>
        <a class="btn btn-outline-primary" href="<?= e($verificationUrl) ?>" target="_blank" rel="noopener">Verificar</a>
        <a class="btn btn-outline-secondary" href="<?= e(url($isOwner ? '/admin/education/certificates' : '/admin/library-events/certificates?id=' . $certificate['event_id'])) ?>">Voltar</a>
    </div>
</div>
<section class="panel education-certificate-sheet-panel">
    <article class="education-certificate-sheet">
        <div class="education-certificate-copy">
            <header class="education-certificate-heading"><span>Certificado</span><h2><?= e($certificate['certificate_title']) ?></h2></header>
            <div class="education-certificate-body">Certificamos que <?= e($certificate['student_name']) ?> <?= e($roleText) ?> <strong><?= e($certificate['event_title']) ?></strong><?= e($period) ?>.</div>
            <footer><strong><?= e($certificate['student_name']) ?></strong></footer>
        </div>
        <footer class="education-certificate-footnote">
            <strong><?= e(getenv('INSTITUTION_CERTIFICATE_NAME') ?: 'Cidade Nova Informa - CNI') ?></strong>
            <span>Emitido em <?= e(date('d/m/Y', strtotime($certificate['issued_at']))) ?></span>
            <span>Código: <?= e($certificate['verification_code']) ?></span>
        </footer>
        <figure class="education-certificate-qr"><img src="<?= e($qrUrl) ?>" alt="QR Code para verificar o certificado"><figcaption>Verifique a autenticidade</figcaption></figure>
    </article>
</section>
