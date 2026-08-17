<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\RegistrationNotifier;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Education;
use App\Models\InstitutionLanding;
use App\Models\InstitutionPage;
use App\Models\LibraryEvent;
use App\Models\MenuItem;
use App\Models\News;
use App\Models\Person;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\User;

class PublicController
{
    public function home(): void
    {
        $this->logAccess();

        $homeCourseSettings = [
            'section_enabled' => SiteSetting::get('home_courses_enabled', '1') === '1',
            'highlights_enabled' => SiteSetting::get('home_course_highlights_enabled', '1') === '1',
            'show_images' => SiteSetting::get('home_courses_show_images', '1') === '1',
            'show_lesson_count' => SiteSetting::get('home_courses_show_lesson_count', '1') === '1',
            'show_summary' => SiteSetting::get('home_courses_show_summary', '1') === '1',
            'show_teacher' => SiteSetting::get('home_courses_show_teacher', '1') === '1',
            'show_button' => SiteSetting::get('home_courses_show_button', '1') === '1',
            'position' => SiteSetting::get('home_courses_position', 'after_news'),
        ];
        $needsPublicCourses = $homeCourseSettings['section_enabled'] || $homeCourseSettings['highlights_enabled'];

        View::render('public/home', [
            'featured' => News::publicList(['featured' => true], 5),
            'urgent' => News::publicList(['urgent' => true], 4),
            'latest' => News::publicList([], 50),
            'popular' => News::popular(5),
            'publicCourses' => $needsPublicCourses ? Education::publicCourses(6) : [],
            'homeCourseSettings' => $homeCourseSettings,
            'libraryEvents' => LibraryEvent::publicUpcoming(6),
            'menuItems' => MenuItem::visible(),
            'homeNotice' => [
                'enabled' => SiteSetting::get('home_notice_enabled', '0'),
                'title' => SiteSetting::get('home_notice_title', ''),
                'text' => SiteSetting::get('home_notice_text', ''),
                'url' => SiteSetting::get('home_notice_url', ''),
                'label' => SiteSetting::get('home_notice_label', ''),
            ],
            'pageTitle' => 'Cidade Nova Informa',
            'canonicalUrl' => url('/'),
        ], 'public');
    }

    public function search(): void
    {
        $q = trim($_GET['q'] ?? '');
        $this->logAccess();

        View::render('public/list', [
            'heading' => $q ? 'Busca por "' . $q . '"' : 'Busca',
            'news' => $q ? News::publicList(['q' => $q], 20) : [],
            'menuItems' => MenuItem::visible(),
            'query' => $q,
            'pageTitle' => 'Busca - Cidade Nova Informa',
            'metaDescription' => 'Resultados de busca no Cidade Nova Informa.',
            'canonicalUrl' => url('/buscar') . ($q ? '?q=' . urlencode($q) : ''),
        ], 'public');
    }

    public function institution(): void
    {
        $this->logAccess();
        $landing = InstitutionLanding::get();

        View::render('public/institution', [
            'landing' => $landing,
            'projects' => InstitutionPage::landingProjects(),
            'areas' => $this->institutionAreas(),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $landing['seo']['title'] ?? 'Instituição - Cidade Nova Informa',
            'metaDescription' => $landing['seo']['description'] ?? 'Conheça a missão, a atuação e o compromisso editorial do Cidade Nova Informa.',
            'canonicalUrl' => url('/instituicao'),
            'ogImage' => !empty($landing['hero']['image']) ? media_url($landing['hero']['image']) : null,
        ], 'public');
    }

    public function events(): void
    {
        $this->logAccess();

        View::render('public/events', [
            'upcomingEvents' => LibraryEvent::publicUpcomingAll(),
            'pastEvents' => LibraryEvent::publicPastAll(),
            'mode' => 'all',
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Eventos - Cidade Nova Informa',
            'metaDescription' => 'Agenda de eventos e atividades abertas do Cidade Nova Informa.',
            'canonicalUrl' => url('/eventos'),
        ], 'public');
    }

