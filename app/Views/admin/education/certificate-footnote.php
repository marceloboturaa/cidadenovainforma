            <footer class="education-certificate-footnote<?= $footerBackgroundEnabled ? '' : ' is-transparent' ?><?= $footerRounded ? ' is-rounded' : '' ?>">
                <div class="education-certificate-footnote-text">
                    <?php if ($showInstitution): ?>
                        <div class="education-certificate-institution">
                            <?php if ($institutionName !== ''): ?><strong><?= e($institutionName) ?></strong><?php endif; ?>
                            <?php if ($institutionCity !== ''): ?><span><?= e($institutionCity) ?></span><?php endif; ?>
                            <?php if ($institutionCnpj !== ''): ?><span>CNPJ: <?= e($institutionCnpj) ?></span><?php endif; ?>
                            <?php if ($institutionSite !== ''): ?><span><?= e($institutionSite) ?></span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($showIssuedMeta || $showCodeMeta || $showTeacherMeta || $showFrequencyMeta): ?>
                        <div class="education-certificate-footnote-meta">
                            <?php if ($showIssuedMeta): ?><span>Emitido em <?= e($issuedAt) ?></span><?php endif; ?>
                            <?php if ($showCodeMeta): ?><span>Código <?= e($certificate['verification_code'] ?? '') ?></span><?php endif; ?>
                            <?php if ($showTeacherMeta): ?><span>Professor: <?= e($course['teacher_name']) ?></span><?php endif; ?>
                            <?php if ($showFrequencyMeta): ?><span>Frequência registrada: <?= e((string) ($certificateStatus['frequency'] ?? 0)) ?>%</span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($showLegal): ?><p><?= e($legalText) ?></p><?php endif; ?>
                </div>
            </footer>
