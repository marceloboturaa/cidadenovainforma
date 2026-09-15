<?php
namespace App\Core {
    class Auth {
        public static ?array $account = null;
        public static function user(): ?array { return self::$account; }
    }
    class Middleware { public static function auth(): void {} }
    class AdminNavigation {
        public static function contains(string $path): bool { return false; }
    }
    class View { public static function render(...$args): void {} }
}
namespace {
    require dirname(__DIR__) . '/app/Core/StudentAccess.php';
    require dirname(__DIR__) . '/app/Core/Router.php';
    require dirname(__DIR__) . '/app/Models/Stats.php';
    use App\Core\StudentAccess;
    function check(bool $ok, string $message): void {
        if (!$ok) { throw new RuntimeException($message); }
    }
    class ProbeController {
        public static int $calls = 0;
        public function action(): void { self::$calls++; }
    }
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    foreach (['estudante', 'estudante,professor', 'master,estudante'] as $roles) {
        $account = ['role_slug' => explode(',', $roles)[0], 'role_slugs' => $roles];
        \App\Core\Auth::$account = $account;
        check(StudentAccess::applies($account), 'Student ceiling applies to mixed roles');
        check(!\App\Models\Stats::canViewSensitiveInfo($account), 'No sensitive dashboard');
        check(!StudentAccess::allowsPermission('education.teach'), 'No teaching permission');
        foreach ([
            ['GET', '/admin/education/course?id=1', true],
            ['POST', '/admin/education/course', false],
            ['POST', '/admin/education/form/submit', true],
            ['POST', '/admin/education/form/grade', false],
            ['GET', '/admin/users/export', false],
            ['POST', '/admin/users/role', false],
            ['GET', '/admin/education/students/report/export', false],
            ['POST', '/admin/education/certificate/status', false],
            ['GET', '/admin/new-management-page', false],
        ] as [$method, $uri, $allowed]) {
            $router = new \App\Core\Router();
            $register = strtolower($method);
            $router->$register(parse_url($uri, PHP_URL_PATH), [ProbeController::class, 'action']);
            ProbeController::$calls = 0;
            http_response_code(200);
            $router->dispatch($method, $uri);
            check(ProbeController::$calls === (int) $allowed, "$roles: $method $uri");
            check($allowed || http_response_code() === 403, 'Denied requests return 403');
        }
    }
    check(!StudentAccess::applies(['role_slug' => 'professor']), 'Teacher remains independent');
    echo "Student routes, mixed roles and dashboard restrictions passed.\n";
}
