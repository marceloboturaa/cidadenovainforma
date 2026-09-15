ALTER TABLE library_event_participants ADD COLUMN IF NOT EXISTS is_coordinator TINYINT(1) NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS library_event_certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    certificate_type ENUM('participacao','coordenacao') NOT NULL,
    student_name VARCHAR(180) NOT NULL,
    event_title VARCHAR(180) NOT NULL,
    event_starts_at DATETIME NULL,
    event_ends_at DATETIME NULL,
    verification_code VARCHAR(48) NOT NULL,
    issued_by BIGINT UNSIGNED NULL,
    issued_at DATETIME NOT NULL,
    UNIQUE KEY uq_event_certificate_recipient_type (event_id, person_id, certificate_type),
    UNIQUE KEY uq_event_certificate_code (verification_code),
    INDEX idx_event_certificate_user (user_id),
    CONSTRAINT fk_event_certificate_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_certificate_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_certificate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_certificate_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
