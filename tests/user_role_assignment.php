<?php
namespace App\Core {
    class Auth
    {
        public static array $roles = [];
        public static function hasRole(string|array $roles): bool
        {
            return (bool) array_intersect((array) $roles, self::$roles);
        }
    }
}
namespace App\Models {
    class Role {
        public static function findBySlug(string $slug): ?array {
            return ['level' => ['master' => 100, 'admin' => 80, 'admin-local' => 60][$slug] ?? 10];
        }
    }
}
namespace {
    function current_user(): array { return ['role_slug' => \App\Core\Auth::$roles[0] ?? '']; }
    require dirname(__DIR__) . '/app/Controllers/Admin/UserController.php';
    $controller = new \App\Controllers\Admin\UserController();
    $policy = new ReflectionMethod($controller, 'canAssignRole');
    $policy->setAccessible(true);
    foreach (['master', 'admin', 'admin-local', 'professor', 'estudante'] as $actor) {
        \App\Core\Auth::$roles = [$actor];
        foreach (['master', 'admin', 'admin-local', 'professor', 'voluntario', 'estudante'] as $target) {
            $level = ['master' => 100, 'admin' => 80, 'admin-local' => 60][$target] ?? 10;
            $allowed = $policy->invoke($controller, ['slug' => $target, 'level' => $level]);
            $expected = $target !== 'master' && ($actor === 'master' || ($actor === 'admin' && $level < 80));
            if ($allowed !== $expected) {
                throw new RuntimeException("Unexpected assignment: $actor -> $target");
            }
        }
    }
    echo "Role assignment: master/admin allowed; master role protected; other roles denied.\n";
}
