<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\RegistrationNotifier;
use App\Core\Session;
use App\Core\SimplePdf;
use App\Core\View;
use App\Models\Education;
use App\Models\LibraryEvent;
use App\Models\Person;
use App\Models\User;

class LibraryEventController
{
    private const MAX_COVER_SIZE = 5 * 1024 * 1024;

    public function index(): void
    {
        Middleware::permission('events.manage');

        View::render('admin/library-events/index', [
            'events' => LibraryEvent::all($this->volunteerScopeUserId()),
            'editing' => $this->editing(),
            'users' => User::activeForAccessLists(),
            'courses' => Education::publicCourses(12),
            'canDeactivate' => $this->currentUserIsMaster(),
        ]);
    }

    public function registrations(): void
    {
        Middleware::permission('event_participants.manage');
        $events = LibraryEvent::all($this->volunteerScopeUserId());
        $eventSummaries = [];

        foreach ($events as $event) {
            $eventSummaries[(int) $event['id']] = LibraryEvent::participantStats((int) $event['id']);
        }

        View::render('admin/library-events/registrations', [
            'events' => $events,
            'eventSummaries' => $eventSummaries,
        ]);
    }

    public function store(): void
    {
        Middleware::permission('events.manage');
        $this->validateCsrf();
        $title = trim((string) ($_POST['title'] ?? ''));

        if ($title === '') {
            Session::flash('error', 'Informe o nome do evento ou atividade.');
            redirect('/admin/library-events');
        }

        $userId = (int) (current_user()['id'] ?? 0);
        $id = LibraryEvent::create(array_merge($_POST, [
            'cover_image' => $this->uploadCover(),
            'created_by' => $userId ?: null,
            'updated_by' => $userId ?: null,
        ]));

        Logger::info('library_events.created', 'Evento criado: ' . $title, $userId ?: null);
        Session::flash('success', 'Evento cadastrado. ID: ' . $id);
        redirect('/admin/library-events/edit?id=' . $id);
    }

    public function update(): void
    {
        Middleware::permission('events.manage');
        $this->validateCsrf();
        $event = $this->eventFromQuery();
        $title = trim((string) ($_POST['title'] ?? ''));

        if ($title === '') {
            Session::flash('error', 'Informe o nome do evento ou atividade.');
            redirect('/admin/library-events/edit?id=' . $event['id']);
        }

        $userId = (int) (current_user()['id'] ?? 0);
        LibraryEvent::update((int) $event['id'], array_merge($_POST, [
            'cover_image' => $this->uploadCover() ?: ($event['cover_image'] ?? null),
            'updated_by' => $userId ?: null,
        ]));

        Logger::info('library_events.updated', 'Evento atualizado: ' . $title, $userId ?: null);
        Session::flash('success', 'Evento atualizado.');
        redirect('/admin/library-events/edit?id=' . $event['id']);
    }

    public function delete(): void
    {
        Middleware::permission('events.manage');
        $this->masterOnly();
        $this->validateCsrf();
        $event = $this->eventFromQuery();

        LibraryEvent::deactivate((int) $event['id']);
        Logger::info('library_events.deactivated', 'Evento desativado: ' . $event['title'], current_user()['id'] ?? null);
        Session::flash('success', 'Evento desativado.');
        redirect('/admin/library-events');
    }

    public function participants(): void
    {
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);

