<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Middleware;

class MediaController
{
    public function tinyMceUpload(): void
    {
        Middleware::auth();

        header('Content-Type: application/json; charset=utf-8');

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['error' => 'Sessao expirada. Atualize a pagina e tente novamente.']);
            return;
        }

        if (empty($_FILES['file']['name']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['error' => 'Envie uma imagem valida.']);
            return;
        }

        $tmpName = (string) ($_FILES['file']['tmp_name'] ?? '');
        $size = (int) ($_FILES['file']['size'] ?? 0);
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];

        if (!$imageInfo || !isset($allowedTypes[$imageInfo[2] ?? 0]) || $size <= 0 || $size > 8 * 1024 * 1024) {
            http_response_code(422);
            echo json_encode(['error' => 'Use JPG, PNG, WEBP ou GIF com ate 8MB.']);
            return;
        }

        $directory = dirname(__DIR__, 3) . '/public/uploads/editor';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            http_response_code(500);
            echo json_encode(['error' => 'A pasta de uploads nao esta gravavel.']);
            return;
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$imageInfo[2]];
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            http_response_code(500);
            echo json_encode(['error' => 'Nao foi possivel salvar a imagem.']);
            return;
        }

        echo json_encode(['location' => url('/public/uploads/editor/' . $filename)], JSON_UNESCAPED_SLASHES);
    }
}
