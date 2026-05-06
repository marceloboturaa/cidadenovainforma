<?php

return [
    'name' => 'Cidade Nova Informa',
    'base_url' => getenv('APP_URL') ?: '',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'session_name' => 'cni_session',
];
