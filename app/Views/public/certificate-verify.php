<?php
$issuedAt = !empty($certificate['issued_at'] ?? null)
    ? date('d/m/Y', strtotime((string) $certificate['issued_at']))
    : null;
$verificationUrl = !empty($certificate['verification_code'] ?? null)
    ? url('/certificado/' . $certificate['verification_code'])
    : url('/certificado/validar');
?>

<section class="certificate-verify-page">
    <header class="certificate-verify-header">
        <span>Validação oficial</span>
        <h1>Verificar certificado</h1>
        <p>Consulte se um certificado foi emitido pelo Cidade Nova Informa usando o código impresso no documento.</p>
    </header>

    <form class="certificate-verify-form" method="get" action="<?= e(url('/certificado/validar')) ?>">
        <label for="certificate-code">Código do certificado</label>
        <div>
            <input id="certificate-code" name="codigo" value="<?= e($code ?? '') ?>" maxlength="48" placeholder="Ex.: A1B2C3D4E5F6" autocomplete="off" required>
            <button type="submit">Verificar</button>
        </div>
    </form>

    <?php if (($code ?? '') !== '' && !$certificate): ?>
        <article class="certificate-verify-result is-invalid">
            <span>Não encontrado</span>
            <h2>Certificado não localizado</h2>
            <p>Confira se o código foi digitado exatamente como aparece no certificado. Caso a dúvida continue, entre em contato com a instituição.</p>
        </article>
    <?php elseif ($certificate): ?>
        <article class="certificate-verify-result is-valid">
            <span>Certificado válido</span>
            <h2>Emitido pelo Cidade Nova Informa</h2>
            <dl>
                <div>
                    <dt>Aluno</dt>
                    <dd><?= e($certificate['student_name'] ?? '') ?></dd>
                </div>
                <div>
                    <dt>Curso</dt>
                    <dd><?= e($certificate['course_title'] ?? '') ?></dd>
                </div>
                <?php if ($issuedAt): ?>
                    <div>
                        <dt>Data de emissão</dt>
                        <dd><?= e($issuedAt) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($certificate['teacher_name'])): ?>
                    <div>
                        <dt>Professor</dt>
                        <dd><?= e($certificate['teacher_name']) ?></dd>
                    </div>
                <?php endif; ?>
                <div>
                    <dt>Código</dt>
                    <dd><?= e($certificate['verification_code'] ?? '') ?></dd>
                </div>
            </dl>
            <p>Este registro confirma que o certificado consta na base oficial da instituição.</p>
            <a href="<?= e($verificationUrl) ?>">Link permanente de verificação</a>
        </article>
    <?php endif; ?>
</section>
