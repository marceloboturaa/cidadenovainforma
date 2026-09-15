<?php
// Schedule every 15 minutes: php /path/to/project/bin/notify-certificates.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/Helpers/functions.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) { require $file; }
    }
});
$config = require dirname(__DIR__) . '/config/app.php';
if (!filter_var($config['base_url'], FILTER_VALIDATE_URL)) {
    fwrite(STDERR, "Configure APP_URL com o endereço público do site antes de enviar avisos.\n");
    exit(1);
}
$result = \App\Models\CertificateNotification::processPending();
echo json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($result['failed'] > 0 ? 1 : 0);
