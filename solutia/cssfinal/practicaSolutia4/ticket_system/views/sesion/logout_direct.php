<?php
// Inicio de sesión 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Eliminar variables de sesión y destruir la sesión
$_SESSION = array();
session_destroy();

// Eliminar cookie de recordarme si existe
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Redirigir a la página de login
header('Location: login.php');
exit;
?>
