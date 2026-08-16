ALTER TABLE news
    ADD COLUMN IF NOT EXISTS hide_summary_in_body TINYINT(1) NOT NULL DEFAULT 0 AFTER summary,
    ADD COLUMN IF NOT EXISTS cover_caption VARCHAR(255) NULL AFTER cover_image,
    ADD COLUMN IF NOT EXISTS cover_size VARCHAR(20) NOT NULL DEFAULT 'full' AFTER cover_caption,
    ADD COLUMN IF NOT EXISTS hide_cover_in_body TINYINT(1) NOT NULL DEFAULT 0 AFTER cover_size,
    ADD COLUMN IF NOT EXISTS co_author_name VARCHAR(160) NULL AFTER hide_cover_in_body;
