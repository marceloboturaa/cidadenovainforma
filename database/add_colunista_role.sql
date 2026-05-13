INSERT INTO roles (name, slug, level, created_at, updated_at)
VALUES ('COLUNISTA', 'colunista', 35, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    level = VALUES(level),
    updated_at = NOW();

INSERT INTO permissions (name, slug, created_at)
VALUES ('Criar notícias', 'news.create', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug = 'news.create'
WHERE roles.slug = 'colunista'
ON DUPLICATE KEY UPDATE
    role_id = role_id;