    public function upcomingEvents(): void
    {
        $this->logAccess();

        View::render('public/events', [
            'upcomingEvents' => LibraryEvent::publicUpcomingAll(),
            'pastEvents' => [],
            'mode' => 'upcoming',
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Eventos futuros - Cidade Nova Informa',
            'metaDescription' => 'Confira os próximos eventos e atividades do Cidade Nova Informa.',
            'canonicalUrl' => url('/eventos/futuros'),
        ], 'public');
    }

    public function pastEvents(): void
    {
        $this->logAccess();

        View::render('public/events', [
            'upcomingEvents' => [],
            'pastEvents' => LibraryEvent::publicPastAll(),
            'mode' => 'past',
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Eventos realizados - Cidade Nova Informa',
            'metaDescription' => 'Histórico de eventos e atividades já realizadas pelo Cidade Nova Informa.',
            'canonicalUrl' => url('/eventos/realizados'),
        ], 'public');
    }

    public function eventShow(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $event = $id ? LibraryEvent::findPublic((int) $id) : null;

        if (!$event) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $this->logAccess();

        View::render('public/event-show', [
            'event' => $event,
            'participantStats' => LibraryEvent::participantStats((int) $event['id']),
            'remainingSlots' => $this->remainingEventSlots($event),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $event['title'] . ' - Eventos - Cidade Nova Informa',
            'metaDescription' => text_excerpt($event['description'] ?? '', 150),
            'canonicalUrl' => url('/evento/' . $event['id']),
            'ogType' => 'article',
            'ogImage' => ($eventImage = event_public_image($event)) ? media_url($eventImage) : null,
            'registrationSuccess' => Session::flash('registration_success'),
            'registrationError' => Session::flash('registration_error'),
        ], 'public');
    }

