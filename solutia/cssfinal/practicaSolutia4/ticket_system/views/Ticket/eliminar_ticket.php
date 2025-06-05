<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Verificar autenticación
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

// Verificar rol de admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    header('Location: ../Tecnico/dashboardTecnico.php?error=unauthorized');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];

    try {
        $pdo->beginTransaction();

        // 1. Eliminar comentarios relacionados
        $stmt = $pdo->prepare("DELETE FROM comments WHERE ticket_id = ?");
        $stmt->execute([$ticket_id]);

        // 2. Eliminar archivos adjuntos (y los archivos físicos si es necesario)
        // Primero obtenemos la información de los archivos para eliminarlos del sistema de archivos
        $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE ticket_id = ?");
        $stmt->execute([$ticket_id]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($attachments as $attachment) {
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }
        }

        // Luego eliminamos los registros de la base de datos
        $stmt = $pdo->prepare("DELETE FROM attachments WHERE ticket_id = ?");
        $stmt->execute([$ticket_id]);

        // 3. Finalmente eliminamos el ticket
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);

        $pdo->commit();
        
        header('Location: ../Tecnico/dashboardTecnico.php?deleted=1');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al eliminar ticket: " . $e->getMessage());
        header('Location: ../Tecnico/dashboardTecnico.php?error=delete');
        exit();
    }
} else {
    header('Location: ../Tecnico/dashboardTecnico.php');
    exit();
}
?>