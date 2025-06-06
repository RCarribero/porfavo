<?php
session_start();

// Registrar cierre de sesión para diagnóstico
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    error_log("Cierre de sesión: Usuario {$_SESSION['username']} con rol {$_SESSION['role']}");
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], 
              $params["secure"], $params["httponly"]);
}

// Destruir la sesión
session_destroy();

// Eliminar la cookie de "Recordarme"
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Redireccionar al login
header('Location: login.php');
exit;
?>
