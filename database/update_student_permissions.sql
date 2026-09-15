-- Restrict grants attached to the student role. Does not reclassify existing users.
START TRANSACTION;
DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.slug = 'estudante' AND p.slug <> 'education.view';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.slug = 'estudante' AND p.slug = 'education.view';
COMMIT;
