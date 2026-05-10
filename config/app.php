<?php

require_once __DIR__ . '/env.php';

return [
    'name' => 'Cidade Nova Informa',
    'base_url' => getenv('APP_URL') ?: '',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'trusted_hosts' => array_values(array_filter(array_map('trim', explode(',', getenv('TRUSTED_HOSTS') ?: '')))),
    'session_name' => 'cni_session',
    'session_timeout' => (int) (getenv('SESSION_TIMEOUT') ?: 1800),
    'session_regenerate_interval' => (int) (getenv('SESSION_REGENERATE_INTERVAL') ?: 600),
    'backup_key' => getenv('BACKUP_KEY') ?: hash('sha256', __DIR__ . '|cidadenovainforma'),
];
