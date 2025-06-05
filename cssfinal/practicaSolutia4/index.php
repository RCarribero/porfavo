<?php
// Incluir el archivo de configuración de rutas
require_once __DIR__ . '/ticket_system/config/paths.php';

// Iniciar sesión
session_start();

// Redirigir al login si no está logueado y no está accediendo al login
if (empty($_SESSION['user_id']) && (($_GET['controller'] ?? '') !== 'user' || ($_GET['action'] ?? '') !== 'login')) {
    header('Location: ' . SYSTEM_URL . 'views/sesion/login.php');
    exit;
}

// Incluir los controladores
require_once CONTROLLERS_PATH . 'UserController.php';
require_once CONTROLLERS_PATH . 'CategoryController.php';
require_once CONTROLLERS_PATH . 'ReportController.php';
require_once CONTROLLERS_PATH . 'AdminController.php';

// Determinar el controlador a utilizar
$controller = $_GET['controller'] ?? 'admin';
$action = $_GET['action'] ?? 'dashboard';

// Crear la instancia del controlador correspondiente
switch ($controller) {
    case 'user':
        $controllerInstance = new UserController();
        break;
    case 'category':
        $controllerInstance = new CategoryController();
        break;
    case 'report':
        $controllerInstance = new ReportController();
        break;
    case 'admin':
        $controllerInstance = new AdminController();
        break;
    default:
        echo 'Controlador no encontrado: ' . $controller;
        exit;
}

// Llamar al método correspondiente
if (method_exists($controllerInstance, $action)) {
    $controllerInstance->$action();
} else {
    // Si el método no existe, mostrar un error
    echo 'Acción no encontrada: ' . $action . ' en el controlador ' . $controller;
}
?>
