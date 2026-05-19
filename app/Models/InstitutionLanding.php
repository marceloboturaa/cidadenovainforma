<?php

namespace App\Models;

class InstitutionLanding
{
    private const SETTING_NAME = 'institution_landing';

    public static function get(): array
    {
        $raw = SiteSetting::get(self::SETTING_NAME, '');
        $data = json_decode($raw, true);

        return self::sanitize(is_array($data) ? $data : []);
    }

    public static function update(array $data): void
    {
        SiteSetting::set(
            self::SETTING_NAME,
            json_encode(self::sanitize($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public static function defaults(): array
    {
        $defaultImage = '/public/assets/img/institution-hero-community.jpg';

        return [
            'hero' => [
                'eyebrow' => 'Área institucional',
                'title' => 'INSTITUTO CIDADE NOVA INFORMA',
                'subtitle' => 'Comunicação comunitária, educação, cultura e transformação social.',
                'button_label' => 'Conheça nossos projetos',
                'button_url' => '#projetos',
                'image' => $defaultImage,
            ],
            'about' => [
                'eyebrow' => 'Quem somos',
                'title' => 'Comunicação popular que nasce do território',
                'body' => '<p>O Cidade Nova Informa nasceu da necessidade de dar visibilidade à vida comunitária, organizar informações úteis e fortalecer a memória do território. O projeto mantém sua base no jornalismo comunitário, com escuta, presença local e compromisso com informações de interesse público.</p><p>A área institucional reúne as frentes sociais e culturais ligadas ao portal: ações sociais, biblioteca comunitária, educação popular, sustentabilidade, horta comunitária, convivência com idosos e iniciativas que aproximam moradores, parceiros, voluntários e lideranças.</p><p>Essa atuação é uma extensão do trabalho jornalístico, sem substituir o foco principal do portal de notícias. A proposta é fortalecer pertencimento, cultura comunitária e transformação social a partir da comunicação popular.</p>',
            ],
            'gallery' => [
                'eyebrow' => 'Galeria',
                'title' => 'Registros da comunidade em movimento',
                'intro' => 'Fotos, vídeos, eventos, oficinas, rádio, biblioteca, horta e ações comunitárias em um acervo vivo e editável.',
                'items' => [
                    [
                        'type' => 'eventos',
                        'title' => 'Eventos comunitários',
                        'description' => 'Encontros, rodas de conversa e atividades abertas ao território.',
                        'url' => '',
                        'cover' => $defaultImage,
                    ],
                    [
                        'type' => 'biblioteca',
                        'title' => 'Biblioteca e leitura',
                        'description' => 'Ações de incentivo à leitura, memória local e educação.',
                        'url' => '',
                        'cover' => $defaultImage,
                    ],
                    [
                        'type' => 'horta',
                        'title' => 'Horta comunitária',
                        'description' => 'Sustentabilidade, cultivo, alimentação e cuidado coletivo.',
                        'url' => '',
                        'cover' => $defaultImage,
                    ],
                    [
                        'type' => 'rádio',
                        'title' => 'Rádio e comunicação',
                        'description' => 'Entrevistas, boletins, avisos e comunicação direta com moradores.',
                        'url' => '',
                        'cover' => $defaultImage,
                    ],
                ],
            ],
            'impact' => [
                'eyebrow' => 'Impacto social',
                'title' => 'Indicadores da atuação comunitária',
                'stats' => [
                    ['value' => '11+', 'label' => 'anos de atuação', 'description' => 'História construída com comunicação comunitária e presença no território.'],
                    ['value' => '100+', 'label' => 'ações realizadas', 'description' => 'Atividades sociais, culturais, educativas e de mobilização local.'],
                    ['value' => 'Milhares', 'label' => 'pessoas impactadas', 'description' => 'Moradores alcançados por informações, projetos e ações comunitárias.'],
                    ['value' => '6', 'label' => 'projetos desenvolvidos', 'description' => 'Frentes institucionais integradas à comunicação, educação, esporte e cultura.'],
                ],
            ],
            'support' => [
                'eyebrow' => 'Apoie o projeto',
                'title' => 'Participe dessa construção coletiva',
                'body' => 'O Instituto Cidade Nova Informa acolhe pessoas, empresas, coletivos e instituições que desejam contribuir com voluntariado, doações, parcerias e apoio técnico.',
                'items' => [
                    ['title' => 'Voluntariado', 'description' => 'Contribua com oficinas, leitura, comunicação, educação ou apoio em eventos.', 'button_label' => 'Quero ajudar', 'url' => '#contato'],
                    ['title' => 'Doações', 'description' => 'Apoie a biblioteca, a horta, ações educativas e atividades comunitárias.', 'button_label' => 'Como doar', 'url' => '#contato'],
                    ['title' => 'Parcerias', 'description' => 'Construa projetos sociais, culturais e educacionais junto ao CNI.', 'button_label' => 'Propor parceria', 'url' => '#contato'],
                    ['title' => 'Contato', 'description' => 'Envie sugestões, propostas, dúvidas e mensagens para a equipe institucional.', 'button_label' => 'Falar com o CNI', 'url' => '#contato'],
                ],
            ],
            'seo' => [
                'title' => 'Instituto Cidade Nova Informa - Comunicação comunitária e transformação social',
                'description' => 'Conheça a atuação institucional do Cidade Nova Informa em comunicação comunitária, educação, cultura, biblioteca, horta e ações sociais.',
            ],
        ];
    }

    public static function linkUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if ($url === '/public/assets/img/institution-hero-community.png') {
            return '/public/assets/img/institution-hero-community.jpg';
        }

        if (preg_match('~^(https?://|mailto:|tel:|/|#)~i', $url)) {
            return $url;
        }

        return '';
    }

    public static function mediaUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (preg_match('#drive\.google\.com/file/d/([^/]+)#i', $url, $match)
            || preg_match('#drive\.google\.com/open\?id=([^&]+)#i', $url, $match)
        ) {
            return 'https://drive.google.com/thumbnail?id=' . rawurlencode($match[1]) . '&sz=w1200';
        }

        if (preg_match('#^(https?://|//|/)#i', $url)) {
            return $url;
        }

        if (preg_match('#^(public/|uploads/)#i', $url)) {
            return '/' . ltrim($url, '/');
        }

        return '';
    }

    private static function sanitize(array $data): array
    {
        $defaults = self::defaults();

        return [
            'hero' => [
                'eyebrow' => self::text($data['hero']['eyebrow'] ?? '', $defaults['hero']['eyebrow']),
                'title' => self::text($data['hero']['title'] ?? '', $defaults['hero']['title']),
                'subtitle' => self::text($data['hero']['subtitle'] ?? '', $defaults['hero']['subtitle']),
                'button_label' => self::text($data['hero']['button_label'] ?? '', $defaults['hero']['button_label']),
                'button_url' => self::linkUrl((string) ($data['hero']['button_url'] ?? '')) ?: $defaults['hero']['button_url'],
                'image' => self::mediaUrl((string) ($data['hero']['image'] ?? '')) ?: $defaults['hero']['image'],
            ],
            'about' => [
                'eyebrow' => self::text($data['about']['eyebrow'] ?? '', $defaults['about']['eyebrow']),
                'title' => self::text($data['about']['title'] ?? '', $defaults['about']['title']),
                'body' => trim((string) ($data['about']['body'] ?? '')) ?: $defaults['about']['body'],
            ],
            'gallery' => [
                'eyebrow' => self::text($data['gallery']['eyebrow'] ?? '', $defaults['gallery']['eyebrow']),
                'title' => self::text($data['gallery']['title'] ?? '', $defaults['gallery']['title']),
                'intro' => self::text($data['gallery']['intro'] ?? '', $defaults['gallery']['intro']),
                'items' => self::galleryItems($data['gallery']['items'] ?? [], $defaults['gallery']['items']),
            ],
            'impact' => [
                'eyebrow' => self::text($data['impact']['eyebrow'] ?? '', $defaults['impact']['eyebrow']),
                'title' => self::text($data['impact']['title'] ?? '', $defaults['impact']['title']),
                'stats' => self::stats($data['impact']['stats'] ?? [], $defaults['impact']['stats']),
            ],
            'support' => [
                'eyebrow' => self::text($data['support']['eyebrow'] ?? '', $defaults['support']['eyebrow']),
                'title' => self::text($data['support']['title'] ?? '', $defaults['support']['title']),
                'body' => self::text($data['support']['body'] ?? '', $defaults['support']['body']),
                'items' => self::supportItems($data['support']['items'] ?? [], $defaults['support']['items']),
            ],
            'seo' => [
                'title' => self::text($data['seo']['title'] ?? '', $defaults['seo']['title']),
                'description' => self::text($data['seo']['description'] ?? '', $defaults['seo']['description']),
            ],
        ];
    }

    private static function text(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }

    private static function galleryItems(array $items, array $defaults): array
    {
        $normalized = [];
        $allowedTypes = ['fotos', 'vídeos', 'eventos', 'oficinas', 'rádio', 'biblioteca', 'horta', 'comunidade'];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $url = self::linkUrl((string) ($item['url'] ?? ''));
            $cover = self::mediaUrl((string) ($item['cover'] ?? ''));
            $type = mb_strtolower(trim((string) ($item['type'] ?? 'fotos')), 'UTF-8');

            if (!in_array($type, $allowedTypes, true)) {
                $type = 'fotos';
            }

            if ($title === '' && $description === '' && $url === '' && $cover === '') {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'title' => $title !== '' ? $title : 'Registro comunitário',
                'description' => $description,
                'url' => $url,
                'cover' => $cover,
            ];
        }

        return $normalized ?: $defaults;
    }

    private static function stats(array $stats, array $defaults): array
    {
        $normalized = [];

        foreach ($stats as $stat) {
            if (!is_array($stat)) {
                continue;
            }

            $value = trim((string) ($stat['value'] ?? ''));
            $label = trim((string) ($stat['label'] ?? ''));
            $description = trim((string) ($stat['description'] ?? ''));

            if ($value === '' && $label === '' && $description === '') {
                continue;
            }

            $normalized[] = [
                'value' => $value !== '' ? $value : '0',
                'label' => $label !== '' ? $label : 'indicador',
                'description' => $description,
            ];
        }

        return $normalized ?: $defaults;
    }

    private static function supportItems(array $items, array $defaults): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $buttonLabel = trim((string) ($item['button_label'] ?? ''));
            $url = self::linkUrl((string) ($item['url'] ?? ''));

            if ($title === '' && $description === '' && $buttonLabel === '' && $url === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title !== '' ? $title : 'Apoio',
                'description' => $description,
                'button_label' => $buttonLabel !== '' ? $buttonLabel : 'Saiba mais',
                'url' => $url,
            ];
        }

        return $normalized ?: $defaults;
    }
}