        View::render('admin/library-events/participants', [
            'event' => $event,
            'participants' => LibraryEvent::participants((int) $event['id']),
            'participantStats' => LibraryEvent::participantStats((int) $event['id']),
            'people' => Person::all(trim((string) ($_GET['q'] ?? '')), $this->volunteerScopeUserId()),
            'users' => User::activeForAccessLists(),
            'query' => trim((string) ($_GET['q'] ?? '')),
        ]);
    }

    public function addParticipant(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $personId = filter_input(INPUT_POST, 'person_id', FILTER_VALIDATE_INT);
        $person = $personId ? Person::find($personId) : null;

        if (!$person || !(int) ($person['active'] ?? 0)) {
            Session::flash('error', 'Pessoa não encontrada.');
            redirect('/admin/library-events/participants?id=' . $event['id']);
        }

        LibraryEvent::attachParticipant(
            (int) $event['id'],
            (int) $person['id'],
            (string) ($_POST['status'] ?? 'inscrito'),
            $_POST['notes'] ?? null,
            current_user()['id'] ?? null
        );

        RegistrationNotifier::eventStatus($event, $person, (string) ($_POST['status'] ?? 'inscrito'));

        Logger::info('library_events.participant_added', 'Participante vinculado: ' . $person['full_name'], current_user()['id'] ?? null);
        Session::flash('success', 'Participante adicionado ao evento.');
        redirect('/admin/library-events/participants?id=' . $event['id']);
    }

    public function createParticipant(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $name = trim((string) ($_POST['full_name'] ?? ''));

        if ($name === '') {
            Session::flash('error', 'Informe o nome completo da pessoa inscrita.');
            redirect('/admin/library-events/participants?id=' . $event['id']);
        }

        $userId = (int) (current_user()['id'] ?? 0);
        $personId = Person::create(array_merge($_POST, [
            'created_by' => $userId ?: null,
            'updated_by' => $userId ?: null,
        ]));

        LibraryEvent::attachParticipant(
            (int) $event['id'],
            $personId,
            (string) ($_POST['status'] ?? 'inscrito'),
            $_POST['participant_notes'] ?? null,
            $userId ?: null
        );

        $person = Person::find($personId);
        if ($person) {
            RegistrationNotifier::eventStatus($event, $person, (string) ($_POST['status'] ?? 'inscrito'));
        }

        Logger::info('library_events.registration_created', 'Inscrição cadastrada: ' . $name, $userId ?: null);
        Session::flash('success', 'Pessoa cadastrada e inscrita no evento.');
        redirect('/admin/library-events/participants?id=' . $event['id']);
    }

    public function addUserParticipant(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $user = $userId ? User::find($userId) : null;

        if (!$user || empty($user['active'])) {
            Session::flash('error', 'Usuário não encontrado ou inativo.');
            redirect('/admin/library-events/participants?id=' . $event['id']);
        }

        $person = Person::findByIdentity(null, (string) ($user['email'] ?? ''), null);
        $personId = $person ? (int) $person['id'] : Person::create([
            'full_name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'contact_authorized' => 1,
            'notes' => 'Criado a partir de usuário já cadastrado no painel.',
            'created_by' => current_user()['id'] ?? null,
            'updated_by' => current_user()['id'] ?? null,
        ]);
        $person = Person::find($personId);

        LibraryEvent::attachParticipant(
            (int) $event['id'],
            $personId,
            (string) ($_POST['status'] ?? 'inscrito'),
            'Adicionado a partir de usuário já cadastrado.',
            current_user()['id'] ?? null
        );

        if ($person) {
            RegistrationNotifier::eventStatus($event, $person, (string) ($_POST['status'] ?? 'inscrito'));
        }

        Logger::info('library_events.user_participant_added', 'Usuário vinculado ao evento: ' . ($user['email'] ?? ''), current_user()['id'] ?? null);
        Session::flash('success', 'Usuário cadastrado como inscrito neste evento.');
        redirect('/admin/library-events/participants?id=' . $event['id']);
    }

    public function exportParticipants(): void
    {
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $participants = LibraryEvent::participants((int) $event['id']);
        $format = strtolower((string) ($_GET['format'] ?? 'csv'));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) $event['title'])) ?: 'evento';
        $slug = trim($slug, '-');

        if ($format === 'pdf') {
            $pdf = SimplePdf::registrationReport($event, $participants);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $slug . '-participantes.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $slug . '-participantes.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Evento', 'Nome', 'Status', 'CPF', 'Nascimento', 'Telefone', 'WhatsApp', 'E-mail', 'CEP', 'Endereco', 'Numero', 'Complemento', 'Bairro', 'Cidade', 'UF', 'Menor', 'Responsavel', 'Parentesco', 'CPF responsavel', 'Telefone responsavel', 'E-mail responsavel', 'Contato autorizado', 'Uso de imagem autorizado', 'Observacoes da inscricao'], ';');
        foreach ($participants as $participant) {
            fputcsv($output, [
                $event['title'] ?? '',
                $participant['full_name'] ?? '',
                $participant['status'] ?? '',
                $participant['cpf'] ?? '',
                $participant['birth_date'] ?? '',
                $participant['phone'] ?? '',
                $participant['whatsapp'] ?? '',
                $participant['email'] ?? '',
                $participant['cep'] ?? '',
                $participant['address'] ?? '',
                $participant['address_number'] ?? '',
                $participant['address_complement'] ?? '',
                $participant['district'] ?? '',
                $participant['city'] ?? '',
                $participant['state'] ?? '',
                !empty($participant['is_minor']) ? 'Sim' : 'Nao',
                $participant['guardian_name'] ?? '',
                $participant['guardian_relation'] ?? '',
                $participant['guardian_cpf'] ?? '',
                $participant['guardian_phone'] ?? '',
                $participant['guardian_email'] ?? '',
                !empty($participant['contact_authorized']) ? 'Sim' : 'Nao',
                !empty($participant['image_authorized']) ? 'Sim' : 'Nao',
                $participant['notes'] ?? '',
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function removeParticipant(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $personId = filter_input(INPUT_GET, 'person_id', FILTER_VALIDATE_INT);

        if ($personId) {
            LibraryEvent::detachParticipant((int) $event['id'], $personId);
            Logger::info('library_events.participant_removed', 'Participante removido do evento: ' . $event['title'], current_user()['id'] ?? null);
            Session::flash('success', 'Participante removido do evento.');
        }

        redirect('/admin/library-events/participants?id=' . $event['id']);
    }

    private function editing(): ?array
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        return $id ? LibraryEvent::find($id) : null;
    }

    private function eventFromQuery(): array
    {
        $event = $this->editing();

        if (!$event) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $scopeUserId = $this->volunteerScopeUserId();
        if (
            $scopeUserId !== null
            && (int) ($event['created_by'] ?? 0) !== $scopeUserId
            && (int) ($event['responsible_user_id'] ?? 0) !== $scopeUserId
        ) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        return $event;
    }

    private function validateCsrf(string $redirectTo = '/admin/library-events'): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessão expirada. Tente novamente.');
            redirect($redirectTo);
        }
    }

    private function masterOnly(): void
    {
        if (!$this->currentUserIsMaster()) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    private function currentUserIsMaster(): bool
    {
        $user = Auth::user();
        return $user && ($user['role_slug'] ?? '') === 'master';
    }

    private function volunteerScopeUserId(): ?int
    {
        $user = Auth::user();

        if (!$user || !Auth::hasRole(['voluntario', 'equipe']) || Auth::hasRole(['master', 'admin', 'admin-local', 'diretor'])) {
            return null;
        }

        return (int) $user['id'];
    }

    private function uploadCover(): ?string
    {
        if (empty($_FILES['cover_image']['name']) || ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($_FILES['cover_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Não foi possível enviar a imagem de capa.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/library-events');
        }

        $tmpName = $_FILES['cover_image']['tmp_name'] ?? '';
        $size = (int) ($_FILES['cover_image']['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowed = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        if (!$imageInfo || !isset($allowed[$imageInfo[2] ?? 0]) || $size <= 0 || $size > self::MAX_COVER_SIZE) {
            Session::flash('error', 'Envie uma imagem JPG, PNG, WEBP ou GIF com até 5MB.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/library-events');
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/events';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!is_writable($directory)) {
            Session::flash('error', 'A pasta de capas de eventos não está gravável.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/library-events');
        }

        $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$imageInfo[2]];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            Session::flash('error', 'Não foi possível salvar a imagem de capa.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin/library-events');
        }

        return '/public/uploads/events/' . $filename;
    }

    private function authorizeParticipantManagement(array $event): void
    {
        Middleware::auth();
        $user = Auth::user();
        if (
            $user
            && (Auth::can('event_participants.manage') || (int) ($event['responsible_user_id'] ?? 0) === (int) $user['id'])
        ) {
            return;
        }

        http_response_code(403);
        View::render('errors/403');
        exit;
    }

    private function downloadPdf(string $filename, string $title, array $lines): void
    {
        $pdf = SimplePdf::fromLines($title, $lines ?: ['Nenhum participante encontrado.']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
