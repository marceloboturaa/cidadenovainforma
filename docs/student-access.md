# Acesso dos estudantes

A política `App\Core\StudentAccess` limita contas com o cargo `estudante`,
mesmo quando também possuem outro cargo. Para exercer funções da equipe,
o administrador precisa revisar os cargos e retirar o cargo de estudante.

Permitido: painel pessoal, cadastro e senha, cursos, aulas, materiais,
entregas, respostas nos fóruns dos cursos e certificados próprios.
As verificações de matrícula e propriedade continuam nos controladores.
Comunicação geral, fórum geral e funções administrativas ficam bloqueados.
Novas rotas administrativas são negadas por padrão para estudantes.

O bloqueio funciona no código mesmo com permissões antigas no banco.
`database/update_student_permissions.sql` remove concessões incompatíveis do
cargo e mantém `education.view`; executar no banco de destino ao publicar.

Cadastros vinculados a cursos agora recebem o cargo estudante. Contas antigas
criadas incorretamente como jornalista precisam de revisão em Usuários:
não são convertidas automaticamente, pois podem ter funções legítimas na equipe.
O cadastro geral continua usando a regra existente de jornalista com aprovação.

Verificação local:

```
php tests/student_access.php
php tests/admin_navigation.php
php tests/certificate_teacher_approval.php
```
