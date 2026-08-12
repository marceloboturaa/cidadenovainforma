<?php

namespace App\Models;

use App\Core\Database;

class Education
{
    public static function ensureSchema(): void
    {
        $db = Database::connection();

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_institutions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(180) NOT NULL,
                slug VARCHAR(190) NOT NULL UNIQUE,
                cnpj VARCHAR(32) NULL,
                city VARCHAR(120) NULL,
                state VARCHAR(2) NULL,
                site VARCHAR(180) NULL,
                logo_path VARCHAR(255) NULL,
                signature_path VARCHAR(255) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_certificate_institutions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_institutions_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_institution_users (
                institution_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                role_slug VARCHAR(40) NOT NULL DEFAULT "admin-local",
                can_issue TINYINT(1) NOT NULL DEFAULT 0,
                expires_at DATETIME NULL,
                approved_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                PRIMARY KEY (institution_id, user_id),
                CONSTRAINT fk_certificate_institution_users_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE CASCADE,
                CONSTRAINT fk_certificate_institution_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_certificate_institution_users_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                institution_id BIGINT UNSIGNED NULL,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                description TEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_certificate_categories_scope (institution_id, slug),
                CONSTRAINT fk_certificate_categories_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_templates (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                institution_id BIGINT UNSIGNED NULL,
                category_id BIGINT UNSIGNED NULL,
                name VARCHAR(160) NOT NULL,
                slug VARCHAR(180) NOT NULL,
                description TEXT NULL,
                front_background VARCHAR(255) NULL,
                back_background VARCHAR(255) NULL,
                legal_text TEXT NULL,
                layout_json LONGTEXT NULL,
                version INT UNSIGNED NOT NULL DEFAULT 1,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_certificate_templates_scope (institution_id, slug),
                CONSTRAINT fk_certificate_templates_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_templates_category FOREIGN KEY (category_id) REFERENCES certificate_categories(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_templates_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_templates_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_template_versions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                template_id BIGINT UNSIGNED NOT NULL,
                version INT UNSIGNED NOT NULL,
                snapshot_json LONGTEXT NOT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                UNIQUE KEY uq_certificate_template_versions (template_id, version),
                CONSTRAINT fk_certificate_template_versions_template FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE CASCADE,
                CONSTRAINT fk_certificate_template_versions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_courses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                summary TEXT NULL,
                cover_image VARCHAR(255) NULL,
                certificate_institution_id BIGINT UNSIGNED NULL,
                certificate_category_id BIGINT UNSIGNED NULL,
                certificate_template_id BIGINT UNSIGNED NULL,
                certificate_activity_type VARCHAR(40) NOT NULL DEFAULT "curso_livre",
                workload_hours DECIMAL(6,2) NULL,
                starts_at DATE NULL,
                ends_at DATE NULL,
                public_enabled TINYINT(1) NOT NULL DEFAULT 0,
                public_access_enabled TINYINT(1) NOT NULL DEFAULT 0,
                playlist_required TINYINT(1) NOT NULL DEFAULT 1,
                certificate_enabled TINYINT(1) NOT NULL DEFAULT 0,
                certificate_title VARCHAR(180) NULL,
                certificate_text TEXT NULL,
                certificate_font_family VARCHAR(80) NULL,
                certificate_background VARCHAR(255) NULL,
                certificate_min_frequency TINYINT UNSIGNED NOT NULL DEFAULT 0,
                certificate_show_recipient TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_nature TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_modality TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_period TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_approval TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_institution TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_meta TINYINT(1) NOT NULL DEFAULT 1,
                certificate_show_legal TINYINT(1) NOT NULL DEFAULT 1,
                certificate_course_nature VARCHAR(180) NULL,
                certificate_modality VARCHAR(80) NULL,
                certificate_approval_criteria VARCHAR(255) NULL,
                certificate_legal_text TEXT NULL,
                certificate_institution_name VARCHAR(180) NULL,
                certificate_institution_city VARCHAR(120) NULL,
                certificate_institution_cnpj VARCHAR(32) NULL,
                certificate_institution_site VARCHAR(180) NULL,
                certificate_objectives TEXT NULL,
                certificate_competencies TEXT NULL,
                certificate_responsible_name VARCHAR(180) NULL,
                certificate_responsible_credential VARCHAR(180) NULL,
                certificate_program_enabled TINYINT(1) NOT NULL DEFAULT 1,
                certificate_program_background VARCHAR(255) NULL,
                certificate_program_extra TEXT NULL,
                certificate_program_columns TINYINT UNSIGNED NOT NULL DEFAULT 2,
                teacher_user_id BIGINT UNSIGNED NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_courses_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_certificate_institution FOREIGN KEY (certificate_institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_certificate_category FOREIGN KEY (certificate_category_id) REFERENCES certificate_categories(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_certificate_template FOREIGN KEY (certificate_template_id) REFERENCES certificate_templates(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_courses_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_modules (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                summary TEXT NULL,
                required TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_modules_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        self::ensureColumn('education_modules', 'required', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER summary');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lessons (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                module_id BIGINT UNSIGNED NULL,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                description_position VARCHAR(20) NOT NULL DEFAULT "after_media",
                video_url VARCHAR(255) NULL,
                image_url VARCHAR(255) NULL,
                public_access VARCHAR(20) NOT NULL DEFAULT "private",
                locked TINYINT(1) NOT NULL DEFAULT 0,
                available_at DATETIME NULL,
                attendance_mode VARCHAR(20) NOT NULL DEFAULT "video",
                sort_order INT NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_lessons_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_lessons_module FOREIGN KEY (module_id) REFERENCES education_modules(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        self::ensureColumn('education_lessons', 'module_id', 'BIGINT UNSIGNED NULL AFTER course_id');
        self::ensureColumn('education_lessons', 'description', 'TEXT NULL AFTER title');
        self::ensureColumn('education_lessons', 'description_position', 'VARCHAR(20) NOT NULL DEFAULT "after_media" AFTER description');
        self::ensureColumn('education_lessons', 'video_url', 'VARCHAR(255) NULL AFTER description');
        self::ensureColumn('education_lessons', 'image_url', 'VARCHAR(255) NULL AFTER video_url');
        self::ensureColumn('education_lessons', 'public_access', 'VARCHAR(20) NOT NULL DEFAULT "private" AFTER image_url');
        self::ensureColumn('education_lessons', 'locked', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER image_url');
        self::ensureColumn('education_lessons', 'available_at', 'DATETIME NULL AFTER locked');
        self::ensureColumn('education_lessons', 'attendance_mode', 'VARCHAR(20) NOT NULL DEFAULT "video" AFTER available_at');
        self::ensureColumn('education_lessons', 'sort_order', 'INT NOT NULL DEFAULT 0 AFTER attendance_mode');
        self::ensureColumn('education_lessons', 'active', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order');
        self::ensureColumn('education_lessons', 'created_at', 'TIMESTAMP NULL AFTER active');
        self::ensureColumn('education_lessons', 'updated_at', 'TIMESTAMP NULL AFTER created_at');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lesson_blocks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lesson_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(40) NOT NULL DEFAULT "text",
                title VARCHAR(180) NULL,
                content LONGTEXT NULL,
                media_url VARCHAR(255) NULL,
                file_path VARCHAR(255) NULL,
                settings_json LONGTEXT NULL,
                public_access VARCHAR(20) NOT NULL DEFAULT "inherit",
                required TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_blocks_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        self::ensureColumn('education_lesson_blocks', 'settings_json', 'LONGTEXT NULL AFTER file_path');
        self::ensureColumn('education_lesson_blocks', 'public_access', 'VARCHAR(20) NOT NULL DEFAULT "inherit" AFTER settings_json');
        self::ensureColumn('education_lesson_blocks', 'required', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER file_path');
        self::ensureColumn('education_lesson_blocks', 'active', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_enrollments (
                course_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT "approved",
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (course_id, user_id),
                CONSTRAINT fk_education_enrollments_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        $enrollmentColumns = $db->query('SHOW COLUMNS FROM education_enrollments')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('status', $enrollmentColumns, true)) {
            $db->exec('ALTER TABLE education_enrollments ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT "approved" AFTER user_id');
        }
        if (!in_array('updated_at', $enrollmentColumns, true)) {
            $db->exec('ALTER TABLE education_enrollments ADD COLUMN updated_at TIMESTAMP NULL AFTER created_at');
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lesson_progress (
                lesson_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                completed_at DATETIME NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (lesson_id, user_id),
                CONSTRAINT fk_education_progress_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lesson_watches (
                lesson_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                completed_at DATETIME NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (lesson_id, user_id),
                CONSTRAINT fk_education_watches_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_watches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_lesson_block_watches (
                block_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                completed_at DATETIME NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (block_id, user_id),
                CONSTRAINT fk_education_block_watches_block FOREIGN KEY (block_id) REFERENCES education_lesson_blocks(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_block_watches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_attendance (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                attendance_date DATE NOT NULL,
                status ENUM("present","absent","justified") NOT NULL DEFAULT "present",
                notes VARCHAR(255) NULL,
                recorded_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_education_attendance_course_user_date_lesson (course_id, user_id, attendance_date, lesson_id),
                INDEX idx_education_attendance_course_date (course_id, attendance_date),
                INDEX idx_education_attendance_lesson (lesson_id),
                CONSTRAINT fk_education_attendance_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_attendance_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );
        $attendanceColumns = $db->query('SHOW COLUMNS FROM education_attendance')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('lesson_id', $attendanceColumns, true)) {
            $db->exec('ALTER TABLE education_attendance ADD COLUMN lesson_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER user_id');
        }
        try {
            $db->exec('ALTER TABLE education_attendance DROP INDEX uq_education_attendance_course_user_date');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE education_attendance ADD UNIQUE KEY uq_education_attendance_course_user_date_lesson (course_id, user_id, attendance_date, lesson_id)');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE education_attendance ADD INDEX idx_education_attendance_lesson (lesson_id)');
        } catch (\Throwable) {
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_forum_topics (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NULL,
                lesson_id BIGINT UNSIGNED NULL,
                central_topic_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                body TEXT NOT NULL,
                status ENUM("open","closed","hidden") NOT NULL DEFAULT "open",
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_education_forum_topics_course (course_id),
                INDEX idx_education_forum_topics_lesson (lesson_id),
                CONSTRAINT fk_education_topics_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_topics_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_topics_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $topicColumns = $db->query('SHOW COLUMNS FROM education_forum_topics')->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array('lesson_id', $topicColumns, true)) {
            $db->exec('ALTER TABLE education_forum_topics ADD COLUMN lesson_id BIGINT UNSIGNED NULL AFTER course_id');
            $db->exec('ALTER TABLE education_forum_topics ADD INDEX idx_education_forum_topics_lesson (lesson_id)');
        }
        if (!in_array('central_topic_id', $topicColumns, true)) {
            $db->exec('ALTER TABLE education_forum_topics ADD COLUMN central_topic_id BIGINT UNSIGNED NULL AFTER lesson_id');
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_forum_replies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id BIGINT UNSIGNED NOT NULL,
                parent_reply_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_education_forum_replies_parent (parent_reply_id),
                CONSTRAINT fk_education_replies_topic FOREIGN KEY (topic_id) REFERENCES education_forum_topics(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_replies_parent FOREIGN KEY (parent_reply_id) REFERENCES education_forum_replies(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        self::ensureColumn('education_forum_replies', 'parent_reply_id', 'BIGINT UNSIGNED NULL AFTER topic_id');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_batches (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                institution_id BIGINT UNSIGNED NULL,
                course_id BIGINT UNSIGNED NULL,
                template_id BIGINT UNSIGNED NULL,
                title VARCHAR(180) NOT NULL,
                source_filename VARCHAR(190) NULL,
                total_rows INT UNSIGNED NOT NULL DEFAULT 0,
                issued_count INT UNSIGNED NOT NULL DEFAULT 0,
                failed_count INT UNSIGNED NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL DEFAULT "draft",
                requested_by BIGINT UNSIGNED NULL,
                approved_by BIGINT UNSIGNED NULL,
                approved_at DATETIME NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_certificate_batches_status (status),
                CONSTRAINT fk_certificate_batches_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_batches_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_batches_template FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_batches_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_batches_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_certificates (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                person_id BIGINT UNSIGNED NULL,
                verification_code VARCHAR(48) NOT NULL,
                validation_hash CHAR(64) NULL,
                status VARCHAR(30) NOT NULL DEFAULT "issued",
                batch_id BIGINT UNSIGNED NULL,
                student_name VARCHAR(180) NULL,
                requested_student_name VARCHAR(180) NULL,
                name_change_status VARCHAR(20) NULL,
                name_change_requested_at DATETIME NULL,
                name_change_reviewed_by BIGINT UNSIGNED NULL,
                name_change_reviewed_at DATETIME NULL,
                authorized_by BIGINT UNSIGNED NULL,
                authorized_at DATETIME NULL,
                issued_by BIGINT UNSIGNED NULL,
                revoked_by BIGINT UNSIGNED NULL,
                revoked_at DATETIME NULL,
                revoked_reason TEXT NULL,
                pdf_path VARCHAR(255) NULL,
                sent_at DATETIME NULL,
                verified_count INT UNSIGNED NOT NULL DEFAULT 0,
                last_verified_at DATETIME NULL,
                issued_at DATETIME NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_education_certificate_course_user (course_id, user_id),
                UNIQUE KEY uq_education_certificate_course_person (course_id, person_id),
                UNIQUE KEY uq_education_certificate_code (verification_code),
                INDEX idx_education_certificate_status (status),
                INDEX idx_education_certificate_hash (validation_hash),
                CONSTRAINT fk_education_certificate_batch FOREIGN KEY (batch_id) REFERENCES certificate_batches(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_certificate_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_certificate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_certificate_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_certificate_authorizer FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_certificate_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_certificate_revoker FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_education_certificate_reviewer FOREIGN KEY (name_change_reviewed_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );
        self::ensureColumn('education_certificates', 'validation_hash', 'CHAR(64) NULL AFTER verification_code');
        self::ensureColumn('education_certificates', 'status', 'VARCHAR(30) NOT NULL DEFAULT "issued" AFTER validation_hash');
        self::ensureColumn('education_certificates', 'batch_id', 'BIGINT UNSIGNED NULL AFTER status');
        self::ensureColumn('education_certificates', 'person_id', 'BIGINT UNSIGNED NULL AFTER user_id');
        self::ensureColumn('education_certificates', 'student_name', 'VARCHAR(180) NULL AFTER verification_code');
        self::ensureColumn('education_certificates', 'requested_student_name', 'VARCHAR(180) NULL AFTER student_name');
        self::ensureColumn('education_certificates', 'name_change_status', 'VARCHAR(20) NULL AFTER requested_student_name');
        self::ensureColumn('education_certificates', 'name_change_requested_at', 'DATETIME NULL AFTER name_change_status');
        self::ensureColumn('education_certificates', 'name_change_reviewed_by', 'BIGINT UNSIGNED NULL AFTER name_change_requested_at');
        self::ensureColumn('education_certificates', 'name_change_reviewed_at', 'DATETIME NULL AFTER name_change_reviewed_by');
        self::ensureColumn('education_certificates', 'authorized_by', 'BIGINT UNSIGNED NULL AFTER name_change_reviewed_at');
        self::ensureColumn('education_certificates', 'authorized_at', 'DATETIME NULL AFTER authorized_by');
        self::ensureColumn('education_certificates', 'issued_by', 'BIGINT UNSIGNED NULL AFTER authorized_at');
        self::ensureColumn('education_certificates', 'revoked_by', 'BIGINT UNSIGNED NULL AFTER issued_by');
        self::ensureColumn('education_certificates', 'revoked_at', 'DATETIME NULL AFTER revoked_by');
        self::ensureColumn('education_certificates', 'revoked_reason', 'TEXT NULL AFTER revoked_at');
        self::ensureColumn('education_certificates', 'pdf_path', 'VARCHAR(255) NULL AFTER revoked_reason');
        self::ensureColumn('education_certificates', 'sent_at', 'DATETIME NULL AFTER pdf_path');
        self::ensureColumn('education_certificates', 'verified_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER sent_at');
        self::ensureColumn('education_certificates', 'last_verified_at', 'DATETIME NULL AFTER verified_count');
        try {
            $db->exec('ALTER TABLE education_certificates MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $exception) {
            // Existing installations may already have the nullable definition.
        }

        $db->exec(
            'CREATE TABLE IF NOT EXISTS certificate_audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                certificate_id BIGINT UNSIGNED NULL,
                institution_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NULL,
                action VARCHAR(80) NOT NULL,
                old_values_json LONGTEXT NULL,
                new_values_json LONGTEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP NULL,
                INDEX idx_certificate_audit_certificate (certificate_id),
                INDEX idx_certificate_audit_action (action),
                CONSTRAINT fk_certificate_audit_certificate FOREIGN KEY (certificate_id) REFERENCES education_certificates(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_audit_institution FOREIGN KEY (institution_id) REFERENCES certificate_institutions(id) ON DELETE SET NULL,
                CONSTRAINT fk_certificate_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        self::ensureColumn('education_courses', 'certificate_institution_id', 'BIGINT UNSIGNED NULL AFTER cover_image');
        self::ensureColumn('education_courses', 'certificate_category_id', 'BIGINT UNSIGNED NULL AFTER certificate_institution_id');
        self::ensureColumn('education_courses', 'certificate_template_id', 'BIGINT UNSIGNED NULL AFTER certificate_category_id');
        self::ensureColumn('education_courses', 'certificate_activity_type', 'VARCHAR(40) NOT NULL DEFAULT "curso_livre" AFTER certificate_template_id');
        self::ensureColumn('education_courses', 'workload_hours', 'DECIMAL(6,2) NULL AFTER certificate_activity_type');
        self::ensureColumn('education_courses', 'starts_at', 'DATE NULL AFTER workload_hours');
        self::ensureColumn('education_courses', 'ends_at', 'DATE NULL AFTER starts_at');
        self::ensureColumn('education_courses', 'public_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER ends_at');
        self::ensureColumn('education_courses', 'public_access_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER public_enabled');
        self::ensureColumn('education_courses', 'playlist_required', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER public_access_enabled');
        self::ensureColumn('education_courses', 'certificate_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER playlist_required');
        self::ensureColumn('education_courses', 'certificate_title', 'VARCHAR(180) NULL AFTER certificate_enabled');
        self::ensureColumn('education_courses', 'certificate_text', 'TEXT NULL AFTER certificate_title');
        self::ensureColumn('education_courses', 'certificate_font_family', 'VARCHAR(80) NULL AFTER certificate_text');
        self::ensureColumn('education_courses', 'certificate_background', 'VARCHAR(255) NULL AFTER certificate_font_family');
        self::ensureColumn('education_courses', 'certificate_min_frequency', 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER certificate_background');
        self::ensureColumn('education_courses', 'certificate_show_recipient', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_min_frequency');
        self::ensureColumn('education_courses', 'certificate_show_nature', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_recipient');
        self::ensureColumn('education_courses', 'certificate_show_modality', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_nature');
        self::ensureColumn('education_courses', 'certificate_show_period', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_modality');
        self::ensureColumn('education_courses', 'certificate_show_approval', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_period');
        self::ensureColumn('education_courses', 'certificate_show_institution', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_approval');
        self::ensureColumn('education_courses', 'certificate_show_meta', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_institution');
        self::ensureColumn('education_courses', 'certificate_show_legal', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_show_meta');
        self::ensureColumn('education_courses', 'certificate_course_nature', 'VARCHAR(180) NULL AFTER certificate_show_legal');
        self::ensureColumn('education_courses', 'certificate_modality', 'VARCHAR(80) NULL AFTER certificate_course_nature');
        self::ensureColumn('education_courses', 'certificate_approval_criteria', 'VARCHAR(255) NULL AFTER certificate_modality');
        self::ensureColumn('education_courses', 'certificate_legal_text', 'TEXT NULL AFTER certificate_approval_criteria');
        self::ensureColumn('education_courses', 'certificate_institution_name', 'VARCHAR(180) NULL AFTER certificate_legal_text');
        self::ensureColumn('education_courses', 'certificate_institution_city', 'VARCHAR(120) NULL AFTER certificate_institution_name');
        self::ensureColumn('education_courses', 'certificate_institution_cnpj', 'VARCHAR(32) NULL AFTER certificate_institution_city');
        self::ensureColumn('education_courses', 'certificate_institution_site', 'VARCHAR(180) NULL AFTER certificate_institution_cnpj');
        self::ensureColumn('education_courses', 'certificate_objectives', 'TEXT NULL AFTER certificate_institution_site');
        self::ensureColumn('education_courses', 'certificate_competencies', 'TEXT NULL AFTER certificate_objectives');
        self::ensureColumn('education_courses', 'certificate_responsible_name', 'VARCHAR(180) NULL AFTER certificate_competencies');
        self::ensureColumn('education_courses', 'certificate_responsible_credential', 'VARCHAR(180) NULL AFTER certificate_responsible_name');
        self::ensureColumn('education_courses', 'certificate_program_enabled', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER certificate_responsible_credential');
        self::ensureColumn('education_courses', 'certificate_program_background', 'VARCHAR(255) NULL AFTER certificate_program_enabled');
        self::ensureColumn('education_courses', 'certificate_program_extra', 'TEXT NULL AFTER certificate_program_background');
        self::ensureColumn('education_courses', 'certificate_program_columns', 'TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER certificate_program_extra');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_forms (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                course_id BIGINT UNSIGNED NOT NULL,
                lesson_id BIGINT UNSIGNED NULL,
                created_by BIGINT UNSIGNED NOT NULL,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_education_forms_course (course_id),
                INDEX idx_education_forms_lesson (lesson_id),
                CONSTRAINT fk_education_forms_course FOREIGN KEY (course_id) REFERENCES education_courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_forms_lesson FOREIGN KEY (lesson_id) REFERENCES education_lessons(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_forms_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_form_questions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_id BIGINT UNSIGNED NOT NULL,
                question TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                required TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT fk_education_form_questions_form FOREIGN KEY (form_id) REFERENCES education_forms(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_form_responses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                form_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_education_form_response_user (form_id, user_id),
                CONSTRAINT fk_education_form_responses_form FOREIGN KEY (form_id) REFERENCES education_forms(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_form_responses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_form_answers (
                response_id BIGINT UNSIGNED NOT NULL,
                question_id BIGINT UNSIGNED NOT NULL,
                answer TEXT NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (response_id, question_id),
                CONSTRAINT fk_education_form_answers_response FOREIGN KEY (response_id) REFERENCES education_form_responses(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_form_answers_question FOREIGN KEY (question_id) REFERENCES education_form_questions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS education_assignment_submissions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                block_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                text_answer TEXT NULL,
                file_path VARCHAR(255) NULL,
                original_name VARCHAR(190) NULL,
                size_bytes BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_education_assignment_user (block_id, user_id),
                CONSTRAINT fk_education_assignment_block FOREIGN KEY (block_id) REFERENCES education_lesson_blocks(id) ON DELETE CASCADE,
                CONSTRAINT fk_education_assignment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        foreach (['education_form_responses', 'education_assignment_submissions'] as $table) {
            self::ensureColumn($table, 'correction_status', "VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER updated_at");
            self::ensureColumn($table, 'grade', 'VARCHAR(40) NULL AFTER correction_status');
            self::ensureColumn($table, 'feedback', 'TEXT NULL AFTER grade');
            self::ensureColumn($table, 'corrected_by', 'BIGINT UNSIGNED NULL AFTER feedback');
            self::ensureColumn($table, 'corrected_at', 'DATETIME NULL AFTER corrected_by');
        }
    }

    public static function coursesForManagement(?int $teacherUserId = null): array
    {
        self::ensureSchema();

        $where = 'education_courses.active = 1 AND education_courses.certificate_activity_type <> "reconhecimento"';
        $params = [];
        if ($teacherUserId) {
            $where .= ' AND education_courses.teacher_user_id = :teacher_user_id';
            $params['teacher_user_id'] = $teacherUserId;
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*,
                    teacher.name AS teacher_name,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count,
                    COUNT(DISTINCT CASE WHEN education_enrollments.status = "approved" THEN education_enrollments.user_id END) AS student_count,
                    COUNT(DISTINCT CASE WHEN education_enrollments.status = "pending" THEN education_enrollments.user_id END) AS pending_student_count
             FROM education_courses
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             LEFT JOIN education_lessons ON education_lessons.course_id = education_courses.id AND education_lessons.active = 1
             LEFT JOIN education_enrollments ON education_enrollments.course_id = education_courses.id
             WHERE ' . $where . '
             GROUP BY education_courses.id
             ORDER BY education_courses.created_at DESC, education_courses.id DESC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function coursesForUser(int $userId, bool $canManage = false): array
    {
        self::ensureSchema();

        if ($canManage) {
            return self::coursesForManagement();
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*,
                    teacher.name AS teacher_name,
                    education_enrollments.status AS enrollment_status,
                    COUNT(DISTINCT CASE WHEN COALESCE(education_modules.required, 1) = 1 THEN education_lessons.id END) AS lesson_count,
                    COUNT(DISTINCT CASE WHEN education_enrollments.status = "approved" AND COALESCE(education_modules.required, 1) = 1 THEN completed.lesson_id END) AS completed_count
             FROM education_courses
             INNER JOIN education_enrollments ON education_enrollments.course_id = education_courses.id
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             LEFT JOIN education_lessons ON education_lessons.course_id = education_courses.id AND education_lessons.active = 1
             LEFT JOIN education_modules ON education_modules.id = education_lessons.module_id
             LEFT JOIN education_lesson_progress completed
                ON completed.lesson_id = education_lessons.id
               AND completed.user_id = :progress_user_id
               AND completed.completed_at IS NOT NULL
             WHERE education_courses.active = 1
               AND education_courses.certificate_activity_type <> "reconhecimento"
               AND education_enrollments.user_id = :enrolled_user_id
             GROUP BY education_courses.id
             ORDER BY education_courses.created_at DESC, education_courses.id DESC'
        );
        $stmt->execute([
            'progress_user_id' => $userId,
            'enrolled_user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public static function publicCourses(int $limit = 6): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*,
                    teacher.name AS teacher_name,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count
             FROM education_courses
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             LEFT JOIN education_lessons ON education_lessons.course_id = education_courses.id AND education_lessons.active = 1
             WHERE education_courses.active = 1
               AND education_courses.public_enabled = 1
             GROUP BY education_courses.id
             ORDER BY education_courses.updated_at DESC, education_courses.created_at DESC, education_courses.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', max(1, min(12, $limit)), \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function studentResponsesForDashboard(int $userId): array
    {
        self::ensureSchema();

        $forms = Database::connection()->prepare(
            'SELECT education_form_responses.id,
                    education_form_responses.updated_at,
                    education_form_responses.correction_status,
                    education_form_responses.grade,
                    education_form_responses.feedback,
                    education_forms.title AS item_title,
                    education_forms.course_id,
                    education_forms.lesson_id,
                    education_courses.title AS course_title,
                    education_lessons.title AS lesson_title
             FROM education_form_responses
             INNER JOIN education_forms ON education_forms.id = education_form_responses.form_id
             INNER JOIN education_courses ON education_courses.id = education_forms.course_id
             LEFT JOIN education_lessons ON education_lessons.id = education_forms.lesson_id
             WHERE education_form_responses.user_id = :user_id
               AND education_forms.active = 1
               AND education_courses.active = 1
             ORDER BY education_form_responses.updated_at DESC, education_form_responses.id DESC
             LIMIT 8'
        );
        $forms->execute(['user_id' => $userId]);

        $assignments = Database::connection()->prepare(
            'SELECT education_assignment_submissions.id,
                    education_assignment_submissions.updated_at,
                    education_assignment_submissions.correction_status,
                    education_assignment_submissions.grade,
                    education_assignment_submissions.feedback,
                    education_lesson_blocks.title AS item_title,
                    education_lesson_blocks.lesson_id,
                    education_lessons.course_id,
                    education_courses.title AS course_title,
                    education_lessons.title AS lesson_title
             FROM education_assignment_submissions
             INNER JOIN education_lesson_blocks ON education_lesson_blocks.id = education_assignment_submissions.block_id
             INNER JOIN education_lessons ON education_lessons.id = education_lesson_blocks.lesson_id
             INNER JOIN education_courses ON education_courses.id = education_lessons.course_id
             WHERE education_assignment_submissions.user_id = :user_id
               AND education_lesson_blocks.active = 1
               AND education_lessons.active = 1
               AND education_courses.active = 1
             ORDER BY education_assignment_submissions.updated_at DESC, education_assignment_submissions.id DESC
             LIMIT 8'
        );
        $assignments->execute(['user_id' => $userId]);

        return [
            'forms' => $forms->fetchAll(),
            'assignments' => $assignments->fetchAll(),
        ];
    }

    public static function findCourse(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_courses.*,
                    teacher.name AS teacher_name,
                    certificate_institutions.name AS certificate_institution_official_name,
                    certificate_institutions.cnpj AS certificate_institution_official_cnpj,
                    certificate_institutions.city AS certificate_institution_official_city,
                    certificate_institutions.state AS certificate_institution_official_state,
                    certificate_institutions.site AS certificate_institution_official_site
             FROM education_courses
             LEFT JOIN users teacher ON teacher.id = education_courses.teacher_user_id
             LEFT JOIN certificate_institutions ON certificate_institutions.id = education_courses.certificate_institution_id
             WHERE education_courses.id = :id AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function modulesForCourse(int $courseId, bool $includeHidden = false): array
    {
        self::ensureSchema();

        $where = 'education_modules.course_id = :course_id';
        if (!$includeHidden) {
            $where .= ' AND education_modules.active = 1';
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_modules.*,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count
             FROM education_modules
             LEFT JOIN education_lessons ON education_lessons.module_id = education_modules.id AND education_lessons.active = 1
             WHERE ' . $where . '
             GROUP BY education_modules.id
             ORDER BY education_modules.active DESC, education_modules.sort_order ASC, education_modules.id ASC'
        );
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    public static function createModule(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_modules (course_id, title, summary, required, sort_order, active, created_at, updated_at)
             VALUES (:course_id, :title, :summary, :required, :sort_order, 1, NOW(), NOW())'
        );
        $stmt->execute(self::modulePayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateModule(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::modulePayload($data);
        $payload['id'] = $id;

        Database::connection()->prepare(
            'UPDATE education_modules
             SET course_id = :course_id,
                 title = :title,
                 summary = :summary,
                 required = :required,
                 sort_order = :sort_order,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateModule(int $id): void
    {
        self::setModuleVisibility($id, false);
    }

    public static function deleteModule(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('DELETE FROM education_modules WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function setModuleVisibility(int $id, bool $active): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_modules SET active = :active, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public static function userCanAccessCourse(int $courseId, int $userId, bool $canManage = false, bool $includePending = false): bool
    {
        if ($canManage) {
            return self::findCourse($courseId) !== null;
        }

        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT 1
             FROM education_enrollments
             INNER JOIN education_courses ON education_courses.id = education_enrollments.course_id
             WHERE education_enrollments.course_id = :course_id
               AND education_enrollments.user_id = :user_id
                AND education_courses.active = 1
                AND (education_enrollments.status = "approved" OR :include_pending = 1)
              LIMIT 1'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId, 'include_pending' => $includePending ? 1 : 0]);

        return (bool) $stmt->fetchColumn();
    }

    public static function enrollmentStatus(int $courseId, int $userId): ?string
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT status FROM education_enrollments WHERE course_id = :course_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);

        return ($status = $stmt->fetchColumn()) ? (string) $status : null;
    }

    public static function lessonsForCourse(int $courseId, int $userId = 0, bool $includeHiddenModules = false): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_lessons.*,
                    progress.completed_at,
                    watches.completed_at AS video_completed_at,
                    education_modules.title AS module_title,
                    education_modules.required AS module_required,
                    education_modules.active AS module_active,
                    (
                        SELECT COUNT(*)
                        FROM education_lesson_blocks assignment_blocks
                        WHERE assignment_blocks.lesson_id = education_lessons.id
                          AND assignment_blocks.active = 1
                          AND assignment_blocks.type = "assignment"
                    ) AS assignment_count,
                    (
                        SELECT COUNT(*)
                        FROM education_lesson_blocks certificate_blocks
                        WHERE certificate_blocks.lesson_id = education_lessons.id
                          AND certificate_blocks.active = 1
                          AND certificate_blocks.type = "certificate"
                    ) AS certificate_count
             FROM education_lessons
             LEFT JOIN education_modules
                ON education_modules.id = education_lessons.module_id
               AND (education_modules.active = 1 OR :include_hidden_modules_join = 1)
             LEFT JOIN education_lesson_progress progress
                ON progress.lesson_id = education_lessons.id
               AND progress.user_id = :user_id
             LEFT JOIN education_lesson_watches watches
                ON watches.lesson_id = education_lessons.id
               AND watches.user_id = :watch_user_id
             WHERE education_lessons.course_id = :course_id
               AND education_lessons.active = 1
               AND (education_lessons.module_id IS NULL OR education_modules.id IS NOT NULL OR :include_hidden_modules_where = 1)
             ORDER BY COALESCE(education_modules.sort_order, 0) ASC,
                      education_lessons.sort_order ASC,
                      education_lessons.id ASC'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
            'watch_user_id' => $userId,
            'include_hidden_modules_join' => $includeHiddenModules ? 1 : 0,
            'include_hidden_modules_where' => $includeHiddenModules ? 1 : 0,
        ]);

        return $stmt->fetchAll();
    }

    public static function lessonsWithSequenceAccess(array $lessons, bool $canManage = false, bool $playlistRequired = true): array
    {
        $previousCompleted = true;

        foreach ($lessons as $index => $lesson) {
            $lessonRequired = (int) ($lesson['module_required'] ?? 1) === 1;
            $lockedBySequence = $playlistRequired && $lessonRequired && !$canManage && !$previousCompleted;
            $lockedBySchedule = !$canManage && !self::lessonIsAvailable($lesson);
            $lessons[$index]['sequence_locked'] = $lockedBySequence ? 1 : 0;
            $lessons[$index]['schedule_locked'] = $lockedBySchedule ? 1 : 0;
            $lessons[$index]['can_watch'] = !$lockedBySequence && !$lockedBySchedule && empty($lesson['locked']);
            if ($lessonRequired) {
                $previousCompleted = !empty($lesson['completed_at']);
            }
        }

        return $lessons;
    }

    public static function userCanAccessLessonInSequence(int $lessonId, int $userId, bool $canManage = false): bool
    {
        if ($canManage) {
            return true;
        }

        $lesson = self::findLesson($lessonId);
        if (!$lesson) {
            return false;
        }
        if (!self::lessonIsAvailable($lesson)) {
            return false;
        }
        $course = self::findCourse((int) $lesson['course_id']);
        if ($course && empty($course['playlist_required'])) {
            return true;
        }

        foreach (self::lessonsForCourse((int) $lesson['course_id'], $userId) as $courseLesson) {
            if ((int) $courseLesson['id'] === $lessonId) {
                return true;
            }

            if ((int) ($courseLesson['module_required'] ?? 1) === 1 && empty($courseLesson['completed_at'])) {
                return false;
            }
        }

        return false;
    }

    public static function findLesson(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_lessons.*,
                    education_courses.title AS course_title,
                    education_modules.active AS module_active
             FROM education_lessons
             INNER JOIN education_courses ON education_courses.id = education_lessons.course_id
             LEFT JOIN education_modules ON education_modules.id = education_lessons.module_id
             WHERE education_lessons.id = :id
               AND education_lessons.active = 1
               AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function blocksForLesson(int $lessonId, bool $includeHidden = false, int $userId = 0): array
    {
        self::ensureSchema();

        $activeFilter = $includeHidden ? '' : ' AND education_lesson_blocks.active = 1';
        $stmt = Database::connection()->prepare(
            'SELECT education_lesson_blocks.*,
                    block_watches.completed_at AS block_video_completed_at
             FROM education_lesson_blocks
             LEFT JOIN education_lesson_block_watches AS block_watches
                ON block_watches.block_id = education_lesson_blocks.id
               AND block_watches.user_id = :user_id
             WHERE education_lesson_blocks.lesson_id = :lesson_id
               ' . $activeFilter . '
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['lesson_id' => $lessonId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function findModule(int $id, bool $includeHidden = false): ?array
    {
        self::ensureSchema();

        $activeFilter = $includeHidden ? '' : ' AND education_modules.active = 1';
        $stmt = Database::connection()->prepare(
            'SELECT education_modules.*, education_courses.teacher_user_id
             FROM education_modules
             INNER JOIN education_courses ON education_courses.id = education_modules.course_id
             WHERE education_modules.id = :id
               ' . $activeFilter . '
               AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function findLessonBlock(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_lesson_blocks.*,
                    education_lessons.course_id,
                    education_lessons.title AS lesson_title
             FROM education_lesson_blocks
             INNER JOIN education_lessons ON education_lessons.id = education_lesson_blocks.lesson_id
             INNER JOIN education_courses ON education_courses.id = education_lessons.course_id
             WHERE education_lesson_blocks.id = :id
               AND education_lessons.active = 1
               AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function createLessonBlock(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_lesson_blocks
                (lesson_id, type, title, content, media_url, file_path, settings_json, public_access, required, sort_order, active, created_at, updated_at)
             VALUES
                (:lesson_id, :type, :title, :content, :media_url, :file_path, :settings_json, :public_access, :required, :sort_order, :active, NOW(), NOW())'
        );
        $stmt->execute(self::blockPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateLessonBlock(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::blockPayload($data);
        $payload['id'] = $id;

        Database::connection()->prepare(
            'UPDATE education_lesson_blocks
             SET lesson_id = :lesson_id,
                 type = :type,
                 title = :title,
                 content = :content,
                 media_url = :media_url,
                 file_path = :file_path,
                 settings_json = :settings_json,
                 public_access = :public_access,
                 required = :required,
                 sort_order = :sort_order,
                 active = :active,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateLessonBlock(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_lesson_blocks SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function setLessonBlockVisibility(int $id, bool $active): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_lesson_blocks SET active = :active, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public static function deleteLessonBlock(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('DELETE FROM education_lesson_blocks WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function createCourse(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_courses
                (title, summary, cover_image, certificate_institution_id, certificate_category_id, certificate_template_id, certificate_activity_type, workload_hours, starts_at, ends_at, public_enabled, public_access_enabled, playlist_required, certificate_enabled, certificate_title, certificate_text, certificate_font_family, certificate_background, certificate_min_frequency, certificate_show_recipient, certificate_show_nature, certificate_show_modality, certificate_show_period, certificate_show_approval, certificate_show_institution, certificate_show_meta, certificate_show_legal, certificate_course_nature, certificate_modality, certificate_approval_criteria, certificate_legal_text, certificate_institution_name, certificate_institution_city, certificate_institution_cnpj, certificate_institution_site, certificate_objectives, certificate_competencies, certificate_responsible_name, certificate_responsible_credential, certificate_program_enabled, certificate_program_background, certificate_program_extra, certificate_program_columns, teacher_user_id, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:title, :summary, :cover_image, :certificate_institution_id, :certificate_category_id, :certificate_template_id, :certificate_activity_type, :workload_hours, :starts_at, :ends_at, :public_enabled, :public_access_enabled, :playlist_required, :certificate_enabled, :certificate_title, :certificate_text, :certificate_font_family, :certificate_background, :certificate_min_frequency, :certificate_show_recipient, :certificate_show_nature, :certificate_show_modality, :certificate_show_period, :certificate_show_approval, :certificate_show_institution, :certificate_show_meta, :certificate_show_legal, :certificate_course_nature, :certificate_modality, :certificate_approval_criteria, :certificate_legal_text, :certificate_institution_name, :certificate_institution_city, :certificate_institution_cnpj, :certificate_institution_site, :certificate_objectives, :certificate_competencies, :certificate_responsible_name, :certificate_responsible_credential, :certificate_program_enabled, :certificate_program_background, :certificate_program_extra, :certificate_program_columns, :teacher_user_id, 1, :created_by, :updated_by, NOW(), NOW())'
        );
        $stmt->execute(self::coursePayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateCourse(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::coursePayload($data);
        $payload['id'] = $id;
        unset($payload['created_by']);

        Database::connection()->prepare(
            'UPDATE education_courses
             SET title = :title,
                 summary = :summary,
                 cover_image = :cover_image,
                 certificate_institution_id = :certificate_institution_id,
                 certificate_category_id = :certificate_category_id,
                 certificate_template_id = :certificate_template_id,
                 certificate_activity_type = :certificate_activity_type,
                 workload_hours = :workload_hours,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 public_enabled = :public_enabled,
                 public_access_enabled = :public_access_enabled,
                 playlist_required = :playlist_required,
                 certificate_enabled = :certificate_enabled,
                 certificate_title = :certificate_title,
                 certificate_text = :certificate_text,
                 certificate_font_family = :certificate_font_family,
                 certificate_background = :certificate_background,
                 certificate_min_frequency = :certificate_min_frequency,
                 certificate_show_recipient = :certificate_show_recipient,
                 certificate_show_nature = :certificate_show_nature,
                 certificate_show_modality = :certificate_show_modality,
                 certificate_show_period = :certificate_show_period,
                 certificate_show_approval = :certificate_show_approval,
                 certificate_show_institution = :certificate_show_institution,
                 certificate_show_meta = :certificate_show_meta,
                 certificate_show_legal = :certificate_show_legal,
                 certificate_course_nature = :certificate_course_nature,
                 certificate_modality = :certificate_modality,
                 certificate_approval_criteria = :certificate_approval_criteria,
                 certificate_legal_text = :certificate_legal_text,
                 certificate_institution_name = :certificate_institution_name,
                 certificate_institution_city = :certificate_institution_city,
                 certificate_institution_cnpj = :certificate_institution_cnpj,
                 certificate_institution_site = :certificate_institution_site,
                 certificate_objectives = :certificate_objectives,
                 certificate_competencies = :certificate_competencies,
                 certificate_responsible_name = :certificate_responsible_name,
                 certificate_responsible_credential = :certificate_responsible_credential,
                 certificate_program_enabled = :certificate_program_enabled,
                 certificate_program_background = :certificate_program_background,
                 certificate_program_extra = :certificate_program_extra,
                 certificate_program_columns = :certificate_program_columns,
                 teacher_user_id = :teacher_user_id,
                 updated_by = :updated_by,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateCourse(int $id): void
    {
        self::ensureSchema();
        Database::connection()
            ->prepare('UPDATE education_courses SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function createLesson(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_lessons
                (course_id, module_id, title, description, description_position, video_url, image_url, public_access, locked, available_at, attendance_mode, sort_order, active, created_at, updated_at)
             VALUES
                (:course_id, :module_id, :title, :description, :description_position, :video_url, :image_url, :public_access, :locked, :available_at, :attendance_mode, :sort_order, 1, NOW(), NOW())'
        );
        $stmt->execute(self::lessonPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateLesson(int $id, array $data): void
    {
        self::ensureSchema();
        $payload = self::lessonPayload($data);
        $payload['id'] = $id;

        Database::connection()->prepare(
            'UPDATE education_lessons
             SET course_id = :course_id,
                 module_id = :module_id,
                 title = :title,
                 description = :description,
                 description_position = :description_position,
                 video_url = :video_url,
                 image_url = :image_url,
                 public_access = :public_access,
                 locked = :locked,
                 available_at = :available_at,
                 attendance_mode = :attendance_mode,
                 sort_order = :sort_order,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute($payload);
    }

    public static function deactivateLesson(int $id): void
    {
        self::ensureSchema();
        Database::connection()
            ->prepare('UPDATE education_lessons SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function syncEnrollments(int $courseId, array $userIds): void
    {
        self::ensureSchema();

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare('DELETE FROM education_enrollments WHERE course_id = ? AND user_id NOT IN (' . ($userIds ? implode(',', array_fill(0, count($userIds), '?')) : '0') . ')')
                ->execute($userIds ? array_merge([$courseId], $userIds) : [$courseId]);
            $stmt = $db->prepare(
                'INSERT INTO education_enrollments (course_id, user_id, status, created_at, updated_at)
                 VALUES (:course_id, :user_id, "approved", NOW(), NOW())
                 ON DUPLICATE KEY UPDATE status = "approved", updated_at = NOW()'
            );

            foreach ($userIds as $userId) {
                $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function enrollmentUserIds(int $courseId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare('SELECT user_id FROM education_enrollments WHERE course_id = :course_id AND status = "approved"');
        $stmt->execute(['course_id' => $courseId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
    }

    public static function enrolledStudentsForCourse(int $courseId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT users.id, users.name, users.email
             FROM education_enrollments
             INNER JOIN users ON users.id = education_enrollments.user_id
             WHERE education_enrollments.course_id = :course_id
               AND education_enrollments.status = "approved"
               AND users.active = 1
             ORDER BY users.name ASC'
        );
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    public static function enrollUserInCourses(int $userId, array $courseIds, string $status = 'pending'): int
    {
        self::ensureSchema();

        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        if (!$courseIds) {
            return 0;
        }

        $status = in_array($status, ['pending', 'approved'], true) ? $status : 'pending';
        $stmt = Database::connection()->prepare(
            'INSERT INTO education_enrollments (course_id, user_id, status, created_at, updated_at)
             VALUES (:course_id, :user_id, :status, NOW(), NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
        );

        $count = 0;
        foreach ($courseIds as $courseId) {
            $stmt->execute([
                'course_id' => $courseId,
                'user_id' => $userId,
                'status' => $status,
            ]);
            $count += $stmt->rowCount() > 0 ? 1 : 0;
        }

        return $count;
    }

    public static function enrollmentUsers(int $courseId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_enrollments.user_id, education_enrollments.status, users.name, users.email
             FROM education_enrollments
             INNER JOIN users ON users.id = education_enrollments.user_id
             WHERE education_enrollments.course_id = :course_id
             ORDER BY education_enrollments.status ASC, users.name ASC'
        );
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    public static function attendanceForCourseDate(int $courseId, string $date, int $lessonId = 0): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT *
             FROM education_attendance
             WHERE course_id = :course_id
               AND attendance_date = :attendance_date
               AND lesson_id = :lesson_id'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'attendance_date' => $date,
            'lesson_id' => $lessonId,
        ]);

        $records = [];
        foreach ($stmt->fetchAll() as $record) {
            $records[(int) $record['user_id']] = $record;
        }

        return $records;
    }

    public static function saveAttendance(int $courseId, string $date, array $rows, ?int $recordedBy, int $lessonId = 0): void
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_attendance
                (course_id, user_id, lesson_id, attendance_date, status, notes, recorded_by, created_at, updated_at)
             VALUES
                (:course_id, :user_id, :lesson_id, :attendance_date, :status, :notes, :recorded_by, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                notes = VALUES(notes),
                recorded_by = VALUES(recorded_by),
                updated_at = NOW()'
        );

        $allowedStatuses = ['present', 'absent', 'justified'];
        foreach ($rows as $userId => $row) {
            $status = (string) ($row['status'] ?? 'present');
            $stmt->execute([
                'course_id' => $courseId,
                'user_id' => (int) $userId,
                'lesson_id' => $lessonId,
                'attendance_date' => $date,
                'status' => in_array($status, $allowedStatuses, true) ? $status : 'present',
                'notes' => self::nullable($row['notes'] ?? null),
                'recorded_by' => $recordedBy,
            ]);

            if ($lessonId > 0) {
                self::markLesson($lessonId, (int) $userId, $status === 'present');
            }
        }
    }

    public static function attendanceReportForCourse(int $courseId, string $startDate, string $endDate): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT users.id,
                    users.name,
                    users.email,
                    COUNT(education_attendance.id) AS total_records,
                    SUM(CASE WHEN education_attendance.status = "present" THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN education_attendance.status = "absent" THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN education_attendance.status = "justified" THEN 1 ELSE 0 END) AS justified_count
             FROM education_enrollments
             INNER JOIN users ON users.id = education_enrollments.user_id
             LEFT JOIN education_attendance
                ON education_attendance.course_id = education_enrollments.course_id
               AND education_attendance.user_id = education_enrollments.user_id
               AND education_attendance.attendance_date BETWEEN :start_date AND :end_date
             WHERE education_enrollments.course_id = :course_id
               AND users.active = 1
             GROUP BY users.id
             ORDER BY users.name ASC'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $stmt->fetchAll();
    }

    public static function attendanceDatesForCourse(int $courseId, string $startDate, string $endDate): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT attendance_date,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN status = "justified" THEN 1 ELSE 0 END) AS justified_count
             FROM education_attendance
             WHERE course_id = :course_id
               AND attendance_date BETWEEN :start_date AND :end_date
             GROUP BY attendance_date
             ORDER BY attendance_date DESC'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $stmt->fetchAll();
    }

    public static function studentReportForCourse(int $courseId, string $startDate, string $endDate): array
    {
        self::ensureSchema();
        $db = Database::connection();

        $studentsStmt = $db->prepare(
            'SELECT users.id,
                    users.name,
                    users.email,
                    education_enrollments.status,
                    education_enrollments.created_at AS enrolled_at
             FROM education_enrollments
             INNER JOIN users ON users.id = education_enrollments.user_id
             WHERE education_enrollments.course_id = :course_id
               AND education_enrollments.status = "approved"
               AND users.active = 1
             ORDER BY users.name ASC'
        );
        $studentsStmt->execute(['course_id' => $courseId]);

        $students = [];
        foreach ($studentsStmt->fetchAll() as $student) {
            $students[(int) $student['id']] = array_merge($student, [
                'lesson_count' => 0,
                'completed_lessons' => 0,
                'progress_percent' => 0,
                'attendance_records' => 0,
                'present_count' => 0,
                'absent_count' => 0,
                'justified_count' => 0,
                'frequency' => 0,
                'activity_total' => 0,
                'activity_done' => 0,
                'activity_pending' => 0,
                'activity_corrected' => 0,
                'activity_pending_correction' => 0,
                'activity_redo' => 0,
                'needs_attention' => false,
                'attention_level' => 'ok',
                'progress_label' => 'Sem aulas registradas',
                'attention_reasons' => [],
                'teacher_actions' => [],
                'activities' => [],
            ]);
        }

        if (!$students) {
            return [
                'students' => [],
                'activityItems' => [],
                'summary' => [
                    'student_count' => 0,
                    'average_frequency' => 0,
                    'average_progress' => 0,
                    'activity_done_percent' => 0,
                    'students_need_attention' => 0,
                    'pending_corrections' => 0,
                    'redo_requests' => 0,
                ],
            ];
        }

        $lessonStatsStmt = $db->prepare(
            'SELECT education_enrollments.user_id,
                    COUNT(DISTINCT education_lessons.id) AS lesson_count,
                    COUNT(DISTINCT CASE WHEN progress.completed_at IS NOT NULL THEN education_lessons.id END) AS completed_lessons
             FROM education_enrollments
             LEFT JOIN education_lessons
                ON education_lessons.course_id = education_enrollments.course_id
               AND education_lessons.active = 1
             LEFT JOIN education_modules
                ON education_modules.id = education_lessons.module_id
             LEFT JOIN education_lesson_progress progress
                ON progress.lesson_id = education_lessons.id
               AND progress.user_id = education_enrollments.user_id
             WHERE education_enrollments.course_id = :course_id
               AND education_enrollments.status = "approved"
               AND COALESCE(education_modules.required, 1) = 1
             GROUP BY education_enrollments.user_id'
        );
        $lessonStatsStmt->execute(['course_id' => $courseId]);
        foreach ($lessonStatsStmt->fetchAll() as $row) {
            $userId = (int) $row['user_id'];
            if (!isset($students[$userId])) {
                continue;
            }

            $lessonCount = (int) ($row['lesson_count'] ?? 0);
            $completed = (int) ($row['completed_lessons'] ?? 0);
            $students[$userId]['lesson_count'] = $lessonCount;
            $students[$userId]['completed_lessons'] = $completed;
            $students[$userId]['progress_percent'] = $lessonCount > 0 ? (int) round(($completed / $lessonCount) * 100) : 0;
        }

        $attendanceStmt = $db->prepare(
            'SELECT user_id,
                    COUNT(id) AS attendance_records,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN status = "justified" THEN 1 ELSE 0 END) AS justified_count
             FROM education_attendance
             WHERE course_id = :course_id
               AND attendance_date BETWEEN :start_date AND :end_date
             GROUP BY user_id'
        );
        $attendanceStmt->execute([
            'course_id' => $courseId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        foreach ($attendanceStmt->fetchAll() as $row) {
            $userId = (int) $row['user_id'];
            if (!isset($students[$userId])) {
                continue;
            }

            $present = (int) ($row['present_count'] ?? 0);
            $absent = (int) ($row['absent_count'] ?? 0);
            $justified = (int) ($row['justified_count'] ?? 0);
            $records = (int) ($row['attendance_records'] ?? 0);
            $students[$userId]['attendance_records'] = $records;
            $students[$userId]['present_count'] = $present;
            $students[$userId]['absent_count'] = $absent;
            $students[$userId]['justified_count'] = $justified;
            $students[$userId]['frequency'] = $records > 0 ? (int) round(($present / $records) * 100) : 0;
        }

        $activityItems = [];
        $formsStmt = $db->prepare(
            'SELECT education_forms.id,
                    education_forms.title,
                    education_forms.lesson_id,
                    education_lessons.title AS lesson_title
             FROM education_forms
             LEFT JOIN education_lessons ON education_lessons.id = education_forms.lesson_id
             WHERE education_forms.course_id = :course_id
               AND education_forms.active = 1
             ORDER BY COALESCE(education_lessons.sort_order, 0) ASC, education_forms.created_at ASC, education_forms.id ASC'
        );
        $formsStmt->execute(['course_id' => $courseId]);
        foreach ($formsStmt->fetchAll() as $form) {
            $activityItems['form:' . (int) $form['id']] = [
                'key' => 'form:' . (int) $form['id'],
                'type' => 'form',
                'title' => $form['title'],
                'lesson_title' => $form['lesson_title'] ?? null,
            ];
        }

        $assignmentsStmt = $db->prepare(
            'SELECT education_lesson_blocks.id,
                    education_lesson_blocks.title,
                    education_lessons.title AS lesson_title
             FROM education_lesson_blocks
             INNER JOIN education_lessons ON education_lessons.id = education_lesson_blocks.lesson_id
             WHERE education_lessons.course_id = :course_id
               AND education_lessons.active = 1
               AND education_lesson_blocks.active = 1
               AND education_lesson_blocks.type = "assignment"
             ORDER BY education_lessons.sort_order ASC, education_lesson_blocks.sort_order ASC, education_lesson_blocks.id ASC'
        );
        $assignmentsStmt->execute(['course_id' => $courseId]);
        foreach ($assignmentsStmt->fetchAll() as $assignment) {
            $activityItems['assignment:' . (int) $assignment['id']] = [
                'key' => 'assignment:' . (int) $assignment['id'],
                'type' => 'assignment',
                'title' => $assignment['title'] ?: 'Tarefa',
                'lesson_title' => $assignment['lesson_title'] ?? null,
            ];
        }

        foreach ($students as &$student) {
            foreach ($activityItems as $key => $item) {
                $student['activities'][$key] = array_merge($item, [
                    'done' => false,
                    'correction_status' => null,
                    'grade' => null,
                    'updated_at' => null,
                ]);
            }
            $student['activity_total'] = count($activityItems);
        }
        unset($student);

        $formResponsesStmt = $db->prepare(
            'SELECT education_form_responses.user_id,
                    education_form_responses.form_id,
                    education_form_responses.correction_status,
                    education_form_responses.grade,
                    education_form_responses.updated_at
             FROM education_form_responses
             INNER JOIN education_forms ON education_forms.id = education_form_responses.form_id
             WHERE education_forms.course_id = :course_id
               AND education_forms.active = 1'
        );
        $formResponsesStmt->execute(['course_id' => $courseId]);
        foreach ($formResponsesStmt->fetchAll() as $response) {
            $userId = (int) $response['user_id'];
            $key = 'form:' . (int) $response['form_id'];
            if (!isset($students[$userId]['activities'][$key])) {
                continue;
            }

            $students[$userId]['activities'][$key]['done'] = true;
            $students[$userId]['activities'][$key]['correction_status'] = $response['correction_status'] ?? null;
            $students[$userId]['activities'][$key]['grade'] = $response['grade'] ?? null;
            $students[$userId]['activities'][$key]['updated_at'] = $response['updated_at'] ?? null;
        }

        $assignmentResponsesStmt = $db->prepare(
            'SELECT education_assignment_submissions.user_id,
                    education_assignment_submissions.block_id,
                    education_assignment_submissions.correction_status,
                    education_assignment_submissions.grade,
                    education_assignment_submissions.updated_at
             FROM education_assignment_submissions
             INNER JOIN education_lesson_blocks ON education_lesson_blocks.id = education_assignment_submissions.block_id
             INNER JOIN education_lessons ON education_lessons.id = education_lesson_blocks.lesson_id
             WHERE education_lessons.course_id = :course_id
               AND education_lessons.active = 1
               AND education_lesson_blocks.active = 1
               AND education_lesson_blocks.type = "assignment"'
        );
        $assignmentResponsesStmt->execute(['course_id' => $courseId]);
        foreach ($assignmentResponsesStmt->fetchAll() as $response) {
            $userId = (int) $response['user_id'];
            $key = 'assignment:' . (int) $response['block_id'];
            if (!isset($students[$userId]['activities'][$key])) {
                continue;
            }

            $students[$userId]['activities'][$key]['done'] = true;
            $students[$userId]['activities'][$key]['correction_status'] = $response['correction_status'] ?? null;
            $students[$userId]['activities'][$key]['grade'] = $response['grade'] ?? null;
            $students[$userId]['activities'][$key]['updated_at'] = $response['updated_at'] ?? null;
        }

        $frequencySum = 0;
        $progressSum = 0;
        $activityTotal = 0;
        $activityDone = 0;

        foreach ($students as &$student) {
            foreach ($student['activities'] as $activity) {
                if (!$activity['done']) {
                    continue;
                }

                $student['activity_done']++;
                if (($activity['correction_status'] ?? '') === 'corrected') {
                    $student['activity_corrected']++;
                } elseif (($activity['correction_status'] ?? '') === 'pending') {
                    $student['activity_pending_correction']++;
                } elseif (($activity['correction_status'] ?? '') === 'redo') {
                    $student['activity_redo']++;
                }
            }

            $student['activity_pending'] = max(0, (int) $student['activity_total'] - (int) $student['activity_done']);
            $student['progress_label'] = self::studentProgressLabel((int) $student['progress_percent'], (int) $student['lesson_count']);
            $student['attention_reasons'] = self::studentAttentionReasons($student);
            $student['teacher_actions'] = self::studentTeacherActions($student);
            $student['needs_attention'] = !empty($student['attention_reasons']) || !empty($student['teacher_actions']);
            $student['attention_level'] = ((int) $student['activity_pending_correction'] > 0 || (int) $student['activity_redo'] > 0 || (int) $student['absent_count'] > 0)
                ? 'warning'
                : ($student['needs_attention'] ? 'notice' : 'ok');
            $frequencySum += (int) $student['frequency'];
            $progressSum += (int) $student['progress_percent'];
            $activityTotal += (int) $student['activity_total'];
            $activityDone += (int) $student['activity_done'];
        }
        unset($student);

        $studentCount = count($students);
        $studentsNeedAttention = 0;
        $pendingCorrections = 0;
        $redoRequests = 0;
        foreach ($students as $student) {
            if (!empty($student['needs_attention'])) {
                $studentsNeedAttention++;
            }
            $pendingCorrections += (int) ($student['activity_pending_correction'] ?? 0);
            $redoRequests += (int) ($student['activity_redo'] ?? 0);
        }

        return [
            'students' => array_values($students),
            'activityItems' => array_values($activityItems),
            'summary' => [
                'student_count' => $studentCount,
                'average_frequency' => $studentCount > 0 ? (int) round($frequencySum / $studentCount) : 0,
                'average_progress' => $studentCount > 0 ? (int) round($progressSum / $studentCount) : 0,
                'activity_done_percent' => $activityTotal > 0 ? (int) round(($activityDone / $activityTotal) * 100) : 0,
                'students_need_attention' => $studentsNeedAttention,
                'pending_corrections' => $pendingCorrections,
                'redo_requests' => $redoRequests,
            ],
        ];
    }

    private static function studentProgressLabel(int $progressPercent, int $lessonCount): string
    {
        if ($lessonCount <= 0) {
            return 'Sem aulas registradas';
        }

        if ($progressPercent >= 100) {
            return 'Curso concluído';
        }

        if ($progressPercent >= 70) {
            return 'Avançado';
        }

        if ($progressPercent >= 35) {
            return 'Em andamento';
        }

        if ($progressPercent > 0) {
            return 'Início do curso';
        }

        return 'Sem progresso';
    }

    private static function studentAttentionReasons(array $student): array
    {
        $reasons = [];

        if ((int) ($student['lesson_count'] ?? 0) > 0 && (int) ($student['progress_percent'] ?? 0) === 0) {
            $reasons[] = 'Aluno ainda não concluiu nenhuma aula.';
        } elseif ((int) ($student['lesson_count'] ?? 0) > 0 && (int) ($student['progress_percent'] ?? 0) < 35) {
            $reasons[] = 'Progresso baixo para acompanhamento.';
        }

        if ((int) ($student['attendance_records'] ?? 0) === 0) {
            $reasons[] = 'Sem chamada registrada no período filtrado.';
        } elseif ((int) ($student['frequency'] ?? 0) < 75) {
            $reasons[] = 'Frequência abaixo de 75% no período.';
        }

        if ((int) ($student['activity_pending'] ?? 0) > 0) {
            $reasons[] = (int) $student['activity_pending'] . ' atividade(s) ainda não entregue(s).';
        }

        if ((int) ($student['activity_redo'] ?? 0) > 0) {
            $reasons[] = (int) $student['activity_redo'] . ' atividade(s) marcada(s) para refazer.';
        }

        return $reasons;
    }

    private static function studentTeacherActions(array $student): array
    {
        $actions = [];

        if ((int) ($student['activity_pending_correction'] ?? 0) > 0) {
            $actions[] = 'Corrigir ' . (int) $student['activity_pending_correction'] . ' atividade(s) enviada(s).';
        }

        if ((int) ($student['activity_redo'] ?? 0) > 0) {
            $actions[] = 'Conferir devolutiva de atividade(s) para refazer.';
        }

        if ((int) ($student['absent_count'] ?? 0) > 0) {
            $actions[] = 'Verificar faltas e justificativas da chamada.';
        }

        if ((int) ($student['attendance_records'] ?? 0) === 0) {
            $actions[] = 'Registrar ou revisar chamada do período.';
        }

        return $actions;
    }

    public static function certificateStatusForCourseUser(int $courseId, int $userId): array
    {
        self::ensureSchema();
        $course = self::findCourse($courseId);
        $progress = self::courseProgressForUser($courseId, $userId);
        $attendance = self::attendanceSummaryForCourseUser($courseId, $userId);
        $minimumFrequency = (int) ($course['certificate_min_frequency'] ?? 0);
        $courseCompleted = $progress['lesson_count'] > 0 && $progress['completed_count'] >= $progress['lesson_count'];
        $frequencyReady = $minimumFrequency <= 0 || $attendance['frequency'] >= $minimumFrequency;

        return [
            'enabled' => !empty($course['certificate_enabled']),
            'lesson_count' => $progress['lesson_count'],
            'completed_count' => $progress['completed_count'],
            'course_completed' => $courseCompleted,
            'attendance_records' => $attendance['records'],
            'frequency' => $attendance['frequency'],
            'minimum_frequency' => $minimumFrequency,
            'frequency_ready' => $frequencyReady,
            'eligible' => !empty($course['certificate_enabled']) && $courseCompleted && $frequencyReady,
            'certificate' => self::certificateForCourseUser($courseId, $userId),
        ];
    }

    public static function issueCertificate(int $courseId, int $userId): array
    {
        self::ensureSchema();
        $certificate = self::certificateForCourseUser($courseId, $userId);
        if ($certificate) {
            return $certificate;
        }

        $user = User::find($userId);
        $code = self::certificateCode($courseId, $userId);
        $hash = self::certificateHash($courseId, $userId, $code);

        Database::connection()->prepare(
            'INSERT IGNORE INTO education_certificates
                (course_id, user_id, verification_code, validation_hash, status, student_name, authorized_by, authorized_at, issued_by, issued_at, created_at, updated_at)
             VALUES
                (:course_id, :user_id, :verification_code, :validation_hash, "issued", :student_name, :authorized_by, NOW(), :issued_by, NOW(), NOW(), NOW())'
        )->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
            'verification_code' => $code,
            'validation_hash' => $hash,
            'student_name' => $user['name'] ?? null,
            'authorized_by' => $userId,
            'issued_by' => $userId,
        ]);

        $certificate = self::certificateForCourseUser($courseId, $userId) ?? [];
        if ($certificate) {
            self::auditCertificate((int) $certificate['id'], null, $userId, 'issued', [], [
                'course_id' => $courseId,
                'user_id' => $userId,
                'verification_code' => $code,
            ]);
        }

        return $certificate;
    }

    public static function recognitionCertificatePeople(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query(
                'SELECT CONCAT("user:", id) AS recipient_key,
                        id,
                        "user" AS recipient_type,
                        name AS display_name,
                        email,
                        NULL AS city,
                        NULL AS state
                 FROM users
                 WHERE active = 1
                 UNION ALL
                 SELECT CONCAT("person:", id) AS recipient_key,
                        id,
                        "person" AS recipient_type,
                        full_name AS display_name,
                        email,
                        city,
                        state
                 FROM people
                 WHERE active = 1
                 ORDER BY display_name ASC'
            )
            ->fetchAll();
    }

    public static function recognitionCertificatesForManagement(): array
    {
        self::ensureSchema();

        return Database::connection()->query(
            'SELECT education_certificates.id,
                    education_certificates.verification_code,
                    education_certificates.status,
                    education_certificates.issued_at,
                    education_courses.title AS recognition_title,
                    COALESCE(NULLIF(education_certificates.student_name, ""), people.full_name, users.name) AS recipient_name,
                    CASE WHEN education_certificates.user_id IS NOT NULL THEN "Usuário" ELSE "Pessoa cadastrada" END AS recipient_kind,
                    certificate_institutions.name AS institution_name
             FROM education_certificates
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             LEFT JOIN people ON people.id = education_certificates.person_id
             LEFT JOIN users ON users.id = education_certificates.user_id
             LEFT JOIN certificate_institutions ON certificate_institutions.id = education_courses.certificate_institution_id
             WHERE education_courses.certificate_activity_type = "reconhecimento"
               AND education_courses.active = 1
               AND education_certificates.status <> "deleted"
             ORDER BY education_certificates.issued_at DESC, education_certificates.id DESC
             LIMIT 80'
        )->fetchAll();
    }

    public static function recognitionCertificateById(int $certificateId): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_certificates.*,
                    education_courses.*,
                    education_certificates.id AS certificate_id,
                    education_certificates.status AS certificate_status,
                    education_certificates.created_at AS certificate_created_at,
                    education_certificates.updated_at AS certificate_updated_at,
                    COALESCE(NULLIF(education_certificates.student_name, ""), people.full_name, users.name) AS recipient_name,
                    CASE WHEN education_certificates.user_id IS NOT NULL THEN "Usuário" ELSE "Pessoa cadastrada" END AS recipient_kind
             FROM education_certificates
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             LEFT JOIN people ON people.id = education_certificates.person_id
             LEFT JOIN users ON users.id = education_certificates.user_id
             WHERE education_certificates.id = :id
               AND education_courses.certificate_activity_type = "reconhecimento"
               AND education_courses.active = 1
               AND education_certificates.status <> "deleted"
             LIMIT 1'
        );
        $stmt->execute(['id' => $certificateId]);

        return $stmt->fetch() ?: null;
    }

    public static function issueRecognitionCertificate(array $data): array
    {
        self::ensureSchema();

        $recipientKey = trim((string) ($data['recipient_key'] ?? ''));
        if ($recipientKey === '' && !empty($data['person_id'])) {
            $recipientKey = 'person:' . (int) $data['person_id'];
        }

        [$recipientType, $recipientIdRaw] = array_pad(explode(':', $recipientKey, 2), 2, '');
        $recipientId = (int) $recipientIdRaw;
        $issuedBy = (int) ($data['issued_by'] ?? 0);

        if ($recipientType === 'user') {
            $stmt = Database::connection()->prepare(
                'SELECT id, name, email
                 FROM users
                 WHERE id = :id
                   AND active = 1
                 LIMIT 1'
            );
            $stmt->execute(['id' => $recipientId]);
            $recipient = $stmt->fetch();
            if (!$recipient) {
                throw new \InvalidArgumentException('Selecione um usuário ativo.');
            }
            $studentName = (string) ($recipient['name'] ?? '');
            $userId = $recipientId;
            $personId = null;
        } elseif ($recipientType === 'person') {
            $stmt = Database::connection()->prepare(
                'SELECT id, full_name, email, city, state
                 FROM people
                 WHERE id = :id
                   AND active = 1
                 LIMIT 1'
            );
            $stmt->execute(['id' => $recipientId]);
            $recipient = $stmt->fetch();
            if (!$recipient) {
                throw new \InvalidArgumentException('Selecione uma pessoa cadastrada.');
            }
            $studentName = (string) ($recipient['full_name'] ?? '');
            $userId = null;
            $personId = $recipientId;
        } else {
            throw new \InvalidArgumentException('Selecione um usuário ou pessoa cadastrada.');
        }

        $activityTitle = trim((string) ($data['activity_title'] ?? ''));
        if ($activityTitle === '') {
            $activityTitle = 'Reconhecimento por atuação voluntária';
        }

        $certificateTitle = trim((string) ($data['certificate_title'] ?? ''));
        if ($certificateTitle === '') {
            $certificateTitle = 'Certificado de reconhecimento';
        }

        $certificateText = trim((string) ($data['certificate_text'] ?? ''));

        $courseId = self::createCourse([
            'title' => $activityTitle,
            'summary' => 'Certificado de reconhecimento emitido para voluntário ou participante.',
            'certificate_institution_id' => $data['institution_id'] ?? null,
            'certificate_activity_type' => 'reconhecimento',
            'public_enabled' => 0,
            'certificate_enabled' => 1,
            'certificate_title' => $certificateTitle,
            'certificate_text' => $certificateText !== '' ? $certificateText : null,
            'certificate_font_family' => $data['certificate_font_family'] ?? null,
            'certificate_background' => $data['certificate_background'] ?? null,
            'certificate_min_frequency' => 0,
            'certificate_show_recipient' => array_key_exists('certificate_show_recipient', $data) ? (!empty($data['certificate_show_recipient']) ? 1 : 0) : 1,
            'certificate_show_nature' => !empty($data['certificate_show_nature']) ? 1 : 0,
            'certificate_show_modality' => !empty($data['certificate_show_modality']) ? 1 : 0,
            'certificate_show_period' => !empty($data['certificate_show_period']) ? 1 : 0,
            'certificate_show_approval' => !empty($data['certificate_show_approval']) ? 1 : 0,
            'certificate_show_institution' => !empty($data['certificate_show_institution']) ? 1 : 0,
            'certificate_show_meta' => !empty($data['certificate_show_meta']) ? 1 : 0,
            'certificate_show_legal' => !empty($data['certificate_show_legal']) ? 1 : 0,
            'certificate_course_nature' => self::nullable($data['certificate_course_nature'] ?? null),
            'certificate_modality' => self::nullable($data['certificate_modality'] ?? null),
            'certificate_approval_criteria' => self::nullable($data['certificate_approval_criteria'] ?? null),
            'certificate_legal_text' => self::nullable($data['certificate_legal_text'] ?? null),
            'certificate_institution_name' => self::nullable($data['certificate_institution_name'] ?? null),
            'certificate_institution_city' => self::nullable($data['certificate_institution_city'] ?? null),
            'certificate_institution_cnpj' => self::nullable($data['certificate_institution_cnpj'] ?? null),
            'certificate_institution_site' => self::nullable($data['certificate_institution_site'] ?? null),
            'certificate_objectives' => self::nullable($data['certificate_objectives'] ?? null),
            'certificate_competencies' => self::nullable($data['certificate_competencies'] ?? null),
            'certificate_responsible_name' => self::nullable($data['certificate_responsible_name'] ?? null),
            'certificate_responsible_credential' => self::nullable($data['certificate_responsible_credential'] ?? null),
            'certificate_program_enabled' => !empty($data['certificate_program_enabled']) ? 1 : 0,
            'certificate_program_background' => $data['certificate_program_background'] ?? null,
            'certificate_program_extra' => self::nullable($data['certificate_program_extra'] ?? null),
            'certificate_program_columns' => $data['certificate_program_columns'] ?? 2,
            'created_by' => $issuedBy ?: null,
            'updated_by' => $issuedBy ?: null,
        ]);

        $hashId = $userId ?? $personId ?? 0;
        $code = self::certificateCode($courseId, $hashId);
        $hash = self::certificateHash($courseId, $hashId, $code);
        Database::connection()->prepare(
            'INSERT INTO education_certificates
                (course_id, user_id, person_id, verification_code, validation_hash, status, student_name, authorized_by, authorized_at, issued_by, issued_at, created_at, updated_at)
             VALUES
                (:course_id, :user_id, :person_id, :verification_code, :validation_hash, "issued", :student_name, :authorized_by, NOW(), :issued_by, NOW(), NOW(), NOW())'
        )->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
            'person_id' => $personId,
            'verification_code' => $code,
            'validation_hash' => $hash,
            'student_name' => $studentName,
            'authorized_by' => $issuedBy ?: null,
            'issued_by' => $issuedBy ?: null,
        ]);

        $certificateId = (int) Database::connection()->lastInsertId();
        self::auditCertificate($certificateId, !empty($data['institution_id']) ? (int) $data['institution_id'] : null, $issuedBy ?: null, 'recognition_issued', [], [
            'course_id' => $courseId,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'verification_code' => $code,
        ]);

        return self::certificateById($certificateId) ?? [
            'id' => $certificateId,
            'course_id' => $courseId,
            'verification_code' => $code,
            'student_name' => $studentName,
        ];
    }

    public static function updateRecognitionCertificate(int $certificateId, array $data): ?array
    {
        self::ensureSchema();
        $recognition = self::recognitionCertificateById($certificateId);
        if (!$recognition) {
            return null;
        }

        $activityTitle = trim((string) ($data['activity_title'] ?? ''));
        $certificateTitle = trim((string) ($data['certificate_title'] ?? ''));

        self::updateCourse((int) $recognition['course_id'], array_merge($recognition, [
            'title' => $activityTitle !== '' ? $activityTitle : ($recognition['title'] ?? 'Reconhecimento'),
            'summary' => $recognition['summary'] ?? 'Certificado de reconhecimento emitido para voluntário ou participante.',
            'certificate_institution_id' => $data['institution_id'] ?? null,
            'certificate_activity_type' => 'reconhecimento',
            'public_enabled' => 0,
            'certificate_enabled' => 1,
            'certificate_title' => $certificateTitle !== '' ? $certificateTitle : ($recognition['certificate_title'] ?? 'Certificado de reconhecimento'),
            'certificate_text' => self::nullable($data['certificate_text'] ?? null),
            'certificate_font_family' => $data['certificate_font_family'] ?? null,
            'certificate_background' => $data['certificate_background'] ?? null,
            'certificate_min_frequency' => 0,
            'certificate_show_recipient' => array_key_exists('certificate_show_recipient', $data) ? (!empty($data['certificate_show_recipient']) ? 1 : 0) : 1,
            'certificate_show_nature' => !empty($data['certificate_show_nature']) ? 1 : 0,
            'certificate_show_modality' => !empty($data['certificate_show_modality']) ? 1 : 0,
            'certificate_show_period' => !empty($data['certificate_show_period']) ? 1 : 0,
            'certificate_show_approval' => !empty($data['certificate_show_approval']) ? 1 : 0,
            'certificate_show_institution' => !empty($data['certificate_show_institution']) ? 1 : 0,
            'certificate_show_meta' => !empty($data['certificate_show_meta']) ? 1 : 0,
            'certificate_show_legal' => !empty($data['certificate_show_legal']) ? 1 : 0,
            'certificate_course_nature' => self::nullable($data['certificate_course_nature'] ?? null),
            'certificate_modality' => self::nullable($data['certificate_modality'] ?? null),
            'certificate_approval_criteria' => self::nullable($data['certificate_approval_criteria'] ?? null),
            'certificate_legal_text' => self::nullable($data['certificate_legal_text'] ?? null),
            'certificate_institution_name' => self::nullable($data['certificate_institution_name'] ?? null),
            'certificate_institution_city' => self::nullable($data['certificate_institution_city'] ?? null),
            'certificate_institution_cnpj' => self::nullable($data['certificate_institution_cnpj'] ?? null),
            'certificate_institution_site' => self::nullable($data['certificate_institution_site'] ?? null),
            'certificate_objectives' => self::nullable($data['certificate_objectives'] ?? null),
            'certificate_competencies' => self::nullable($data['certificate_competencies'] ?? null),
            'certificate_responsible_name' => self::nullable($data['certificate_responsible_name'] ?? null),
            'certificate_responsible_credential' => self::nullable($data['certificate_responsible_credential'] ?? null),
            'certificate_program_enabled' => !empty($data['certificate_program_enabled']) ? 1 : 0,
            'certificate_program_background' => $data['certificate_program_background'] ?? null,
            'certificate_program_extra' => self::nullable($data['certificate_program_extra'] ?? null),
            'certificate_program_columns' => $data['certificate_program_columns'] ?? 2,
            'updated_by' => $data['updated_by'] ?? null,
        ]));

        self::auditCertificate($certificateId, !empty($data['institution_id']) ? (int) $data['institution_id'] : null, !empty($data['updated_by']) ? (int) $data['updated_by'] : null, 'recognition_updated', $recognition, [
            'course_id' => $recognition['course_id'],
            'title' => $activityTitle,
        ]);

        return self::certificateById($certificateId);
    }

    public static function setRecognitionCertificateStatus(int $certificateId, string $action, int $userId): bool
    {
        self::ensureSchema();
        $recognition = self::recognitionCertificateById($certificateId);
        if (!$recognition) {
            return false;
        }

        $status = match ($action) {
            'lock' => 'locked',
            'unlock' => 'issued',
            'revoke' => 'revoked',
            'delete' => 'deleted',
            default => null,
        };

        if ($status === null) {
            return false;
        }

        $db = Database::connection();
        $db->prepare(
            'UPDATE education_certificates
             SET status = :status,
                 revoked_by = CASE WHEN :status = "revoked" THEN :user_id ELSE revoked_by END,
                 revoked_at = CASE WHEN :status = "revoked" THEN NOW() ELSE revoked_at END,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $certificateId,
            'status' => $status,
            'user_id' => $userId ?: null,
        ]);

        if ($status === 'deleted') {
            $db->prepare('UPDATE education_courses SET active = 0, updated_by = :updated_by, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'id' => (int) $recognition['course_id'],
                    'updated_by' => $userId ?: null,
                ]);
        }

        self::auditCertificate($certificateId, !empty($recognition['certificate_institution_id']) ? (int) $recognition['certificate_institution_id'] : null, $userId ?: null, 'recognition_' . $action, [
            'status' => $recognition['certificate_status'] ?? $recognition['status'] ?? null,
        ], [
            'status' => $status,
        ]);

        return true;
    }

    public static function setCertificateStatus(int $certificateId, string $action, int $userId): bool
    {
        self::ensureSchema();

        $certificate = self::certificateById($certificateId);
        if (!$certificate || ($certificate['certificate_activity_type'] ?? '') === 'reconhecimento') {
            return false;
        }

        $status = match ($action) {
            'issue' => 'issued',
            'revoke' => 'revoked',
            'delete' => 'deleted',
            default => null,
        };

        if ($status === null) {
            return false;
        }

        $sql = 'UPDATE education_certificates SET status = :status, updated_at = NOW() WHERE id = :id';
        $params = [
            'id' => $certificateId,
            'status' => $status,
        ];

        if ($action === 'issue') {
            $sql = 'UPDATE education_certificates
                    SET status = :status,
                        revoked_by = NULL,
                        revoked_at = NULL,
                        revoked_reason = NULL,
                        updated_at = NOW()
                    WHERE id = :id';
        } elseif ($action === 'revoke') {
            $sql = 'UPDATE education_certificates
                    SET status = :status,
                        revoked_by = :user_id,
                        revoked_at = NOW(),
                        revoked_reason = :reason,
                        updated_at = NOW()
                    WHERE id = :id';
            $params['user_id'] = $userId ?: null;
            $params['reason'] = 'Revogado pela central de certificados.';
        }

        Database::connection()->prepare($sql)->execute($params);

        self::auditCertificate($certificateId, !empty($certificate['certificate_institution_id']) ? (int) $certificate['certificate_institution_id'] : null, $userId ?: null, 'certificate_' . $action, [
            'status' => $certificate['status'] ?? null,
        ], [
            'status' => $status,
        ]);

        return true;
    }

    public static function certificateForCourseUser(int $courseId, int $userId): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_certificates.*,
                    COALESCE(NULLIF(education_certificates.student_name, ""), users.name) AS student_name,
                    users.name AS user_name,
                    users.email AS student_email
             FROM education_certificates
             INNER JOIN users ON users.id = education_certificates.user_id
             WHERE education_certificates.course_id = :course_id
               AND education_certificates.user_id = :user_id
               AND education_certificates.status <> "deleted"
             LIMIT 1'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public static function certificateInstitutions(): array
    {
        self::ensureSchema();
        return Database::connection()
            ->query('SELECT * FROM certificate_institutions WHERE active = 1 ORDER BY name ASC')
            ->fetchAll();
    }

    public static function createCertificateInstitution(array $data): int
    {
        self::ensureSchema();

        $name = trim((string) ($data['name'] ?? ''));
        $slug = self::uniqueCertificateInstitutionSlug(slugify($name));

        Database::connection()->prepare(
            'INSERT INTO certificate_institutions
                (name, slug, cnpj, city, state, site, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:name, :slug, :cnpj, :city, :state, :site, 1, :created_by, :updated_by, NOW(), NOW())'
        )->execute([
            'name' => $name,
            'slug' => $slug,
            'cnpj' => self::nullable($data['cnpj'] ?? null),
            'city' => self::nullable($data['city'] ?? null),
            'state' => self::nullable($data['state'] ?? null),
            'site' => self::nullable($data['site'] ?? null),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function certificatePeriodForCourseUser(int $courseId, int $userId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT
                MIN(education_enrollments.created_at) AS enrolled_at,
                MAX(education_lesson_progress.completed_at) AS completed_at,
                MAX(education_attendance.attendance_date) AS last_attendance_at,
                MAX(education_certificates.issued_at) AS issued_at
             FROM education_enrollments
             LEFT JOIN education_lessons
                ON education_lessons.course_id = education_enrollments.course_id
               AND education_lessons.active = 1
             LEFT JOIN education_lesson_progress
                ON education_lesson_progress.lesson_id = education_lessons.id
               AND education_lesson_progress.user_id = education_enrollments.user_id
               AND education_lesson_progress.completed_at IS NOT NULL
             LEFT JOIN education_attendance
                ON education_attendance.course_id = education_enrollments.course_id
               AND education_attendance.user_id = education_enrollments.user_id
             LEFT JOIN education_certificates
                ON education_certificates.course_id = education_enrollments.course_id
               AND education_certificates.user_id = education_enrollments.user_id
             WHERE education_enrollments.course_id = :course_id
               AND education_enrollments.user_id = :user_id'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $row = $stmt->fetch() ?: [];

        $endDates = array_filter([
            $row['completed_at'] ?? null,
            $row['last_attendance_at'] ?? null,
            $row['issued_at'] ?? null,
        ]);
        usort($endDates, static fn (string $left, string $right): int => strtotime($left) <=> strtotime($right));

        return [
            'start' => $row['enrolled_at'] ?? null,
            'end' => $endDates ? end($endDates) : null,
        ];
    }

    public static function certificatesForUser(int $userId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_certificates.*,
                    education_courses.title AS course_title,
                    education_courses.certificate_title,
                    education_courses.certificate_activity_type,
                    education_courses.teacher_user_id,
                    teacher.name AS teacher_name,
                    COALESCE(NULLIF(education_certificates.student_name, ""), users.name) AS student_name
             FROM education_certificates
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             INNER JOIN users ON users.id = education_certificates.user_id
             LEFT JOIN users AS teacher ON teacher.id = education_courses.teacher_user_id
             WHERE education_certificates.user_id = :user_id
               AND education_courses.active = 1
               AND education_certificates.status <> "deleted"
             ORDER BY education_certificates.issued_at DESC, education_certificates.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function requestCertificateNameChange(int $courseId, int $userId, string $requestedName): void
    {
        self::ensureSchema();
        Database::connection()->prepare(
            'UPDATE education_certificates
             SET requested_student_name = :requested_student_name,
                 name_change_status = "pending",
                 name_change_requested_at = NOW(),
                 name_change_reviewed_by = NULL,
                 name_change_reviewed_at = NULL,
                 updated_at = NOW()
             WHERE course_id = :course_id AND user_id = :user_id'
        )->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
            'requested_student_name' => trim($requestedName),
        ]);
    }

    public static function certificateNameRequestsForCourse(int $courseId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT education_certificates.*,
                    COALESCE(NULLIF(education_certificates.student_name, ""), users.name) AS student_name,
                    users.name AS user_name,
                    users.email AS student_email
             FROM education_certificates
             INNER JOIN users ON users.id = education_certificates.user_id
             WHERE education_certificates.course_id = :course_id
               AND education_certificates.name_change_status = "pending"
             ORDER BY education_certificates.name_change_requested_at ASC'
        );
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll();
    }

    public static function reviewCertificateNameChange(int $certificateId, bool $approved, int $reviewedBy): ?array
    {
        self::ensureSchema();
        $certificate = self::certificateById($certificateId);
        if (!$certificate || ($certificate['name_change_status'] ?? '') !== 'pending') {
            return $certificate;
        }

        Database::connection()->prepare(
            'UPDATE education_certificates
             SET student_name = CASE WHEN :approved = 1 THEN requested_student_name ELSE student_name END,
                 requested_student_name = NULL,
                 name_change_status = :status,
                 name_change_reviewed_by = :reviewed_by,
                 name_change_reviewed_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $certificateId,
            'approved' => $approved ? 1 : 0,
            'status' => $approved ? 'approved' : 'rejected',
            'reviewed_by' => $reviewedBy,
        ]);

        return self::certificateById($certificateId);
    }

    public static function certificateById(int $certificateId): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT education_certificates.*,
                    education_courses.title AS course_title,
                    education_courses.certificate_activity_type,
                    education_courses.teacher_user_id,
                    users.name AS user_name,
                    COALESCE(users.email, people.email) AS student_email,
                    COALESCE(NULLIF(education_certificates.student_name, ""), users.name, people.full_name) AS student_name
             FROM education_certificates
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             LEFT JOIN users ON users.id = education_certificates.user_id
             LEFT JOIN people ON people.id = education_certificates.person_id
             WHERE education_certificates.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $certificateId]);
        return $stmt->fetch() ?: null;
    }

    public static function certificateByVerificationCode(string $code): ?array
    {
        self::ensureSchema();

        $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code) ?? '');
        if ($code === '') {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_certificates.*,
                    COALESCE(NULLIF(education_certificates.student_name, ""), users.name, people.full_name) AS student_name,
                    users.name AS user_name,
                    people.full_name AS person_name,
                    education_courses.title AS course_title,
                    education_courses.certificate_activity_type,
                    education_courses.certificate_title,
                    education_courses.certificate_min_frequency,
                    education_courses.certificate_course_nature,
                    education_courses.certificate_modality,
                    education_courses.certificate_approval_criteria,
                    education_courses.certificate_legal_text,
                    COALESCE(NULLIF(education_courses.certificate_institution_name, ""), certificate_institutions.name) AS certificate_institution_name,
                    COALESCE(NULLIF(education_courses.certificate_institution_city, ""), CONCAT_WS(" - ", certificate_institutions.city, certificate_institutions.state)) AS certificate_institution_city,
                    COALESCE(NULLIF(education_courses.certificate_institution_cnpj, ""), certificate_institutions.cnpj) AS certificate_institution_cnpj,
                    COALESCE(NULLIF(education_courses.certificate_institution_site, ""), certificate_institutions.site) AS certificate_institution_site,
                    education_courses.certificate_objectives,
                    education_courses.certificate_competencies,
                    education_courses.certificate_responsible_name,
                    education_courses.certificate_responsible_credential,
                    teacher.name AS teacher_name
             FROM education_certificates
             LEFT JOIN users ON users.id = education_certificates.user_id
             LEFT JOIN people ON people.id = education_certificates.person_id
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             LEFT JOIN certificate_institutions ON certificate_institutions.id = education_courses.certificate_institution_id
             LEFT JOIN users AS teacher ON teacher.id = education_courses.teacher_user_id
             WHERE education_certificates.verification_code = :code
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $certificate = $stmt->fetch() ?: null;

        if ($certificate && ($certificate['status'] ?? 'issued') === 'issued') {
            Database::connection()->prepare(
                'UPDATE education_certificates
                 SET verified_count = verified_count + 1,
                     last_verified_at = NOW()
                 WHERE id = :id'
            )->execute(['id' => (int) $certificate['id']]);
        }

        return $certificate;
    }

    public static function certificateCenterStats(): array
    {
        self::ensureSchema();
        $db = Database::connection();

        $statusRows = $db->query(
            'SELECT status, COUNT(*) AS total
             FROM education_certificates
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             WHERE education_courses.certificate_activity_type <> "reconhecimento"
               AND education_certificates.status <> "deleted"
             GROUP BY status'
        )->fetchAll();

        $recentRows = $db->query(
            'SELECT education_certificates.id,
                    education_certificates.verification_code,
                    education_certificates.status,
                    education_certificates.issued_at,
                    education_courses.title AS course_title,
                    COALESCE(NULLIF(education_certificates.student_name, ""), users.name, people.full_name) AS student_name
             FROM education_certificates
             INNER JOIN education_courses ON education_courses.id = education_certificates.course_id
             LEFT JOIN users ON users.id = education_certificates.user_id
             LEFT JOIN people ON people.id = education_certificates.person_id
             WHERE education_courses.certificate_activity_type <> "reconhecimento"
               AND education_certificates.status <> "deleted"
             ORDER BY education_certificates.issued_at DESC, education_certificates.id DESC
             LIMIT 8'
        )->fetchAll();

        return [
            'total_certificates' => (int) $db->query('SELECT COUNT(*) FROM education_certificates INNER JOIN education_courses ON education_courses.id = education_certificates.course_id WHERE education_certificates.status <> "deleted" AND education_courses.certificate_activity_type <> "reconhecimento"')->fetchColumn(),
            'issued_certificates' => (int) $db->query('SELECT COUNT(*) FROM education_certificates INNER JOIN education_courses ON education_courses.id = education_certificates.course_id WHERE education_certificates.status = "issued" AND education_courses.certificate_activity_type <> "reconhecimento"')->fetchColumn(),
            'revoked_certificates' => (int) $db->query('SELECT COUNT(*) FROM education_certificates INNER JOIN education_courses ON education_courses.id = education_certificates.course_id WHERE education_certificates.status = "revoked" AND education_courses.certificate_activity_type <> "reconhecimento"')->fetchColumn(),
            'verified_total' => (int) $db->query('SELECT COALESCE(SUM(verified_count), 0) FROM education_certificates INNER JOIN education_courses ON education_courses.id = education_certificates.course_id WHERE education_certificates.status <> "deleted" AND education_courses.certificate_activity_type <> "reconhecimento"')->fetchColumn(),
            'certificate_courses' => (int) $db->query('SELECT COUNT(*) FROM education_courses WHERE certificate_enabled = 1 AND certificate_activity_type <> "reconhecimento"')->fetchColumn(),
            'institutions' => (int) $db->query('SELECT COUNT(*) FROM certificate_institutions WHERE active = 1')->fetchColumn(),
            'templates' => (int) $db->query('SELECT COUNT(*) FROM certificate_templates WHERE active = 1')->fetchColumn(),
            'pending_batches' => (int) $db->query('SELECT COUNT(*) FROM certificate_batches WHERE status IN ("draft", "pending", "approved", "processing")')->fetchColumn(),
            'by_status' => array_column($statusRows, 'total', 'status'),
            'recent' => $recentRows,
        ];
    }

    public static function markLesson(int $lessonId, int $userId, bool $completed): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'INSERT INTO education_lesson_progress (lesson_id, user_id, completed_at, updated_at)
             VALUES (:lesson_id, :user_id, :completed_at, NOW())
             ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at), updated_at = NOW()'
        )->execute([
            'lesson_id' => $lessonId,
            'user_id' => $userId,
            'completed_at' => $completed ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public static function markLessonVideoWatched(int $lessonId, int $userId): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'INSERT INTO education_lesson_watches (lesson_id, user_id, completed_at, updated_at)
             VALUES (:lesson_id, :user_id, NOW(), NOW())
             ON DUPLICATE KEY UPDATE completed_at = NOW(), updated_at = NOW()'
        )->execute([
            'lesson_id' => $lessonId,
            'user_id' => $userId,
        ]);
    }

    public static function userWatchedLessonVideo(int $lessonId, int $userId): bool
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT completed_at
             FROM education_lesson_watches
             WHERE lesson_id = :lesson_id
               AND user_id = :user_id
               AND completed_at IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute(['lesson_id' => $lessonId, 'user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function markLessonBlockVideoWatched(int $blockId, int $userId): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'INSERT INTO education_lesson_block_watches (block_id, user_id, completed_at, updated_at)
             VALUES (:block_id, :user_id, NOW(), NOW())
             ON DUPLICATE KEY UPDATE completed_at = NOW(), updated_at = NOW()'
        )->execute([
            'block_id' => $blockId,
            'user_id' => $userId,
        ]);
    }

    public static function lessonCompletionRequirements(int $lessonId, int $userId): array
    {
        self::ensureSchema();
        $lesson = self::findLesson($lessonId);
        $course = $lesson ? self::findCourse((int) $lesson['course_id']) : null;
        $requireVideos = !$course || !empty($course['playlist_required']);

        $stmt = Database::connection()->prepare(
            'SELECT education_lesson_blocks.id,
                    education_lesson_blocks.type,
                    education_lesson_blocks.title,
                    education_lesson_blocks.media_url,
                    block_watches.completed_at AS block_video_completed_at,
                    assignment_submissions.id AS assignment_submission_id
             FROM education_lesson_blocks
             LEFT JOIN education_lesson_block_watches AS block_watches
                ON block_watches.block_id = education_lesson_blocks.id
               AND block_watches.user_id = :watch_user_id
             LEFT JOIN education_assignment_submissions AS assignment_submissions
                ON assignment_submissions.block_id = education_lesson_blocks.id
               AND assignment_submissions.user_id = :assignment_user_id
             WHERE education_lesson_blocks.lesson_id = :lesson_id
               AND education_lesson_blocks.active = 1
               AND education_lesson_blocks.required = 1
               AND education_lesson_blocks.type IN ("video", "assignment")
               AND (education_lesson_blocks.type <> "video" OR COALESCE(education_lesson_blocks.media_url, "") <> "")
             ORDER BY education_lesson_blocks.sort_order ASC, education_lesson_blocks.id ASC'
        );
        $stmt->execute([
            'lesson_id' => $lessonId,
            'watch_user_id' => $userId,
            'assignment_user_id' => $userId,
        ]);

        $requirements = [
            'required_video_count' => 0,
            'watched_video_count' => 0,
            'required_assignment_count' => 0,
            'submitted_assignment_count' => 0,
            'required_forum_count' => 0,
            'replied_forum_count' => 0,
            'pending' => [],
        ];

        foreach ($stmt->fetchAll() as $row) {
            $title = trim((string) ($row['title'] ?? '')) ?: (($row['type'] ?? '') === 'assignment' ? 'Tarefa obrigatória' : 'Vídeo obrigatório');
            if (($row['type'] ?? '') === 'video') {
                if (!$requireVideos) {
                    continue;
                }
                $requirements['required_video_count']++;
                if (!empty($row['block_video_completed_at'])) {
                    $requirements['watched_video_count']++;
                    continue;
                }
                $requirements['pending'][] = ['type' => 'video', 'title' => $title];
                continue;
            }

            if (($row['type'] ?? '') === 'assignment') {
                $requirements['required_assignment_count']++;
                if (!empty($row['assignment_submission_id'])) {
                    $requirements['submitted_assignment_count']++;
                    continue;
                }
                $requirements['pending'][] = ['type' => 'assignment', 'title' => $title];
            }
        }

        $forumStmt = Database::connection()->prepare(
            'SELECT education_forum_topics.id,
                    education_forum_topics.title,
                    student_replies.id AS reply_id
             FROM education_forum_topics
             LEFT JOIN education_forum_replies AS student_replies
                ON student_replies.topic_id = education_forum_topics.id
               AND student_replies.user_id = :user_id
               AND student_replies.active = 1
             WHERE education_forum_topics.lesson_id = :lesson_id
               AND education_forum_topics.status <> "hidden"
             ORDER BY education_forum_topics.created_at ASC, education_forum_topics.id ASC'
        );
        $forumStmt->execute([
            'lesson_id' => $lessonId,
            'user_id' => $userId,
        ]);

        foreach ($forumStmt->fetchAll() as $topic) {
            $requirements['required_forum_count']++;
            if (!empty($topic['reply_id'])) {
                $requirements['replied_forum_count']++;
                continue;
            }

            $title = trim((string) ($topic['title'] ?? '')) ?: 'Fórum da aula';
            $requirements['pending'][] = ['type' => 'forum', 'title' => 'Responder fórum: ' . $title];
        }

        $requirements['complete'] = empty($requirements['pending']);

        return $requirements;
    }

    public static function forumTopics(?int $courseId = null, ?int $lessonId = null): array
    {
        self::ensureSchema();

        $params = [];
        if ($lessonId) {
            $scope = 'education_forum_topics.lesson_id = :lesson_id';
            $params['lesson_id'] = $lessonId;
        } elseif ($courseId) {
            $scope = 'education_forum_topics.course_id = :course_id AND education_forum_topics.lesson_id IS NULL';
            $params['course_id'] = $courseId;
        } else {
            $scope = 'education_forum_topics.course_id IS NULL AND education_forum_topics.lesson_id IS NULL';
        }

        $stmt = Database::connection()->prepare(
            'SELECT education_forum_topics.*,
                    users.name AS user_name,
                    COUNT(education_forum_replies.id) AS reply_count
             FROM education_forum_topics
             INNER JOIN users ON users.id = education_forum_topics.user_id
             LEFT JOIN education_forum_replies
                ON education_forum_replies.topic_id = education_forum_topics.id
               AND education_forum_replies.active = 1
             WHERE ' . $scope . '
               AND education_forum_topics.status <> "hidden"
             GROUP BY education_forum_topics.id
             ORDER BY education_forum_topics.updated_at DESC, education_forum_topics.created_at DESC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function forumTopicCountForCourse(int $courseId): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*)
             FROM education_forum_topics
             WHERE course_id = :course_id
               AND status <> "hidden"'
        );
        $stmt->execute(['course_id' => $courseId]);

        return (int) $stmt->fetchColumn();
    }

    public static function createForumTopic(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_forum_topics (course_id, lesson_id, central_topic_id, user_id, title, body, status, created_at, updated_at)
             VALUES (:course_id, :lesson_id, :central_topic_id, :user_id, :title, :body, "open", NOW(), NOW())'
        );
        $stmt->execute(self::topicPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateForumTopic(int $id, array $data): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'UPDATE education_forum_topics
             SET title = :title,
                 body = :body,
                 user_id = :user_id,
                 updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'title' => trim((string) ($data['title'] ?? '')),
            'body' => trim((string) ($data['body'] ?? '')),
            'user_id' => (int) ($data['user_id'] ?? 0),
            'id' => $id,
        ]);
    }

    public static function setForumTopicCentralId(int $topicId, int $centralTopicId): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forum_topics SET central_topic_id = :central_topic_id, updated_at = NOW() WHERE id = :id')
            ->execute(['central_topic_id' => $centralTopicId, 'id' => $topicId]);
    }

    public static function findForumTopic(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_forum_topics.*,
                    users.name AS user_name,
                    users.email AS user_email
             FROM education_forum_topics
             INNER JOIN users ON users.id = education_forum_topics.user_id
             WHERE education_forum_topics.id = :id
               AND education_forum_topics.status <> "hidden"
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function forumRepliesForTopics(array $topicIds, bool $includeHidden = false): array
    {
        self::ensureSchema();

        $topicIds = array_values(array_unique(array_filter(array_map('intval', $topicIds))));
        if (!$topicIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($topicIds), '?'));
        $stmt = Database::connection()->prepare(
            'SELECT education_forum_replies.*,
                    users.name AS user_name,
                    parent_users.name AS parent_user_name
             FROM education_forum_replies
             INNER JOIN users ON users.id = education_forum_replies.user_id
             LEFT JOIN education_forum_replies parent_replies ON parent_replies.id = education_forum_replies.parent_reply_id
             LEFT JOIN users parent_users ON parent_users.id = parent_replies.user_id
             WHERE education_forum_replies.topic_id IN (' . $placeholders . ')
               ' . ($includeHidden ? '' : 'AND education_forum_replies.active = 1') . '
             ORDER BY education_forum_replies.created_at ASC, education_forum_replies.id ASC'
        );
        $stmt->execute($topicIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $reply) {
            $grouped[(int) $reply['topic_id']][] = $reply;
        }

        return $grouped;
    }

    public static function findForumReply(int $id, bool $includeHidden = false): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_forum_replies.*,
                    education_forum_topics.course_id,
                    education_forum_topics.lesson_id,
                    education_forum_topics.central_topic_id,
                    users.name AS user_name,
                    users.email AS user_email
             FROM education_forum_replies
             INNER JOIN education_forum_topics ON education_forum_topics.id = education_forum_replies.topic_id
             INNER JOIN users ON users.id = education_forum_replies.user_id
             WHERE education_forum_replies.id = :id
               ' . ($includeHidden ? '' : 'AND education_forum_replies.active = 1') . '
               AND education_forum_topics.status <> "hidden"
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function forumReplies(int $topicId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_forum_replies.*, users.name AS user_name, parent_users.name AS parent_user_name
             FROM education_forum_replies
             INNER JOIN users ON users.id = education_forum_replies.user_id
             LEFT JOIN education_forum_replies parent_replies ON parent_replies.id = education_forum_replies.parent_reply_id
             LEFT JOIN users parent_users ON parent_users.id = parent_replies.user_id
             WHERE education_forum_replies.topic_id = :topic_id
               AND education_forum_replies.active = 1
             ORDER BY education_forum_replies.created_at ASC, education_forum_replies.id ASC'
        );
        $stmt->execute(['topic_id' => $topicId]);

        return $stmt->fetchAll();
    }

    public static function createForumReply(array $data): int
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'INSERT INTO education_forum_replies (topic_id, parent_reply_id, user_id, body, active, created_at, updated_at)
             VALUES (:topic_id, :parent_reply_id, :user_id, :body, 1, NOW(), NOW())'
        );
        $stmt->execute(self::replyPayload($data));

        Database::connection()
            ->prepare('UPDATE education_forum_topics SET updated_at = NOW() WHERE id = :id')
            ->execute(['id' => (int) $data['topic_id']]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function updateForumReply(int $replyId, string $body): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forum_replies SET body = :body, updated_at = NOW() WHERE id = :id')
            ->execute([
                'body' => trim($body),
                'id' => $replyId,
            ]);

        Database::connection()
            ->prepare(
                'UPDATE education_forum_topics
                 INNER JOIN education_forum_replies ON education_forum_replies.topic_id = education_forum_topics.id
                 SET education_forum_topics.updated_at = NOW()
                 WHERE education_forum_replies.id = :id'
            )
            ->execute(['id' => $replyId]);
    }

    public static function hideForumReply(int $replyId): void
    {
        self::setForumReplyActive($replyId, false);
    }

    public static function restoreForumReply(int $replyId): void
    {
        self::setForumReplyActive($replyId, true);
    }

    public static function setForumReplyActive(int $replyId, bool $active): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forum_replies SET active = :active, updated_at = NOW() WHERE id = :id')
            ->execute([
                'active' => $active ? 1 : 0,
                'id' => $replyId,
            ]);
    }

    public static function closeForumTopic(int $topicId): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forum_topics SET status = "closed", updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $topicId]);
    }

    public static function hideForumTopic(int $topicId): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forum_topics SET status = "hidden", updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $topicId]);
    }

    public static function formsForCourse(int $courseId, ?int $lessonId = null): array
    {
        self::ensureSchema();

        $scope = $lessonId ? 'lesson_id = :lesson_id' : 'course_id = :course_id AND lesson_id IS NULL';
        $params = $lessonId ? ['lesson_id' => $lessonId] : ['course_id' => $courseId];
        $stmt = Database::connection()->prepare(
            'SELECT education_forms.*,
                    users.name AS creator_name,
                    (SELECT COUNT(*) FROM education_form_questions WHERE education_form_questions.form_id = education_forms.id) AS question_count,
                    (SELECT COUNT(*) FROM education_form_responses WHERE education_form_responses.form_id = education_forms.id) AS response_count
             FROM education_forms
             INNER JOIN users ON users.id = education_forms.created_by
             WHERE education_forms.active = 1 AND ' . $scope . '
             ORDER BY education_forms.created_at DESC, education_forms.id DESC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function findForm(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_forms.*, education_courses.teacher_user_id
             FROM education_forms
             INNER JOIN education_courses ON education_courses.id = education_forms.course_id
             WHERE education_forms.id = :id AND education_forms.active = 1 AND education_courses.active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function formQuestions(int $formId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT * FROM education_form_questions WHERE form_id = :form_id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['form_id' => $formId]);

        return $stmt->fetchAll();
    }

    public static function createForm(array $data, array $questions): int
    {
        self::ensureSchema();
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'INSERT INTO education_forms (course_id, lesson_id, created_by, title, description, active, created_at, updated_at)
                 VALUES (:course_id, :lesson_id, :created_by, :title, :description, 1, NOW(), NOW())'
            );
            $stmt->execute([
                'course_id' => (int) ($data['course_id'] ?? 0),
                'lesson_id' => !empty($data['lesson_id']) ? (int) $data['lesson_id'] : null,
                'created_by' => (int) ($data['created_by'] ?? 0),
                'title' => trim((string) ($data['title'] ?? '')),
                'description' => self::nullable($data['description'] ?? null),
            ]);
            $formId = (int) $db->lastInsertId();

            self::replaceFormQuestions($formId, $questions);
            $db->commit();

            return $formId;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function updateForm(int $id, array $data, array $questions): void
    {
        self::ensureSchema();
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare(
                'UPDATE education_forms
                 SET title = :title, description = :description, updated_at = NOW()
                 WHERE id = :id'
            )->execute([
                'id' => $id,
                'title' => trim((string) ($data['title'] ?? '')),
                'description' => self::nullable($data['description'] ?? null),
            ]);
            $db->prepare('DELETE FROM education_form_questions WHERE form_id = :form_id')->execute(['form_id' => $id]);
            self::replaceFormQuestions($id, $questions);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private static function replaceFormQuestions(int $formId, array $questions): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO education_form_questions (form_id, question, sort_order, required, created_at, updated_at)
             VALUES (:form_id, :question, :sort_order, 1, NOW(), NOW())'
        );
        $sort = 10;
        foreach ($questions as $question) {
            $question = trim((string) $question);
            if ($question === '') {
                continue;
            }
            $stmt->execute([
                'form_id' => $formId,
                'question' => $question,
                'sort_order' => $sort,
            ]);
            $sort += 10;
        }
    }

    public static function deactivateForm(int $id): void
    {
        self::ensureSchema();

        Database::connection()
            ->prepare('UPDATE education_forms SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function formResponse(int $formId, int $userId): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT * FROM education_form_responses WHERE form_id = :form_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['form_id' => $formId, 'user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public static function formAnswersForResponse(?int $responseId): array
    {
        if (!$responseId) {
            return [];
        }

        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT question_id, answer FROM education_form_answers WHERE response_id = :response_id');
        $stmt->execute(['response_id' => $responseId]);

        $answers = [];
        foreach ($stmt->fetchAll() as $row) {
            $answers[(int) $row['question_id']] = $row['answer'];
        }

        return $answers;
    }

    public static function formResponses(int $formId): array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_form_responses.*, users.name AS user_name, users.email AS user_email,
                    corrector.name AS corrector_name
             FROM education_form_responses
             INNER JOIN users ON users.id = education_form_responses.user_id
             LEFT JOIN users AS corrector ON corrector.id = education_form_responses.corrected_by
             WHERE education_form_responses.form_id = :form_id
             ORDER BY education_form_responses.updated_at DESC, education_form_responses.created_at DESC'
        );
        $stmt->execute(['form_id' => $formId]);

        return $stmt->fetchAll();
    }

    public static function findFormResponse(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT education_form_responses.*, education_forms.course_id, education_forms.lesson_id
             FROM education_form_responses
             INNER JOIN education_forms ON education_forms.id = education_form_responses.form_id
             WHERE education_form_responses.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function saveFormResponse(int $formId, int $userId, array $answers): void
    {
        self::ensureSchema();
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $db->prepare(
                'INSERT INTO education_form_responses (form_id, user_id, created_at, updated_at)
                 VALUES (:form_id, :user_id, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    updated_at = NOW(),
                    correction_status = \'pending\',
                    grade = NULL,
                    feedback = NULL,
                    corrected_by = NULL,
                    corrected_at = NULL'
            )->execute(['form_id' => $formId, 'user_id' => $userId]);
            $responseId = (int) $db->lastInsertId();

            if ($responseId === 0) {
                $stmt = $db->prepare(
                    'SELECT id FROM education_form_responses WHERE form_id = :form_id AND user_id = :user_id LIMIT 1'
                );
                $stmt->execute(['form_id' => $formId, 'user_id' => $userId]);
                $responseId = (int) $stmt->fetchColumn();
            }

            if ($responseId <= 0) {
                throw new \RuntimeException('Nao foi possivel registrar a resposta do formulario.');
            }

            $stmt = $db->prepare(
                'INSERT INTO education_form_answers (response_id, question_id, answer, updated_at)
                 VALUES (:response_id, :question_id, :answer, NOW())
                 ON DUPLICATE KEY UPDATE answer = VALUES(answer), updated_at = NOW()'
            );
            foreach ($answers as $questionId => $answer) {
                $stmt->execute([
                    'response_id' => $responseId,
                    'question_id' => (int) $questionId,
                    'answer' => self::nullable($answer),
                ]);
            }
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function gradeFormResponse(int $responseId, string $status, string $grade, string $feedback, int $correctedBy): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'UPDATE education_form_responses
             SET correction_status = :status,
                 grade = :grade,
                 feedback = :feedback,
                 corrected_by = :corrected_by,
                 corrected_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $responseId,
            'status' => self::correctionStatus($status),
            'grade' => self::nullable($grade),
            'feedback' => self::nullable($feedback),
            'corrected_by' => $correctedBy,
        ]);
    }

    public static function assignmentSubmission(int $blockId, int $userId): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT * FROM education_assignment_submissions WHERE block_id = :block_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['block_id' => $blockId, 'user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public static function findAssignmentSubmission(int $id): ?array
    {
        self::ensureSchema();

        $stmt = Database::connection()->prepare(
            'SELECT * FROM education_assignment_submissions WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function assignmentSubmissionsForBlocks(array $blockIds): array
    {
        self::ensureSchema();
        $blockIds = array_values(array_unique(array_filter(array_map('intval', $blockIds))));
        if (!$blockIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($blockIds), '?'));
        $stmt = Database::connection()->prepare(
            'SELECT education_assignment_submissions.*, users.name AS user_name, users.email AS user_email,
                    corrector.name AS corrector_name
             FROM education_assignment_submissions
             INNER JOIN users ON users.id = education_assignment_submissions.user_id
             LEFT JOIN users AS corrector ON corrector.id = education_assignment_submissions.corrected_by
             WHERE education_assignment_submissions.block_id IN (' . $placeholders . ')
             ORDER BY education_assignment_submissions.updated_at DESC, education_assignment_submissions.created_at DESC'
        );
        $stmt->execute($blockIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $submission) {
            $grouped[(int) $submission['block_id']][] = $submission;
        }

        return $grouped;
    }

    public static function saveAssignmentSubmission(int $blockId, int $userId, string $textAnswer, ?array $file): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'INSERT INTO education_assignment_submissions
                (block_id, user_id, text_answer, file_path, original_name, size_bytes, created_at, updated_at)
             VALUES
                (:block_id, :user_id, :text_answer, :file_path, :original_name, :size_bytes, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                text_answer = VALUES(text_answer),
                file_path = COALESCE(VALUES(file_path), file_path),
                original_name = COALESCE(VALUES(original_name), original_name),
                size_bytes = COALESCE(VALUES(size_bytes), size_bytes),
                updated_at = NOW(),
                correction_status = \'pending\',
                grade = NULL,
                feedback = NULL,
                corrected_by = NULL,
                corrected_at = NULL'
        )->execute([
            'block_id' => $blockId,
            'user_id' => $userId,
            'text_answer' => self::nullable($textAnswer),
            'file_path' => $file['file_path'] ?? null,
            'original_name' => $file['original_name'] ?? null,
            'size_bytes' => $file['size_bytes'] ?? null,
        ]);
    }

    public static function gradeAssignmentSubmission(int $submissionId, string $status, string $grade, string $feedback, int $correctedBy): void
    {
        self::ensureSchema();

        Database::connection()->prepare(
            'UPDATE education_assignment_submissions
             SET correction_status = :status,
                 grade = :grade,
                 feedback = :feedback,
                 corrected_by = :corrected_by,
                 corrected_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $submissionId,
            'status' => self::correctionStatus($status),
            'grade' => self::nullable($grade),
            'feedback' => self::nullable($feedback),
            'corrected_by' => $correctedBy,
        ]);
    }

    private static function coursePayload(array $data): array
    {
        return [
            'title' => trim((string) ($data['title'] ?? '')),
            'summary' => self::nullable($data['summary'] ?? null),
            'cover_image' => self::nullable($data['cover_image'] ?? null),
            'certificate_institution_id' => !empty($data['certificate_institution_id']) ? (int) $data['certificate_institution_id'] : null,
            'certificate_category_id' => !empty($data['certificate_category_id']) ? (int) $data['certificate_category_id'] : null,
            'certificate_template_id' => !empty($data['certificate_template_id']) ? (int) $data['certificate_template_id'] : null,
            'certificate_activity_type' => self::activityType($data['certificate_activity_type'] ?? 'curso_livre'),
            'workload_hours' => self::nullableDecimal($data['workload_hours'] ?? null),
            'starts_at' => self::nullableDate($data['starts_at'] ?? null),
            'ends_at' => self::nullableDate($data['ends_at'] ?? null),
            'public_enabled' => !empty($data['public_enabled']) ? 1 : 0,
            'public_access_enabled' => !empty($data['public_access_enabled']) ? 1 : 0,
            'playlist_required' => array_key_exists('playlist_required', $data) ? (!empty($data['playlist_required']) ? 1 : 0) : 1,
            'certificate_enabled' => !empty($data['certificate_enabled']) ? 1 : 0,
            'certificate_title' => self::nullable($data['certificate_title'] ?? null),
            'certificate_text' => self::nullable($data['certificate_text'] ?? null),
            'certificate_font_family' => self::certificateFont($data['certificate_font_family'] ?? null),
            'certificate_background' => self::nullable($data['certificate_background'] ?? null),
            'certificate_min_frequency' => max(0, min(100, (int) ($data['certificate_min_frequency'] ?? 0))),
            'certificate_show_recipient' => array_key_exists('certificate_show_recipient', $data) ? (!empty($data['certificate_show_recipient']) ? 1 : 0) : 1,
            'certificate_show_nature' => array_key_exists('certificate_show_nature', $data) ? (!empty($data['certificate_show_nature']) ? 1 : 0) : 1,
            'certificate_show_modality' => array_key_exists('certificate_show_modality', $data) ? (!empty($data['certificate_show_modality']) ? 1 : 0) : 1,
            'certificate_show_period' => array_key_exists('certificate_show_period', $data) ? (!empty($data['certificate_show_period']) ? 1 : 0) : 1,
            'certificate_show_approval' => array_key_exists('certificate_show_approval', $data) ? (!empty($data['certificate_show_approval']) ? 1 : 0) : 1,
            'certificate_show_institution' => array_key_exists('certificate_show_institution', $data) ? (!empty($data['certificate_show_institution']) ? 1 : 0) : 1,
            'certificate_show_meta' => array_key_exists('certificate_show_meta', $data) ? (!empty($data['certificate_show_meta']) ? 1 : 0) : 1,
            'certificate_show_legal' => array_key_exists('certificate_show_legal', $data) ? (!empty($data['certificate_show_legal']) ? 1 : 0) : 1,
            'certificate_course_nature' => self::nullable($data['certificate_course_nature'] ?? null),
            'certificate_modality' => self::nullable($data['certificate_modality'] ?? null),
            'certificate_approval_criteria' => self::nullable($data['certificate_approval_criteria'] ?? null),
            'certificate_legal_text' => self::nullable($data['certificate_legal_text'] ?? null),
            'certificate_institution_name' => self::nullable($data['certificate_institution_name'] ?? null),
            'certificate_institution_city' => self::nullable($data['certificate_institution_city'] ?? null),
            'certificate_institution_cnpj' => self::nullable($data['certificate_institution_cnpj'] ?? null),
            'certificate_institution_site' => self::nullable($data['certificate_institution_site'] ?? null),
            'certificate_objectives' => self::nullable($data['certificate_objectives'] ?? null),
            'certificate_competencies' => self::nullable($data['certificate_competencies'] ?? null),
            'certificate_responsible_name' => self::nullable($data['certificate_responsible_name'] ?? null),
            'certificate_responsible_credential' => self::nullable($data['certificate_responsible_credential'] ?? null),
            'certificate_program_enabled' => array_key_exists('certificate_program_enabled', $data) ? (!empty($data['certificate_program_enabled']) ? 1 : 0) : 1,
            'certificate_program_background' => self::nullable($data['certificate_program_background'] ?? null),
            'certificate_program_extra' => self::nullable($data['certificate_program_extra'] ?? null),
            'certificate_program_columns' => max(1, min(4, (int) ($data['certificate_program_columns'] ?? 2))),
            'teacher_user_id' => !empty($data['teacher_user_id']) ? (int) $data['teacher_user_id'] : null,
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    private static function activityType(mixed $type): string
    {
        $type = trim((string) $type);
        return in_array($type, ['curso_livre', 'oficina', 'palestra', 'capacitacao', 'evento', 'acao_comunitaria', 'voluntariado', 'reconhecimento', 'extensao', 'formacao_continuada'], true)
            ? $type
            : 'curso_livre';
    }

    private static function certificateFont(mixed $font): ?string
    {
        $font = trim((string) $font);
        $allowed = ['system', 'serif', 'georgia', 'garamond', 'playfair', 'montserrat'];
        return in_array($font, $allowed, true) ? $font : null;
    }

    private static function nullableDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private static function nullableDecimal(mixed $value): ?string
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return number_format(max(0, (float) $value), 2, '.', '');
    }

    private static function courseProgressForUser(int $courseId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT education_lessons.id) AS lesson_count,
                    COUNT(DISTINCT CASE WHEN progress.completed_at IS NOT NULL THEN education_lessons.id END) AS completed_count
             FROM education_lessons
             LEFT JOIN education_modules ON education_modules.id = education_lessons.module_id
             LEFT JOIN education_lesson_progress progress
                ON progress.lesson_id = education_lessons.id
               AND progress.user_id = :user_id
             WHERE education_lessons.course_id = :course_id
               AND education_lessons.active = 1
               AND COALESCE(education_modules.required, 1) = 1'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $row = $stmt->fetch() ?: [];

        return [
            'lesson_count' => (int) ($row['lesson_count'] ?? 0),
            'completed_count' => (int) ($row['completed_count'] ?? 0),
        ];
    }

    private static function attendanceSummaryForCourseUser(int $courseId, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT education_lessons.id) AS records,
                    COUNT(DISTINCT CASE WHEN progress.completed_at IS NOT NULL THEN education_lessons.id END) AS present_count
             FROM education_lessons
             LEFT JOIN education_modules ON education_modules.id = education_lessons.module_id
             LEFT JOIN education_lesson_progress progress
                ON progress.lesson_id = education_lessons.id
               AND progress.user_id = :user_id
             WHERE education_lessons.course_id = :course_id
               AND education_lessons.active = 1
               AND COALESCE(education_modules.required, 1) = 1
               AND education_lessons.attendance_mode <> "none"'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $row = $stmt->fetch() ?: [];
        $records = (int) ($row['records'] ?? 0);
        $present = (int) ($row['present_count'] ?? 0);

        return [
            'records' => $records,
            'frequency' => $records > 0 ? (int) round(($present / $records) * 100) : 0,
        ];
    }

    private static function certificateCode(int $courseId, int $userId): string
    {
        return strtoupper(substr(hash('sha256', $courseId . ':' . $userId . ':' . microtime(true) . ':' . random_bytes(16)), 0, 20));
    }

    private static function uniqueCertificateInstitutionSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'instituicao';
        $slug = $base;
        $suffix = 2;

        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM certificate_institutions WHERE slug = :slug');
        while (true) {
            $stmt->execute(['slug' => $slug]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }

            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    private static function certificateHash(int $courseId, int $userId, string $code): string
    {
        return hash('sha256', implode('|', [$courseId, $userId, $code, dirname(__DIR__, 2)]));
    }

    private static function auditCertificate(int $certificateId, ?int $institutionId, ?int $userId, string $action, array $oldValues, array $newValues): void
    {
        Database::connection()->prepare(
            'INSERT INTO certificate_audit_logs
                (certificate_id, institution_id, user_id, action, old_values_json, new_values_json, ip_address, user_agent, created_at)
             VALUES
                (:certificate_id, :institution_id, :user_id, :action, :old_values_json, :new_values_json, :ip_address, :user_agent, NOW())'
        )->execute([
            'certificate_id' => $certificateId,
            'institution_id' => $institutionId,
            'user_id' => $userId,
            'action' => $action,
            'old_values_json' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'new_values_json' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    private static function modulePayload(array $data): array
    {
        return [
            'course_id' => (int) ($data['course_id'] ?? 0),
            'title' => trim((string) ($data['title'] ?? '')),
            'summary' => self::nullable($data['summary'] ?? null),
            'required' => array_key_exists('required', $data) ? (!empty($data['required']) ? 1 : 0) : 1,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private static function lessonPayload(array $data): array
    {
        return [
            'course_id' => (int) ($data['course_id'] ?? 0),
            'module_id' => !empty($data['module_id']) ? (int) $data['module_id'] : null,
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => self::nullable($data['description'] ?? null),
            'description_position' => self::descriptionPosition($data['description_position'] ?? 'after_media'),
            'video_url' => self::nullable($data['video_url'] ?? null),
            'image_url' => self::nullable($data['image_url'] ?? null),
            'public_access' => self::lessonPublicAccess($data['public_access'] ?? 'private'),
            'locked' => !empty($data['locked']) ? 1 : 0,
            'available_at' => self::datetimeOrNull($data['available_at'] ?? null),
            'attendance_mode' => self::attendanceMode($data['attendance_mode'] ?? 'video'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private static function lessonIsAvailable(array $lesson): bool
    {
        $availableAt = trim((string) ($lesson['available_at'] ?? ''));
        return $availableAt === '' || strtotime($availableAt) <= time();
    }

    private static function attendanceMode(mixed $mode): string
    {
        $mode = strtolower(trim((string) $mode));
        return in_array($mode, ['video', 'manual', 'none'], true) ? $mode : 'video';
    }

    private static function lessonPublicAccess(mixed $access): string
    {
        $access = strtolower(trim((string) $access));
        return in_array($access, ['private', 'preview', 'public'], true) ? $access : 'private';
    }

    private static function blockPublicAccess(mixed $access): string
    {
        $access = strtolower(trim((string) $access));
        return in_array($access, ['inherit', 'public', 'private'], true) ? $access : 'inherit';
    }

    private static function descriptionPosition(mixed $position): string
    {
        $position = strtolower(trim((string) $position));
        return in_array($position, ['top', 'after_media', 'hidden'], true) ? $position : 'after_media';
    }

    private static function datetimeOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime(str_replace('T', ' ', $value));
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private static function blockPayload(array $data): array
    {
        $allowedTypes = ['video', 'text', 'image', 'article', 'file', 'embed', 'quiz', 'assignment', 'certificate', 'podcast', 'audio'];
        $type = strtolower(trim((string) ($data['type'] ?? 'text')));

        return [
            'lesson_id' => (int) ($data['lesson_id'] ?? 0),
            'type' => in_array($type, $allowedTypes, true) ? $type : 'text',
            'title' => self::nullable($data['title'] ?? null),
            'content' => self::nullable($data['content'] ?? null),
            'media_url' => self::nullable($data['media_url'] ?? null),
            'file_path' => self::nullable($data['file_path'] ?? null),
            'settings_json' => self::blockSettingsJson($data),
            'public_access' => self::blockPublicAccess($data['public_access'] ?? 'inherit'),
            'required' => array_key_exists('required', $data) ? (!empty($data['required']) ? 1 : 0) : 1,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'active' => array_key_exists('active', $data) ? (!empty($data['active']) ? 1 : 0) : 1,
        ];
    }

    private static function blockSettingsJson(array $data): ?string
    {
        $imageWidth = (int) ($data['image_width'] ?? 100);
        if (!in_array($imageWidth, [35, 50, 70, 100], true)) {
            $imageWidth = 100;
        }

        $contentPosition = strtolower(trim((string) ($data['content_position'] ?? 'after_media')));
        if (!in_array($contentPosition, ['before_media', 'after_media'], true)) {
            $contentPosition = 'after_media';
        }

        $textStyle = strtolower(trim((string) ($data['text_style'] ?? 'default')));
        if (!in_array($textStyle, ['default', 'highlight', 'note'], true)) {
            $textStyle = 'default';
        }

        return json_encode([
            'image_width' => $imageWidth,
            'content_position' => $contentPosition,
            'text_style' => $textStyle,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function topicPayload(array $data): array
    {
        return [
            'course_id' => !empty($data['course_id']) ? (int) $data['course_id'] : null,
            'lesson_id' => !empty($data['lesson_id']) ? (int) $data['lesson_id'] : null,
            'central_topic_id' => !empty($data['central_topic_id']) ? (int) $data['central_topic_id'] : null,
            'user_id' => (int) ($data['user_id'] ?? 0),
            'title' => trim((string) ($data['title'] ?? '')),
            'body' => trim((string) ($data['body'] ?? '')),
        ];
    }

    private static function replyPayload(array $data): array
    {
        return [
            'topic_id' => (int) ($data['topic_id'] ?? 0),
            'parent_reply_id' => !empty($data['parent_reply_id']) ? (int) $data['parent_reply_id'] : null,
            'user_id' => (int) ($data['user_id'] ?? 0),
            'body' => trim((string) ($data['body'] ?? '')),
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }

    private static function correctionStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, ['pending', 'corrected', 'redo'], true) ? $status : 'pending';
    }

    private static function ensureColumn(string $table, string $column, string $definition): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $db->quote($column));
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}
