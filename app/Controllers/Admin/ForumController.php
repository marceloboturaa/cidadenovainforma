<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\Forum;

class ForumController
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    private const BLOCKED_EXTENSIONS = ['bat', 'cmd', 'com', 'exe', 'htaccess', 'html', 'htm', 'js', 'msi', 'phtml', 'phar', 'php', 'pl', 'ps1', 'py', 'sh', 'shtml', 'vbs'];

    public function index(): void
    {
        $this->authorizeForum();
        $user = current_user();

        View::render('admin/forum/index', [
            'areas' => Forum::areasForUser((int) $user['id']),
            'unreadCount' => Forum::unreadCount((int) $user['id']),
        ]);
    }

    public function area(): void
    {
        $this->authorizeForum();
        $area = $this->areaFromQuery();
        $userId = (int) current_user()['id'];
        $this->authorizeAreaView($area, $userId);

        View::render('admin/forum/area', [
            'area' => $area,
            'categories' => Forum::categoriesForArea((int) $area['id']),
            'topics' => Forum::topicsForArea((int) $area['id']),
            'canPost' => Forum::canPostArea($area, $userId),
            'canModerate' => Forum::canModerateArea($area, $userId),
        ]);
    }

    public function topic(): void
    {
        $this->authorizeForum();
        $topic = $this->topicFromQuery();
        $userId = (int) current_user()['id'];
        $area = Forum::findArea((string) $topic['area_slug']);
        $this->authorizeAreaView($area, $userId);
        Forum::markTopicNotificationsRead((int) $topic['id'], $userId);

        View::render('admin/forum/topic', [
            'topic' => $topic,
            'area' => $area,
            'replies' => Forum::repliesForTopic((int) $topic['id']),
            'attachments' => $this->attachmentsByOwner(Forum::attachmentsForTopic((int) $topic['id'])),
            'canPost' => $topic['status'] === 'open' && Forum::canPostArea($area, $userId),
            'canModerate' => Forum::canModerateArea($area, $userId),
        ]);
    }

    public function storeTopic(): void
    {
        $this->authorizeForum();
        $this->validateCsrf('/admin/forum');
        $area = $this->areaFromQuery();
        $userId = (int) current_user()['id'];
        $this->authorizeAreaPost($area, $userId);

        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($title === '' || $body === '') {
            Session::flash('error', 'Informe título e mensagem do tópico.');
            redirect('/admin/forum/area?area=' . $area['slug']);
        }

        $topicId = Forum::createTopic([
            'area_id' => $area['id'],
            'category_id' => $_POST['category_id'] ?? null,
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_public' => isset($_POST['is_public']) && Forum::canModerateArea($area, $userId),
        ]);
        $this->saveAttachments($topicId, null, $userId);

        Session::flash('success', 'Tópico criado.');
        redirect('/admin/forum/topic?id=' . $topicId);
    }

    public function reply(): void
    {
        $this->authorizeForum();
        $this->validateCsrf('/admin/forum');
        $topic = $this->topicFromQuery();
        $area = Forum::findArea((string) $topic['area_slug']);
        $userId = (int) current_user()['id'];
        $this->authorizeAreaPost($area, $userId);

        if ($topic['status'] !== 'open') {
            Session::flash('error', 'Este tópico está fechado.');
            redirect('/admin/forum/topic?id=' . $topic['id']);
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            Session::flash('error', 'Escreva a resposta antes de enviar.');
            redirect('/admin/forum/topic?id=' . $topic['id']);
        }

        $replyId = Forum::createReply([
            'topic_id' => $topic['id'],
            'user_id' => $userId,
            'body' => $body,
        ]);
        $this->saveAttachments(null, $replyId, $userId);

        Session::flash('success', 'Resposta enviada.');
        redirect('/admin/forum/topic?id=' . $topic['id']);
    }

    public function moderateTopic(): void
    {
        $this->authorizeForum();
        $this->validateCsrf('/admin/forum');
        $topic = $this->topicFromQuery();
        $area = Forum::findArea((string) $topic['area_slug']);
        $userId = (int) current_user()['id'];
        $this->authorizeModeration($area, $userId);

        Forum::setTopicStatus((int) $topic['id'], (string) ($_POST['status'] ?? 'open'));
        Session::flash('success', 'Moderação aplicada.');
        redirect('/admin/forum/area?area=' . $area['slug']);
    }

    public function deleteReply(): void
    {
        $this->authorizeForum();
        $this->validateCsrf('/admin/forum');
        $topic = $this->topicFromQuery();
        $area = Forum::findArea((string) $topic['area_slug']);
        $userId = (int) current_user()['id'];
        $this->authorizeModeration($area, $userId);

        $replyId = filter_input(INPUT_GET, 'reply_id', FILTER_VALIDATE_INT);
        if ($replyId) {
            Forum::deactivateReply($replyId);
        }

        Session::flash('success', 'Resposta removida.');
        redirect('/admin/forum/topic?id=' . $topic['id']);
    }

    public function category(): void
    {
        $this->authorizeForum();
        $this->validateCsrf('/admin/forum');
        $area = $this->areaFromQuery();
        $userId = (int) current_user()['id'];
        $this->authorizeModeration($area, $userId);

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            Forum::createCategory((int) $area['id'], $name);
        }

        Session::flash('success', 'Categoria atualizada.');
        redirect('/admin/forum/area?area=' . $area['slug']);
    }

    public function download(): void
    {
        $this->authorizeForum();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $attachment = $id ? Forum::findAttachment($id) : null;

        if (!$attachment) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $area = $this->areaById((int) $attachment['area_id']);
        $this->authorizeAreaView($area, (int) current_user()['id']);

        $path = dirname(__DIR__, 3) . '/' . ltrim((string) $attachment['path'], '/');
        if (!is_file($path)) {
            Session::flash('error', 'Anexo não encontrado.');
            redirect('/admin/forum');
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $this->safeMimeType((string) $attachment['mime_type']));
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', basename((string) $attachment['original_name'])) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    private function authorizeForum(): void
    {
        if (!Auth::can('forum.view') && !Auth::can('forum.create') && !Auth::can('forum.moderate')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function areaFromQuery(): array
    {
        $slug = trim((string) ($_GET['area'] ?? ''));
        $area = $slug !== '' ? Forum::findArea($slug) : null;

        if (!$area) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $area;
    }

    private function topicFromQuery(): array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $topic = $id ? Forum::findTopic($id) : null;

        if (!$topic || $topic['status'] === 'hidden') {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        return $topic;
    }

    private function areaById(int $areaId): array
    {
        foreach (Forum::areasForUser((int) current_user()['id']) as $area) {
            if ((int) $area['id'] === $areaId) {
                return $area;
            }
        }

        http_response_code(404);
        View::render('errors/404');
        exit;
    }

    private function authorizeAreaView(?array $area, int $userId): void
    {
        if (!$area || !Forum::canViewArea($area, $userId)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeAreaPost(?array $area, int $userId): void
    {
        if (!$area || !Forum::canPostArea($area, $userId)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function authorizeModeration(?array $area, int $userId): void
    {
        if (!$area || !Forum::canModerateArea($area, $userId)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function validateCsrf(string $redirectTo): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($redirectTo);
        }
    }

    private function saveAttachments(?int $topicId, ?int $replyId, int $userId): void
    {
        if (empty($_FILES['attachments']['name']) || !is_array($_FILES['attachments']['name'])) {
            return;
        }

        $directory = dirname(__DIR__, 3) . '/storage/documents/forum';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        foreach ($_FILES['attachments']['name'] as $index => $name) {
            if (($_FILES['attachments']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $size = (int) ($_FILES['attachments']['size'][$index] ?? 0);
            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

            if ($size <= 0 || $size > self::MAX_FILE_SIZE || $extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
                continue;
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $extension;
            $target = $directory . '/' . $filename;

            if (!move_uploaded_file($_FILES['attachments']['tmp_name'][$index], $target)) {
                continue;
            }

            Forum::addAttachment([
                'topic_id' => $topicId,
                'reply_id' => $replyId,
                'uploaded_by' => $userId,
                'path' => '/storage/documents/forum/' . $filename,
                'original_name' => (string) $name,
                'mime_type' => mime_content_type($target) ?: 'application/octet-stream',
                'size_bytes' => $size,
            ]);
        }
    }

    private function attachmentsByOwner(array $attachments): array
    {
        $result = ['topic' => [], 'replies' => []];

        foreach ($attachments as $attachment) {
            if (!empty($attachment['reply_id'])) {
                $result['replies'][(int) $attachment['reply_id']][] = $attachment;
                continue;
            }

            $result['topic'][] = $attachment;
        }

        return $result;
    }

    private function safeMimeType(string $mime): string
    {
        return preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $mime)
            ? $mime
            : 'application/octet-stream';
    }
}
