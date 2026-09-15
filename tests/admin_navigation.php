<?php

// Run in isolation: php tests/admin_navigation.php
namespace App\Core {
    class Auth
    {
        public static ?array $account = null;
        public static array $permissions = [];
        public static function user(): ?array { return self::$account; }
        public static function hasRole(string|array $roles): bool { return (bool) array_intersect((array) $roles, explode(',', self::$account['role_slugs'] ?? '')); }
        public static function can(string $permission): bool { return (self::$account['role_slug'] ?? '') === 'master' || in_array($permission, self::$permissions, true); }
    }
}
namespace App\Models {
    class Document
    {
        public static bool $access = false;
        public static function userCanUpload(int $id): bool { return false; }
        public static function userHasAnyAccess(int $id): bool { return self::$access; }
    }
    class InstitutionPage
    {
        public static bool $access = false;
        public static function manageableForUser(int $id, bool $master): array { return self::$access ? [['id' => 1]] : []; }
    }
}
namespace {
    require dirname(__DIR__) . '/app/Core/StudentAccess.php';
    require dirname(__DIR__) . '/app/Core/AdminNavigation.php';
    use App\Core\AdminNavigation;
    use App\Core\Auth;
    function check(bool $condition, string $message): void { if (!$condition) { throw new \RuntimeException($message); } }
    check(AdminNavigation::visibleGroups() === [], 'Guest menu must be empty');
    foreach (['usuario', 'admin', 'admin-local', 'diretor', 'professor', 'delegado-emissor'] as $role) {
        Auth::$account = ['id' => 1, 'role_slug' => $role, 'role_slugs' => $role];
        Auth::$permissions = [];
        foreach (['/admin/news', '/admin/users', '/admin/education/manage', '/admin/education/certificate-administration', '/admin/forum'] as $path) {
            check(!AdminNavigation::allows($path), "$role cannot access $path without permission");
        }
        $reportAccess = in_array($role, ['admin', 'admin-local', 'diretor', 'professor'], true);
        check(AdminNavigation::allows('/admin/education/certificate-report') === $reportAccess, 'Report role access');
        $hubAccess = $reportAccess;
        check(AdminNavigation::allows('/admin/education/certificate-center') === $hubAccess, 'Central access by role');
        check(isset(AdminNavigation::visibleGroups()['Cursos e certificados']) === $hubAccess, 'Course group follows hub access');
        Auth::$permissions = ['education.teach', 'news.create', 'certificates.issue'];
        foreach (['/admin/news', '/admin/education/manage'] as $path) {
            check(AdminNavigation::allows($path), "Explicit permission must allow $path");
        }
        check(AdminNavigation::allows('/admin/education/certificate-center') === $hubAccess, 'Central requires a teacher or administrator role even with permissions');
    }
    foreach (['estudante', 'estudante,professor'] as $roles) {
        Auth::$account = ['id' => 1, 'role_slug' => 'estudante', 'role_slugs' => $roles];
        Auth::$permissions = ['education.teach', 'certificates.issue', 'certificates.manage'];
        check(AdminNavigation::allows('/admin/education/certificates'), 'Students retain personal certificates');
        foreach (['/admin/education/certificate-center', '/admin/education/certificate-report', '/admin/education/certificate-administration'] as $path) {
            check(!AdminNavigation::allows($path), 'Students cannot access certificate management: ' . $path);
        }
    }
    Auth::$account = ['id' => 1, 'role_slug' => 'usuario', 'role_slugs' => 'usuario'];
    Auth::$permissions = [];
    \App\Models\Document::$access = true;
    \App\Models\InstitutionPage::$access = true;
    check(AdminNavigation::allows('/admin/documents'), 'Individual document access');
    check(AdminNavigation::allows('/admin/institution-pages'), 'Individual institution access');
    Auth::$account = ['id' => 1, 'role_slug' => 'master', 'role_slugs' => 'master'];
    foreach (AdminNavigation::groups() as $items) foreach ($items as [$path]) {
        check(AdminNavigation::allows($path), "Master access to $path");
        check(AdminNavigation::contains($path), "Route guard includes $path");
    }
    check(!AdminNavigation::allows('/admin/unknown'), 'Unknown menu items are denied');
    echo "Menu: perfis, permissões concedidas/retiradas, grupos vazios e acessos individuais validados.\n";
}
