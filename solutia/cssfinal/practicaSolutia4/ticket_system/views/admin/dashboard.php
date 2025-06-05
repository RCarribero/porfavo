<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Verificar autenticación de forma robusta
if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

// Verificación directa del rol sin necesidad de consultar la base de datos
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    error_log("Intento de acceso al panel de administrador con rol no autorizado: " . ($_SESSION['role'] ?? 'rol no definido'));
    header('Location: ../cliente/dashboard.php');
    exit();
}

// Obtener datos del usuario para uso posterior (verificación ya se hizo arriba)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Consulta adicional para obtener datos completos del usuario (si es necesario)
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$user_id]);
    $admin_data = $stmt->fetch();
    
    // Verificación adicional de seguridad
    if (!$admin_data) {
        error_log("Error crítico: Usuario con ID {$user_id} no encontrado en la base de datos o no es administrador");
        // Destruir sesión por seguridad
        session_destroy();
        header('Location: ../sesion/login.php?error=session_invalid');
        exit();
    }
    
    // Reforzar el rol en la sesión para prevenir manipulaciones
    $_SESSION['role'] = $admin_data['role'];
    $_SESSION['admin_auth'] = true;
    
    // Actualizar nombre de usuario en la sesión para mostrar correctamente en el header
    if (isset($admin_data['username']) && !empty($admin_data['username'])) {
        $_SESSION['username'] = $admin_data['username'];
    } elseif (isset($admin_data['name']) && !empty($admin_data['name'])) {
        $_SESSION['username'] = $admin_data['name'];
    }
    
} catch(PDOException $e) {
    // Si hay error en la base de datos, continuar (mostrar dashboard)
    error_log("Error al verificar rol de administrador: " . $e->getMessage());
}

// Definir BASE_PATH correctamente
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/porfavo/solutia/cssfinal/practicaSolutia4/'); // Ruta relativa desde la raíz del servidor
}

require_once __DIR__ . '/../partials/header.php';
?>

