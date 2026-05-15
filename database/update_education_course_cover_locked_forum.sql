ALTER TABLE education_lessons
    ADD COLUMN IF NOT EXISTS locked TINYINT(1) NOT NULL DEFAULT 0 AFTER image_url;

CREATE TABLE IF NOT EXISTS education_lesson_watches (
    lesson_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (lesson_id, user_id),
    CONSTRAINT fk_education_watches_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_education_watches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
