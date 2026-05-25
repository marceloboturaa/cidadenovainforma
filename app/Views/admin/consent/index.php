<?php
$categoryOptions = array_filter($categories, static fn (array $category): bool => empty($category['required']));
$jsonPretty = static function (?string $json): string {
    $decoded = json_decode((string) $json, true);
    return is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) $json;
};
?>

<div class="page-heading">
    <div>
        <p>LGPD Brasil</p>
        <h1>Painel CMP LGPD</h1>
    </div>
    <div class="heading-actions">
        <a class="btn btn-outline-primary icon-btn" href="<?= e(url('/politica-de-cookies')) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Política pública</a>
        <a class="btn btn-primary icon-btn" href="<?= e(url('/admin/consent/export')) ?>"><i class="bi bi-download" aria-hidden="true"></i>Exportar consentimentos</a>
    </div>
</div>

<section class="metric-grid consent-metric-grid">
    <article><span>Consentimentos</span><strong><?= e((string) ($stats['records'] ?? 0)) ?></strong></article>
    <article><span>Hoje</span><strong><?= e((string) ($stats['today'] ?? 0)) ?></strong></article>
    <article><span>Categorias ativas</span><strong><?= e((string) ($stats['categories'] ?? 0)) ?></strong></article>
    <article><span>Scripts ativos</span><strong><?= e((string) ($stats['scripts'] ?? 0)) ?></strong></article>
</section>

<section class="consent-admin-grid">
    <article class="panel consent-panel">
        <div class="section-heading">
            <h2>Textos e aparência</h2>
            <span>Versão <?= e($settings['policy_version'] ?? '1.0') ?></span>
        </div>
        <form method="post" action="<?= e(url('/admin/consent/settings')) ?>" class="consent-form">
            <?= csrf_field() ?>
            <label class="form-label">Título do banner<input class="form-control" name="banner_title" value="<?= e($settings['banner_title'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
            <label class="form-label">Texto do banner<textarea class="form-control" name="banner_text" rows="3" <?= $canEditTexts ? '' : 'disabled' ?>><?= e($settings['banner_text'] ?? '') ?></textarea></label>
            <label class="form-label">Título da política<input class="form-control" name="policy_title" value="<?= e($settings['policy_title'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
            <label class="form-label">Texto da política<textarea class="form-control" name="policy_text" rows="8" <?= $canEditTexts ? '' : 'disabled' ?>><?= e($settings['policy_text'] ?? '') ?></textarea></label>
            <div class="consent-form-grid">
                <label class="form-label">Versão<input class="form-control" name="policy_version" value="<?= e($settings['policy_version'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
                <label class="form-label">Aceitar<input class="form-control" name="accept_label" value="<?= e($settings['accept_label'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
                <label class="form-label">Rejeitar<input class="form-control" name="reject_label" value="<?= e($settings['reject_label'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
                <label class="form-label">Personalizar<input class="form-control" name="customize_label" value="<?= e($settings['customize_label'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
                <label class="form-label">Salvar<input class="form-control" name="save_label" value="<?= e($settings['save_label'] ?? '') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
            </div>
            <div class="consent-color-grid">
                <?php foreach (['primary_color' => 'Cor principal', 'secondary_color' => 'Cor secundária', 'background_color' => 'Fundo', 'text_color' => 'Texto'] as $field => $label): ?>
                    <label class="form-label"><?= e($label) ?><input class="form-control form-control-color" type="color" name="<?= e($field) ?>" value="<?= e($settings[$field] ?? '#111827') ?>" <?= $canEditTexts ? '' : 'disabled' ?>></label>
                <?php endforeach; ?>
            </div>
            <?php if ($canEditTexts): ?>
                <button class="btn btn-primary icon-btn"><i class="bi bi-save" aria-hidden="true"></i>Salvar textos</button>
            <?php endif; ?>
        </form>
    </article>

    <article class="panel consent-panel" id="categorias">
        <div class="section-heading">
            <h2>Categorias</h2>
            <span>Necessários sempre ativos</span>
        </div>
        <div class="consent-list">
            <?php foreach ($categories as $category): ?>
                <form method="post" action="<?= e(url('/admin/consent/category')) ?>" class="consent-row-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e((string) $category['id']) ?>">
                    <input class="form-control" name="name" value="<?= e($category['name']) ?>" <?= $canManage ? '' : 'disabled' ?>>
                    <input class="form-control" name="slug" value="<?= e($category['slug']) ?>" <?= $canManage ? '' : 'disabled' ?>>
                    <textarea class="form-control" name="description" rows="2" <?= $canManage ? '' : 'disabled' ?>><?= e($category['description'] ?? '') ?></textarea>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="required" value="1" <?= !empty($category['required']) ? 'checked' : '' ?> <?= $canManage ? '' : 'disabled' ?>>Necessária</label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" <?= !empty($category['active']) ? 'checked' : '' ?> <?= $canManage ? '' : 'disabled' ?>>Ativa</label>
                    <input class="form-control" type="number" name="sort_order" value="<?= e((string) $category['sort_order']) ?>" <?= $canManage ? '' : 'disabled' ?>>
                    <?php if ($canManage): ?>
                        <button class="btn btn-outline-primary btn-sm">Salvar</button>
                    <?php endif; ?>
                </form>
                <?php if ($canManage && empty($category['required'])): ?>
                    <form method="post" action="<?= e(url('/admin/consent/category/delete')) ?>" onsubmit="return confirm('Remover esta categoria?');">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e((string) $category['id']) ?>">
                        <button class="btn btn-outline-danger btn-sm">Remover</button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if ($canManage): ?>
            <form method="post" action="<?= e(url('/admin/consent/category')) ?>" class="consent-create-form">
                <?= csrf_field() ?>
                <input class="form-control" name="name" placeholder="Nova categoria">
                <input class="form-control" name="slug" placeholder="slug">
                <textarea class="form-control" name="description" rows="2" placeholder="Descrição"></textarea>
                <label class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" checked>Ativa</label>
                <button class="btn btn-primary btn-sm">Criar categoria</button>
            </form>
        <?php endif; ?>
    </article>
