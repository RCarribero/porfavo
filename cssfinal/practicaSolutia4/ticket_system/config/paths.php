<?php
/**
 * Archivo de configuración de rutas para el sistema de tickets
 * Este archivo centraliza todas las rutas importantes del sistema
 * para facilitar su mantenimiento y evitar inconsistencias
 */

// Definir la raíz del proyecto (ruta absoluta)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__, 3)) . '/');
}

// Definir la URL base del proyecto
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . '://' . $host . '/porfavo/solutia/cssfinal/practicaSolutia4/');
}

// Definir la URL del sistema de tickets
if (!defined('SYSTEM_URL')) {
    define('SYSTEM_URL', BASE_URL . 'ticket_system/');
}

// Definir rutas absolutas para directorios importantes
define('CONFIG_PATH', ROOT_PATH . 'ticket_system/config/');
define('CONTROLLERS_PATH', ROOT_PATH . 'ticket_system/controllers/');
define('MODELS_PATH', ROOT_PATH . 'ticket_system/models/');
define('VIEWS_PATH', ROOT_PATH . 'ticket_system/views/');
define('ASSETS_PATH', ROOT_PATH . 'ticket_system/assets/');
define('UPLOADS_PATH', ROOT_PATH . 'ticket_system/uploads/');

// Definir URLs para directorios importantes
define('ASSETS_URL', SYSTEM_URL . 'assets/');
define('UPLOADS_URL', SYSTEM_URL . 'uploads/');

/**
 * Función para obtener la ruta absoluta dentro del sistema
 * @param string $path Ruta relativa dentro del proyecto 
 * @return string Ruta absoluta completa
 */
function getSystemPath($path) {
    return ROOT_PATH . $path;
}

/**
 * Función para obtener la URL absoluta dentro del sistema
 * @param string $path Ruta relativa dentro del proyecto
 * @return string URL absoluta completa
 */
function getSystemUrl($path) {
    return SYSTEM_URL . $path;
}
?>
