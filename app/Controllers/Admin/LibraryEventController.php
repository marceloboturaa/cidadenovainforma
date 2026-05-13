<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\Session;
use App\Core\View;
use App\Models\LibraryEvent;
use App\Models\Person;
use App\Models\User;

class LibraryEventController
{
    public function index(): void
    {
        Middleware::permission('events.manage');

        View::render('admin/library-events/index', [
            'events' => LibraryEvent::all(),
            'editing' => $this->editing(),
            'users' => User::activeForAccessLists(),
            'canDeactivate' => $this->currentUserIsMaster(),
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
        Middleware::permission('event_participants.manage');
        $event = $this->eventFromQuery();

        View::render('admin/library-events/participants', [
            'event' => $event,
            'participants' => LibraryEvent::participants((int) $event['id']),
            'people' => Person::all(trim((string) ($_GET['q'] ?? ''))),
            'query' => trim((string) ($_GET['q'] ?? '')),
        ]);
    }

    public function addParticipant(): void
    {
        Middleware::permission('event_participants.manage');
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
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

        Logger::info('library_events.participant_added', 'Participante vinculado: ' . $person['full_name'], current_user()['id'] ?? null);
        Session::flash('success', 'Participante adicionado ao evento.');
        redirect('/admin/library-events/participants?id=' . $event['id']);
    }

    public function removeParticipant(): void
    {
        Middleware::permission('event_participants.manage');
        $this->validateCsrf('/admin/library-events');
        $event = $this->eventFromQuery();
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
}
