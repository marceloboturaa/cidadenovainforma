ALTER TABLE education_lessons
    ADD COLUMN IF NOT EXISTS description_position VARCHAR(20) NOT NULL DEFAULT 'after_media' AFTER description;

ALTER TABLE education_lesson_blocks
    ADD COLUMN IF NOT EXISTS settings_json LONGTEXT NULL AFTER file_path;
