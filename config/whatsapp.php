<?php

require_once __DIR__ . '/env.php';

return [
    'provider' => getenv('WHATSAPP_API_PROVIDER') ?: '',
    'token' => getenv('WHATSAPP_API_TOKEN') ?: '',
    'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '',
    'api_version' => getenv('WHATSAPP_API_VERSION') ?: 'v25.0',
    'enabled' => filter_var(getenv('WHATSAPP_API_ENABLED') ?: false, FILTER_VALIDATE_BOOL),
];
