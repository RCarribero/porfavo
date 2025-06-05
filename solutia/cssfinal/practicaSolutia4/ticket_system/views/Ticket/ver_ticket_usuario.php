<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    echo "Ticket no especificado.";
    exit();
}

$ticket_id = $_GET['id'];

// Obtener ticket
$sql_ticket = "SELECT * FROM tickets WHERE id = :id AND user_id = :user_id";
$stmt = $pdo->prepare($sql_ticket);
$stmt->execute(['id' => $ticket_id, 'user_id' => $_SESSION['id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "Ticket no encontrado.";
    exit();
}

// Insertar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
    $sql_comment = "INSERT INTO comments (ticket_id, user_id, comment) VALUES (:ticket_id, :user_id, :comment)";
    $stmt = $pdo->prepare($sql_comment);
    $stmt->execute([ 
        'ticket_id' => $ticket_id,
        'user_id' => $_SESSION['id'],
        'comment' => $_POST['comment']
    ]);
    header("Location: ../Ticket/ver_ticket.php?id=$ticket_id");
    exit();
}

// Obtener comentarios
$sql_comments = "SELECT c.comment, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.ticket_id = :ticket_id ORDER BY c.created_at ASC";
$stmt = $pdo->prepare($sql_comments);
$stmt->execute(['ticket_id' => $ticket_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Ticket</title>
    <link rel="stylesheet" href="../css/estilodashboard.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" alt="Logo del Sistema">
            </div>
            <div class="header-right">
                <div class="theme-toggle">
                    <button id="theme-button">Modo Oscuro</button>
                </div>
                <div class="user-menu">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> ▼</span>
                    <div class="user-dropdown">
                        <a href="../../index.php?controller=user&action=logout">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </header>

        <nav class="navbar">
            <ul>
                <li><a href="../cliente/dashboard.php">Panel</a></li>
                <li><a href="../cliente/misTickets.php">Mis Tickets</a></li>
                <li><a href="../cliente/gestionPerfilUsuario.php">Editar Perfil</a></li>
                <li><a href="../cliente/clienteTecnico.php">Comunicación</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <h2>Ticket: <?php echo htmlspecialchars($ticket['title']); ?></h2>
            <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
            <p><strong>Estado:</strong> <?php echo $ticket['status']; ?> | <strong>Prioridad:</strong> <?php echo $ticket['priority']; ?></p>
            <p><strong>Creado:</strong> <?php echo $ticket['created_at']; ?></p>

            <hr>

            <h3>Comentarios</h3>
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $c): ?>
                    <div class="comment-box">
                        <p><strong><?php echo htmlspecialchars($c['username']); ?>:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                        <p><small><?php echo $c['created_at']; ?></small></p>
                        <hr>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay comentarios todavía.</p>
            <?php endif; ?>

            <form method="POST">
                <label for="comment">Agregar comentario:</label><br>
                <textarea name="comment" id="comment" rows="4" cols="50" required></textarea><br>
                <button type="submit">Enviar comentario</button>
            </form>

            <br>
            <!-- Enlaces para volver -->
            <a href="../cliente/dashboard.php">← Volver al Dashboard</a> | 
            <a href="../cliente/misTickets.php">← Volver a Mis Tickets</a>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeButton = document.getElementById('theme-button');
            const body = document.body;
            
            // Check for saved theme preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
                themeButton.textContent = 'Modo Claro';
            }
            
            themeButton.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                const isDarkMode = body.classList.contains('dark-mode');
                
                if (isDarkMode) {
                    themeButton.textContent = 'Modo Claro';
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    themeButton.textContent = 'Modo Oscuro';
                    localStorage.setItem('darkMode', 'disabled');
                }
            });
        });
    </script>
</body>
</html>
