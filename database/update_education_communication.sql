CREATE TABLE IF NOT EXISTS education_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    student_user_id BIGINT UNSIGNED NOT NULL,
    teacher_user_id BIGINT UNSIGNED NULL,
    status ENUM('aberta','encerrada') NOT NULL DEFAULT 'aberta',
    last_message_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_education_conversation_user (course_id, student_user_id),
    INDEX idx_education_conversations_teacher (teacher_user_id),
    INDEX idx_education_conversations_last_message (last_message_at),
    CONSTRAINT fk_education_conversations_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_conversations_student FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_conversations_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education_conversation_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    read_at DATETIME NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    INDEX idx_education_messages_conversation (conversation_id, created_at),
    INDEX idx_education_messages_sender (sender_user_id),
    CONSTRAINT fk_education_messages_conversation FOREIGN KEY (conversation_id) REFERENCES education_conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_messages_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
