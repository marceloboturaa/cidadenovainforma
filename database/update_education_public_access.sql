-- Separa a vitrine publica do acesso aberto sem login.
ALTER TABLE education_courses
    ADD COLUMN IF NOT EXISTS public_access_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER public_enabled;
