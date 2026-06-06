-- Atualiza inscricoes de eventos e usuarios para novos fluxos.

ALTER TABLE library_event_participants
    ADD COLUMN IF NOT EXISTS heard_about VARCHAR(80) NULL AFTER notes;

ALTER TABLE library_event_participants
    ADD COLUMN IF NOT EXISTS event_expectations TEXT NULL AFTER heard_about;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_required TINYINT(1) NOT NULL DEFAULT 0 AFTER registration_course_id;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_requested_by BIGINT UNSIGNED NULL AFTER profile_update_required;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_update_requested_at DATETIME NULL AFTER profile_update_requested_by;
