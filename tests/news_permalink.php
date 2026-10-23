<?php
namespace App\Core {
    class Auth
    {
        public static array $roles = [];
        public static function user(): array { return ['id' => 7]; }
        public static function hasRole(string|array $roles): bool
        {
            return (bool) array_intersect((array) $roles, self::$roles);
        }
    }
}
namespace App\Models {
    class News
    {
        public static array $calls = [];
        public static function uniqueSlug(string $value, int $id): string
        {
            self::$calls[] = [$value, $id];
            return $value === 'ocupado' ? 'ocupado-2' : $value;
        }
    }
}
namespace {
    require dirname(__DIR__) . '/app/Helpers/functions.php';
    require dirname(__DIR__) . '/app/Controllers/Admin/NewsController.php';
    $controller = new \App\Controllers\Admin\NewsController();
    $method = new ReflectionMethod($controller, 'slugAfterEdit');
    $method->setAccessible(true);
    foreach (['master', 'admin', 'admin-local', 'editor', 'colunista'] as $role) {
        \App\Core\Auth::$roles = [$role];
        foreach ([7, 8] as $author) {
            $news = ['id' => 42, 'author_id' => $author, 'slug' => 'link-original'];
            $allowed = $author === 7 || in_array($role, ['master', 'admin', 'admin-local'], true);
            foreach ([null, '', '!!!', 'link-original', 'Novo Link', 'ocupado', ['forjado']] as $input) {
                $_POST = ['title' => 'Titulo alterado', 'intent' => 'submit'];
                if ($input !== null) { $_POST['slug'] = $input; }
                $expected = $allowed && $input === 'Novo Link' ? 'novo-link'
                    : ($allowed && $input === 'ocupado' ? 'ocupado-2' : 'link-original');
                if ($method->invoke($controller, $news) !== $expected) {
                    throw new RuntimeException("Falha no link: $role / autor $author");
                }
            }
        }
    }
    if (!in_array(['ocupado', 42], \App\Models\News::$calls, true)) {
        throw new RuntimeException('A alteracao deve verificar links duplicados ignorando a propria materia.');
    }
    echo "Links preservados; alteracoes manuais restritas aos perfis autorizados e ao autor.\n";
}
