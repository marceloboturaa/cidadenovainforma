<?php

namespace App\Core;

class WhatsAppNotifier
{
    public static function sendText(?string $phone, string $message): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/whatsapp.php';

        if (empty($config['enabled']) || ($config['provider'] ?? '') !== 'meta') {
            return false;
        }

        $token = trim((string) ($config['token'] ?? ''));
        $phoneNumberId = trim((string) ($config['phone_number_id'] ?? ''));
        $recipient = self::normalizePhone($phone);

        if ($token === '' || $phoneNumberId === '' || $recipient === '') {
            return false;
        }

        $apiVersion = preg_replace('/[^v0-9.]/', '', (string) ($config['api_version'] ?? 'v20.0')) ?: 'v20.0';
        $url = 'https://graph.facebook.com/' . $apiVersion . '/' . rawurlencode($phoneNumberId) . '/messages';
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ], JSON_UNESCAPED_UNICODE);

        if (!$payload || !function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return true;
        }

        Logger::info('event_registration.whatsapp_failed', 'Falha ao enviar WhatsApp: HTTP ' . $status . ' ' . ($error ?: (string) $response), null);
        return false;
    }

    private static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }
}
