-- Separa a vitrine publica do acesso aberto sem login.
ALTER TABLE education_courses
    ADD COLUMN IF NOT EXISTS public_access_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER public_enabled;

ALTER TABLE education_lessons
    ADD COLUMN IF NOT EXISTS public_access VARCHAR(20) NOT NULL DEFAULT 'private' AFTER image_url;

ALTER TABLE education_lesson_blocks
    ADD COLUMN IF NOT EXISTS public_access VARCHAR(20) NOT NULL DEFAULT 'inherit' AFTER settings_json;
