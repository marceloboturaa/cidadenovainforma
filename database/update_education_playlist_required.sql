ALTER TABLE education_courses
    ADD COLUMN playlist_required TINYINT(1) NOT NULL DEFAULT 1 AFTER public_enabled;
