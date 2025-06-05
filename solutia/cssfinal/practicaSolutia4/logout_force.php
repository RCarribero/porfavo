<?php
// Script temporal para forzar el cierre de sesión y limpiar cookies
session_start();
$_SESSION = array();
session_destroy();
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
header('Location: /practicaSolutia4/ticket_system/views/sesion/login.php');
exit;