<style>
    :root {
        --color-primary: #3498db;
        --color-primary-dark: #2c3e50;
        --color-bg: #f8f9fa;
        --color-text: #343a40;
        --color-card: #ffffff;
        --color-border: #dee2e6;
        --color-success: #28a745;
        --color-danger: #dc3545;
        --color-warning: #ffc107;
        --color-info: #3498db;
        --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background-color: var(--color-bg);
        color: var(--color-text);
        transition: all 0.3s ease;
    }

    body.dark-mode {
        --color-primary: #ff8c42;
        --color-primary-dark: #2c3e50;
        --color-bg: #121212;
        --color-text: #f8f9fa;
        --color-card: #1e1e1e;
        --color-border: #444;
        --shadow-sm: 0 2px 4px rgba(255,255,255,0.1);
        --shadow-md: 0 4px 6px rgba(255,255,255,0.1);
        --shadow-lg: 0 10px 15px rgba(255,255,255,0.1);
    }

    .dashboard-container {
        background-color: var(--color-card);
        border-radius: 15px;
        padding: 35px;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
        border: 1px solid var(--color-border);
    }

    .dashboard-title {
        color: var(--color-primary);
        margin-bottom: 30px;
        font-weight: 700;
        border-bottom: 3px solid var(--color-primary);
        padding-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 1.5rem;
    }

    /* Eliminar subrayado de enlaces y botones */
    a, button {
        text-decoration: none !important;
        outline: none !important;
    }

    a:hover, button:hover {
        text-decoration: none !important;
        outline: none !important;
    }

    a:focus, button:focus {
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    a:active, button:active {
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .admin-card {
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        background-color: var(--color-card);
        border-left: 5px solid var(--color-primary);
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .admin-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--color-primary);
    }

    .admin-card-header {
        background-color: var(--color-primary);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 1.2rem;
    }

    .admin-card-header i {
        font-size: 1.5rem;
    }

    .admin-card-body {
        padding: 20px;
    }

    .admin-card-title {
        color: var(--color-text);
        margin-bottom: 15px;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .admin-card-text {
        color: var(--color-text);
        opacity: 0.8;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .admin-card-link {
        background-color: var(--color-primary);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 1rem;
    }

    .admin-card-link:hover {
        background-color: var(--color-primary-dark);
        transform: translateY(-3px);
        color: white;
        box-shadow: var(--shadow-md);
    }

    .admin-section {
        margin-bottom: 40px;
    }

    .admin-section:last-child {
        margin-bottom: 0;
    }

    .admin-section-title {
        color: var(--color-primary);
        margin-bottom: 25px;
        font-weight: 700;
        border-bottom: 3px solid var(--color-primary);
        padding-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 1.3rem;
    }

    .admin-section-title i {
        font-size: 1.5rem;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .admin-card {
        animation: fadeIn 0.5s ease-out;
    }

    .admin-section-title {
        animation: fadeIn 0.5s ease-out;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="main-title">
            <i class="fas fa-tachometer-alt"></i>
            <span>Panel de Administración</span>
        </h2>
    </div>
    
    <div class="dashboard-container">
        <div class="admin-section">
            <h3 class="admin-section-title">
                <i class="fas fa-tools"></i>
                <span>Herramientas de Administración</span>
            </h3>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <i class="fas fa-users me-2"></i> Usuarios
                        </div>
                        <div class="admin-card-body">
                            <h5 class="admin-card-title">Gestión de Usuarios</h5>
                            <p class="admin-card-text">Administra los usuarios del sistema.</p>
                            <a href="../users/index.php" class="admin-card-link">
                                <i class="fas fa-arrow-right me-2"></i>
                                <span>Ir a Usuarios</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <i class="fas fa-tags me-2"></i> Categorías
                        </div>
                        <div class="admin-card-body">
                            <h5 class="admin-card-title">Gestión de Categorías</h5>
                            <p class="admin-card-text">
                                <?php if (isset($categories) && count($categories) > 0): ?>
                                    Hay <?php echo count($categories); ?> categorías registradas.
                                <?php else: ?>
                                    No hay categorías registradas.
                                <?php endif; ?>
                            </p>
                            <a href="../categories/index.php" class="admin-card-link">
                                <i class="fas fa-arrow-right me-2"></i>
                                <span>Ir a Categorías</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <i class="fas fa-chart-bar me-2"></i> Reportes
                        </div>
                        <div class="admin-card-body">
                            <h5 class="admin-card-title">Informes y Estadísticas</h5>
                            <p class="admin-card-text">Visualiza reportes del sistema.</p>
                            <a href="../reports/dashboard.php" class="admin-card-link">
                                <i class="fas fa-arrow-right me-2"></i>
                                <span>Ir a Reportes</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h3 class="admin-section-title">
                <i class="fas fa-file-alt"></i>
                <span>Informes Personalizados</span>
            </h3>
            
            <div class="admin-card">
                <div class="admin-card-header">
                    <i class="fas fa-file-alt me-2"></i> Informes Personalizados
                </div>
                <div class="admin-card-body">
                    <h5 class="admin-card-title">Generar Informes Personalizados</h5>
                    <p class="admin-card-text">Crea informes personalizados según tus necesidades.</p>
                    <a href="../reports/custom_report.php" class="admin-card-link w-100">
                        <i class="fas fa-plus me-2"></i>
                        <span>Crear Informe Personalizado</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
 
<script>
    // Script para el modo oscuro/claro
    document.addEventListener('DOMContentLoaded', function() {
        const themeButton = document.getElementById('theme-button');
        const body = document.body;
        const icon = themeButton.querySelector('i');

        // Cargar preferencia al inicio
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
        }

        themeButton.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDarkMode = body.classList.contains('dark-mode');
            
            if (isDarkMode) {
                themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                themeButton.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    });
</script>

<?php 
// Corregir la ruta del footer usando ruta relativa directa
require_once __DIR__ . '/../partials/footer.php'; 
?>