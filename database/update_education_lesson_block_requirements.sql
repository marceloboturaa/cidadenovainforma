ALTER TABLE education_lesson_blocks
    ADD COLUMN IF NOT EXISTS required TINYINT(1) NOT NULL DEFAULT 1 AFTER file_path;

CREATE TABLE IF NOT EXISTS education_lesson_block_watches (
    block_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (block_id, user_id),
    CONSTRAINT fk_education_block_watches_block FOREIGN KEY (block_id) REFERENCES education_lesson_blocks(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_block_watches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
