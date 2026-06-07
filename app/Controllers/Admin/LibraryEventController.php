<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Middleware;
use App\Core\RegistrationNotifier;
use App\Core\Session;
use App\Core\SimplePdf;
use App\Core\View;
use App\Models\Document;
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
            'courses' => Education::publicCourses(100),
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
        $attendanceDate = $this->attendanceDateFromRequest($_GET['attendance_date'] ?? null);

        View::render('admin/library-events/participants', [
            'event' => $event,
            'participants' => LibraryEvent::participants((int) $event['id']),
            'participantStats' => LibraryEvent::participantStats((int) $event['id']),
            'attendanceDate' => $attendanceDate,
            'attendanceRows' => LibraryEvent::attendanceForDate((int) $event['id'], $attendanceDate),
            'attendanceDates' => LibraryEvent::attendanceDates((int) $event['id']),
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
            current_user()['id'] ?? null,
            $_POST['heard_about'] ?? null,
            $_POST['event_expectations'] ?? null,
            $_POST['registration_extra_answer'] ?? null
        );

        RegistrationNotifier::eventStatus($event, $person, (string) ($_POST['status'] ?? 'inscrito'));

        Logger::info('library_events.participant_added', 'Participante vinculado: ' . $person['full_name'], current_user()['id'] ?? null);
        Session::flash('success', 'Participante adicionado ao evento.');
        redirect('/admin/library-events/participants?id=' . $event['id']);
    }

    public function saveAttendance(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $date = $this->attendanceDateFromRequest($_POST['attendance_date'] ?? null);
        $attendance = $_POST['attendance'] ?? [];
        $attendance = is_array($attendance) ? $attendance : [];

        $saved = LibraryEvent::saveAttendance((int) $event['id'], $date, $attendance, (int) (current_user()['id'] ?? 0));
        Logger::info('library_events.attendance_saved', 'Chamada salva para o evento: ' . $event['title'] . ' em ' . $date, current_user()['id'] ?? null);
        Session::flash($saved > 0 ? 'success' : 'error', $saved > 0 ? 'Chamada salva para ' . $saved . ' participante(s).' : 'Nenhuma presença foi salva.');
        redirect('/admin/library-events/participants?id=' . $event['id'] . '&attendance_date=' . urlencode($date));
    }

    public function renameAttendanceDate(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $oldDate = $this->attendanceDateFromRequest($_POST['old_attendance_date'] ?? null);
        $newDate = $this->attendanceDateFromRequest($_POST['new_attendance_date'] ?? null);

        if ($oldDate === $newDate) {
            Session::flash('error', 'Informe uma nova data diferente da atual.');
            redirect('/admin/library-events/participants?id=' . $event['id'] . '&attendance_date=' . urlencode($oldDate));
        }

        $updated = LibraryEvent::renameAttendanceDate((int) $event['id'], $oldDate, $newDate);
        Logger::info('library_events.attendance_date_changed', 'Data da chamada alterada no evento: ' . $event['title'], current_user()['id'] ?? null);
        Session::flash($updated > 0 ? 'success' : 'error', $updated > 0 ? 'Data da chamada atualizada.' : 'Nenhuma chamada foi encontrada nessa data.');
        redirect('/admin/library-events/participants?id=' . $event['id'] . '&attendance_date=' . urlencode($newDate));
    }

    public function emailDocument(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);

        $mode = in_array((string) ($_POST['recipient_mode'] ?? 'all'), ['all', 'selected', 'attendance'], true)
            ? (string) $_POST['recipient_mode']
            : 'all';
        $personIds = $_POST['person_ids'] ?? [];
        $personIds = is_array($personIds) ? $personIds : [];
        $date = $mode === 'attendance' ? $this->attendanceDateFromRequest($_POST['attendance_date'] ?? null) : null;
        $attendanceStatus = in_array((string) ($_POST['attendance_status'] ?? ''), ['presente', 'ausente', 'justificado'], true)
            ? (string) $_POST['attendance_status']
            : null;
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($subject === '') {
            $subject = 'Documento do evento ' . ($event['title'] ?? '');
        }

        $document = $this->uploadEventDocument();
        if (!$document) {
            redirect('/admin/library-events/participants?id=' . $event['id']);
        }

        $recipients = LibraryEvent::emailRecipients((int) $event['id'], $mode, $personIds, $date, $attendanceStatus);
        if (!$recipients) {
            Session::flash('error', 'Nenhum participante com e-mail válido foi encontrado para este envio.');
            redirect('/admin/library-events/participants?id=' . $event['id']);
        }

        $documentUrl = url($document['path']);
        if (!empty($_POST['publish_public_document'])) {
            $documentId = Document::create([
                'uploaded_by' => current_user()['id'] ?? 0,
                'title' => trim((string) ($_POST['public_document_title'] ?? '')) ?: ('Documento do evento - ' . ($event['title'] ?? '')),
                'path' => $document['path'],
                'mime_type' => $document['mime_type'],
                'original_name' => $document['original_name'],
                'size_bytes' => $document['size_bytes'],
                'source_label' => 'Documento enviado pelo evento: ' . ($event['title'] ?? 'Evento'),
                'is_public' => 1,
                'allow_download' => !empty($_POST['public_document_download']),
            ]);
            $documentUrl = url('/documentos/visualizar?id=' . $documentId);
        }
        $sent = 0;
        foreach ($recipients as $recipient) {
            $html = '<p>Olá, ' . e($recipient['full_name'] ?? '') . '.</p>'
                . '<p>' . nl2br(e($message !== '' ? $message : 'Segue o documento do evento.')) . '</p>'
                . '<p><a href="' . e($documentUrl) . '">Abrir documento</a></p>'
                . '<p>Evento: ' . e($event['title'] ?? '') . '</p>';
            $text = "Olá, " . ($recipient['full_name'] ?? '') . ".\n\n"
                . ($message !== '' ? $message : 'Segue o documento do evento.') . "\n\n"
                . "Documento: " . $documentUrl . "\n"
                . "Evento: " . ($event['title'] ?? '');
            if (Mailer::send((string) $recipient['email'], $subject, $html, $text)) {
                $sent++;
            }
        }

        Logger::info('library_events.document_emailed', 'Documento enviado por e-mail no evento: ' . $event['title'], current_user()['id'] ?? null);
        Session::flash($sent > 0 ? 'success' : 'error', $sent > 0 ? 'Documento enviado para ' . $sent . ' participante(s).' : 'Não foi possível enviar o e-mail.');
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
            $userId ?: null,
            $_POST['heard_about'] ?? null,
            $_POST['event_expectations'] ?? null,
            $_POST['registration_extra_answer'] ?? null
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
        $status = $this->participantStatusFromQuery();
        $participants = LibraryEvent::participants((int) $event['id'], $status);
        $format = strtolower((string) ($_GET['format'] ?? 'csv'));
        $report = strtolower((string) ($_GET['report'] ?? ''));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) $event['title'])) ?: 'evento';
        $slug = trim($slug, '-');

        if ($report === 'names') {
            $participants = LibraryEvent::participants((int) $event['id'], 'inscrito');

            if ($format === 'pdf') {
                $pdf = SimplePdf::participantNamesReport($event, $participants);
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $slug . '-nomes-dos-inscritos.pdf"');
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                exit;
            }

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $slug . '-nomes-dos-inscritos.csv"');
            echo "\xEF\xBB\xBF";
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nº', 'Nome'], ';');
            foreach ($participants as $index => $participant) {
                fputcsv($output, [
                    $index + 1,
                    $participant['full_name'] ?? '',
                ], ';');
            }
            fclose($output);
            exit;
        }

        if ($report === 'attendance') {
            $attendanceDate = isset($_GET['attendance_date']) && $_GET['attendance_date'] !== ''
                ? $this->attendanceDateFromRequest($_GET['attendance_date'])
                : null;
            $attendanceStatus = in_array((string) ($_GET['attendance_status'] ?? ''), ['presente', 'ausente', 'justificado'], true)
                ? (string) $_GET['attendance_status']
                : null;
            if ($attendanceDate) {
                $participants = array_map(static function (array $row): array {
                    $row['status'] = $row['attendance_status'] ?? 'sem chamada';
                    return $row;
                }, LibraryEvent::attendanceForDate((int) $event['id'], $attendanceDate));
                if ($attendanceStatus) {
                    $participants = array_values(array_filter(
                        $participants,
                        fn (array $participant): bool => ($participant['attendance_status'] ?? '') === $attendanceStatus
                    ));
                }
            } elseif ($status === null) {
                $participants = array_values(array_filter(
                    $participants,
                    fn (array $participant): bool => ($participant['status'] ?? '') !== 'cancelado'
                ));
            }

            if ($format === 'pdf') {
                $pdf = SimplePdf::attendanceReport($event, $participants);
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $slug . '-lista-de-chamada.pdf"');
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                exit;
            }

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $slug . '-lista-de-chamada.csv"');
            echo "\xEF\xBB\xBF";
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nº', 'Data', 'Nome', 'Status', 'WhatsApp', 'Observação', 'Assinatura'], ';');
            foreach ($participants as $index => $participant) {
                fputcsv($output, [
                    $index + 1,
                    $attendanceDate ?? '',
                    $participant['full_name'] ?? '',
                    $participant['status'] ?? '',
                    $participant['whatsapp'] ?? $participant['phone'] ?? '',
                    $participant['attendance_notes'] ?? '',
                    '',
                ], ';');
            }
            fclose($output);
            exit;
        }

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
        fputcsv($output, ['Evento', 'Nome', 'Status', 'CPF', 'Nascimento', 'Telefone', 'WhatsApp', 'E-mail', 'CEP', 'Endereço', 'Número', 'Complemento', 'Bairro', 'Cidade', 'UF', 'Menor', 'Responsável', 'Parentesco', 'CPF responsável', 'Telefone responsável', 'E-mail responsável', 'Contato autorizado', 'Uso de imagem autorizado', 'Como soube do evento', 'O que espera do evento', 'Resposta da pergunta do evento', 'Observações da inscrição'], ';');
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
                $participant['heard_about'] ?? '',
                $participant['event_expectations'] ?? '',
                $participant['registration_extra_answer'] ?? '',
                $participant['notes'] ?? '',
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function bulkParticipantStatus(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);

        $action = (string) ($_POST['bulk_action'] ?? 'selected');
        $status = (string) ($_POST['status'] ?? 'inscrito');
        $personIds = $_POST['person_ids'] ?? [];
        $personIds = is_array($personIds) ? $personIds : [];

        if ($action === 'all_pending') {
            $updated = LibraryEvent::updatePendingParticipantStatuses((int) $event['id'], 'inscrito');
            $message = $updated . ' inscriÃ§Ã£o(Ãµes) pendente(s) atualizada(s).';
        } else {
            $updated = LibraryEvent::updateParticipantStatuses((int) $event['id'], $personIds, $status);
            $message = $updated . ' participante(s) selecionado(s) atualizado(s).';
        }

        Logger::info('library_events.participants_bulk_status', 'Status em lote atualizado no evento: ' . $event['title'], current_user()['id'] ?? null);
        Session::flash($updated > 0 ? 'success' : 'error', $updated > 0 ? $message : 'Nenhuma inscriÃ§Ã£o foi alterada.');
        redirect('/admin/library-events/participants?id=' . $event['id']);
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

    public function removeAllParticipants(): void
    {
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
        $this->authorizeParticipantManagement($event);
        $confirmation = trim((string) ($_POST['confirm_remove_all'] ?? ''));

        if ($confirmation !== 'REMOVER') {
            Session::flash('error', 'Digite REMOVER para excluir todos os participantes deste evento.');
            redirect('/admin/library-events/participants?id=' . $event['id']);
        }

        $removed = LibraryEvent::detachAllParticipants((int) $event['id']);
        Logger::info('library_events.participants_removed_all', 'Todos os participantes removidos do evento: ' . $event['title'], current_user()['id'] ?? null);
        Session::flash('success', $removed . ' participante(s) removido(s) deste evento.');
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

    private function uploadEventDocument(): ?array
    {
        if (empty($_FILES['document']['name']) || ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Session::flash('error', 'Selecione um documento para enviar.');
            return null;
        }

        if (($_FILES['document']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Não foi possível receber o documento enviado.');
            return null;
        }

        $tmpName = (string) ($_FILES['document']['tmp_name'] ?? '');
        $size = (int) ($_FILES['document']['size'] ?? 0);
        $extension = strtolower(pathinfo((string) $_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];

        if ($size <= 0 || $size > 8 * 1024 * 1024 || !in_array($extension, $allowed, true)) {
            Session::flash('error', 'Envie um arquivo PDF, Word, Excel ou imagem com até 8 MB.');
            return null;
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/documents';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        if (!is_writable($directory)) {
            Session::flash('error', 'A pasta de documentos do evento não está gravável.');
            return null;
        }

        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($tmpName, $target)) {
            Session::flash('error', 'Não foi possível salvar o documento.');
            return null;
        }

        return [
            'path' => '/public/uploads/documents/' . $filename,
            'mime_type' => (string) ($_FILES['document']['type'] ?? 'application/octet-stream'),
            'original_name' => (string) $_FILES['document']['name'],
            'size_bytes' => $size,
        ];
    }

    private function attendanceDateFromRequest(mixed $value): string
    {
        $date = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        return date('Y-m-d');
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

    private function participantStatusFromQuery(): ?string
    {
        $status = strtolower(trim((string) ($_GET['status'] ?? '')));
        return in_array($status, ['pendente', 'inscrito', 'presente', 'ausente', 'cancelado'], true) ? $status : null;
    }
}