    public function submitEventRegistration(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $event = $id ? LibraryEvent::findPublic((int) $id) : null;

        if (!$event || ($event['status'] ?? '') !== 'aberto' || empty($event['registration_enabled'])) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('registration_error', 'Sessão expirada. Atualize a página e tente novamente.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        if ($this->remainingEventSlots($event) === 0) {
            Session::flash('registration_error', 'As vagas deste evento estão esgotadas no momento.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        $extraAnswer = $this->registrationExtraAnswer($event);
        if (!empty($event['registration_question_required']) && trim($extraAnswer) === '') {
            Session::flash('registration_error', 'Responda a pergunta obrigatória do evento.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        $name = trim((string) ($_POST['full_name'] ?? ''));
        $contact = trim((string) ($_POST['whatsapp'] ?? '') . (string) ($_POST['phone'] ?? '') . (string) ($_POST['email'] ?? ''));

        if ($name === '' || $contact === '') {
            Session::flash('registration_error', 'Informe seu nome e pelo menos um contato.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        if (empty($_POST['contact_authorized'])) {
            Session::flash('registration_error', 'Autorize o contato para que a equipe possa confirmar sua inscrição.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        if (!empty($_POST['is_minor'])) {
            $guardianName = trim((string) ($_POST['guardian_name'] ?? ''));
            $guardianRelation = trim((string) ($_POST['guardian_relation'] ?? ''));
            $guardianPhone = trim((string) ($_POST['guardian_phone'] ?? ''));

            if ($guardianName === '' || $guardianRelation === '' || $guardianPhone === '') {
                Session::flash('registration_error', 'Para menor de idade, informe nome, parentesco e telefone do responsável.');
                redirect('/evento/' . $event['id'] . '#inscricao');
            }
        }

        if (!$this->loginRequestIsValid()) {
            Session::flash('registration_error', 'Para criar login, informe e-mail e senha com confirmação igual e pelo menos 8 caracteres.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        $person = Person::findByIdentity($_POST['cpf'] ?? null, $_POST['email'] ?? null, $_POST['whatsapp'] ?? null);
        $personId = $person ? (int) $person['id'] : Person::create(array_merge($_POST, [
            'contact_authorized' => 1,
            'created_by' => null,
            'updated_by' => null,
        ]));
        $person = Person::find($personId) ?: $person;

        $existing = LibraryEvent::participant((int) $event['id'], $personId);
        if ($existing) {
            Session::flash('registration_success', 'Sua inscrição já está registrada com status: ' . ucfirst((string) $existing['status']) . '.');
            redirect('/evento/' . $event['id'] . '#inscricao');
        }

        LibraryEvent::attachParticipant(
            (int) $event['id'],
            $personId,
            'pendente',
            'Inscrição enviada pela página pública. Aguardando confirmação do master/admin.',
            null,
            $_POST['heard_about'] ?? null,
            $_POST['event_expectations'] ?? null,
            $extraAnswer
        );

        $loginRequested = $this->createPendingLoginIfRequested($person ?: ['full_name' => $name, 'email' => $_POST['email'] ?? ''], $event, $personId);
        if ($person) {
            RegistrationNotifier::eventStatus($event, $person, 'pendente', $loginRequested);
        }

        Session::flash('registration_success', 'Inscrição enviada. Ela ficará pendente até a confirmação da equipe.');
        redirect('/evento/' . $event['id'] . '#inscricao');
    }

    public function institutionArea(): void
    {
        $areas = $this->institutionAreas();
        $slug = $_GET['slug'] ?? '';
        $area = $areas[$slug] ?? null;
        $this->logAccess();

        if (!$area) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        View::render('public/institution-area', [
            'area' => $area,
            'areas' => $areas,
            'photos' => $area['photos'],
            'relatedNews' => News::relatedToInstitutionPage($area, 6),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $area['name'] . ' - Instituição - Cidade Nova Informa',
            'metaDescription' => $area['summary'],
            'canonicalUrl' => url('/instituicao/' . $area['slug']),
        ], 'public');
    }

    private function registrationExtraAnswer(array $event): string
    {
        if (empty($event['registration_question_label'])) {
            return '';
        }

        $answer = $_POST['registration_extra_answer'] ?? '';
        if (is_array($answer)) {
            $answer = implode(', ', array_filter(array_map('trim', array_map('strval', $answer))));
        }

        return trim((string) $answer);
    }

    public function documents(): void
    {
        $this->logAccess();

        View::render('public/documents', [
            'documents' => Document::publicAll(),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Documentos - Cidade Nova Informa',
            'metaDescription' => 'Documentos públicos disponibilizados pelo Cidade Nova Informa.',
            'canonicalUrl' => url('/documentos'),
        ], 'public');
    }

    public function verifyCertificate(): void
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($_GET['codigo'] ?? $_GET['code'] ?? '')) ?? '');
        $certificate = $code !== '' ? Education::certificateByVerificationCode($code) : null;

        $this->logAccess();

        View::render('public/certificate-verify', [
            'code' => $code,
            'certificate' => $certificate,
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Verificar certificado - Cidade Nova Informa',
            'metaDescription' => 'Consulte a autenticidade de certificados emitidos pelo Cidade Nova Informa.',
            'canonicalUrl' => url('/certificado/validar'),
        ], 'public');
    }

    public function cookiePolicy(): void
    {
        $this->logAccess();

        $settings = Consent::settings();
        View::render('public/cookie-policy', [
            'settings' => $settings,
            'categories' => Consent::categories(true),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => ($settings['policy_title'] ?? 'Política de Cookies') . ' - Cidade Nova Informa',
            'metaDescription' => 'Política de cookies e preferências de privacidade conforme LGPD.',
            'canonicalUrl' => url('/politica-de-cookies'),
        ], 'public');
    }

    public function consentConfig(): void
    {
        $this->json(Consent::publicConfig());
    }

    public function saveConsent(): void
    {
        $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $this->json([
            'ok' => true,
            'consent' => Consent::registerConsent($payload),
        ]);
    }

    public function downloadDocument(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document || empty($document['is_public']) || empty($document['allow_download'])) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        if (Document::isExternalLink($document)) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $path = Document::absolutePath($document);
        if (!$path || !is_file($path)) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $downloadName = str_replace(['"', "\r", "\n"], '', basename($document['original_name']));

        header('X-Content-Type-Options: nosniff');
        header('X-Download-Options: noopen');
        header('Content-Type: ' . $this->safeMimeType((string) $document['mime_type']));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');

        readfile($path);
        exit;
    }

    public function viewDocument(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $document = $id ? Document::find($id) : null;

        if (!$document || empty($document['is_public'])) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        if (Document::isExternalLink($document)) {
            $googlePreviewUrl = Document::googlePreviewUrl($document);
            $googlePdfUrl = Document::googlePdfExportUrl($document);

            if (isset($_GET['inline']) && $googlePdfUrl) {
                $this->streamGooglePdf($googlePdfUrl, (string) ($document['original_name'] ?? 'documento.pdf'), true);
                return;
            }

            $this->logAccess();
            View::render('public/document-view', [
                'document' => $document,
                'viewerType' => $googlePdfUrl ? 'pdf' : ($googlePreviewUrl ? 'google' : 'external'),
                'documentSrc' => $googlePdfUrl ? url('/documentos/visualizar?id=' . $document['id'] . '&inline=1') : ($googlePreviewUrl ?: ''),
                'documentText' => '',
                'pdfStartPage' => $googlePdfUrl ? 2 : 1,
                'externalUrl' => (string) $document['path'],
                'downloadUrl' => null,
                'menuItems' => MenuItem::visible(),
                'query' => '',
                'pageTitle' => ($document['title'] ?? 'Documento') . ' - Documentos - Cidade Nova Informa',
                'metaDescription' => 'Visualizacao de documento publico do Cidade Nova Informa.',
                'canonicalUrl' => url('/documentos/visualizar?id=' . $document['id']),
            ], 'public');
            return;
        }

        $path = Document::absolutePath($document);
        if (!$path || !is_file($path)) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        if (!isset($_GET['inline'])) {
            $viewer = $this->documentViewerData($document, $path, !empty($document['allow_download']));
            $this->logAccess();
            View::render('public/document-view', [
                'document' => $document,
                'viewerType' => $viewer['type'],
                'documentSrc' => $viewer['src'],
                'documentText' => $viewer['text'],
                'pdfStartPage' => 1,
                'externalUrl' => '',
                'downloadUrl' => !empty($document['allow_download']) ? url('/documentos/download?id=' . $document['id']) : null,
                'menuItems' => MenuItem::visible(),
                'query' => '',
                'pageTitle' => ($document['title'] ?? 'Documento') . ' - Documentos - Cidade Nova Informa',
                'metaDescription' => 'Visualizacao de documento publico do Cidade Nova Informa.',
                'canonicalUrl' => url('/documentos/visualizar?id=' . $document['id']),
            ], 'public');
            return;
        }

        $filename = str_replace(['"', "\r", "\n"], '', basename($document['original_name']));

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $this->safeMimeType((string) $document['mime_type']));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=3600');

        readfile($path);
        exit;
    }

    public function archive(): void
    {
        $this->logAccess();

        View::render('public/list', [
            'heading' => 'Reprise Cidade Nova',
            'news' => News::publicList(['is_archive' => 1, 'archive_order' => true], 30),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => 'Reprise Cidade Nova - Cidade Nova Informa',
            'metaDescription' => 'Reportagens antigas republicadas em reprise para preservação histórica.',
            'canonicalUrl' => url('/reprise'),
        ], 'public');
    }

    public function legacyArchive(): void
    {
        redirect('/reprise');
    }

    public function category(): void
    {
        $category = Category::findBySlug($_GET['slug'] ?? '');
        $this->logAccess();

        if (!$category) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        View::render('public/list', [
            'heading' => $category['name'],
            'news' => News::publicList(['category_id' => $category['id']], 20),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $category['name'] . ' - Cidade Nova Informa',
            'metaDescription' => $category['description'] ?: 'Notícias da categoria ' . $category['name'],
            'canonicalUrl' => url('/categoria/' . $category['slug']),
        ], 'public');
    }

    public function tag(): void
    {
        $tag = Tag::findBySlug($_GET['slug'] ?? '');
        $this->logAccess();

        if (!$tag) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $tagDisplayName = $tag['display_name'] ?? $tag['name'];

        View::render('public/list', [
            'heading' => '#' . $tagDisplayName,
            'news' => News::publicList(['tag_id' => $tag['id']], 20),
            'menuItems' => MenuItem::visible(),
            'query' => '',
            'pageTitle' => $tagDisplayName . ' - Cidade Nova Informa',
            'metaDescription' => 'Notícias marcadas com ' . $tagDisplayName,
            'canonicalUrl' => url('/tag/' . $tag['slug']),
        ], 'public');
    }

    public function show(): void
    {
        $news = News::findPublishedBySlug($_GET['slug'] ?? '');

        if (!$news) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        News::incrementViews((int) $news['id']);
        $this->logAccess((int) $news['id']);

        View::render('public/show', [
            'news' => $news,
            'tags' => Tag::publicForNews((int) $news['id']),
            'related' => News::publicList(['category_id' => $news['category_id']], 4),
            'menuItems' => MenuItem::visible(),
            'pageTitle' => $news['title'] . ' - Cidade Nova Informa',
            'metaDescription' => text_excerpt($news['summary'] ?: $news['content'], 150),
            'canonicalUrl' => url('/noticia/' . $news['slug']),
            'ogType' => 'article',
            'ogImage' => ($publicImage = news_public_image($news)) ? media_url($publicImage) : null,
        ], 'public');
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/eventos'), 'priority' => '0.7'],
            ['loc' => url('/eventos/futuros'), 'priority' => '0.6'],
            ['loc' => url('/eventos/realizados'), 'priority' => '0.5'],
            ['loc' => url('/instituicao'), 'priority' => '0.6'],
            ['loc' => url('/buscar'), 'priority' => '0.4'],
            ['loc' => url('/reprise'), 'priority' => '0.7'],
        ];

        foreach ($this->institutionAreas() as $area) {
            $urls[] = ['loc' => url('/instituicao/' . $area['slug']), 'priority' => '0.6'];
        }

        foreach (LibraryEvent::publicAll() as $event) {
            $urls[] = [
                'loc' => url('/evento/' . $event['id']),
                'priority' => '0.6',
                'lastmod' => date('Y-m-d', strtotime($event['updated_at'] ?? $event['created_at'] ?? 'now')),
            ];
        }

        foreach (Category::active() as $category) {
            $urls[] = ['loc' => url('/categoria/' . $category['slug']), 'priority' => '0.7'];
        }

        foreach (Tag::all() as $tag) {
            if ((int) $tag['news_count'] > 0) {
                $urls[] = ['loc' => url('/tag/' . $tag['slug']), 'priority' => '0.5'];
            }
        }

        foreach (News::publicList([], 50) as $item) {
            $urls[] = [
                'loc' => url('/noticia/' . $item['slug']),
                'priority' => '0.8',
                'lastmod' => date('Y-m-d', strtotime($item['updated_at'] ?? $item['published_at'] ?? 'now')),
            ];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $item) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . e($item['loc']) . '</loc>' . PHP_EOL;
            if (!empty($item['lastmod'])) {
                echo '    <lastmod>' . e($item['lastmod']) . '</lastmod>' . PHP_EOL;
            }
            echo '    <priority>' . e($item['priority']) . '</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /login\n";
        echo 'Sitemap: ' . url('/sitemap.xml') . "\n";
    }

    private function logAccess(?int $newsId = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO access_logs (news_id, ip_address, user_agent, path, created_at)
             VALUES (:news_id, :ip_address, :user_agent, :path, NOW())'
        );
        $stmt->execute([
            'news_id' => $newsId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'path' => substr($_SERVER['REQUEST_URI'] ?? '/', 0, 255),
        ]);
    }

    private function safeMimeType(string $mime): string
    {
        return preg_match('/^[A-Za-z0-9.+-]+\/[A-Za-z0-9.+-]+$/', $mime)
            ? $mime
            : 'application/octet-stream';
    }

    private function documentViewerData(array $document, string $path, bool $canDownload): array
    {
        $mime = strtolower((string) ($document['mime_type'] ?? ''));
        $extension = strtolower(pathinfo((string) ($document['original_name'] ?? ''), PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            return [
                'type' => 'image',
                'src' => url('/documentos/visualizar?id=' . $document['id'] . '&inline=1'),
                'text' => '',
            ];
        }

        if (in_array($mime, ['text/plain', 'text/csv'], true) || in_array($extension, ['txt', 'csv'], true)) {
            return [
                'type' => 'text',
                'src' => '',
                'text' => (string) file_get_contents($path),
            ];
        }

        if ($mime === 'application/pdf' || $extension === 'pdf') {
            return [
                'type' => 'pdf',
                'src' => url('/documentos/visualizar?id=' . $document['id'] . '&inline=1'),
                'text' => '',
            ];
        }

        return [
            'type' => 'unavailable',
            'src' => '',
            'text' => '',
        ];
    }

    private function streamGooglePdf(string $url, string $filename, bool $publicCache): void
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'follow_location' => 1,
                'ignore_errors' => true,
                'header' => "User-Agent: CidadeNovaInforma/1.0\r\n",
            ],
        ]);
        $pdf = @file_get_contents($url, false, $context);

        if (!$pdf || strncmp($pdf, '%PDF', 4) !== 0) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        $safeName = str_replace(['"', "\r", "\n"], '', pathinfo($filename, PATHINFO_FILENAME) ?: 'documento');

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $safeName . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: ' . ($publicCache ? 'public, max-age=600' : 'private, max-age=0, must-revalidate'));

        echo $pdf;
        exit;
    }

    private function institutionAreas(): array
    {
        $areas = InstitutionPage::all();

        return array_column($areas, null, 'slug');
    }

    private function institutionPhotos(string $slug): array
    {
        $files = glob(dirname(__DIR__, 2) . '/public/uploads/news/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];
        $files = array_values(array_filter($files, 'is_file'));
        sort($files);

        $offsets = ['biblioteca' => 0, 'horta' => 2, 'radio' => 4];
        $offset = $offsets[$slug] ?? 0;
        $selected = array_slice(array_values(array_unique(array_merge(array_slice($files, $offset), $files))), 0, 4);

        return array_map(
            fn (string $file): string => '/public/uploads/news/' . basename($file),
            $selected
        );
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function createPendingLoginIfRequested(array $person, array $event, int $personId): bool
    {
        if (empty($_POST['create_login'])) {
            return false;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['login_password'] ?? '');
        $confirmation = (string) ($_POST['login_password_confirmation'] ?? '');

        if (!$email || strlen($password) < 8 || $password !== $confirmation || User::findByEmail($email)) {
            return false;
        }

        $role = Role::findBySlug('estudante') ?: Role::findBySlug('jornalista');
        if (!$role) {
            return false;
        }

        $courseIds = array_map(fn (array $course): int => (int) $course['id'], $event['linked_courses'] ?? []);
        if (!$courseIds && !empty($event['event_course_id'])) {
            $courseIds[] = (int) $event['event_course_id'];
        }

        $userId = User::create([
            'name' => $person['full_name'] ?? ($_POST['full_name'] ?? ''),
            'email' => $email,
            'password' => $password,
            'role_id' => $role['id'],
            'active' => 0,
            'registration_origin' => 'event',
            'registration_event_id' => $event['id'] ?? null,
            'registration_person_id' => $personId,
            'registration_course_id' => $courseIds[0] ?? null,
        ]);

        Education::enrollUserInCourses($userId, $courseIds, 'pending');

        return true;
    }

    private function remainingEventSlots(array $event): ?int
    {
        $capacity = !empty($event['capacity']) ? (int) $event['capacity'] : null;
        if (!$capacity) {
            return null;
        }

        return max(0, $capacity - LibraryEvent::activeParticipantCount((int) $event['id']));
    }

    private function loginRequestIsValid(): bool
    {
        if (empty($_POST['create_login'])) {
            return true;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['login_password'] ?? '');
        $confirmation = (string) ($_POST['login_password_confirmation'] ?? '');

        return (bool) $email && strlen($password) >= 8 && $password === $confirmation;
    }
}
