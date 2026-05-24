-- Atualizacao: tarefas/certificados nos materiais da aula e sistema de chamada.
-- A coluna education_lesson_blocks.type ja aceita texto livre, entao nao precisa alterar tabela para os novos tipos:
-- assignment = Tarefa
-- certificate = Certificado

CREATE TABLE IF NOT EXISTS education_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','justified') NOT NULL DEFAULT 'present',
    notes VARCHAR(255) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_education_attendance_course_user_date_lesson (course_id, user_id, attendance_date, lesson_id),
    INDEX idx_education_attendance_course_date (course_id, attendance_date),
    INDEX idx_education_attendance_lesson (lesson_id),
    CONSTRAINT fk_education_attendance_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_attendance_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE education_attendance ADD COLUMN IF NOT EXISTS lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER user_id;
