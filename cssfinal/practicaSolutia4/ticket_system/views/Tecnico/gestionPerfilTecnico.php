<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

$sql = "SELECT username, email FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $_SESSION['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoUsername = trim($_POST['username']);
    $nuevoEmail = trim($_POST['email']);
    $nuevaPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (!empty($nuevoUsername) && !empty($nuevoEmail)) {
        if (!empty($nuevaPassword) && $nuevaPassword !== $confirmPassword) {
            $mensaje = "Las contraseñas no coinciden.";
        } else {
            $actualizaSQL = "UPDATE users SET username = :username, email = :email";
            $parametros = [
                'username' => $nuevoUsername,
                'email' => $nuevoEmail,
            ];

            if (!empty($nuevaPassword)) {
                $actualizaSQL .= ", password = :password";
                $parametros['password'] = password_hash($nuevaPassword, PASSWORD_DEFAULT);
            }

            $actualizaSQL .= " WHERE id = :id";
            $parametros['id'] = $_SESSION['id'];

            $stmt = $pdo->prepare($actualizaSQL);
            if ($stmt->execute($parametros)) {
                $mensaje = "Perfil actualizado correctamente.";
                $user['username'] = $nuevoUsername;
                $user['email'] = $nuevoEmail;
            } else {
                $mensaje = "Error al actualizar el perfil.";
            }
        }
    } else {
        $mensaje = "Nombre de usuario y correo no pueden estar vacíos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Perfil - Sistema de Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            /* Colores base */
            --primary-color: #3498db; /* Azul modo claro */
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --background-color: #ffffff; /* Fondo blanco para modo claro */
            --text-color: #343a40;
            --shadow-color: rgba(0,0,0,0.1);
            --card-bg: #f8f9fa;
            --border-color: #dee2e6;
            
            /* Estados */
            --status-open: #e74c3c;
            --status-in-progress: #f39c12;
            --status-resolved: #2ecc71;
            --status-closed: #7f8c8d;
            
            /* Espaciado */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            
            /* Radios */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-full: 9999px;
        }

        /* Modo oscuro */
        body.dark-mode {
            --primary-color: #ffa726; /* Naranja modo oscuro */
            --secondary-color: #ff7043;
            --accent-color: #ff4081;
            --background-color: #121212; /* Fondo oscuro */
            --text-color: #ffffff;
            --shadow-color: rgba(255,255,255,0.1);
            --card-bg: #1e1e1e;
            --border-color: #333;
            
            --status-open: #ff4081;
            --status-in-progress: #ffd740;
            --status-resolved: #4caf50;
            --status-closed: #9e9e9e;
        }

        /* Base styles */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Botones */
        button, .btn {
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        button[type="submit"], .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .btn-danger {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        /* Formularios */
        .form-control {
            padding: var(--space-sm);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            transition: border-color 0.2s ease;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        label {
            display: block;
            margin-bottom: var(--space-sm);
            color: var(--text-color);
            font-weight: 500;
        }

        /* Cards */
        .card {
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 6px var(--shadow-color);
            transition: transform 0.2s ease;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px var(--shadow-color);
        }

        .card-body {
            padding: var(--space-lg);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
        }

        /* Iconos de las cajitas */
        .card .fas {
            color: var(--text-color);
            transition: color 0.3s ease;
        }

        .dark-mode .card .fas {
            color: white;
        }

        /* Alertas */
        .alert {
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Header */
        .header {
            display: block;
            width: 100%;
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px var(--shadow-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md) 0;
        }

        /* Padding para el contenido principal */
        .main-content {
            padding-top: 8rem;
        }

        /* Navegación */
        nav {
            margin-bottom: var(--space-xl);
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        nav ul li {
            margin-bottom: var(--space-sm);
        }

        nav a {
            text-decoration: none;
            color: var(--text-color);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            display: inline-block;
        }

        nav a:hover, nav a.active {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        /* Padding para el contenido principal */
        .main-content {
            padding-top: 5rem;
        }

        /* Dashboard cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
        }

        .logo img {
            height: 40px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        /* Nav links */
        .nav-links {
            display: flex;
            gap: var(--space-md);
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            background-color: var(--primary-color);
            color: white;
        }

        .nav-links a:hover, .nav-links a.active {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }

        /* Theme toggle button */
        #theme-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        #theme-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        /* User menu */
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu > span {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            cursor: pointer;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-full);
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            min-width: 200px;
            background-color: var(--card-bg);
            box-shadow: 0 4px 6px var(--shadow-color);
            border-radius: var(--radius-md);
            padding: var(--space-sm) 0;
            z-index: 1;
            border: 1px solid var(--border-color);
        }

        .user-menu:hover .user-dropdown {
            display: block;
        }

        .user-dropdown a {
            display: block;
            padding: var(--space-sm) var(--space-md);
            color: var(--text-color);
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .user-dropdown a:hover {
            background-color: var(--background-color);
        }

        /* Main content */
        .main-content {
            padding: var(--space-md) 0;
        }

        /* Profile settings */
        .profile-settings {
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            margin-top: var(--space-lg);
            box-shadow: 0 4px 6px var(--shadow-color);
            border: 1px solid var(--border-color);
        }

        .profile-settings h2 {
            color: var(--text-color);
            margin-bottom: var(--space-lg);
            padding-bottom: var(--space-sm);
            border-bottom: 1px solid var(--border-color);
        }

        .profile-form {
            margin-top: var(--space-lg);
        }

        .form-group {
            margin-bottom: var(--space-md);
        }

        .action-buttons {
            display: flex;
            gap: var(--space-md);
            margin-top: var(--space-lg);
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Card titles */
        .card-title {
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .card-number {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        /* Dark mode alert adjustments */
        .dark-mode .alert-success {
            background-color: #155724;
            color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .dark-mode .alert-danger {
            background-color: #721c24;
            color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 0 var(--space-sm);
            }

            .header {
                flex-direction: column;
                gap: var(--space-md);
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
                margin-top: var(--space-md);
            }

            .nav-links {
                margin-top: var(--space-md);
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="light-mode">
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" alt="Logo del Sistema">
            </div>
            <div class="nav-links">
                <a href="../Tecnico/dashboardTecnico.php" class="active">Panel Técnico</a>
                <a href="../Tecnico/gestionPerfilTecnico.php">Editar Perfil</a>
            </div>
            <div class="header-right">
                <div class="theme-toggle">
                    <button id="theme-button" class="btn btn-primary">Modo Oscuro</button>
                </div>
                <div class="user-menu">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> ▼</span>
                    <div class="user-dropdown">
                        <a class="dropdown-item" href="../../../index.php?controller=user&action=logout">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">

        <main class="main-content">
            <!-- Resumen General -->
            <div class="dashboard-summary mb-4" style="margin-top: 4rem;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card h-100 card-total-tickets">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-user me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">Información de Usuario</h5>
                                    </div>
                                    <div class="badge bg-primary rounded-pill px-3 py-2">Técnico</div>
                                </div>
                                <h2 class="card-number"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 card-in-progress">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-envelope me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">Correo Electrónico</h5>
                                    </div>
                                    <div class="badge bg-primary rounded-pill px-3 py-2"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <h2 class="card-number"><?php echo substr_count($user['email'], '@') ? 'Válido' : 'Inválido'; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 card-urgent">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-key me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">Seguridad</h5>
                                    </div>
                                    <div class="badge bg-primary rounded-pill px-3 py-2">Contraseña</div>
                                </div>
                                <h2 class="card-number">********</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Perfil -->
            <div class="profile-settings">
                <h2>Gestión de Perfil</h2>
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo strpos($mensaje, 'Error') !== false ? 'danger' : 'success'; ?>">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="profile-form" onsubmit="return validarFormulario()">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Nombre de Usuario:</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Correo Electrónico:</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Nueva Contraseña (opcional):</label>
                                <input type="password" id="password" name="password" placeholder="Deja en blanco si no deseas cambiarla" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirmar Contraseña:</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la nueva contraseña" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='../Tecnico/dashboardTecnico.php'">Cancelar</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tema oscuro
            const themeButton = document.getElementById('theme-button');
            const body = document.body;
            
            // Verificar si hay un tema guardado en localStorage
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark-mode') {
                body.classList.add('dark-mode');
                body.classList.remove('light-mode');
                themeButton.innerHTML = 'Modo Claro';
            } else {
                body.classList.add('light-mode');
                body.classList.remove('dark-mode');
                themeButton.innerHTML = 'Modo Oscuro';
            }
            
            themeButton.addEventListener('click', function() {
                const isDark = body.classList.contains('dark-mode');
                if (isDark) {
                    body.classList.remove('dark-mode');
                    body.classList.add('light-mode');
                } else {
                    body.classList.remove('light-mode');
                    body.classList.add('dark-mode');
                }
                
                // Guardar el tema en localStorage
                localStorage.setItem('theme', isDark ? 'light-mode' : 'dark-mode');
                
                this.innerHTML = isDark ? 'Modo Oscuro' : 'Modo Claro';
            });
        });

        function validarFormulario() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== '' || confirmPassword !== '') {
                if (password !== confirmPassword) {
                    alert('Las contraseñas no coinciden.');
                    return false;
                }
            }

            return confirm('¿Estás seguro de que deseas guardar los cambios?');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>