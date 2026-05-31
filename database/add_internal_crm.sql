CREATE TABLE IF NOT EXISTS people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    cpf VARCHAR(20) NULL,
    birth_date DATE NULL,
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    cep VARCHAR(12) NULL,
    address VARCHAR(255) NULL,
    address_number VARCHAR(30) NULL,
    address_complement VARCHAR(120) NULL,
    district VARCHAR(120) NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(2) NULL,
    is_minor TINYINT(1) NOT NULL DEFAULT 0,
    guardian_name VARCHAR(160) NULL,
    guardian_relation VARCHAR(80) NULL,
    guardian_cpf VARCHAR(20) NULL,
    guardian_phone VARCHAR(30) NULL,
    guardian_email VARCHAR(190) NULL,
    contact_authorized TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_people_name (full_name),
    INDEX idx_people_contact (email, whatsapp),
    CONSTRAINT fk_people_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_people_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS library_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    location VARCHAR(160) NULL,
    cover_image VARCHAR(255) NULL,
    capacity INT UNSIGNED NULL,
    responsible_user_id BIGINT UNSIGNED NULL,
    status ENUM('aberto','encerrado','cancelado') NOT NULL DEFAULT 'aberto',
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_library_events_starts_at (starts_at),
    CONSTRAINT fk_library_events_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_library_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_library_events_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE people ADD COLUMN IF NOT EXISTS cep VARCHAR(12) NULL AFTER email;
ALTER TABLE people ADD COLUMN IF NOT EXISTS address_number VARCHAR(30) NULL AFTER address;
ALTER TABLE people ADD COLUMN IF NOT EXISTS address_complement VARCHAR(120) NULL AFTER address_number;
ALTER TABLE people ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER district;
ALTER TABLE people ADD COLUMN IF NOT EXISTS state VARCHAR(2) NULL AFTER city;
ALTER TABLE people ADD COLUMN IF NOT EXISTS is_minor TINYINT(1) NOT NULL DEFAULT 0 AFTER state;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_relation VARCHAR(80) NULL AFTER guardian_name;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_cpf VARCHAR(20) NULL AFTER guardian_relation;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_phone VARCHAR(30) NULL AFTER guardian_cpf;
ALTER TABLE people ADD COLUMN IF NOT EXISTS guardian_email VARCHAR(190) NULL AFTER guardian_phone;
ALTER TABLE library_events ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) NULL AFTER location;

CREATE TABLE IF NOT EXISTS library_event_participants (
    event_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (event_id, person_id),
    CONSTRAINT fk_event_participants_event FOREIGN KEY (event_id) REFERENCES library_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_participants_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE library_event_participants
    MODIFY status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente';

INSERT INTO permissions (name, slug, created_at)
VALUES
    ('Gerenciar pessoas internas', 'people.manage', NOW()),
    ('Gerenciar eventos internos', 'events.manage', NOW()),
    ('Gerenciar participantes de eventos', 'event_participants.manage', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN ('people.manage', 'events.manage', 'event_participants.manage')
WHERE roles.slug IN ('master', 'admin', 'admin-local', 'voluntario')
ON DUPLICATE KEY UPDATE
    role_id = role_id;
