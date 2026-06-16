CREATE TABLE IF NOT EXISTS event_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    participant_user_id BIGINT UNSIGNED NOT NULL,
    responsible_user_id BIGINT UNSIGNED NULL,
    status ENUM('aberta','encerrada') NOT NULL DEFAULT 'aberta',
    last_message_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_event_conversation_user (event_id, participant_user_id),
    INDEX idx_event_conversations_responsible (responsible_user_id),
    INDEX idx_event_conversations_last_message (last_message_at),
    CONSTRAINT fk_event_conversations_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_conversations_participant FOREIGN KEY (participant_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_conversations_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_conversation_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    read_at DATETIME NULL,
    INDEX idx_event_messages_conversation (conversation_id, created_at),
    INDEX idx_event_messages_sender (sender_user_id),
    CONSTRAINT fk_event_messages_conversation FOREIGN KEY (conversation_id) REFERENCES event_conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
