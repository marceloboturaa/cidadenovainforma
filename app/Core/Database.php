<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            self::logConnectionError($exception, $config);
            http_response_code(500);
            exit('Erro ao conectar ao banco de dados.');
        }

        return self::$connection;
    }

    private static function logConnectionError(PDOException $exception, array $config): void
    {
        $message = sprintf(
            "[%s] %s | host=%s port=%s database=%s username=%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            (string) ($config['host'] ?? ''),
            (string) ($config['port'] ?? ''),
            (string) ($config['database'] ?? ''),
            (string) ($config['username'] ?? '')
        );

        $dir = dirname(__DIR__, 2) . '/storage/temp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            error_log('Database connection error: ' . $message);
            return;
        }

        @file_put_contents($dir . '/database-error.log', $message, FILE_APPEND);
        error_log('Database connection error: ' . trim($message));
    }
}
