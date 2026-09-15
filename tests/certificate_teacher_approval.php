<?php
namespace App\Core {
    class Auth {
        public static $role = 'professor';
        public static function can($permission) { return false; }
        public static function hasRole($roles) { return in_array(self::$role, (array) $roles, true); }
    }
    class Middleware { public static function auth() {} }
    class View { public static function render($view) {} }
    class Csrf { public static function validate($token) { return $token === 'valid'; } }
    class Logger { public static function info(...$args) {} }
    class Session { public static function flash(...$args) {} }
}
namespace App\Models {
    class Education {
        public static $teacher = 10;
        public static $calls = 0;
        public static function certificateById($id) { return ['id' => $id, 'course_id' => 1, 'status' => 'pending']; }
        public static function findCourse($id) { return ['id' => $id, 'teacher_user_id' => self::$teacher]; }
        public static function setCertificateStatus(...$args) { self::$calls++; return true; }
    }
}
namespace {
    require dirname(__DIR__) . '/app/Controllers/Admin/EducationController.php';
    function current_user() { return ['id' => 10]; }
    class RedirectResult extends RuntimeException {}
    function redirect($url) { throw new RedirectResult($url); }
    $controller = new \App\Controllers\Admin\EducationController();
    foreach ([['professor', 10, 'issue', 'valid', 1], ['professor', 20, 'issue', 'valid', 0], ['professor', 10, 'delete', 'valid', 0], ['estudante', 10, 'issue', 'valid', 0], ['professor', 10, 'issue', 'invalid', 0]] as [$role, $teacher, $action, $token, $expected]) {
        \App\Core\Auth::$role = $role; \App\Models\Education::$teacher = $teacher; \App\Models\Education::$calls = 0;
        $_POST = ['certificate_id' => 1, 'action' => $action, '_token' => $token];
        try { $controller->certificateStatus(); } catch (RedirectResult $e) {}
        if (\App\Models\Education::$calls !== $expected) throw new RuntimeException('Authorization failure: ' . $role . '/' . $teacher . '/' . $action . '/' . $token);
    }
    echo "Professor: aprovação do próprio curso, acesso negado e CSRF aprovados.\n";
}
