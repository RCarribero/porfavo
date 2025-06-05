<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'], $_POST['ticket_id'])) {
    $comment_id = $_POST['comment_id'];
    $ticket_id = $_POST['ticket_id'];

    // Eliminar el comentario
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);

    // Redirigir al ticket
    header("Location: ../Tecnico/ver_comentarios.php?ticket_id=" . urlencode($ticket_id));
    exit();
} else {
    echo "No se pudo eliminar el comentario.";
}