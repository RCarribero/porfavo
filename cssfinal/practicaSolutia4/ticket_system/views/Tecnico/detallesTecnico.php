<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_GET['id'])) {
    die('ID de ticket no proporcionado.');
}

$ticket_id = $_GET['id'];

// Obtener datos del ticket
$stmt = $pdo->prepare("
    SELECT t.*, u.username AS cliente, c.name AS categoria
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    JOIN categories c ON t.category_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket no encontrado.");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualizar estado si se envió
    if (isset($_POST['actualizar'])) {
        $nuevo_estado = $_POST['nuevo_estado'] ?? $ticket['status'];
        $nueva_prioridad = $_POST['nueva_prioridad'] ?? $ticket['priority'];
        $comentario = trim($_POST['comentario'] ?? '');
        $user_id = $_SESSION['user_id'] ?? 1; // técnico logueado (ajustar según sesión real)

        // Actualizar estado y prioridad
        $update = $pdo->prepare("UPDATE tickets SET status = ?, priority = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$nuevo_estado, $nueva_prioridad, $ticket_id]);

        // Insertar comentario si hay
        if (!empty($comentario)) {
            $insert = $pdo->prepare("INSERT INTO comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
            $insert->execute([$ticket_id, $user_id, $comentario]);
        }

        // Redirigir al dashboard
        header("Location: ../Tecnico/dashboardTecnico.php");
        exit;
    }
}

// Obtener comentarios y adjuntos
$comentarios = $pdo->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.ticket_id = ? 
    ORDER BY c.created_at DESC
");
$comentarios->execute([$ticket_id]);

$adjuntos = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ?");
$adjuntos->execute([$ticket_id]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Ticket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h3 {
            color: #2c3e50;
        }
        .ticket-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 5px;
        }
        select, textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            min-height: 100px;
        }
        button, .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover {
            background: #2980b9;
        }
        .comment {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            background: white;
        }
        .comment-meta {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .attachment {
            margin: 5px 0;
        }
        .priority-high {
            color: #e74c3c;
            font-weight: bold;
        }
        .priority-medium {
            color: #f39c12;
            font-weight: bold;
        }
        .priority-low {
            color: #2ecc71;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="ticket-info">
        <h1>Detalles del Ticket #<?php echo htmlspecialchars($ticket['id']); ?></h1>
        <p><strong>Título:</strong> <?php echo htmlspecialchars($ticket['title']); ?></p>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($ticket['cliente']); ?></p>
        <p><strong>Categoría:</strong> <?php echo htmlspecialchars($ticket['categoria']); ?></p>
        <p><strong>Prioridad:</strong> 
            <span class="priority-<?php echo htmlspecialchars($ticket['priority']); ?>">
                <?php 
                $priorityLabels = [
                    'high' => 'Alta',
                    'medium' => 'Media',
                    'low' => 'Baja'
                ];
                echo htmlspecialchars($priorityLabels[$ticket['priority']] ?? $ticket['priority']); 
                ?>
            </span>
        </p>
        <p><strong>Estado actual:</strong> 
            <?php 
            $statusLabels = [
                'open' => 'Abierto',
                'in_progress' => 'En progreso',
                'resolved' => 'Resuelto',
                'closed' => 'Cerrado'
            ];
            echo htmlspecialchars($statusLabels[$ticket['status']] ?? $ticket['status']); 
            ?>
        </p>
        <p><strong>Descripción:</strong><br><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
    </div>

    <form method="POST">
        <div class="form-section">
            <h3>Actualizar Estado y Prioridad</h3>
            
            <label for="nuevo_estado">Nuevo Estado:</label>
            <select name="nuevo_estado">
                <option value="open" <?php if ($ticket['status'] == 'open') echo 'selected'; ?>>Abierto</option>
                <option value="in_progress" <?php if ($ticket['status'] == 'in_progress') echo 'selected'; ?>>En progreso</option>
                <option value="resolved" <?php if ($ticket['status'] == 'resolved') echo 'selected'; ?>>Resuelto</option>
                <option value="closed" <?php if ($ticket['status'] == 'closed') echo 'selected'; ?>>Cerrado</option>
            </select>
            
            <label for="nueva_prioridad">Nueva Prioridad:</label>
            <select name="nueva_prioridad">
                <option value="high" <?php if ($ticket['priority'] == 'high') echo 'selected'; ?>>Alta</option>
                <option value="medium" <?php if ($ticket['priority'] == 'medium') echo 'selected'; ?>>Media</option>
                <option value="low" <?php if ($ticket['priority'] == 'low') echo 'selected'; ?>>Baja</option>
            </select>
        </div>

        <div class="form-section">
            <h3>Agregar Comentario</h3>
            <textarea name="comentario" placeholder="Escribe un comentario..."></textarea>
        </div>

        <?php if ($adjuntos->rowCount() > 0): ?>
        <div class="form-section">
            <h3>Archivos Adjuntos</h3>
            <ul>
                <?php foreach ($adjuntos as $archivo): ?>
                    <li class="attachment">
                        <a href="<?php echo htmlspecialchars($archivo['filepath']); ?>" download>
                            <?php echo htmlspecialchars($archivo['filename']); ?>
                        </a> (<?php echo round($archivo['filesize'] / 1024, 2); ?> KB)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($comentarios->rowCount() > 0): ?>
        <div class="form-section">
            <h3>Comentarios</h3>
            <?php foreach ($comentarios as $com): ?>
                <div class="comment">
                    <div class="comment-meta">
                        <strong><?php echo htmlspecialchars($com['username']); ?></strong> - 
                        <?php echo date('d/m/Y H:i', strtotime($com['created_at'])); ?>
                    </div>
                    <?php echo nl2br(htmlspecialchars($com['comment'])); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <button type="submit" name="actualizar">Actualizar Ticket</button>
        <a href="../Tecnico/dashboardTecnico.php" class="btn">Volver al Panel</a>
    </form>
</body>
</html>