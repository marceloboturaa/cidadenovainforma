<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        $content = ob_get_clean();

        require dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
    }
}
