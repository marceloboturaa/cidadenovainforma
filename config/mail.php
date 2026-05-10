<?php

require_once __DIR__ . '/env.php';

return [
    'mailer' => getenv('MAIL_MAILER') ?: 'mail',
    'host' => getenv('MAIL_HOST') ?: '',
    'port' => (int) (getenv('MAIL_PORT') ?: 587),
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'username' => getenv('MAIL_USERNAME') ?: '',
    'password' => getenv('MAIL_PASSWORD') ?: '',
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: (getenv('MAIL_USERNAME') ?: 'no-reply@localhost'),
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Cidade Nova Informa',
];
