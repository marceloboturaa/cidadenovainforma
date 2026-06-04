-- Cria a permissao para gerenciar o aviso da pagina inicial.
-- MASTER tem acesso total pelo sistema. ADMIN recebe a permissao por padrao.
-- Outros cargos so devem receber esta permissao se forem liberados em Autorizações.

INSERT INTO permissions (name, slug, created_at)
VALUES ('Gerenciar aviso da pagina inicial', 'home_notice.manage', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
INNER JOIN permissions ON permissions.slug = 'home_notice.manage'
WHERE roles.slug = 'admin';
