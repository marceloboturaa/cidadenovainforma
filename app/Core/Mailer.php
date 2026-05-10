<?php

namespace App\Core;

class Mailer
{
    public static function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';
        $mailer = strtolower((string) ($config['mailer'] ?? 'mail'));
        $text ??= trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

        if ($mailer === 'smtp') {
            return self::sendSmtp($config, $to, $subject, $html, $text);
        }

        return self::sendMail($config, $to, $subject, $html, $text);
    }

    private static function sendMail(array $config, string $to, string $subject, string $html, string $text): bool
    {
        $boundary = 'cni_' . bin2hex(random_bytes(16));
        $from = self::formatAddress((string) $config['from_address'], (string) $config['from_name']);
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $from,
            'Reply-To: ' . $from,
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $body = self::multipartBody($boundary, $text, $html);

        return mail($to, self::encodedSubject($subject), $body, implode("\r\n", $headers));
    }

    private static function sendSmtp(array $config, string $to, string $subject, string $html, string $text): bool
    {
        $host = (string) ($config['host'] ?? '');
        $port = (int) ($config['port'] ?? 587);
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $encryption = strtolower((string) ($config['encryption'] ?? 'tls'));
        $password = self::normalizedPassword($host, $password);

        if ($host === '' || $username === '' || $password === '') {
            throw new \RuntimeException('Configuração SMTP incompleta.');
        }

        $transportHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $socket = stream_socket_client($transportHost . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            throw new \RuntimeException('Falha ao conectar ao SMTP: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($socket, 20);
        self::expect($socket, [220]);
        self::command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);

        if ($encryption === 'tls') {
            self::command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \RuntimeException('Falha ao iniciar TLS no SMTP.');
            }
            self::command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        }

        self::command($socket, 'AUTH LOGIN', [334]);
        self::command($socket, base64_encode($username), [334]);
        self::command($socket, base64_encode($password), [235]);

        $fromAddress = (string) ($config['from_address'] ?: $username);
        self::command($socket, 'MAIL FROM:<' . self::cleanEmail($fromAddress) . '>', [250]);
        self::command($socket, 'RCPT TO:<' . self::cleanEmail($to) . '>', [250, 251]);
        self::command($socket, 'DATA', [354]);

        $boundary = 'cni_' . bin2hex(random_bytes(16));
        $headers = [
            'From: ' . self::formatAddress($fromAddress, (string) $config['from_name']),
            'To: <' . self::cleanEmail($to) . '>',
            'Subject: ' . self::encodedSubject($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . self::multipartBody($boundary, $text, $html);
        fwrite($socket, self::dotEscape($message) . "\r\n.\r\n");
        self::expect($socket, [250]);
        self::command($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    }

    private static function command($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expectedCodes);
    }

    private static function expect($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException('Resposta SMTP inesperada: ' . trim($response));
        }

        return $response;
    }

    private static function multipartBody(string $boundary, string $text, string $html): string
    {
        return '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $text . "\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $html . "\r\n\r\n"
            . '--' . $boundary . "--\r\n";
    }

    private static function formatAddress(string $email, string $name): string
    {
        $email = self::cleanEmail($email);
        $name = trim(str_replace(["\r", "\n", '"'], '', $name));

        return $name !== '' ? '"' . $name . '" <' . $email . '>' : '<' . $email . '>';
    }

    private static function cleanEmail(string $email): string
    {
        return str_replace(["\r", "\n", '<', '>'], '', trim($email));
    }

    private static function normalizedPassword(string $host, string $password): string
    {
        if (strtolower($host) === 'smtp.gmail.com') {
            return preg_replace('/\s+/', '', $password) ?? $password;
        }

        return $password;
    }

    private static function encodedSubject(string $subject): string
    {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    private static function dotEscape(string $message): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = preg_replace('/^\./m', '..', $message) ?? $message;

        return str_replace("\n", "\r\n", $message);
    }
}
