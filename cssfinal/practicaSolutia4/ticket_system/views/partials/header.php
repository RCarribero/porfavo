<?php 
// Verificar si BASE_PATH está definido
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__FILE__) . '/../../');
}

// Asegurarse de que BASE_PATH termine con una barra diagonal
if (substr(BASE_PATH, -1) !== '/') {
    define('BASE_PATH', BASE_PATH . '/');
}

// Incluir el archivo de rutas si no está incluido
if (!defined('SYSTEM_URL')) {
    require_once dirname(__FILE__, 4) . '/ticket_system/config/paths.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tickets</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery y DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Variables CSS globales -->
    <link rel="stylesheet" href="/solutia/cssfinal/practicaSolutia4/ticket_system/views/css/variables.css">
    
    <style>
        :root {
            --color-primary: #3498db;
            --color-primary-dark: #2980b9;
            --color-bg: #f8f9fa;
            --color-text: #343a40;
            --color-card: #ffffff;
            --color-border: #dee2e6;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            transition: all 0.3s ease;
        }

        body.dark-mode {
            --color-bg: #1a1a1a;
            --color-text: #ffffff;
            --color-card: #2d2d2d;
            --color-border: #3d3d3d;
        }

        .navbar {
            background-color: var(--color-card) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        body.dark-mode .navbar {
            background-color: var(--color-card) !important;
            box-shadow: 0 2px 4px rgba(255,255,255,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--color-primary) !important;
        }

        .nav-link {
            color: var(--color-text) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        body.dark-mode .nav-link {
            color: var(--color-text) !important;
        }

        .nav-link:hover {
            color: var(--color-primary) !important;
            opacity: 0.8;
        }

        .user-menu {
            color: var(--color-text);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-menu:hover {
            opacity: 0.8;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            background-color: var(--color-card);
        }

        body.dark-mode .dropdown-menu {
            box-shadow: 0 4px 6px rgba(255,255,255,0.1);
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--color-text);
        }

        body.dark-mode .dropdown-item {
            color: var(--color-text);
        }

        .dropdown-item:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        body.dark-mode .dropdown-item:hover {
            background-color: rgba(255, 140, 66, 0.1);
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .theme-toggle {
            color: var(--color-text);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
        }

        body.dark-mode .theme-toggle {
            color: var(--color-text);
        }

        .theme-toggle:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        body.dark-mode .theme-toggle:hover {
            background-color: rgba(255, 140, 66, 0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SYSTEM_URL; ?>views/admin/dashboard.php">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" 
                     alt="Logo" style="max-height: 40px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SYSTEM_URL; ?>views/admin/dashboard.php">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SYSTEM_URL; ?>views/reports/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SYSTEM_URL; ?>views/reports/custom_report.php">
                            <i class="fas fa-chart-bar me-1"></i> Informes
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="theme-toggle">
                        <i class="fas fa-moon"></i>
                        <span>Modo Oscuro</span>
                    </div>
                    <div class="dropdown ms-3">
                        <button class="btn user-menu dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php 
                                    if (isset($_SESSION['role'])) {
                                        if ($_SESSION['role'] === 'admin') {
                                            echo SYSTEM_URL . 'views/admin/gestionPerfilAdmin.php';
                                        } else if ($_SESSION['role'] === 'tech') {
                                            echo SYSTEM_URL . 'views/Tecnico/gestionPerfilTecnico.php';
                                        } else {
                                            echo SYSTEM_URL . 'views/cliente/perfil.php';
                                        }
                                    } else {
                                        echo SYSTEM_URL . 'views/sesion/login.php';
                                    }
                                ?>">
                                    <i class="fas fa-user-cog"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SYSTEM_URL; ?>views/sesion/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
