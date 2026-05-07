<article class="institution-page institution-modern">
    <header class="institution-hero">
        <span>Portal institucional</span>
        <h1>Cidade Nova Informa</h1>
        <p>
            Bem-vindo ao espaço oficial do Cidade Nova Informa. Esta página apresenta a instituição, sua história e os projetos que fazem parte da atuação comunitária do portal.
        </p>
        <div class="institution-hero-actions">
            <a href="#projetos">Ver áreas</a>
            <a href="#historia">Nossa história</a>
        </div>
    </header>

    <section class="institution-intro">
        <div>
            <span>Apresentação</span>
            <h2>Comunicação local com responsabilidade e presença comunitária</h2>
        </div>
        <p>
            O Cidade Nova Informa atua na produção e organização de conteúdos de interesse público, com atenção à vida do bairro, aos serviços essenciais, aos projetos comunitários e às histórias que fazem parte da rotina local.
        </p>
    </section>

    <section class="institution-values" aria-label="Missão, visão e valores">
        <article>
            <span>Missão</span>
            <p>Informar a comunidade com responsabilidade, clareza e compromisso social, fortalecendo o acesso a notícias, serviços e registros da vida local.</p>
        </article>
        <article>
            <span>Visão</span>
            <p>Ser uma referência comunitária em comunicação, memória e utilidade pública para Cidade Nova e região.</p>
        </article>
        <article>
            <span>Valores</span>
            <p>Ética, transparência, participação, respeito, valorização da comunidade e preservação da memória local.</p>
        </article>
    </section>
<section class="institution-story" id="historia">
    <div class="institution-story-header">
        <span>História</span>
        <h2>Nossa trajetória</h2>
    </div>
<p class="institution-story-text">
    A história do Cidade Nova Informa nasce da necessidade de dar visibilidade aos acontecimentos do território, organizar informações úteis e valorizar registros importantes para os moradores. Com o tempo, o portal passou a integrar jornalismo, memória local e projetos de participação comunitária.
    
    
     <span> <a 
        href="https://cidadenovainforma.blogspot.com/p/historia.html" 
        target="_blank"
        class="institution-read-more"
    >
        Ver mais
    </a></span>
</p>
</section>

    <section class="institution-values institution-organization" aria-label="Diretoria e organização">
        <article>
            <span>Diretoria</span>
            <p>Coordenação institucional, direção editorial e responsáveis por projetos atuam de forma integrada na organização das frentes do portal.</p>
        </article>
        <article>
            <span>Notas públicas</span>
            <p>Comunicados oficiais, avisos institucionais e posicionamentos públicos são publicados nas áreas correspondentes do Cidade Nova Informa.</p>
        </article>
        <article>
            <span>Contatos</span>
            <p>Os canais digitais e redes sociais concentram o atendimento, sugestões de pauta e comunicação com leitores, parceiros e colaboradores.</p>
        </article>
    </section>

    <section class="institution-projects" id="projetos">
        <div class="institution-section-head">
            <span>Setores e projetos</span>
            <h2>Áreas institucionais</h2>
        </div>
        <div class="institution-project-grid">
            <?php foreach ($areas as $area): ?>
                <article class="institution-project-card">
                    <span><?= e($area['kicker']) ?></span>
                    <h3><?= e($area['name']) ?></h3>
                    <p><?= e($area['summary']) ?></p>
                    <a href="<?= e(url('/instituicao/' . $area['slug'])) ?>">Abrir página</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    
</article>
