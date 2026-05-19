<?php

namespace App\Models;

use App\Core\Database;

class InstitutionPage
{
    public static function all(): array
    {
        self::ensureTables();
        self::seedDefaults();

        $rows = Database::connection()
            ->query('SELECT * FROM institution_pages ORDER BY sort_order ASC, name ASC')
            ->fetchAll();

        return array_map([self::class, 'normalize'], $rows);
    }

    public static function landingProjects(): array
    {
        self::ensureTables();
        self::seedDefaults();

        $rows = Database::connection()
            ->query('SELECT * FROM institution_pages WHERE show_on_landing = 1 ORDER BY sort_order ASC, name ASC')
            ->fetchAll();

        return array_map([self::class, 'normalize'], $rows);
    }

    public static function find(string $slug): ?array
    {
        self::ensureTables();
        self::seedDefaults();

        $stmt = Database::connection()->prepare('SELECT * FROM institution_pages WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $page = $stmt->fetch();

        return $page ? self::normalize($page) : null;
    }

    public static function update(string $slug, array $data): void
    {
        self::ensureTables();

        Database::connection()->prepare(
            'UPDATE institution_pages
             SET name = :name,
                 kicker = :kicker,
                 summary = :summary,
                 description = :description,
                 team_json = :team_json,
                 materials_json = :materials_json,
                 photos_json = :photos_json,
                 galleries_json = :galleries_json,
                 cover_image = :cover_image,
                 cta_label = :cta_label,
                 cta_url = :cta_url,
                 show_on_landing = :show_on_landing,
                 search_terms = :search_terms,
                 related_tags_json = :related_tags_json,
                 updated_at = NOW()
             WHERE slug = :slug'
        )->execute([
            'slug' => $slug,
            'name' => trim($data['name']),
            'kicker' => trim($data['kicker']),
            'summary' => trim($data['summary']),
            'description' => trim($data['description']),
            'team_json' => json_encode(self::lines($data['team'] ?? ''), JSON_UNESCAPED_UNICODE),
            'materials_json' => json_encode(self::lines($data['materials'] ?? ''), JSON_UNESCAPED_UNICODE),
            'photos_json' => json_encode(self::photoLines($data['photos'] ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'galleries_json' => json_encode(self::galleries($data['galleries'] ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'cover_image' => self::normalizePhotoUrl($data['cover_image'] ?? ''),
            'cta_label' => trim($data['cta_label'] ?? ''),
            'cta_url' => self::normalizeLinkUrl($data['cta_url'] ?? ''),
            'show_on_landing' => !empty($data['show_on_landing']) ? 1 : 0,
            'search_terms' => trim($data['search_terms'] ?? ''),
            'related_tags_json' => json_encode(self::tagSlugs($data['related_tags'] ?? []), JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function manageableForUser(int $userId, bool $isMaster = false): array
    {
        self::ensureTables();
        self::seedDefaults();

        if ($isMaster) {
            return self::all();
        }

        $stmt = Database::connection()->prepare(
            'SELECT institution_pages.*
             FROM institution_pages
             INNER JOIN institution_page_users ON institution_page_users.page_slug = institution_pages.slug
             WHERE institution_page_users.user_id = :user_id
             ORDER BY institution_pages.sort_order ASC, institution_pages.name ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map([self::class, 'normalize'], $stmt->fetchAll());
    }

    public static function userResponsibilities(int $userId): array
    {
        self::ensureTables();

        $stmt = Database::connection()->prepare('SELECT page_slug FROM institution_page_users WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return array_column($stmt->fetchAll(), 'page_slug');
    }

    public static function syncUserResponsibilities(int $userId, array $slugs): void
    {
        self::ensureTables();
        self::seedDefaults();

        $validSlugs = array_column(self::all(), 'slug');
        $slugs = array_values(array_intersect(array_unique(array_map('strval', $slugs)), $validSlugs));
        $db = Database::connection();

        $db->beginTransaction();

        try {
            $db->prepare('DELETE FROM institution_page_users WHERE user_id = :user_id')->execute(['user_id' => $userId]);
            $stmt = $db->prepare('INSERT IGNORE INTO institution_page_users (page_slug, user_id, created_at) VALUES (:page_slug, :user_id, NOW())');

            foreach ($slugs as $slug) {
                $stmt->execute(['page_slug' => $slug, 'user_id' => $userId]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function canManage(string $slug, int $userId, bool $isMaster = false): bool
    {
        if ($isMaster) {
            return self::find($slug) !== null;
        }

        return in_array($slug, self::userResponsibilities($userId), true);
    }

    public static function defaults(): array
    {
        $defaultImage = '/public/assets/img/institution-hero-community.jpg';

        return [
            [
                'slug' => 'jornalismo-comunitario',
                'name' => 'Jornalismo Comunitário',
                'kicker' => 'Comunicação popular e utilidade pública',
                'summary' => 'Produção de notícias, memória local, serviços e informações de interesse público para o território.',
                'description' => 'O Jornalismo Comunitário é a base do Cidade Nova Informa. A frente organiza pautas do bairro, registra histórias, acompanha serviços públicos e fortalece a circulação de informações úteis para moradores, lideranças e parceiros.',
                'team' => ['Equipe editorial', 'Colaboradores locais', 'Moradores e fontes comunitárias'],
                'materials' => ['Sugestões de pauta', 'Cobertura comunitária', 'Registros de memória local'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Conhecer o jornalismo',
                'cta_url' => '',
                'show_on_landing' => 1,
                'search' => 'jornalismo comunicação comunidade bairro notícia noticias',
                'related_tags' => ['jornalismo', 'bairro', 'comunidade'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'biblioteca',
                'name' => 'Biblioteca Comunitária',
                'kicker' => 'Leitura, memória e formação',
                'summary' => 'Espaço dedicado à leitura, pesquisa e preservação de registros importantes para a comunidade.',
                'description' => 'A Biblioteca reúne ações de incentivo à leitura, consulta a conteúdos educativos e valorização da memória local. O espaço apoia estudantes, moradores e leitores interessados em conhecer melhor a história e as iniciativas da instituição.',
                'team' => ['Coordenação institucional', 'Colaboradores de leitura', 'Voluntários e apoiadores culturais'],
                'materials' => ['Reprise comunitária', 'Apoio à leitura', 'Registros históricos'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Conhecer a biblioteca',
                'cta_url' => '',
                'show_on_landing' => 1,
                'search' => 'biblioteca',
                'related_tags' => ['biblioteca'],
                'sort_order' => 20,
            ],
            [
                'slug' => 'educacao',
                'name' => 'Educação',
                'kicker' => 'Educação popular e formação cidadã',
                'summary' => 'Atividades educativas, oficinas, cursos e ações de aprendizagem ligadas à comunicação e ao território.',
                'description' => 'A frente de Educação reúne oficinas, formações, cursos e atividades de educação popular. O objetivo é ampliar oportunidades de aprendizagem, fortalecer autonomia comunitária e aproximar estudantes, educadores e moradores.',
                'team' => ['Educadores parceiros', 'Coordenação pedagógica', 'Voluntários e estudantes'],
                'materials' => ['Oficinas comunitárias', 'Cursos e formações', 'Materiais de aprendizagem'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Ver ações educativas',
                'cta_url' => '',
                'show_on_landing' => 1,
                'search' => 'educação educacao curso oficina formação formacao',
                'related_tags' => ['educacao', 'formacao'],
                'sort_order' => 30,
            ],
            [
                'slug' => 'horta',
                'name' => 'Horta Comunitária',
                'kicker' => 'Educação ambiental e cuidado coletivo',
                'summary' => 'Projeto voltado ao cultivo, sustentabilidade, alimentação saudável e participação comunitária.',
                'description' => 'A Horta aproxima a comunidade de práticas sustentáveis e do cuidado com o território. O espaço integra cultivo, educação ambiental e ações coletivas que valorizam alimentação saudável, preservação e participação.',
                'team' => ['Coordenação do projeto', 'Equipe de manutenção', 'Educadores e participantes da comunidade'],
                'materials' => ['Registros de plantio', 'Orientações de cultivo', 'Ações educativas'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Conhecer a horta',
                'cta_url' => '',
                'show_on_landing' => 1,
                'search' => 'horta',
                'related_tags' => ['horta'],
                'sort_order' => 40,
            ],
            [
                'slug' => 'idosos',
                'name' => 'Projeto com Idosos',
                'kicker' => 'Convivência, cuidado e pertencimento',
                'summary' => 'Ações de convivência, escuta, cultura e fortalecimento de vínculos com pessoas idosas da comunidade.',
                'description' => 'O Projeto com Idosos valoriza convivência, escuta e participação social. As ações buscam criar oportunidades de encontro, cuidado, memória, cultura e fortalecimento de vínculos entre gerações.',
                'team' => ['Coordenação social', 'Voluntários', 'Parceiros da rede comunitária'],
                'materials' => ['Rodas de conversa', 'Atividades culturais', 'Ações de convivência'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Conhecer o projeto',
                'cta_url' => '',
                'show_on_landing' => 1,
                'search' => 'idosos terceira idade convivência memoria',
                'related_tags' => ['idosos', 'comunidade'],
                'sort_order' => 50,
            ],
            [
                'slug' => 'esporte',
                'name' => 'Esporte',
                'kicker' => 'Movimento, lazer e inclusão comunitária',
                'summary' => 'Atividades esportivas, recreativas e de convivência para fortalecer saúde, disciplina, lazer e integração no território.',
                'description' => 'A frente de Esporte promove atividades físicas, torneios, encontros recreativos e ações de lazer como ferramentas de inclusão social, convivência e cuidado com a saúde. O projeto aproxima crianças, jovens, adultos e famílias por meio do movimento, do trabalho em equipe e do fortalecimento comunitário.',
                'team' => ['Coordenação esportiva', 'Educadores físicos e voluntários', 'Apoiadores comunitários'],
                'materials' => ['Calendário de atividades', 'Torneios e encontros', 'Ações de lazer e saúde'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Conhecer o esporte',
                'cta_url' => '',
                'show_on_landing' => 1,
                'search' => 'esporte futebol lazer atividade fisica saude jovens comunidade',
                'related_tags' => ['esporte', 'comunidade', 'saude'],
                'sort_order' => 55,
            ],
            [
                'slug' => 'radio',
                'name' => 'Rádio Comunitária',
                'kicker' => 'Comunicação, voz e serviço público',
                'summary' => 'Canal de comunicação para programação ao vivo, boletins, entrevistas e avisos de utilidade pública.',
                'description' => 'A Rádio Cidade Nova Informa fortalece a comunicação direta com a comunidade por meio da programação online, entrevistas, boletins e avisos de utilidade pública. É um canal voltado à participação, escuta e circulação de informação local.',
                'team' => ['Apresentação e produção', 'Equipe editorial', 'Técnica e apoio de comunicação'],
                'materials' => ['https://radiowebcni.ismyradio.com/', 'https://radio.cidadenovainforma.com.br/'],
                'photos' => [],
                'galleries' => [],
                'cover_image' => $defaultImage,
                'cta_label' => 'Ouvir a rádio',
                'cta_url' => '',
                'show_on_landing' => 0,
                'search' => 'radio rádio',
                'related_tags' => ['radio', 'rádio'],
                'sort_order' => 60,
            ],
        ];
    }

    private static function normalize(array $page): array
    {
        $page['team'] = self::decode($page['team_json'] ?? '[]');
        $page['materials'] = self::decode($page['materials_json'] ?? '[]');
        $page['photos'] = self::decode($page['photos_json'] ?? '[]');
        $page['galleries'] = self::decode($page['galleries_json'] ?? '[]');
        $page['related_tags'] = self::decode($page['related_tags_json'] ?? '[]');
        $page['cover_image'] = self::normalizePhotoUrl($page['cover_image'] ?? '') ?: '/public/assets/img/institution-hero-community.jpg';
        $page['cta_label'] = trim((string) ($page['cta_label'] ?? ''));
        $page['cta_url'] = self::normalizeLinkUrl($page['cta_url'] ?? '');
        $page['show_on_landing'] = (int) ($page['show_on_landing'] ?? 1);
        unset($page['team_json'], $page['materials_json'], $page['photos_json'], $page['galleries_json'], $page['related_tags_json']);

        return $page;
    }

    private static function lines(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R+/', $value) ?: [])));
    }

    private static function photoLines(string $value): array
    {
        $photos = [];

        foreach (self::lines($value) as $line) {
            $photo = self::normalizePhotoUrl($line);

            if ($photo !== '') {
                $photos[] = $photo;
            }
        }

        return array_values(array_unique($photos));
    }

    private static function tagSlugs(array|string $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,;|]+/', $value);
        $items = $items ?: [];

        return array_values(array_unique(array_filter(array_map(function (string $tag): string {
            return slugify($tag);
        }, array_map('trim', $items)))));
    }

    private static function galleries(array $value): array
    {
        $titles = $value['title'] ?? [];
        $descriptions = $value['description'] ?? [];
        $urls = $value['url'] ?? [];
        $covers = $value['cover'] ?? [];
        $galleries = [];

        foreach ($urls as $index => $url) {
            $url = trim((string) $url);
            $title = trim((string) ($titles[$index] ?? ''));

            if ($url === '' || $title === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }

            $galleries[] = [
                'title' => $title,
                'description' => trim((string) ($descriptions[$index] ?? '')),
                'url' => $url,
                'cover' => self::normalizePhotoUrl((string) ($covers[$index] ?? '')),
            ];
        }

        return $galleries;
    }

    private static function normalizePhotoUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if ($value === '/public/assets/img/institution-hero-community.png') {
            return '/public/assets/img/institution-hero-community.jpg';
        }

        if (preg_match('#drive\.google\.com/file/d/([^/]+)#i', $value, $match)
            || preg_match('#drive\.google\.com/open\?id=([^&]+)#i', $value, $match)
        ) {
            return 'https://drive.google.com/thumbnail?id=' . rawurlencode($match[1]) . '&sz=w1200';
        }

        if (preg_match('#^https?://#i', $value) || str_starts_with($value, '//')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        if (preg_match('#^(public/|uploads/)#i', $value)) {
            return '/' . ltrim($value, '/');
        }

        return '';
    }

    private static function normalizeLinkUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (preg_match('~^(https?://|mailto:|tel:|/|#)~i', $value)) {
            return $value;
        }

        return '';
    }

    private static function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function ensureTables(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS institution_pages (
                slug VARCHAR(80) PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                kicker VARCHAR(180) NOT NULL,
                summary TEXT NOT NULL,
                description TEXT NOT NULL,
                team_json TEXT NULL,
                materials_json TEXT NULL,
                photos_json TEXT NULL,
                galleries_json TEXT NULL,
                cover_image VARCHAR(255) NULL,
                cta_label VARCHAR(80) NULL,
                cta_url VARCHAR(255) NULL,
                show_on_landing TINYINT(1) NOT NULL DEFAULT 1,
                search_terms VARCHAR(255) NOT NULL,
                related_tags_json TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB'
        );
        self::ensureColumn('institution_pages', 'galleries_json', 'TEXT NULL AFTER photos_json');
        self::ensureColumn('institution_pages', 'cover_image', 'VARCHAR(255) NULL AFTER galleries_json');
        self::ensureColumn('institution_pages', 'cta_label', 'VARCHAR(80) NULL AFTER cover_image');
        self::ensureColumn('institution_pages', 'cta_url', 'VARCHAR(255) NULL AFTER cta_label');
        self::ensureColumn('institution_pages', 'show_on_landing', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER cta_url');
        self::ensureColumn('institution_pages', 'related_tags_json', 'TEXT NULL AFTER search_terms');
        $db->exec(
            'CREATE TABLE IF NOT EXISTS institution_page_users (
                page_slug VARCHAR(80) NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (page_slug, user_id),
                CONSTRAINT fk_institution_page_users_page FOREIGN KEY (page_slug) REFERENCES institution_pages(slug) ON DELETE CASCADE,
                CONSTRAINT fk_institution_page_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        self::ensureColumn('institution_page_users', 'created_at', 'TIMESTAMP NULL');

        $done = true;
    }

    private static function seedDefaults(): void
    {
        static $done = false;

        if ($done) {
            return;
        }

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO institution_pages
                (slug, name, kicker, summary, description, team_json, materials_json, photos_json, galleries_json, cover_image, cta_label, cta_url, show_on_landing, search_terms, related_tags_json, sort_order, created_at, updated_at)
             VALUES
                (:slug, :name, :kicker, :summary, :description, :team_json, :materials_json, :photos_json, :galleries_json, :cover_image, :cta_label, :cta_url, :show_on_landing, :search_terms, :related_tags_json, :sort_order, NOW(), NOW())'
        );

        foreach (self::defaults() as $page) {
            $stmt->execute([
                'slug' => $page['slug'],
                'name' => $page['name'],
                'kicker' => $page['kicker'],
                'summary' => $page['summary'],
                'description' => $page['description'],
                'team_json' => json_encode($page['team'], JSON_UNESCAPED_UNICODE),
                'materials_json' => json_encode($page['materials'], JSON_UNESCAPED_UNICODE),
                'photos_json' => json_encode($page['photos'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'galleries_json' => json_encode($page['galleries'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'cover_image' => $page['cover_image'],
                'cta_label' => $page['cta_label'],
                'cta_url' => $page['cta_url'],
                'show_on_landing' => $page['show_on_landing'],
                'search_terms' => $page['search'],
                'related_tags_json' => json_encode($page['related_tags'], JSON_UNESCAPED_UNICODE),
                'sort_order' => $page['sort_order'],
            ]);
        }

        self::applyOneTimeDefaultAdjustments();

        $done = true;
    }

    private static function applyOneTimeDefaultAdjustments(): void
    {
        if (SiteSetting::get('institution_pages_default_adjusted_20260516', '0') === '1') {
            return;
        }

        $db = Database::connection();
        $db->exec("UPDATE institution_pages SET name = 'Biblioteca Comunitária' WHERE slug = 'biblioteca' AND name = 'Biblioteca'");
        $db->exec("UPDATE institution_pages SET name = 'Horta Comunitária' WHERE slug = 'horta' AND name = 'Horta'");
        $db->exec("UPDATE institution_pages SET name = 'Rádio Comunitária', show_on_landing = 0 WHERE slug = 'radio' AND name IN ('Rádio', 'Radio')");
        $db->exec("UPDATE institution_pages SET sort_order = 20 WHERE slug = 'biblioteca' AND sort_order = 10");
        $db->exec("UPDATE institution_pages SET sort_order = 40 WHERE slug = 'horta' AND sort_order = 20");
        $db->exec("UPDATE institution_pages SET sort_order = 60 WHERE slug = 'radio' AND sort_order = 30");
        $db->exec("UPDATE institution_pages SET cover_image = '/public/assets/img/institution-hero-community.jpg' WHERE cover_image IS NULL OR cover_image = ''");

        SiteSetting::set('institution_pages_default_adjusted_20260516', '1');
    }

    private static function ensureColumn(string $table, string $column, string $definition): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $db->quote($column));

        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}
