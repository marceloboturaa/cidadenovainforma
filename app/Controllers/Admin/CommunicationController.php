<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Models\Communication;

class CommunicationController
{
    public function index(): void
    {
        $user = current_user();

        View::render('admin/communication/index', [
            'conversations' => $user ? Communication::conversationsForUser($user) : [],
            'events' => $user ? Communication::availableEventsForUser($user) : [],
            'courses' => $user ? Communication::availableCoursesForUser($user) : [],
            'courseContacts' => $user ? Communication::availableCourseContactsForUser($user) : [],
            'selectedConversation' => null,
            'selectedType' => 'event',
            'messages' => [],
            'canModerateCommunication' => $user ? Communication::canModerate($user) : false,
            'canStartCourseContact' => $user ? (Communication::canModerateEducation($user) || \App\Core\Auth::hasRole('professor') || \App\Core\Auth::can('education.teach')) : false,
        ]);
    }

    public function show(): void
    {
        $user = current_user();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $type = strtolower((string) ($_GET['type'] ?? 'event'));
        $type = $type === 'education' ? 'education' : 'event';
        $conversation = null;
        if ($user && $id) {
            $conversation = $type === 'education'
                ? Communication::findEducationConversationForUser($id, $user)
                : Communication::findConversationForUser($id, $user);
        }

        if (!$conversation) {
            Session::flash('error', 'Conversa nao encontrada ou sem permissao de acesso.');
            redirect('/admin/communication');
        }

        View::render('admin/communication/index', [
            'conversations' => Communication::conversationsForUser($user),
            'events' => Communication::availableEventsForUser($user),
            'courses' => Communication::availableCoursesForUser($user),
            'courseContacts' => Communication::availableCourseContactsForUser($user),
            'selectedConversation' => $conversation,
            'selectedType' => $type,
            'messages' => $type === 'education'
                ? Communication::educationMessages((int) $conversation['id'])
                : Communication::messages((int) $conversation['id']),
            'canModerateCommunication' => Communication::canModerate($user),
            'canStartCourseContact' => Communication::canModerateEducation($user) || \App\Core\Auth::hasRole('professor') || \App\Core\Auth::can('education.teach'),
        ]);
    }

    public function start(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin/communication');
        }

        $user = current_user();
        $channel = strtolower((string) ($_POST['channel'] ?? 'event'));

        if (!$user) {
            Session::flash('error', 'Sua sessao expirou. Entre novamente.');
            redirect('/admin/communication');
        }

        if ($channel === 'education') {
            [$courseId, $studentUserId] = $this->courseStartPayload();
            if (!$courseId) {
                Session::flash('error', 'Selecione um curso para iniciar a conversa.');
                redirect('/admin/communication');
            }

            $conversationId = Communication::startEducationConversation($courseId, $user, $studentUserId);
            if (!$conversationId) {
                Session::flash('error', 'Nao foi possivel iniciar conversa para este curso.');
                redirect('/admin/communication');
            }

            Logger::info('communication.education_started', 'Conversa de curso iniciada.', (int) $user['id']);
            redirect('/admin/communication/show?type=education&id=' . $conversationId);
        }

        $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

        if (!$eventId) {
            Session::flash('error', 'Selecione um evento para iniciar a conversa.');
            redirect('/admin/communication');
        }

        $conversationId = Communication::startConversation($eventId, $user);
        if (!$conversationId) {
            Session::flash('error', 'Nao foi possivel iniciar conversa para este evento.');
            redirect('/admin/communication');
        }

        Logger::info('communication.started', 'Conversa de evento iniciada.', (int) $user['id']);
        redirect('/admin/communication/show?id=' . $conversationId);
    }

    public function send(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin/communication');
        }

        $user = current_user();
        $conversationId = filter_input(INPUT_POST, 'conversation_id', FILTER_VALIDATE_INT);
        $type = strtolower((string) ($_POST['conversation_type'] ?? 'event'));
        $type = $type === 'education' ? 'education' : 'event';
        $conversation = null;
        if ($user && $conversationId) {
            $conversation = $type === 'education'
                ? Communication::findEducationConversationForUser($conversationId, $user)
                : Communication::findConversationForUser($conversationId, $user);
        }

        if (!$user || !$conversation) {
            Session::flash('error', 'Conversa nao encontrada ou sem permissao de acesso.');
            redirect('/admin/communication');
        }

        $sent = $type === 'education'
            ? Communication::addEducationMessage((int) $conversation['id'], (int) $user['id'], (string) ($_POST['body'] ?? ''))
            : Communication::addMessage((int) $conversation['id'], (int) $user['id'], (string) ($_POST['body'] ?? ''));

        if (!$sent) {
            Session::flash('error', 'Digite uma mensagem antes de enviar.');
            redirect('/admin/communication/show?type=' . $type . '&id=' . $conversation['id']);
        }

        Logger::info('communication.message_sent', 'Mensagem enviada em conversa interna.', (int) $user['id']);
        redirect('/admin/communication/show?type=' . $type . '&id=' . $conversation['id']);
    }

    public function deleteMessage(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin/communication');
        }

        $user = current_user();
        $messageId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        $conversationId = filter_input(INPUT_POST, 'conversation_id', FILTER_VALIDATE_INT);
        $type = strtolower((string) ($_POST['conversation_type'] ?? 'event'));
        $type = $type === 'education' ? 'education' : 'event';

        if (!$user || !$messageId || !$conversationId) {
            Session::flash('error', 'Mensagem nao encontrada.');
            redirect('/admin/communication');
        }

        if (!Communication::deleteMessage($messageId, (int) $user['id'], $type)) {
            Session::flash('error', 'Voce so pode apagar mensagens enviadas por voce.');
            redirect('/admin/communication/show?type=' . $type . '&id=' . $conversationId);
        }

        Logger::info('communication.message_deleted', 'Mensagem removida pelo autor.', (int) $user['id']);
        Session::flash('success', 'Mensagem apagada.');
        redirect('/admin/communication/show?type=' . $type . '&id=' . $conversationId);
    }

    private function courseStartPayload(): array
    {
        $contact = trim((string) ($_POST['course_contact'] ?? ''));
        if ($contact !== '' && str_contains($contact, ':')) {
            [$courseId, $studentUserId] = array_pad(explode(':', $contact, 2), 2, null);
            return [(int) $courseId, (int) $studentUserId ?: null];
        }

        return [filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0, null];
    }
}
