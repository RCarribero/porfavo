<?php
/**
 * Manejador de errores centralizado
 * Incluir este archivo en los scripts principales para gestionar errores 404
 */

function handle404Error() {
    header("HTTP/1.0 404 Not Found");
    include_once __DIR__ . '/../views/errors/404.php';
    exit();
}

// Ejemplo de uso:
// if (!file_exists($requested_file)) {
//    handle404Error();
// }
