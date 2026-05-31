-- Garante acesso ao sistema de inscricoes e exportacao para administradores.

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
