ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS certificate_auto_release TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS starts_at DATE NULL;
ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS ends_at DATE NULL;
ALTER TABLE education_courses ADD COLUMN IF NOT EXISTS workload_hours DECIMAL(6,2) NULL;
