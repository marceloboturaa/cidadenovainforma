# Publicar o site online

Este guia serve para levar as matérias criadas no localhost para a hospedagem.

## 1. Baixar backup no painel

No localhost:

1. Acesse `/login`.
2. Entre com o usuário MASTER.
3. Vá em `Backups`.
4. Clique em `Baixar backup agora`.

O arquivo `.zip` gerado contém:

- `database.sql`: banco com matérias, usuários, categorias, tags, menu e configurações editoriais.
- `public/uploads`: imagens enviadas nas matérias.
- `manifest.json`: resumo do pacote.

## 2. Enviar os arquivos do sistema

Na hospedagem, envie todos os arquivos do projeto por FTP, cPanel ou gerenciador de arquivos.

Arquivos/pastas importantes:

- `app`
- `config`
- `database`
- `public`
- `routes`
- `storage`
- `index.php`
- `.htaccess`

## 3. Criar banco na hospedagem

No painel da hospedagem:

1. Crie um banco MySQL.
2. Crie um usuário do banco.
3. Dê permissão total desse usuário ao banco.
4. Anote host, banco, usuário e senha.

## 4. Importar as matérias

No phpMyAdmin da hospedagem:

1. Selecione o banco criado.
2. Vá em `Importar`.
3. Envie o arquivo `database.sql` que veio dentro do backup.
4. Execute a importação.

## 5. Enviar imagens

Extraia o backup no computador e envie a pasta:

```text
public/uploads
```

para o mesmo caminho no servidor:

```text
public/uploads
```

Sem isso, as matérias aparecem, mas as imagens de capa podem ficar quebradas.

## 6. Configurar banco online

Edite `config/database.php` no servidor com os dados da hospedagem:

```php
return [
    'driver' => 'mysql',
    'host' => 'HOST_DA_HOSPEDAGEM',
    'port' => '3306',
    'database' => 'NOME_DO_BANCO',
    'username' => 'USUARIO_DO_BANCO',
    'password' => 'SENHA_DO_BANCO',
    'charset' => 'utf8mb4',
];
```

Também ajuste `config/app.php`:

```php
'base_url' => 'https://seudominio.com.br',
```

## 7. Conferência final

Abra no navegador:

- `/`
- `/reprise`
- `/login`
- `/admin/news`
- `/sitemap.xml`
- `/robots.txt`

Confira se:

- As matérias aparecem.
- As imagens carregam.
- O menu está correto.
- O login MASTER funciona.
- O sitemap abre.

## Rotina recomendada de backup

Antes de publicar novas mudanças ou mexer no banco online:

1. Baixe um backup do site online.
2. Guarde o arquivo em uma pasta com a data.
3. Só depois faça atualizações.

Exemplo de organização:

```text
Backups Cidade Nova/
2026-05-06 antes de publicar.zip
2026-05-10 depois de novas matérias.zip
```
