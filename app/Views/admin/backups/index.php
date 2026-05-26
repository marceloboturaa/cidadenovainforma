<div class="page-heading">
    <div>
        <p>Segurança do conteúdo</p>
        <h1>Backups</h1>
    </div>
</div>

<section class="panel backup-panel">
    <h2>Backup completo do conteúdo</h2>
    <p>
        Baixe um pacote com o banco de dados, imagens enviadas em `public/uploads` e documentos de `storage/documents`.
        Use esse arquivo para guardar uma cópia local ou migrar o conteúdo do site para a hospedagem.
    </p>

    <?php if (!$canZip): ?>
        <div class="alert alert-danger">A extensão ZipArchive não está habilitada no PHP.</div>
    <?php endif; ?>

    <?php if (!$canShell): ?>
        <div class="alert alert-warning">A função shell_exec está desabilitada; o dump automático do banco pode não funcionar.</div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/backups/download')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary">Baixar backup agora</button>
    </form>

    <form method="post" action="<?= e(url('/admin/backups/import')) ?>" enctype="multipart/form-data" class="import-form mt-4">
        <?= csrf_field() ?>
        <label class="form-label" for="full_backup">Importar backup completo (.zip)</label>
        <input class="form-control" id="full_backup" name="full_backup" type="file" accept=".zip,application/zip" required>
        <p class="small text-muted m-0">Atualiza o banco pelo `database.sql` do backup e copia os arquivos de `public/uploads` e `storage/documents`.</p>
        <button class="btn btn-outline-danger">Importar backup completo</button>
    </form>
</section>

<section class="panel backup-panel">
    <h2>Exportar e importar notícias</h2>
    <p>
        Gere um pacote somente com as matérias, categorias, tags e imagens usadas nas matérias. Use essa opção para transferir notícias entre instalações sem substituir o banco inteiro.
    </p>

    <div class="backup-actions">
        <form method="post" action="<?= e(url('/admin/backups/news/export')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-primary">Exportar notícias</button>
        </form>

        <form method="post" action="<?= e(url('/admin/backups/news/import')) ?>" enctype="multipart/form-data" class="import-form">
            <?= csrf_field() ?>
            <label class="form-label" for="news_backup">Arquivo de notícias (.zip)</label>
            <input class="form-control" id="news_backup" name="news_backup" type="file" accept=".zip,application/zip" required>

            <label class="import-option">
                <input type="checkbox" name="mode" value="update">
                Atualizar notícias existentes com o mesmo slug
            </label>

            <button class="btn btn-primary">Importar notícias</button>
        </form>
    </div>
</section>

<section class="panel">
    <h2>Como subir no site online</h2>
    <ol class="deploy-list">
        <li>Envie os arquivos do projeto para a hospedagem por FTP, cPanel ou gerenciador de arquivos.</li>
        <li>Crie um banco MySQL na hospedagem.</li>
        <li>Importe o arquivo `database.sql` do backup pelo phpMyAdmin.</li>
        <li>Envie a pasta `public/uploads` do backup para `public/uploads` no servidor.</li>
        <li>Altere `config/database.php` com host, nome do banco, usuário e senha da hospedagem.</li>
        <li>Acesse `/login` no domínio e confira se as matérias, imagens e menu aparecem.</li>
    </ol>
</section>
