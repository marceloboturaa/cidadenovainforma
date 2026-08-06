ALTER TABLE education_modules
    ADD COLUMN required TINYINT(1) NOT NULL DEFAULT 1 AFTER summary;
