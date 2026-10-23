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
namespace {
    require dirname(__DIR__) . '/app/Controllers/Admin/UserController.php';
    $controller = new \App\Controllers\Admin\UserController();
    $policy = new ReflectionMethod($controller, 'canAssignRole');
    $policy->setAccessible(true);
    foreach (['master', 'admin', 'admin-local', 'professor', 'estudante'] as $actor) {
        \App\Core\Auth::$roles = [$actor];
        foreach (['master', 'admin', 'professor', 'voluntario', 'estudante'] as $target) {
            $allowed = $policy->invoke($controller, ['slug' => $target]);
            $expected = in_array($actor, ['master', 'admin'], true) && $target !== 'master';
            if ($allowed !== $expected) {
                throw new RuntimeException("Unexpected assignment: $actor -> $target");
            }
        }
    }
    echo "Role assignment: master/admin allowed; master role protected; other roles denied.\n";
}
