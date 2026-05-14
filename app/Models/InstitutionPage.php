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
        return [
            [
                'slug' => 'biblioteca',
                'name' => 'Biblioteca',
                'kicker' => 'Leitura, memória e formação',
                'summary' => 'Espaço dedicado à leitura, pesquisa e preservação de registros importantes para a comunidade.',
                'description' => 'A Biblioteca reúne ações de incentivo à leitura, consulta a conteúdos educativos e valorização da memória local. O espaço apoia estudantes, moradores e leitores interessados em conhecer melhor a história e as iniciativas da instituição.',
                'team' => ['Coordenação institucional', 'Colaboradores de leitura', 'Voluntários e apoiadores culturais'],
                'materials' => ['Acervo comunitário', 'Apoio à leitura', 'Registros históricos'],
                'photos' => [],
                'galleries' => [],
                'search' => 'biblioteca',
                'related_tags' => ['biblioteca'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'horta',
                'name' => 'Horta',
                'kicker' => 'Educação ambiental e cuidado coletivo',
                'summary' => 'Projeto voltado ao cultivo, sustentabilidade, alimentação saudável e participação comunitária.',
                'description' => 'A Horta aproxima a comunidade de práticas sustentáveis e do cuidado com o território. O espaço integra cultivo, educação ambiental e ações coletivas que valorizam alimentação saudável, preservação e participação.',
                'team' => ['Coordenação do projeto', 'Equipe de manutenção', 'Educadores e participantes da comunidade'],
                'materials' => ['Registros de plantio', 'Orientações de cultivo', 'Ações educativas'],
                'photos' => [],
                'galleries' => [],
                'search' => 'horta',
                'related_tags' => ['horta'],
                'sort_order' => 20,
            ],
            [
                'slug' => 'radio',
                'name' => 'Rádio',
                'kicker' => 'Comunicação, voz e serviço público',
                'summary' => 'Canal de comunicação para programação ao vivo, boletins, entrevistas e avisos de utilidade pública.',
                'description' => 'A Rádio Cidade Nova Informa fortalece a comunicação direta com a comunidade por meio da programação online, entrevistas, boletins e avisos de utilidade pública. É um canal voltado à participação, escuta e circulação de informação local.',
                'team' => ['Apresentação e produção', 'Equipe editorial', 'Técnica e apoio de comunicação'],
                'materials' => ['https://radiowebcni.ismyradio.com/', 'https://radio.cidadenovainforma.com.br/'],
                'photos' => [],
                'galleries' => [],
                'search' => 'radio rádio',
                'related_tags' => ['radio', 'rádio'],
                'sort_order' => 30,
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
                search_terms VARCHAR(255) NOT NULL,
                related_tags_json TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB'
        );
        self::ensureColumn('institution_pages', 'galleries_json', 'TEXT NULL AFTER photos_json');
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
                (slug, name, kicker, summary, description, team_json, materials_json, photos_json, galleries_json, search_terms, related_tags_json, sort_order, created_at, updated_at)
             VALUES
                (:slug, :name, :kicker, :summary, :description, :team_json, :materials_json, :photos_json, :galleries_json, :search_terms, :related_tags_json, :sort_order, NOW(), NOW())'
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
                'search_terms' => $page['search'],
                'related_tags_json' => json_encode($page['related_tags'], JSON_UNESCAPED_UNICODE),
                'sort_order' => $page['sort_order'],
            ]);
        }

        $done = true;
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
