ALTER TABLE news
    ADD COLUMN IF NOT EXISTS cover_caption VARCHAR(255) NULL AFTER cover_image,
    ADD COLUMN IF NOT EXISTS hide_cover_in_body TINYINT(1) NOT NULL DEFAULT 0 AFTER cover_caption;
