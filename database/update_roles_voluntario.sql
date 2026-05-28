-- Padronizacao de cargos: EQUIPE passa a ser VOLUNTARIO.
-- O slug antigo e renomeado para preservar usuarios e permissoes existentes.

INSERT INTO roles (name, slug, level, created_at, updated_at)
SELECT 'VOLUNTARIO', 'voluntario', 20, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'voluntario');

SET @voluntario_role_id := (SELECT id FROM roles WHERE slug = 'voluntario' LIMIT 1);
SET @equipe_role_id := (SELECT id FROM roles WHERE slug = 'equipe' LIMIT 1);

UPDATE users
SET role_id = @voluntario_role_id,
    updated_at = NOW()
WHERE @equipe_role_id IS NOT NULL
  AND role_id = @equipe_role_id;

INSERT IGNORE INTO user_roles (user_id, role_id, created_at)
SELECT user_id, @voluntario_role_id, COALESCE(created_at, NOW())
FROM user_roles
WHERE @equipe_role_id IS NOT NULL
  AND role_id = @equipe_role_id;

DELETE FROM user_roles
WHERE @equipe_role_id IS NOT NULL
  AND role_id = @equipe_role_id;

DELETE FROM role_permissions
WHERE @equipe_role_id IS NOT NULL
  AND role_id = @equipe_role_id;

DELETE FROM roles
WHERE @equipe_role_id IS NOT NULL
  AND id = @equipe_role_id;

UPDATE roles
SET name = 'VOLUNTARIO',
    level = 20,
    updated_at = NOW()
WHERE id = @voluntario_role_id;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug IN (
    'people.manage',
    'events.manage',
    'event_participants.manage',
    'forum.view',
    'forum.create'
)
WHERE roles.slug = 'voluntario';

DELETE role_permissions
FROM role_permissions
INNER JOIN roles ON roles.id = role_permissions.role_id
INNER JOIN permissions ON permissions.id = role_permissions.permission_id
WHERE roles.slug = 'voluntario'
  AND permissions.slug IN (
    'documents.view',
    'education.manage',
    'education.teach',
    'users.manage',
    'permissions.manage',
    'logs.view',
    'news.manage',
    'news.approve',
    'categories.manage',
    'tags.manage',
    'comments.moderate',
    'ads.manage',
    'regions.manage',
    'menu.manage',
    'documents.manage'
  );
