<?php $isRadioArea = ($area['slug'] ?? '') === 'radio'; ?>
<?php $hasGalleries = !empty($area['galleries']); ?>
<?php $hasLoosePhotos = !empty($photos); ?>

<?php $areaCoverImage = !empty($area['cover_image']) ? media_url($area['cover_image']) : ''; ?>

<article class="institution-area-page">
    <nav class="institution-breadcrumb" aria-label="Caminho">
        <a href="<?= e(url('/instituicao')) ?>">Instituição</a>
        <span><?= e($area['name']) ?></span>
    </nav>

    <header class="institution-area-hero <?= $areaCoverImage ? 'has-area-image' : '' ?>" <?= $areaCoverImage ? 'style="--institution-area-image: url(\'' . e($areaCoverImage) . '\');"' : '' ?>>
        <span><?= e($area['kicker']) ?></span>
        <h1><?= e($area['name']) ?></h1>
        <div><?= article_html($area['description']) ?></div>
    </header>

    <section class="area-layout">
        <div class="area-main">
            <section class="area-section">
                <div class="institution-section-head">
                    <span>Projeto</span>
                    <h2>Descrição do projeto</h2>
                </div>
                <div><?= article_html($area['description']) ?></div>
            </section>

            <?php if ($isRadioArea): ?>
                <section class="area-section radio-listen-section">
                    <div class="institution-section-head">
                        <span>Rádio ao vivo</span>
                        <h2>Ouça a Rádio Cidade Nova Informa</h2>
                    </div>
                    <div class="radio-player-card">
                        <div class="radio-links">
                            <a href="https://radiowebcni.ismyradio.com/">Abrir Radio Web CNI</a>
                            <a href="https://radio.cidadenovainforma.com.br/">Abrir rádio oficial</a>
                        </div>
                        <div class="radio-embed">
                            <div class="cstrEmbed" data-type="newStreamPlayer" data-publicToken="9fd14a3e-a30a-4bee-aa36-5c190d33a579" data-theme="light" data-color="ED0000" data-channelId="" data-rendered="false">
                                <a href="https://www.caster.fm">Shoutcast Hosting</a>
                                <a href="https://www.caster.fm">Stream Hosting</a>
                                <a href="https://www.caster.fm">Radio Server Hosting</a>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($hasGalleries): ?>
                <section class="area-section">
                    <div class="institution-section-head">
                        <span>Galeria</span>
                        <h2>Álbuns de fotos</h2>
                    </div>
                    <div class="institution-gallery-grid">
                        <?php foreach ($area['galleries'] as $gallery): ?>
                            <article class="institution-gallery-card">
                                <a class="institution-gallery-cover" href="<?= e($gallery['url']) ?>" aria-label="Abrir galeria <?= e($gallery['title']) ?>">
                                    <?php if (!empty($gallery['cover'])): ?>
                                        <img src="<?= e(media_url($gallery['cover'])) ?>" alt="<?= e($gallery['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span><?= e(mb_strtoupper(mb_substr($gallery['title'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                                    <?php endif; ?>
                                </a>
                                <div>
                                    <h3><?= e($gallery['title']) ?></h3>
                                    <?php if (!empty($gallery['description'])): ?>
                                        <p><?= e($gallery['description']) ?></p>
                                    <?php endif; ?>
                                    <a class="institution-gallery-button" href="<?= e($gallery['url']) ?>">Abrir Galeria</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($hasLoosePhotos): ?>
                <section class="area-section">
                    <div class="institution-section-head">
                        <span>Fotos</span>
                        <h2>Fotos avulsas</h2>
                    </div>
                    <div class="area-photo-grid">
                        <?php foreach ($photos as $photo): ?>
                            <figure>
                                <img src="<?= e(media_url($photo)) ?>" alt="<?= e($area['name']) ?>" loading="lazy">
                                <a href="<?= e(media_url($photo)) ?>">Abrir imagem</a>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="area-section">
                <div class="institution-section-head">
                    <span>Notícias</span>
                    <h2>Matérias relacionadas</h2>
                </div>
                <?php if ($relatedNews): ?>
                    <div class="news-grid compact-grid">
                        <?php foreach ($relatedNews as $item): ?>
                            <?php require dirname(__DIR__) . '/public/partials/news-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <article class="empty-public area-empty-news">
                        <h2>Nenhuma matéria relacionada encontrada</h2>
                        <p>As notícias vinculadas a <?= e($area['name']) ?> aparecerão aqui quando forem publicadas.</p>
                    </article>
                <?php endif; ?>
            </section>
        </div>

        <aside class="area-side">
            <section>
                <h2>Equipe responsável</h2>
                <ul>
                    <?php foreach ($area['team'] as $member): ?>
                        <li><?= e($member) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section>
                <h2><?= $isRadioArea ? 'Acessos da rádio' : 'Informações da área' ?></h2>
                <ul>
                    <?php if ($isRadioArea): ?>
                        <li><a href="https://radiowebcni.ismyradio.com/">Radio Web CNI</a></li>
                        <li><a href="https://radio.cidadenovainforma.com.br/">Rádio Cidade Nova Informa</a></li>
                    <?php else: ?>
                        <?php foreach ($area['materials'] as $material): ?>
                            <?php $line = link_line($material); ?>
                            <li>
                                <?php if ($line['url']): ?>
                                    <a href="<?= e($line['url']) ?>"><?= e($line['label']) ?></a>
                                <?php else: ?>
                                    <?= e($line['label']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>
            <section>
                <h2>Outras áreas</h2>
                <nav class="area-nav">
                    <?php foreach ($areas as $other): ?>
                        <a class="<?= $other['slug'] === $area['slug'] ? 'active' : '' ?>" href="<?= e(url('/instituicao/' . $other['slug'])) ?>">
                            <?= e($other['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </section>
        </aside>
    </section>
</article>

<?php if ($isRadioArea): ?>
    <script src="//cdn.cloud.caster.fm//widgets/embed.js"></script>
<?php endif; ?>

