<section class="listing-head">
    <h1>Documentos</h1>
    <p>Arquivos públicos disponibilizados pela equipe do Cidade Nova Informa.</p>
</section>

<?php if ($documents): ?>
    <script>
        (function () {
            function normalize(value) {
                return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('document-search');
                const typeFilter = document.getElementById('document-type-filter');
                const cards = Array.from(document.querySelectorAll('[data-document-card]'));
                const emptyState = document.querySelector('[data-document-empty]');

                if (!searchInput || !typeFilter) {
                    return;
                }

                function applyFilters() {
                    const query = normalize(searchInput.value.trim());
                    const type = typeFilter.value;
                    let visibleCount = 0;

                    cards.forEach(function (card) {
                        const title = normalize(card.dataset.title || '');
                        const matchesQuery = query === '' || title.includes(query);
                        const matchesType = type === '' || card.dataset.type === type;
                        const visible = matchesQuery && matchesType;

                        card.hidden = !visible;
                        visibleCount += visible ? 1 : 0;
                    });

                    if (emptyState) {
                        emptyState.hidden = visibleCount > 0;
                    }
                }

                searchInput.addEventListener('input', applyFilters);
                typeFilter.addEventListener('change', applyFilters);
            });
        })();
    </script>
<?php endif; ?>

<?php
$documentTypes = array_values(array_unique(array_filter(array_map(function (array $document): string {
    return \App\Models\Document::typeLabel($document);
}, $documents))));
sort($documentTypes);
?>

<?php if ($documents): ?>
    <section class="public-document-filters" aria-label="Filtros de documentos">
        <label>
            <span>Pesquisar</span>
            <input type="search" id="document-search" placeholder="Nome do documento" autocomplete="off">
        </label>
        <label>
            <span>Tipo</span>
            <select id="document-type-filter">
                <option value="">Todos</option>
                <?php foreach ($documentTypes as $type): ?>
                    <option value="<?= e($type) ?>"><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </section>
<?php endif; ?>

<section class="public-document-list">
    <?php foreach ($documents as $document): ?>
        <?php
        $documentType = \App\Models\Document::typeLabel($document);
        $isLink = \App\Models\Document::isExternalLink($document);
        $viewUrl = \App\Models\Document::publicUrl($document);
        $extension = strtolower(pathinfo((string) ($document['original_name'] ?? ''), PATHINFO_EXTENSION));
        $mime = strtolower((string) ($document['mime_type'] ?? ''));
        $isImage = str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
        $isPdf = $mime === 'application/pdf' || $extension === 'pdf';
        $isText = str_starts_with($mime, 'text/') || in_array($extension, ['txt', 'csv'], true);
        $isOffice = in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'], true);
        $officeViewer = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($viewUrl);
        ?>
        <article class="public-document-card" data-document-card data-title="<?= e(mb_strtolower($document['title'] ?? '', 'UTF-8')) ?>" data-type="<?= e($documentType) ?>">
            <div>
                <span><?= e($documentType) ?></span>
                <h2><?= e($document['title']) ?></h2>
                <?php if ($isLink): ?>
                    <p><?= e(parse_url($viewUrl, PHP_URL_HOST) ?: $viewUrl) ?></p>
                <?php endif; ?>
            </div>
            <a href="<?= e($isLink ? $viewUrl : url('/documentos/download?id=' . $document['id'])) ?>"<?= $isLink ? ' target="_blank" rel="noopener"' : '' ?>><?= $isLink ? 'Abrir link' : 'Baixar' ?></a>
            <?php if ($isImage || $isPdf || $isText || $isOffice || $isLink): ?>
                <details class="public-document-preview">
                    <summary>Ver na página</summary>
                    <?php if ($isLink): ?>
                        <div class="public-document-link-preview">
                            <strong><?= e($viewUrl) ?></strong>
                            <a href="<?= e($viewUrl) ?>" target="_blank" rel="noopener">Abrir em nova aba</a>
                        </div>
                    <?php elseif ($isImage): ?>
                        <img src="<?= e($viewUrl) ?>" alt="<?= e($document['title']) ?>" loading="lazy">
                    <?php elseif ($isPdf || $isText): ?>
                        <iframe src="<?= e($viewUrl) ?>" title="<?= e($document['title']) ?>"></iframe>
                    <?php elseif ($isOffice): ?>
                        <iframe src="<?= e($officeViewer) ?>" title="<?= e($document['title']) ?>"></iframe>
                        <p>A visualização de documentos Office depende do arquivo estar acessível publicamente na internet.</p>
                    <?php endif; ?>
                </details>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

    <?php if ($documents): ?>
        <article class="empty-public public-document-no-results" data-document-empty hidden>
            <h2>Nenhum documento encontrado</h2>
            <p>Ajuste a pesquisa ou o tipo selecionado.</p>
        </article>
    <?php endif; ?>

    <?php if (!$documents): ?>
        <article class="empty-public">
            <h2>Nenhum documento público</h2>
            <p>Quando houver documentos liberados ao público, eles aparecerão aqui.</p>
        </article>
    <?php endif; ?>
</section>
