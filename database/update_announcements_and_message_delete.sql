CREATE TABLE IF NOT EXISTS announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    url VARCHAR(255) NULL,
    button_label VARCHAR(80) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_announcements_active_created (active, created_at),
    CONSTRAINT fk_announcements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcement_reads (
    announcement_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL,
    PRIMARY KEY (announcement_id, user_id),
    CONSTRAINT fk_announcement_reads_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    CONSTRAINT fk_announcement_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    INDEX idx_event_messages_conversation (conversation_id, created_at),
    INDEX idx_event_messages_sender (sender_user_id),
    CONSTRAINT fk_event_messages_conversation FOREIGN KEY (conversation_id) REFERENCES event_conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_messages_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE event_conversation_messages
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER read_at;

ALTER TABLE event_conversation_messages
    ADD COLUMN IF NOT EXISTS deleted_by BIGINT UNSIGNED NULL AFTER deleted_at;

ALTER TABLE education_conversation_messages
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER read_at;

ALTER TABLE education_conversation_messages
    ADD COLUMN IF NOT EXISTS deleted_by BIGINT UNSIGNED NULL AFTER deleted_at;

INSERT IGNORE INTO permissions (name, slug, created_at)
VALUES ('Gerenciar avisos internos', 'announcements.manage', NOW());

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug = 'announcements.manage'
WHERE roles.slug = 'admin';
