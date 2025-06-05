<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

// Verificar si el ID del ticket está presente en la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("El ticket no existe.");
}

// Obtener el ID del ticket desde la URL
$id_ticket = $_GET['id'];

// Obtener los datos del formulario
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';

// Validar que los campos requeridos no estén vacíos
if (empty($titulo) || empty($descripcion)) {
    die("Faltan datos del formulario.");
}

// Actualizar solo el título y descripción (no el estado)
$sql = "UPDATE tickets SET title = :titulo, description = :descripcion WHERE id = :id";
$stmt = $pdo->prepare($sql);

$stmt->bindParam(':titulo', $titulo);
$stmt->bindParam(':descripcion', $descripcion);
$stmt->bindParam(':id', $id_ticket, PDO::PARAM_INT);

if ($stmt->execute()) {
    // Redirigir al dashboard del cliente después de la actualización
    header('Location: ../cliente/misTickets.php');
    exit();
} else {
    echo "Error al actualizar el ticket.";
}
?>
