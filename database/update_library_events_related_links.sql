ALTER TABLE library_events
ADD COLUMN IF NOT EXISTS related_links TEXT NULL AFTER cover_image;
