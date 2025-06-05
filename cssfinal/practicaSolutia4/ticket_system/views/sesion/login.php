<?php
// Inicio de sesión con configuración segura
session_start([
    'cookie_lifetime' => 86400, // 1 día de duración
    'cookie_secure' => isset($_SERVER['HTTPS']), // Solo HTTPS si está disponible
    'cookie_httponly' => true, // Protección contra XSS
    'use_strict_mode' => true // Mayor seguridad
]);

include("../../config/database.php");
$database = new Database();
$pdo = $database->getConnection();

// Configuración de seguridad
define('MAX_LOGIN_ATTEMPTS', 5); // Intentos máximos antes de bloqueo
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutos de bloqueo (en segundos)
define('REMEMBER_ME_EXPIRY', 30 * 24 * 60 * 60); // 30 días para "Recordarme"

// Limpiar sesión si acceden al login estando logueados
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['id'])) {
    session_unset();
    session_destroy();
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['email_or_username']) && !empty($_POST['password'])) {
        $email_or_username = trim($_POST['email_or_username']);
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']) ? true : false;

        // Verificar intentos fallidos usando solo la sesión
        $attempts_key = 'login_attempts_' . md5($email_or_username);
        $last_attempt_key = 'last_attempt_' . md5($email_or_username);
        
        $login_attempts = isset($_SESSION[$attempts_key]) ? $_SESSION[$attempts_key] : 0;
        $last_attempt = isset($_SESSION[$last_attempt_key]) ? $_SESSION[$last_attempt_key] : 0;
        
        // Verificar si la cuenta está temporalmente bloqueada
        if ($login_attempts >= MAX_LOGIN_ATTEMPTS && (time() - $last_attempt) < LOGIN_LOCKOUT_TIME) {
            $remaining_time = ceil((LOGIN_LOCKOUT_TIME - (time() - $last_attempt)) / 60);
            $error = "Demasiados intentos fallidos. Por favor, espere $remaining_time minutos antes de intentar nuevamente.";
        } else {
            try {
                $sql = "SELECT id, username, password, role FROM users WHERE email = :credential OR username = :credential";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':credential', $email_or_username, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() === 1) {
                    $user = $stmt->fetch();
                    if (password_verify($password, $user['password'])) {
                        // Restablecer contador de intentos fallidos
                        unset($_SESSION[$attempts_key]);
                        unset($_SESSION[$last_attempt_key]);
                        
                        // Regenerar ID de sesión por seguridad
                        session_regenerate_id(true);
                        
                        // Establecer variables de sesión de forma robusta
                        $_SESSION['user_id'] = $user['id']; // Cambiado a 'user_id' para coincidir con index.php
                        $_SESSION['id'] = $user['id']; // Mantener 'id' para compatibilidad con código existente
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['admin_auth'] = ($user['role'] === 'admin') ? true : false; // Variable adicional para verificación robusta de admin
                        
                        // Opción "Recordarme" sin usar base de datos
                        if ($remember_me) {
                            // Generar token seguro
                            $token = bin2hex(random_bytes(32));
                            $expiry = time() + REMEMBER_ME_EXPIRY;
                            
                            // Almacenar token en cookie (sin usar DB)
                            $cookie_value = $user['id'] . '|' . $token . '|' . hash('sha256', $user['password']);
                            setcookie(
                                'remember_me', 
                                $cookie_value, 
                                $expiry, 
                                '/', 
                                '', 
                                isset($_SERVER['HTTPS']), 
                                true
                            );
                        }
                        
                        // Redirección según el rol de manera robusta
                        if ($user['role'] === 'tech') {
                            $redirect_page = '../Tecnico/dashboardTecnico.php';
                        } 
                        elseif ($user['role'] === 'admin') {
                            $redirect_page = '../admin/dashboard.php';
                        } 
                        elseif ($user['role'] === 'client') {
                            $redirect_page = '../cliente/dashboard.php';
                        } 
                        else {
                            // Por defecto, si el rol no es reconocido
                            $redirect_page = '../cliente/dashboard.php';
                        }
                         
                        // Redirección con JavaScript como respaldo
                        echo '<script>window.location.href = "'.$redirect_page.'";</script>';
                        header("Location: ".$redirect_page);
                        exit();
                    } else {
                        // Incrementar contador de intentos fallidos
                        $_SESSION[$attempts_key] = $login_attempts + 1;
                        $_SESSION[$last_attempt_key] = time();
                        
                        $remaining_attempts = MAX_LOGIN_ATTEMPTS - ($login_attempts + 1);
                        $error_message = "Contraseña incorrecta. Intentos restantes: $remaining_attempts";
                    }
                } else {
                    $error_message = "Usuario/Email no encontrado";
                }
            } catch (PDOException $e) {
                error_log("Error de base de datos: " . $e->getMessage());
                $error_message = "Error del sistema. Por favor intente más tarde.";
            }
        }
    } else {
        $error_message = "Por favor complete todos los campos";
    }
}

