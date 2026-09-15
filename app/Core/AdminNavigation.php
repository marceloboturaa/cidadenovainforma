<?php

namespace App\Core;

use App\Models\Document;
use App\Models\InstitutionPage;

class AdminNavigation
{
    public static function groups(): array
    {
        return [
            'Geral' => [['/admin', 'Dashboard', 'speedometer2']],
            'Usuários e acessos' => [['/admin/users', 'Usuários', 'people'], ['/admin/authorizations', 'Autorizações', 'shield-lock']],
            'Conteúdo' => [['/admin/news', 'Notícias', 'newspaper'], ['/admin/categories', 'Categorias', 'folder2-open'], ['/admin/tags', 'Tags', 'tags']],
            'Institucional' => [['/admin/institution-pages', 'Instituição', 'building'], ['/admin/people', 'Pessoas', 'person-lines-fill'], ['/admin/library-events', 'Eventos', 'calendar-event'], ['/admin/registrations', 'Inscrições', 'clipboard-check'], ['/admin/documents', 'Documentos', 'file-earmark-arrow-down']],
            'Cursos e certificados' => [['/admin/education', 'Curso', 'mortarboard'], ['/admin/education/certificates', 'Meus certificados', 'award'], ['/admin/education/certificate-center', 'Central de certificados', 'patch-check'], ['/admin/education/manage', 'Escola', 'house-door']],
            'Comunicação' => [['/admin/communication', 'Comunicação', 'chat-dots'], ['/admin/forum', 'Fóruns', 'chat-square-text']],
            'Gestão do site' => [['/admin/menu', 'Menu', 'list-ul'], ['/admin/backups', 'Backups', 'cloud-arrow-down'], ['/admin/consent', 'LGPD Cookies', 'shield-check']],
            'Minha conta' => [['/admin/profile', 'Meu cadastro', 'person-vcard'], ['/admin/password', 'Minha senha', 'key']],
        ];
    }

    public static function allows(string $path): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (StudentAccess::applies($user)) {
            return StudentAccess::allowsRoute('GET', $path);
        }
        if (in_array($path, ['/admin/education/certificate-center', '/admin/education/certificate-report', '/admin/education/certificate-administration'], true)) {
            if (Auth::hasRole('estudante') || !Auth::hasRole(['master', 'admin', 'admin-local', 'diretor', 'professor'])) {
                return false;
            }
        }

        $permissions = match ($path) {
            '/admin/users' => ['users.manage'],
            '/admin/news' => ['news.create', 'news.manage', 'news.approve'],
            '/admin/categories' => ['categories.manage'],
            '/admin/tags' => ['tags.manage'],
            '/admin/people' => ['people.manage'],
            '/admin/library-events' => ['events.manage'],
            '/admin/registrations' => ['event_participants.manage'],
            '/admin/education' => ['education.view', 'education.manage', 'education.teach'],
            '/admin/education/manage' => ['education.manage', 'education.teach'],
            '/admin/education/certificates' => ['education.view', 'education.manage', 'education.teach', 'certificates.issue', 'certificates.manage'],
            '/admin/education/recognitions', '/admin/education/certificate-administration' => ['certificates.issue', 'certificates.manage'],
            '/admin/forum' => ['forum.view', 'forum.create', 'forum.moderate'],
            '/admin/consent' => ['consent.view'],
            default => null,
        };
        if ($permissions !== null) {
            foreach ($permissions as $permission) {
                if (Auth::can($permission)) {
                    return true;
                }
            }
            return false;
        }

        return match ($path) {
            '/admin/education/certificate-center', '/admin/education/certificate-report' => Auth::hasRole(['master', 'admin', 'admin-local', 'diretor', 'professor']),
            '/admin/authorizations' => Auth::hasRole('master'),
            '/admin/menu', '/admin/backups' => ($user['role_slug'] ?? '') === 'master',
            '/admin/institution-pages' => Auth::hasRole(['master', 'admin']) || (bool) InstitutionPage::manageableForUser((int) $user['id'], false),
            '/admin/documents' => Auth::can('documents.view') || (Auth::can('documents.manage') && !Auth::hasRole('diretor')) || Document::userCanUpload((int) $user['id']) || Document::userHasAnyAccess((int) $user['id']),
            // These pages are available to every authenticated account.
            '/admin', '/admin/profile', '/admin/password', '/admin/communication' => true,
            default => false,
        };
    }

    public static function contains(string $path): bool
    {
        foreach (self::groups() as $items) {
            foreach ($items as $item) {
                if ($item[0] === $path) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function visibleGroups(): array
    {
        $groups = [];
        foreach (self::groups() as $label => $items) {
            $visible = array_values(array_filter($items, fn ($item) => self::allows($item[0])));
            if ($visible) {
                $groups[$label] = $visible;
            }
        }
        return $groups;
    }
}
