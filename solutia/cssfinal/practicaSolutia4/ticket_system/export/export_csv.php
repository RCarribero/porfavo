<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Configurar zona horaria
ini_set('date.timezone', 'Europe/Madrid');

// Verificar sesión
if (!isset($_SESSION['id'])) {
    header('Location: ../../views/session/login.php');
    exit();
}

// Usar tickets filtrados de la sesión si existen, sino todos los tickets
if (isset($_SESSION['filtered_tickets']) && !empty($_SESSION['filtered_tickets'])) {
    $tickets = $_SESSION['filtered_tickets'];
} else {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $_SESSION['id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generar nombre de archivo único
$filename = 'historial_tickets_' . date('Y-m-d_H-i-s') . '.csv';

// Configurar cabeceras para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Crear un stream para el CSV
$output = fopen('php://output', 'w');

// Escribir la primera fila con los encabezados
$headers = ['ID', 'Título', 'Descripción', 'Prioridad', 'Estado', 'Fecha de Creación', 'Fecha de Actualización'];
fputcsv($output, $headers);

// Escribir los datos de cada ticket
foreach ($tickets as $ticket) {
    $row = [
        $ticket['id'],
        $ticket['title'],
        $ticket['description'],
        $ticket['priority'],
        $ticket['status'],
        date('d/m/Y H:i', strtotime($ticket['created_at'])),
        date('d/m/Y H:i', strtotime($ticket['updated_at']))
    ];
    fputcsv($output, $row);
}

fclose($output);
exit();