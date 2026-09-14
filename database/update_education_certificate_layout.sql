-- Opções de layout da frente e do verso dos certificados.
ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS certificate_footer_on_back TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS certificate_program_background_enabled TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS certificate_program_text_color VARCHAR(20) NULL;
