<?php
$issuedAt = !empty($certificate['issued_at'] ?? null)
    ? date('d/m/Y', strtotime((string) $certificate['issued_at']))
    : null;
$verificationUrl = !empty($certificate['verification_code'] ?? null)
    ? url('/certificado/' . $certificate['verification_code'])
    : url('/certificado/validar');
$courseNature = trim((string) ($certificate['certificate_course_nature'] ?? '')) ?: 'Curso Livre de Capacitação Profissional';
$courseModality = trim((string) ($certificate['certificate_modality'] ?? ''));
$approvalCriteria = trim((string) ($certificate['certificate_approval_criteria'] ?? ''));
$legalText = trim((string) ($certificate['certificate_legal_text'] ?? ''));
$institutionName = trim((string) ($certificate['certificate_institution_name'] ?? '')) ?: 'Cidade Nova Informa';
$institutionCity = trim((string) ($certificate['certificate_institution_city'] ?? ''));
$institutionCnpj = trim((string) ($certificate['certificate_institution_cnpj'] ?? ''));
$institutionSite = trim((string) ($certificate['certificate_institution_site'] ?? ''));
$objectives = trim((string) ($certificate['certificate_objectives'] ?? ''));
$competencies = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($certificate['certificate_competencies'] ?? '')) ?: [])));
$responsibleName = trim((string) ($certificate['certificate_responsible_name'] ?? '')) ?: trim((string) ($certificate['teacher_name'] ?? ''));
$responsibleCredential = trim((string) ($certificate['certificate_responsible_credential'] ?? ''));
$hasCurriculum = $objectives !== '' || $competencies || $responsibleName !== '' || $responsibleCredential !== '' || $courseModality !== '' || $approvalCriteria !== '';
$certificateStatus = $certificate['status'] ?? 'issued';
$certificateIsValid = $certificateStatus === 'issued';
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
        <article class="certificate-verify-result <?= $certificateIsValid ? 'is-valid' : 'is-invalid' ?>">
            <div class="certificate-verify-status">
                <span><?= $certificateIsValid ? 'Certificado válido' : 'Certificado não vigente' ?></span>
                <strong><?= $certificateIsValid ? 'Registro localizado na base oficial' : 'Registro localizado com status: ' . e($certificateStatus) ?></strong>
            </div>
            <h2><?= e($certificate['course_title'] ?? 'Certificado') ?></h2>
            <p><?= e($courseNature) ?></p>
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
                <?php if ($courseModality !== ''): ?>
                    <div>
                        <dt>Modalidade</dt>
                        <dd><?= e($courseModality) ?></dd>
                    </div>
                <?php endif; ?>
                <div>
                    <dt>Código</dt>
                    <dd><?= e($certificate['verification_code'] ?? '') ?></dd>
                </div>
            </dl>
            <?php if ($hasCurriculum): ?>
                <section class="certificate-verify-curriculum" aria-label="Informações curriculares">
                    <?php if ($objectives !== ''): ?>
                        <article>
                            <h3>Objetivos do curso</h3>
                            <p><?= nl2br(e($objectives)) ?></p>
                        </article>
                    <?php endif; ?>
                    <?php if ($competencies): ?>
                        <article>
                            <h3>Competências desenvolvidas</h3>
                            <ul>
                                <?php foreach ($competencies as $competency): ?>
                                    <li><?= e($competency) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endif; ?>
                    <?php if ($responsibleName !== '' || $responsibleCredential !== ''): ?>
                        <article>
                            <h3>Responsável pelo curso</h3>
                            <?php if ($responsibleName !== ''): ?><p><?= e($responsibleName) ?></p><?php endif; ?>
                            <?php if ($responsibleCredential !== ''): ?><p><?= e($responsibleCredential) ?></p><?php endif; ?>
                        </article>
                    <?php endif; ?>
                    <?php if ($approvalCriteria !== ''): ?>
                        <article>
                            <h3>Critério de aprovação</h3>
                            <p><?= e($approvalCriteria) ?></p>
                        </article>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            <section class="certificate-verify-institution">
                <h3>Instituição emissora</h3>
                <p>
                    <strong><?= e($institutionName) ?></strong>
                    <?php if ($institutionCity !== ''): ?><span><?= e($institutionCity) ?></span><?php endif; ?>
                    <?php if ($institutionCnpj !== ''): ?><span>CNPJ: <?= e($institutionCnpj) ?></span><?php endif; ?>
                    <?php if ($institutionSite !== ''): ?><span><?= e($institutionSite) ?></span><?php endif; ?>
                </p>
                <?php if ($legalText !== ''): ?><small><?= e($legalText) ?></small><?php endif; ?>
            </section>
            <a href="<?= e($verificationUrl) ?>">Link permanente de verificação</a>
        </article>
    <?php endif; ?>
</section>
