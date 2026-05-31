-- Habilita inscricao publica com confirmacao posterior no painel.

ALTER TABLE library_event_participants
    MODIFY status ENUM('pendente','inscrito','presente','ausente','cancelado') NOT NULL DEFAULT 'pendente';

ALTER TABLE people
    ADD COLUMN IF NOT EXISTS image_authorized TINYINT(1) NOT NULL DEFAULT 0 AFTER contact_authorized;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS event_cep VARCHAR(12) NULL AFTER location;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS event_address VARCHAR(255) NULL AFTER location;

ALTER TABLE library_events
    ADD COLUMN IF NOT EXISTS registration_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER capacity;

INSERT INTO permissions (name, slug, created_at)
VALUES
    ('Gerenciar pessoas internas', 'people.manage', NOW()),
    ('Gerenciar eventos internos', 'events.manage', NOW()),
    ('Gerenciar participantes de eventos', 'event_participants.manage', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions
    ON permissions.slug IN ('people.manage', 'events.manage', 'event_participants.manage')
WHERE roles.slug IN ('master', 'admin', 'admin-local', 'voluntario');
