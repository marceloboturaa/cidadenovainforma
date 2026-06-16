<?php

require_once __DIR__ . '/env.php';

return [
    'driver' => 'mysql',
    'host' => env_value('DB_HOST', '127.0.0.1'),
    'port' => env_value('DB_PORT', '3306'),
    'database' => env_value('DB_DATABASE', 'cidadenovainforma'),
    'username' => env_value('DB_USERNAME', 'root'),
    'password' => env_value('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
