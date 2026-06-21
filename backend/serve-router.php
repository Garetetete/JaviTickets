<?php

// Router para el servidor embebido de PHP (php -S) apuntando a public/.
// Equivale al server.php que usa `artisan serve`.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Servir archivos estáticos existentes en public/ tal cual.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
