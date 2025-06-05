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
                        
                        // Establecer variables de sesión
                        $_SESSION['id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        
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
                        
                        // Redirección según el rol
                        if ($user['role'] === 'tech') {
                            $redirect_page = '../Tecnico/dashboardTecnico.php';
                        }
                        if ($user['role'] === 'admin') {
                            $redirect_page = '../admin/dashboard.php';
                        }
                        if ($user['role'] === 'client') {
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
                        $error = "Contraseña incorrecta. Intentos restantes: $remaining_attempts";
                    }
                } else {
                    $error = "Usuario/Email no encontrado";
                }
            } catch (PDOException $e) {
                error_log("Error de base de datos: " . $e->getMessage());
                $error = "Error del sistema. Por favor intente más tarde.";
            }
        }
    } else {
        $error = "Por favor complete todos los campos";
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
                    
                    // Establecer variables de sesión
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirección según el rol
                    $redirect_page = ($user['role'] === 'tech') ? '../Tecnico/dashboardTecnico.php' : '../Cliente/dashboard.php';
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
    <link rel="stylesheet" href="../css/estilologin.css">
    <style>
        .remember-me {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .remember-me input {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" alt="Logo del Sistema">
            </div>
            <div class="theme-toggle">
                <button id="theme-button">Modo Oscuro</button>
            </div>
        </header>

        <main class="main-content">
            <div class="login-box">
                <h1>Sistema de Tickets de Soporte</h1>

                <?php if (isset($error)): ?>
                    <div class="error-message" style="color: red; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label for="email_or_username">Usuario o Correo electrónico:</label>
                        <input type="text" id="email_or_username" name="email_or_username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Recordarme en este dispositivo</label>
                    </div>
                    <button type="submit" class="login-button">Iniciar Sesión</button>
                </form>
                <div class="links">
                    <a href="../../lib/enviarCorreosRecuperarContra/olvidarContra.php">¿Olvidó su contraseña?</a>
                    <a href="../sesion/register.php">Registrarse</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Script para cambiar entre modo oscuro y modo claro
        const themeButton = document.getElementById('theme-button');
        const body = document.body;

        themeButton.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                themeButton.textContent = 'Modo Claro';
            } else {
                themeButton.textContent = 'Modo Oscuro';
            }
        });
        
        // Limpiar mensajes de error al empezar a escribir
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                const errorMsg = document.querySelector('.error-message');
                if (errorMsg) errorMsg.style.display = 'none';
            });
        });
    </script>
</body>
</html>

