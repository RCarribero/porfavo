<?php
class AdminController {
    public function __construct() {
        session_start();
        // Verificar si el usuario está autenticado y es administrador
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /pruebafinal/index.php?controller=user&action=login');
            exit;
        }
    }
    
    public function dashboard() {
        // Cargar la vista del dashboard
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
    
    public function settings() {
        // Cargar la vista de configuración
        require_once __DIR__ . '/../views/admin/settings.php';
    }
}
?>
