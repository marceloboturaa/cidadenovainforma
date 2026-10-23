<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\News;
use App\Models\NewsRedirect;
use InvalidArgumentException;

class NewsRedirectController
{
    private function authorize(): void
    {
        Middleware::auth();
        if (!Auth::hasRole(['master', 'admin', 'admin-local'])) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    public function index(): void
    {
        $this->authorize();
        View::render('admin/news-redirects/index', [
            'redirects' => NewsRedirect::all(),
            'articles' => Database::connection()->query('SELECT id, title, slug FROM news WHERE status = "published" ORDER BY title')->fetchAll(),
        ]);
    }

    public function save(): void
    {
        $this->authorize();
        $this->validateToken();
        try {
            $path = NewsRedirect::sourcePath(is_string($_POST['source_path'] ?? null) ? $_POST['source_path'] : '');
            $news = News::find((int) ($_POST['news_id'] ?? 0));
            if (!$news || $news['status'] !== 'published') {
                throw new InvalidArgumentException('Selecione uma matéria publicada.');
            }
            if ($path === '/noticia/' . $news['slug']) {
                throw new InvalidArgumentException('O link antigo deve ser diferente do destino.');
            }
            if (str_starts_with($path, '/noticia/') && News::findPublishedBySlug(rawurldecode(substr($path, 9)))) {
                throw new InvalidArgumentException('Esse link já pertence a uma matéria publicada.');
            }
            NewsRedirect::save($path, (int) $news['id']);
            Session::flash('success', 'Redirecionamento salvo.');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('/admin/news-redirects');
    }

    public function delete(): void
    {
        $this->authorize();
        $this->validateToken();
        NewsRedirect::delete((int) ($_POST['id'] ?? 0));
        Session::flash('success', 'Redirecionamento removido.');
        redirect('/admin/news-redirects');
    }

    private function validateToken(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect('/admin/news-redirects');
        }
    }
}
