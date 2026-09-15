<?php
$isValid = ($certificate['status'] ?? '') === 'issued';
$activity = ($certificate['certificate_activity_type'] ?? '') === 'evento' ? 'Evento' : (($certificate['certificate_activity_type'] ?? '') === 'reconhecimento' ? 'Reconhecimento' : 'Curso');
?>
<section class="certificate-verify-page">
    <header class="certificate-verify-header">
        <span>Autenticidade do documento</span><h1>Verificar certificado</h1>
        <p>Digite o código impresso no certificado ou acesse o QR Code do documento.</p>
    </header>
    <form class="certificate-verify-form" method="post" action="<?= e(url('/certificado/validar')) ?>">
        <label for="certificate-code">Código de verificação</label>
        <div><input id="certificate-code" name="codigo" value="<?= e($code ?? '') ?>" maxlength="48" pattern="[A-Za-z0-9]{8,48}" placeholder="Código do certificado" autocomplete="off" spellcheck="false" required><button type="submit">Verificar</button></div>
    </form>
    <?php if (($code ?? '') !== ''): ?>
    <article class="certificate-verify-result <?= $isValid ? 'is-valid' : 'is-invalid' ?>" role="status">
        <div class="certificate-verify-status"><span><?= $isValid ? 'Certificado válido' : (($certificate['status'] ?? '') === 'revoked' ? 'Certificado revogado' : 'Certificado não localizado') ?></span></div>
        <?php if ($isValid): ?>
        <h2><?= e($certificate['document_label']) ?></h2>
        <dl>
            <div><dt>Titular — nome abreviado</dt><dd><?= e($certificate['recipient_initials']) ?></dd></div>
            <div><dt><?= e($activity) ?></dt><dd><?= e($certificate['course_title']) ?></dd></div>
            <div><dt>Instituição emissora</dt><dd><?= e($certificate['institution_name']) ?></dd></div>
            <?php if (!empty($certificate['issued_at'])): ?><div><dt>Data de emissão</dt><dd><?= e(date('d/m/Y', strtotime($certificate['issued_at']))) ?></dd></div><?php endif; ?>
            <div><dt>Código</dt><dd><?= e($certificate['verification_code']) ?></dd></div>
        </dl>
        <p>Confira estes dados com o documento apresentado pelo titular. O nome completo fica disponível no certificado, em acesso restrito.</p>
        <?php elseif (($certificate['status'] ?? '') === 'revoked'): ?>
        <p>Este certificado não está vigente. Consulte a instituição emissora para esclarecimentos.</p>
        <?php else: ?>
        <p>Confira o código no documento. Certificados ainda não liberados não ficam disponíveis nesta consulta.</p>
        <?php endif; ?>
    </article>
    <?php endif; ?>
    <section class="certificate-verify-institution">
        <h2>Privacidade na consulta</h2>
        <p>Esta página confirma a autenticidade usando o código do documento. O nome é abreviado, e dados como CPF, e-mail, telefone, notas e motivo de revogação não são exibidos.</p>
        <p>Compartilhe o código somente com quem precisa verificar o certificado. Para consultar seus documentos completos ou solicitar correção, acesse sua conta e procure a equipe responsável pelo curso ou evento.</p>
        <a href="<?= e(url('/admin/education/certificates')) ?>">Acessar meus certificados</a>
    </section>
</section>