// Verificar cookie "Recordarme" sin usar base de datos
if (!isset($_SESSION['id']) && isset($_COOKIE['remember_me'])) {
    try {
        $cookie_parts = explode('|', $_COOKIE['remember_me']);
        if (count($cookie_parts) === 3) {
            $user_id = $cookie_parts[0];
            $token = $cookie_parts[1];
            $password_hash = $cookie_parts[2];
            
            // Verificar que el usuario existe y su contraseña no ha cambiado
            $sql = "SELECT id, username, role, password FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                
                // Verificar que la contraseña no ha cambiado (comparando hashes)
                if (hash('sha256', $user['password']) === $password_hash) {
                    // Regenerar ID de sesión por seguridad
                    session_regenerate_id(true);
                    
                    // Establecer variables de sesión de forma robusta
                    $_SESSION['user_id'] = $user['id']; // Cambiado a 'user_id' para coincidir con index.php
                    $_SESSION['id'] = $user['id']; // Mantener 'id' para compatibilidad
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['admin_auth'] = ($user['role'] === 'admin') ? true : false; // Variable adicional para verificación robusta de admin
                    
                    // Redirección según el rol
                    if ($user['role'] === 'tech') {
                        $redirect_page = '../Tecnico/dashboardTecnico.php';
                    } elseif ($user['role'] === 'admin') {
                        $redirect_page = '../admin/dashboard.php';
                    } else { // cliente por defecto
                        $redirect_page = '../cliente/dashboard.php';
                    }
                    header("Location: ".$redirect_page);
                    exit();
                }
            }
        }
        
        // Si llegamos aquí, la cookie es inválida - borrarla
        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    } catch (Exception $e) {
        error_log("Error al verificar cookie remember_me: " . $e->getMessage());
        // Borrar cookie en caso de error
        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #3498db;
            --color-primary-dark: #2980b9;
            --color-bg: #f8f9fa;
            --color-text: #343a40;
            --color-card: #ffffff;
            --color-border: #dee2e6;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            transition: all 0.3s ease;
        }

        body.dark-mode {
            --color-primary: #ff8c42;
            --color-bg: #121212;
            --color-text: #f8f9fa;
            --color-card: #1e1e1e;
            --color-border: #444;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        .navbar {
            background-color: var(--color-card) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        body.dark-mode .navbar {
            box-shadow: 0 2px 4px rgba(255,255,255,0.1);
        }

        .navbar-brand img {
            filter: brightness(1);
            transition: filter 0.3s ease;
        }

        body.dark-mode .navbar-brand img {
            filter: brightness(0.8);
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

        .login-box {
            background-color: var(--color-card);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 400px;
            margin: 2rem auto 0;
            transition: all 0.3s ease;
        }

        .login-box h1 {
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .login-box h1 i {
            color: var(--color-primary);
        }

        body.dark-mode .login-box {
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
        }

        .form-control {
            background-color: var(--color-card);
            border: 1px solid var(--color-border);
            color: var(--color-text);
            transition: all 0.3s ease;
        }

        body.dark-mode .form-control {
            background-color: #3c3c3c;
            border-color: #555;
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--color-primary-dark);
            border-color: var(--color-primary-dark);
        }

        body.dark-mode .btn-primary {
            background-color: #ff8c42;
            border-color: #ff8c42;
        }

        body.dark-mode .btn-primary:hover {
            background-color: #e67e22;
            border-color: #e67e22;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-dark);
            cursor: default;
            user-select: none;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 5px;
            transition: all 0.3s;
            background: none;
        }

        .form-group input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .login-button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            user-select: none;
        }

        .login-button:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            user-select: none;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: var(--color-danger);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--color-success);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--color-primary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: var(--color-primary-dark);
        }

        .input-group-text {
            background-color: rgba(52, 152, 219, 0.1);
            border: none;
            color: var(--color-primary);
        }

        .form-control {
            border-left: none !important;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        .form-check-input {
            margin-top: 0;
            margin-right: 0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid var(--color-primary);
            border-radius: 0.25rem;
            background-color: white;
            transition: all 0.3s;
        }

        .form-check-input:checked {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        .form-check-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--color-dark);
            font-size: 0.95rem;
            cursor: pointer;
        }

        .form-check-label i {
            color: var(--color-primary);
            transition: transform 0.3s;
        }

        .form-check-label:hover i {
            transform: scale(1.1);
        }

        #theme-button {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        #theme-button:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        body.dark-mode #theme-button {
            background-color: #ff8c42;
        }

        body.dark-mode #theme-button:hover {
            background-color: #e67e22;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <div class="navbar-brand">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" 
                     alt="Logo" style="max-height: 40px;">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="d-flex align-items-center justify-content-end">
                    <button id="theme-button" class="btn btn-sm">
                        <i class="fas fa-moon"></i> Modo Oscuro
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Tema oscuro/claro
        const themeButton = document.getElementById('theme-button');
        const body = document.body;

        // Verificar preferencia guardada
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
    </script>

    <div class="container">
        <div class="login-box">
            <h1><i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión</h1>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="email_or_username">
                        <i class="fas fa-envelope me-2"></i>Email o Usuario:
                    </label>
                    <input type="text" id="email_or_username" name="email_or_username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-key me-2"></i>Contraseña:
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">
                        <i class="fas fa-save me-2"></i>Recordar dispositivo
                    </label>
                </div>
                <button type="submit" class="login-button">Ingresar</button>
            </form>

            <div class="back-link">
                <a href="../../lib/enviarCorreosRecuperarContra/olvidarContra.php">
                    <i class="fas fa-key me-2"></i>¿Olvidó su contraseña?
                </a>
                <br>
                <a href="register.php">
                    <i class="fas fa-user-plus me-2"></i>¿No tienes cuenta? Regístrate
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS + Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript para mejoras interactivas -->
    <script>
        // Validación en tiempo real
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email_or_username = document.getElementById('email_or_username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email_or_username || !password) {
                e.preventDefault();
                alert('¡Todos los campos son obligatorios!');
            }
        });

        // Efectos hover en inputs
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.border = '1px solid var(--color-primary)';
                input.parentElement.style.boxShadow = '0 0 0 0.25rem rgba(52, 152, 219, 0.25)';
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.style.border = '';
                input.parentElement.style.boxShadow = '';
            });
        });
    </script>
</body>
</html>