</section>

<section class="panel consent-panel" id="scripts">
    <div class="section-heading">
        <h2>Scripts opcionais</h2>
        <span>Carregados somente após consentimento</span>
    </div>
    <div class="consent-script-list">
        <?php foreach ($scripts as $script): ?>
            <form method="post" action="<?= e(url('/admin/consent/script')) ?>" class="consent-script-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e((string) $script['id']) ?>">
                <input class="form-control" name="name" value="<?= e($script['name']) ?>" <?= $canManage ? '' : 'disabled' ?>>
                <select class="form-select" name="category_id" <?= $canManage ? '' : 'disabled' ?>>
                    <?php foreach ($categoryOptions as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= (int) $script['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="form-control" name="provider" value="<?= e($script['provider'] ?? '') ?>" placeholder="Google Analytics, Meta Pixel..." <?= $canManage ? '' : 'disabled' ?>>
                <select class="form-select" name="script_type" <?= $canManage ? '' : 'disabled' ?>><option value="inline" <?= $script['script_type'] === 'inline' ? 'selected' : '' ?>>Inline</option><option value="src" <?= $script['script_type'] === 'src' ? 'selected' : '' ?>>URL externa</option></select>
                <input class="form-control" name="src" value="<?= e($script['src'] ?? '') ?>" placeholder="https://..." <?= $canManage ? '' : 'disabled' ?>>
                <textarea class="form-control" name="code" rows="4" placeholder="Código do script" <?= $canManage ? '' : 'disabled' ?>><?= e($script['code'] ?? '') ?></textarea>
                <label class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" <?= !empty($script['active']) ? 'checked' : '' ?> <?= $canManage ? '' : 'disabled' ?>>Ativo</label>
                <?php if ($canManage): ?><button class="btn btn-outline-primary btn-sm">Salvar script</button><?php endif; ?>
            </form>
            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('/admin/consent/script/delete')) ?>" onsubmit="return confirm('Remover este script?');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e((string) $script['id']) ?>">
                    <button class="btn btn-outline-danger btn-sm">Remover script</button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php if ($canManage): ?>
        <form method="post" action="<?= e(url('/admin/consent/script')) ?>" class="consent-script-form consent-script-create">
            <?= csrf_field() ?>
            <input class="form-control" name="name" placeholder="Nome do script">
            <select class="form-select" name="category_id"><?php foreach ($categoryOptions as $category): ?><option value="<?= e((string) $category['id']) ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select>
            <input class="form-control" name="provider" placeholder="Fornecedor">
            <select class="form-select" name="script_type"><option value="inline">Inline</option><option value="src">URL externa</option></select>
            <input class="form-control" name="src" placeholder="URL externa se aplicável">
            <textarea class="form-control" name="code" rows="4" placeholder="Código inline se aplicável"></textarea>
            <label class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1">Ativo</label>
            <button class="btn btn-primary btn-sm">Cadastrar script</button>
        </form>
    <?php endif; ?>
</section>

<section class="consent-admin-grid">
    <article class="panel consent-panel">
        <div class="section-heading"><h2>Consentimentos</h2><span>Últimos registros</span></div>
        <div class="table-responsive"><table class="table align-middle consent-table"><thead><tr><th>Visitante</th><th>Usuário</th><th>Versão</th><th>Preferências</th><th>Data</th></tr></thead><tbody>
            <?php foreach ($records as $record): ?><tr><td><?= e(substr((string) $record['visitor_id'], 0, 8)) ?></td><td><?= e($record['user_name'] ?? 'Visitante') ?></td><td><?= e($record['policy_version']) ?></td><td><pre><?= e($jsonPretty($record['preferences_json'] ?? '')) ?></pre></td><td><?= e(date('d/m/Y H:i', strtotime((string) $record['created_at']))) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </article>
    <article class="panel consent-panel">
        <div class="section-heading"><h2>Histórico</h2><span>Auditoria administrativa</span></div>
        <div class="consent-audit-list">
            <?php foreach ($audits as $audit): ?><article><strong><?= e($audit['description']) ?></strong><span><?= e($audit['user_name'] ?? 'Sistema') ?> · <?= e(date('d/m/Y H:i', strtotime((string) $audit['created_at']))) ?></span></article><?php endforeach; ?>
        </div>
    </article>
</section>
