<?php

return [
    'name' => 'Cidade Nova Informa',
    'base_url' => getenv('APP_URL') ?: 'http://localhost/cidadenovainforma',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (bool) (getenv('APP_DEBUG') ?: true),
    'session_name' => 'cni_session',
];
