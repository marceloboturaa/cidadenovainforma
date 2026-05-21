-- Atualizacao: certificados emitidos ao final dos cursos.

ALTER TABLE education_courses
    ADD COLUMN IF NOT EXISTS certificate_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER cover_image,
    ADD COLUMN IF NOT EXISTS certificate_title VARCHAR(180) NULL AFTER certificate_enabled,
    ADD COLUMN IF NOT EXISTS certificate_text TEXT NULL AFTER certificate_title,
    ADD COLUMN IF NOT EXISTS certificate_background VARCHAR(255) NULL AFTER certificate_text,
    ADD COLUMN IF NOT EXISTS certificate_min_frequency TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER certificate_background;

CREATE TABLE IF NOT EXISTS education_certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    verification_code VARCHAR(48) NOT NULL,
    issued_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_education_certificate_course_user (course_id, user_id),
    UNIQUE KEY uq_education_certificate_code (verification_code),
    CONSTRAINT fk_education_certificate_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_certificate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
