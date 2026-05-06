# Cidade Nova Informa

Portal jornalístico em PHP, MySQL, JavaScript, HTML5, CSS3 e arquitetura MVC.

## Etapa 1 entregue: autenticação e permissões

Esta primeira base implementa:

- Front controller em `index.php`.
- Rotas em `routes/web.php`.
- Camada MVC em `app/Controllers`, `app/Models` e `app/Views`.
- Conexão PDO segura em `app/Core/Database.php`.
- Login com `password_hash` e `password_verify`.
- Sessão HTTP-only com `SameSite=Lax`.
- Proteção CSRF em formulários POST.
- Middleware de autenticação e permissão.
- Cargos: MASTER, ADMIN, ADMIN LOCAL e JORNALISTA.
- Permissões vinculadas por cargo.
- Recuperação de senha com token seguro.
- Dashboard administrativo inicial.
- Cadastro inicial de usuários pelo painel.
- Logs de eventos do sistema.

## Etapa 2 entregue: painel administrativo e CRUD editorial

O painel administrativo possui:

- Indicadores gerais de usuários, notícias, pendências e comentários.
- Quadro de status editorial: rascunho, pendente, rejeitada, publicada e arquivada.
- Listagem de notícias recentes.
- Área para gráfico simples de acessos, preparada para a página pública.
- Logs recentes do sistema.

O CRUD de notícias implementa:

- Criação de notícia, reportagem, artigo ou coluna.
- Edição de matérias.
- Salvamento como rascunho.
- Envio para aprovação.
- Aprovação e publicação por usuários com permissão `news.approve`.
- Rejeição por usuários com permissão `news.approve`.
- Arquivamento.
- Categoria, tags, resumo, conteúdo e imagem de capa.
- Upload de capa em JPG, PNG ou WEBP até 3MB.
- Controle por permissão e autoria:
  jornalistas editam suas próprias matérias ainda não publicadas; administradores gerenciam o fluxo completo.

## Etapa 3 entregue: categorias, tags e portal público

O painel administrativo possui:

- CRUD completo de categorias.
- Categoria pai para subcategorias.
- Ativação/desativação de categorias.
- Remoção segura de categorias, mantendo notícias sem categoria em vez de apagar conteúdo.
- CRUD completo de tags.
- Contadores de uso em categorias e tags.

O portal público possui:

- Home em `/` com layout jornalístico responsivo.
- Destaque principal.
- Notícias urgentes.
- Últimas notícias.
- Mais lidas.
- Navegação por categorias.
- Busca pública em `/buscar?q=termo`.
- Listagem por categoria em `/categoria?slug=...`.
- Listagem por tag em `/tag?slug=...`.
- Página de matéria em `/noticia?slug=...`.
- Incremento de visualizações.
- Registro de acessos em `access_logs`.
- Meta description e Open Graph básicos.
- Lazy loading nas imagens das listas.

## Instalação local no XAMPP

1. Crie o banco e tabelas:

```bash
mysql -u root < database/schema.sql
```

2. Gere cargos, permissões, categorias e usuário MASTER:

```bash
php database/seed.php
```

O seed cria categorias iniciais: Geral, Cidade, Política, Educação, Saúde, Esporte, Cultura, Horta e Rádio.

3. Acesse:

```text
http://localhost/cidadenovainforma
```

Credenciais locais padrão:

```text
E-mail: master@cidadenovainforma.local
Senha: Master@12345
```

Para trocar as credenciais do seed:

```bash
set MASTER_EMAIL=seuemail@dominio.com
set MASTER_PASSWORD=SenhaForte123
php database/seed.php
```

## Configuração

As configurações ficam em:

- `config/app.php`
- `config/database.php`

Por padrão, o banco usado é `cidadenovainforma` com usuário `root` e senha vazia, padrão comum do XAMPP.

## Próximas melhorias

1. Galeria de mídias:
   upload múltiplo, reaproveitamento de imagens já enviadas e organização por matéria.

2. Comentários e interação:
   comentários públicos moderados, curtidas, compartilhamento por WhatsApp e botão de copiar link.

3. Rádio:
   player ao vivo, programação, podcasts e destaques da categoria Rádio.

4. Horta:
   página especial com calendário de plantio, dicas e agenda comunitária.

## Melhorias já incluídas

- Editor rico no formulário de notícias.
- URLs amigáveis: `/noticia/slug`, `/categoria/slug` e `/tag/slug`.
- SEO básico com `sitemap.xml`, `robots.txt`, canonical, Open Graph e dados estruturados para matérias.
- Menu público gerenciável pelo MASTER.
- Categoria Cidade renomeada para Bairro.
- Modo Acervo para republicar reportagens antigas com data original, autor original, fonte, link original e aviso editorial.
- Página pública `/acervo`.

## Como usar o Acervo

No painel, acesse `Notícias`, crie ou edite uma matéria e marque `Matéria de acervo`.

Preencha:

- Data original.
- Autor original.
- Fonte original.
- Link original.
- Observação editorial.

Na página pública da matéria, o sistema mostra um aviso de acervo antes do texto, explicando que é uma reportagem antiga e exibindo os dados da publicação original.

## Backups e publicação online

O MASTER pode acessar `Backups` no painel e baixar um pacote `.zip` com:

- `database.sql`
- `public/uploads`
- `manifest.json`

Use esse pacote para guardar uma cópia de segurança ou levar as matérias do localhost para a hospedagem.

Guia completo:

```text
docs/PUBLICAR_ONLINE.md
```
