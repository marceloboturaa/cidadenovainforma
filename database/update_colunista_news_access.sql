-- Remove o acesso editorial padrao do cargo COLUNISTA.
-- Para liberar um colunista, atribua tambem um cargo com permissao editorial
-- em Usuarios, ou ajuste as permissoes do cargo em Autorizações.

DELETE role_permissions
FROM role_permissions
INNER JOIN roles ON roles.id = role_permissions.role_id
INNER JOIN permissions ON permissions.id = role_permissions.permission_id
WHERE roles.slug = 'colunista'
  AND permissions.slug IN ('news.create', 'news.manage', 'news.approve');
