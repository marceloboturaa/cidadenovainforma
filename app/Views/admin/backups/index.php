<div class="page-heading">
    <div>
        <p>Segurança do conteúdo</p>
        <h1>Backups</h1>
    </div>
</div>

<section class="panel backup-panel">
    <h2>Backup completo do conteúdo</h2>
    <p>
        Baixe um pacote com o banco de dados e as imagens enviadas em `public/uploads`.
        Use esse arquivo para guardar uma cópia local ou migrar as matérias para a hospedagem.
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
