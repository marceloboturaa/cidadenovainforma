-- Atualizacao: agendamento de aulas e frequencia por aula.

ALTER TABLE education_lessons
    ADD COLUMN IF NOT EXISTS available_at DATETIME NULL AFTER locked,
    ADD COLUMN IF NOT EXISTS attendance_mode VARCHAR(20) NOT NULL DEFAULT 'video' AFTER available_at;

ALTER TABLE education_attendance
    ADD COLUMN IF NOT EXISTS lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER user_id;

ALTER TABLE education_attendance
    DROP INDEX uq_education_attendance_course_user_date;

ALTER TABLE education_attendance
    ADD UNIQUE KEY uq_education_attendance_course_user_date_lesson (course_id, user_id, attendance_date, lesson_id),
    ADD INDEX idx_education_attendance_lesson (lesson_id);
