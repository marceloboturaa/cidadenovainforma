<?php
namespace App\Core {
    class RedirectTestPDO extends \PDO
    {
        public function exec(string $statement): int|false
        {
            if (str_contains($statement, 'CREATE TABLE IF NOT EXISTS news_redirects')) { return 0; }
            return parent::exec($statement);
        }
    }
    class Database
    {
        public static \PDO $pdo;
        public static function connection(): \PDO { return self::$pdo; }
    }
}
namespace {
    function url(string $path = ''): string { return 'https://example.test/portal/' . ltrim($path, '/'); }
    require dirname(__DIR__) . '/app/Models/NewsRedirect.php';
    use App\Models\NewsRedirect;
    function check(bool $condition, string $message): void {
        if (!$condition) { throw new RuntimeException($message); }
    }
    foreach (['/noticia/antiga', 'https://example.test/portal/noticia/antiga', '/portal/noticia/antiga/'] as $input) {
        check(NewsRedirect::sourcePath($input) === '/noticia/antiga', 'Normalize same-site links and installation base');
    }
    foreach (['', '/', '//evil.test/x', 'https://evil.test/x', '/admin/users', '/%61dmin/users', '/x?slug=y', '/x#y', '/a/../b', '/a%0db', '/x%5cy'] as $input) {
        try { NewsRedirect::sourcePath($input); } catch (InvalidArgumentException $e) { continue; }
        throw new RuntimeException('Invalid source accepted: ' . $input);
    }
    $pdo = new \App\Core\RedirectTestPDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \App\Core\Database::$pdo = $pdo;
    $pdo->exec('CREATE TABLE news (id INTEGER PRIMARY KEY, slug TEXT, status TEXT)');
    $pdo->exec('CREATE TABLE news_redirects (id INTEGER PRIMARY KEY, source_path TEXT, news_id INTEGER)');
    $pdo->exec("INSERT INTO news VALUES (1, 'destino', 'published'), (2, 'rascunho', 'draft')");
    $pdo->exec("INSERT INTO news_redirects VALUES (1, '/antigo', 1), (2, '/privado', 2), (3, '/excluido', 99), (4, '/noticia/destino', 1)");
    check(NewsRedirect::destination('/antigo') === '/noticia/destino', 'Resolve published destination');
    foreach (['/privado', '/excluido', '/inexistente', '/noticia/destino'] as $path) {
        check(NewsRedirect::destination($path) === null, 'Reject missing, unpublished and self destinations');
    }
    $pdo->exec("UPDATE news SET slug = 'novo-link' WHERE id = 1");
    check(NewsRedirect::destination('/antigo') === '/noticia/novo-link', 'Follow article permalink changes');
    NewsRedirect::delete(1);
    check(NewsRedirect::destination('/antigo') === null, 'Remove mapping');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    check(!NewsRedirect::redirectMissing(), 'Never redirect POST');
    echo "News redirects: normalization, destination visibility, permalink changes and removal passed.\n";
}
