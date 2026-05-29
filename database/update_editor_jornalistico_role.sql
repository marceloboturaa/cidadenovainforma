-- Adiciona o cargo de Editor Jornalístico.
-- Execute no phpMyAdmin ou no cliente MySQL do banco do site.
-- Nao apaga dados existentes.

INSERT IGNORE INTO roles (name, slug, level, created_at, updated_at)
VALUES ('EDITOR JORNALISTICO', 'editor-jornalistico', 45, NOW(), NOW());

UPDATE roles
SET name = 'EDITOR JORNALISTICO',
    level = 45,
    updated_at = NOW()
WHERE slug = 'editor-jornalistico';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions
    ON permissions.slug IN (
        'news.manage',
        'news.approve',
        'news.create',
        'categories.manage',
        'tags.manage',
        'comments.moderate'
    )
WHERE roles.slug = 'editor-jornalistico';
