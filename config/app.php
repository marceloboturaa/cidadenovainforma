<?php

return [
    'name' => 'Cidade Nova Informa',
    'base_url' => getenv('APP_URL') ?: '',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'session_name' => 'cni_session',
    'session_timeout' => (int) (getenv('SESSION_TIMEOUT') ?: 1800),
    'session_regenerate_interval' => (int) (getenv('SESSION_REGENERATE_INTERVAL') ?: 600),
];
