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
            'selectedConversation' => null,
            'messages' => [],
            'canModerateCommunication' => $user ? Communication::canModerate($user) : false,
        ]);
    }

    public function show(): void
    {
        $user = current_user();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $conversation = ($user && $id) ? Communication::findConversationForUser($id, $user) : null;

        if (!$conversation) {
            Session::flash('error', 'Conversa nao encontrada ou sem permissao de acesso.');
            redirect('/admin/communication');
        }

        View::render('admin/communication/index', [
            'conversations' => Communication::conversationsForUser($user),
            'events' => Communication::availableEventsForUser($user),
            'selectedConversation' => $conversation,
            'messages' => Communication::messages((int) $conversation['id']),
            'canModerateCommunication' => Communication::canModerate($user),
        ]);
    }

    public function start(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            Session::flash('error', 'Sessao expirada. Tente novamente.');
            redirect('/admin/communication');
        }

        $user = current_user();
        $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

        if (!$user || !$eventId) {
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
        $conversation = ($user && $conversationId) ? Communication::findConversationForUser($conversationId, $user) : null;

        if (!$user || !$conversation) {
            Session::flash('error', 'Conversa nao encontrada ou sem permissao de acesso.');
            redirect('/admin/communication');
        }

        if (!Communication::addMessage((int) $conversation['id'], (int) $user['id'], (string) ($_POST['body'] ?? ''))) {
            Session::flash('error', 'Digite uma mensagem antes de enviar.');
            redirect('/admin/communication/show?id=' . $conversation['id']);
        }

        Logger::info('communication.message_sent', 'Mensagem enviada em conversa de evento.', (int) $user['id']);
        redirect('/admin/communication/show?id=' . $conversation['id']);
    }
}
