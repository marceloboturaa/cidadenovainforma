# Avisos de certificado pronto

Ao emitir ou aprovar um certificado de curso para um usuário cadastrado, o sistema
cria um aviso privado com o botão **Abrir certificado** e tenta enviar um e-mail.
Também cobre a liberação automática e certificados de reconhecimento vinculados
a usuários. Pessoas sem conta e certificados de eventos usam fluxos distintos.

O aviso aparece no sino **Avisos** e no painel do estudante; pode ser marcado como
lido. O link exige login e mantém as verificações de propriedade do certificado.

## Publicação

1. Aplicar `database/update_certificate_notifications.sql` (a aplicação também
   cria a tabela automaticamente, seguindo o padrão atual do projeto).
2. Configurar o e-mail em `config/mail.php`/variáveis de ambiente e `APP_URL`
   com o endereço público completo do site.
3. Agendar a cada 15 minutos:

   ```sh
   php /caminho/do/projeto/bin/notify-certificates.php
   ```

A rotina processa até 50 certificados por execução, incluindo certificados já
emitidos antes desta alteração. Não é necessário que o estudante entre no site.
Sem agendamento, novos certificados são notificados na emissão, mas falhas e
certificados antigos dependem da execução manual dessa rotina.

`certificate_notifications` registra o aviso, as tentativas, a última falha e
`email_sent_at`. Esse horário significa aceitação pelo serviço de e-mail, não
confirmação de entrega ou leitura. Tentativas respeitam intervalo de 15 minutos.
Envios concluídos não são repetidos; marcar o aviso como lido não dispara outro.
Uma interrupção entre o envio SMTP e o registro no banco pode causar repetição
na próxima tentativa. O registro é por certificado, inclusive se for reemitido.

Validação: `php tests/certificate_notifications.php` usa banco SQLite temporário
em memória e remetente simulado; não envia e-mails reais.
