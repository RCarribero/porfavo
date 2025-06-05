<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

// Mostrar mensaje de éxito si existe
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Filtros desde GET
$estado = $_GET['status'] ?? '';
$categoria = $_GET['category'] ?? '';
$fecha_inicio = $_GET['start_date'] ?? '';
$fecha_fin = $_GET['end_date'] ?? '';

// Construir consulta SQL con filtros
$sql = "SELECT * FROM tickets WHERE user_id = :user_id";
$params = ['user_id' => $_SESSION['id']];

if (!empty($estado)) {
    $sql .= " AND status = :status";
    $params['status'] = $estado;
}

if (!empty($categoria)) {
    $sql .= " AND category = :category";
    $params['category'] = $categoria;
}

if (!empty($fecha_inicio)) {
    $sql .= " AND created_at >= :start_date";
    $params['start_date'] = $fecha_inicio . ' 00:00:00';
}

if (!empty($fecha_fin)) {
    $sql .= " AND created_at <= :end_date";
    $params['end_date'] = $fecha_fin . ' 23:59:59';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/estilodashboard.css">
    <style>
        .alert-success {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #d6e9c6;
            border-radius: 4px;
            color: #3c763d;
            background-color: #dff0d8;
        }
    </style>
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
                        <a class="dropdown-item" href="/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=logout">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <nav class="navbar">
            <ul>
                <li><a href="../cliente/dashboard.php" class="active">Panel</a></li>
                <li><a href="../cliente/misTickets.php">Mis Tickets</a></li>
                <li><a href="../cliente/gestionPerfilUsuario.php">Editar Perfil</a></li>
                <li><a href="../cliente/clienteTecnico.php">Comunicación</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <?php if (!empty($success_message)): ?>
                <div class="alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-summary">
                <h2>Resumen</h2>
                <div class="summary-cards">
                    <div class="card">
                        <h3>Tickets Abiertos</h3>
                        <p><?php echo count(array_filter($tickets, function($ticket) { 
                            return $ticket['status'] == 'open' || $ticket['status'] == 'in_progress'; 
                        })); ?></p>
                    </div>
                    <div class="card">
                        <h3>Tickets Resueltos</h3>
                        <p><?php echo count(array_filter($tickets, function($ticket) { 
                            return $ticket['status'] == 'resolved' || $ticket['status'] == 'closed'; 
                        })); ?></p>
                    </div>
                    <div class="card">
                        <h3>Total Tickets</h3>
                        <p><?php echo count($tickets); ?></p>
                    </div>
                </div>
                <div class="buttons-container">
                    <button class="new-ticket-button"><a href="../../../Ticket/crearTicket.php">+ Nuevo Ticket</a></button>
                    <div class="download-buttons">
                        <a href="../../../export/export_pdf.php" class="download-btn">Descargar PDF</a>
                        <a href="../../../export/export_csv.php" class="download-btn">Descargar CSV</a>
                    </div>
                </div>
            </div>

            <div class="recent-tickets">
                <h2>Tickets Recientes</h2>

                <form method="GET" class="filter-form">
                    <!-- (formulario igual que antes, sin cambios) -->
                    <!-- ... -->
                </form>

                <?php
                $ticketsRecientes = array_filter($tickets, function($ticket) {
                    $fechaTicket = strtotime($ticket['created_at']);
                    $unaSemanaAntes = strtotime('-7 days');
                    return $fechaTicket >= $unaSemanaAntes;
                });
                ?>

                <?php if (count($ticketsRecientes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Descripción</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha de creación</th>
                            <th>Fecha actualización</th>
                            <th>Ver detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ticketsRecientes as $ticket): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['id']) ?></td>
                            <td><?= htmlspecialchars($ticket['title']) ?></td>
                            <td><?= htmlspecialchars($ticket['description']) ?></td>
                            <td><?= htmlspecialchars($ticket['priority']) ?></td>
                            <td><?= htmlspecialchars($ticket['status']) ?></td>
                            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                            <td><?= htmlspecialchars($ticket['updated_at']) ?></td>
                            <td><a href="../Ticket/ver_ticket.php?id=<?= $ticket['id'] ?>">Ver detalles</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No se han creado tickets en la última semana.</p>
                <?php endif; ?>

                <div style="margin-top: 20px; text-align: right;">
                    <a href="../cliente/misTickets.php" class="btn-ver-todos">Ver todos los tickets →</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeButton = document.getElementById('theme-button');
            const body = document.body;

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
