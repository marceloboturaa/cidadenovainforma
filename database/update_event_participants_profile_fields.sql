-- Atualiza inscricoes de eventos e usuarios para novos fluxos.

ALTER TABLE library_event_participants
    ADD COLUMN IF NOT EXISTS heard_about VARCHAR(80) NULL AFTER notes;

ALTER TABLE library_event_participants
    ADD COLUMN IF NOT EXISTS event_expectations TEXT NULL AFTER heard_about;

ALTER TABLE library_event_participants
    ADD COLUMN IF NOT EXISTS registration_extra_answer TEXT NULL AFTER event_expectations;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS registration_question_label VARCHAR(180) NULL AFTER related_links;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS registration_question_type VARCHAR(20) NOT NULL DEFAULT 'text' AFTER registration_question_label;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS registration_question_options TEXT NULL AFTER registration_question_type;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS registration_question_required TINYINT(1) NOT NULL DEFAULT 0 AFTER registration_question_options;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_required TINYINT(1) NOT NULL DEFAULT 0 AFTER registration_course_id;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_fields TEXT NULL AFTER profile_update_required;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_requested_by BIGINT UNSIGNED NULL AFTER profile_update_fields;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_requested_at DATETIME NULL AFTER profile_update_requested_by;

CREATE TABLE IF NOT EXISTS library_event_attendance (
    event_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('presente','ausente','justificado') NOT NULL DEFAULT 'presente',
    notes VARCHAR(255) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (event_id, person_id, attendance_date),
    INDEX idx_event_attendance_date (event_id, attendance_date),
    CONSTRAINT fk_event_attendance_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_attendance_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_attendance_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE team_documents
    ADD COLUMN IF NOT EXISTS source_label VARCHAR(255) NULL AFTER size_bytes;
