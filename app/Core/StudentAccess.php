<?php

namespace App\Core;

/** Student access is an explicit ceiling, including accounts with extra roles. */
class StudentAccess
{
    public static function applies(?array $user): bool
    {
        $roles = array_map('trim', explode(',', (string) ($user['role_slugs'] ?? '')));
        $roles[] = $user['role_slug'] ?? '';
        return in_array('estudante', $roles, true);
    }

    public static function allowsPermission(string $permission): bool
    {
        return $permission === 'education.view';
    }

    public static function allowsRoute(string $method, string $path): bool
    {
        $routes = [
            'GET' => [
                '/admin', '/admin/profile', '/admin/password', '/admin/education',
                '/admin/education/course', '/admin/education/lesson',
                '/admin/education/certificates', '/admin/education/certificate',
                '/admin/education/block/download', '/admin/education/assignment/download',
            ],
            'POST' => [
                '/admin/profile', '/admin/password', '/admin/announcement/read',
                '/admin/education/form/submit', '/admin/education/assignment/submit',
                '/admin/education/watch', '/admin/education/block/watch', '/admin/education/progress',
                '/admin/education/forum/topic', '/admin/education/forum/topic/update',
                '/admin/education/forum/reply', '/admin/education/forum/reply/update',
                '/admin/education/certificate/request', '/admin/education/certificate/name-change',
            ],
        ];
        return in_array($path, $routes[$method] ?? [], true);
    }
}
