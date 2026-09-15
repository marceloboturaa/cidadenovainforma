-- Apply after the education and announcements tables exist.
CREATE TABLE IF NOT EXISTS certificate_notifications (
    certificate_id BIGINT UNSIGNED PRIMARY KEY,
    announcement_id BIGINT UNSIGNED NULL,
    email_sent_at DATETIME NULL,
    email_attempted_at DATETIME NULL,
    email_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_certificate_notifications_email (email_sent_at, email_attempted_at),
    CONSTRAINT fk_certificate_notification_certificate FOREIGN KEY (certificate_id) REFERENCES education_certificates(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificate_notification_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE SET NULL
) ENGINE=InnoDB;
