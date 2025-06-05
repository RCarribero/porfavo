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
    $tipoMensaje = 'success';
}

// Inicializar notificaciones en sesión si no existen
$notifications = [];
$unread_count = 0;

// Obtener notificaciones de sesión
if (isset($_SESSION['notifications'])) {
    $notifications = $_SESSION['notifications'];
    
    // Contar notificaciones no leídas
    foreach ($notifications as $notification) {
        if ($notification['read_at'] === null) {
            $unread_count++;
        }
    }
}

// Si es la primera vez que el usuario inicia sesión en esta sesión
if (!isset($_SESSION['welcome_notification_created'])) {
    // Crear notificación de bienvenida
    // Configurar timezone para España
    date_default_timezone_set('Europe/Madrid');
    
    $welcome_notification = [
        'title' => 'Bienvenido al sistema',
        'message' => '¡Bienvenido! Has iniciado sesión en el sistema.',
        'created_at' => date('Y-m-d H:i:s'),
        'read_at' => null
    ];
    
    // Agregar la notificación a la sesión
    $notifications[] = $welcome_notification;
    $_SESSION['notifications'] = $notifications;
    $_SESSION['welcome_notification_created'] = true;
    
    // Actualizar contador de notificaciones
    $unread_count++;
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

// Contar tickets por estado
$counts = [
    'open' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0
];

foreach ($tickets as $ticket) {
    $counts[$ticket['status']]++;
}

// Mensaje de éxito si existe
$mensaje = $_GET['mensaje'] ?? '';
$tipoMensaje = $_GET['tipo_mensaje'] ?? '';
$_SESSION['filtered_tickets'] = $tickets;

// Obtener tickets recientes (últimos 7 días)
$ticketsRecientes = array_filter($tickets, function($ticket) {
    return strtotime($ticket['created_at']) >= strtotime('-7 days');
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Tickets</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Estilos personalizados -->
    <style>
        :root {
            --color-primary: #3498db;
            --color-primary-dark: #e67e22;
            --color-bg: #f8f9fa;
            --color-text: #343a40;
            --color-card: #ffffff;
            --color-border: #dee2e6;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            transition: all 0.3s ease;
        }

        body.dark-mode {
            --color-primary: #ff8c42;
            --color-bg: #121212;
            --color-text: #f8f9fa;
            --color-card: #1e1e1e;
            --color-border: #444;
        }

        .header {
            background: var(--color-card);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
            border-bottom: 2px solid var(--color-primary);
        }

        .user-menu {
            cursor: pointer;
            transition: all 0.3s;
            color: var(--color-primary);
        }

        .user-menu:hover {
            opacity: 0.8;
        }

        .dashboard-container {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        body.dark-mode .dashboard-container {
            background-color: #2c2c2c;
        }

        /* Estilos para el botón de tema */
        #theme-button {
            background-color: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-primary);
            transition: all 0.3s ease;
        }

        #theme-button:hover {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--color-primary);
        }

        body.dark-mode #theme-button {
            border-color: var(--color-border);
            color: var(--color-primary);
        }

        body.dark-mode #theme-button:hover {
            background-color: rgba(255, 140, 66, 0.1);
            color: var(--color-primary);
        }

        /* Estilos para el título principal */
        .main-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-title i {
            color: #343A40;
        }

        body.dark-mode .main-title {
            color: #f8f9fa;
        }

        body.dark-mode .main-title i {
            color: #f8f9fa;
        }

        .dashboard-title {
            color: var(--color-primary);
            margin-bottom: 25px;
            font-weight: 700;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: var(--color-card);
            border-left: 4px solid var(--color-primary);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }

        .summary-title {
            font-size: 1rem;
            color: var(--color-text);
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .card-icon {
            font-size: 2rem;
            color: var(--color-primary);
            opacity: 0.7;
        }

        .tickets-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
        }

        .tickets-table thead {
            background-color: var(--color-primary);
            color: white;
        }

        .tickets-table th {
            padding: 15px;
            text-align: left;
        }

        .tickets-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--color-border);
        }

        .tickets-table tr:last-child td {
            border-bottom: none;
        }

        .tickets-table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-open {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-in_progress {
            background-color: #bee5eb;
            color: #0c5460;
        }

        .status-resolved {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-closed {
            background-color: #d6d8db;
            color: #383d41;
        }

        .btn-new-ticket {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-new-ticket:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
        }

        .btn-view {
            background-color: transparent;
            border: 1px solid var(--color-primary);
            color: var(--color-primary);
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view:hover {
            background-color: var(--color-primary);
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-left: 4px solid var(--color-success);
            color: var(--color-success);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border-left: 4px solid var(--color-danger);
            color: var(--color-danger);
        }

        .main-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(#343A40);
            margin-bottom: 20px;
        }

        .section-container {
            margin-bottom: 40px;
        }

        .no-tickets {
            padding: 20px;
            text-align: center;
            color: var(--color-text);
            opacity: 0.7;
        }

        .sidebar {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .nav-link {
            color: var(--color-text);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--color-primary);
        }

        body.dark-mode .nav-link:hover, 
        body.dark-mode .nav-link.active {
            background-color: rgba(255, 140, 66, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <header class="header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="logo">
                        <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" 
                            alt="Logo" style="max-width: 150px;">
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <button id="theme-button" class="btn btn-sm">
                            <i class="fas fa-moon"></i> Modo Oscuro
                        </button>
                        <div class="user-menu position-relative">
                            <span class="d-flex align-items-center gap-2">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?> ▼
                            </span>
                            <div class="dropdown-menu position-absolute end-0 mt-2 shadow" 
                                style="display: none; min-width: 180px; background-color: var(--color-card);">
                                <a href="../sesion/gestionPerfilUsuario.php" class="dropdown-item d-flex align-items-center gap-2">
                                    <i class="fas fa-user-cog"></i> Mi Perfil
                                </a>
                                <a href="../sesion/logout.php" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="container mt-4">
            <div class="row">
                <div class="col-md-3">
                    <nav class="sidebar">
                        <ul class="nav flex-column w-100">
                            <li class="nav-item">
                                <a class="nav-link active d-flex align-items-center gap-2" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="misTickets.php">
                                    <i class="fas fa-ticket-alt"></i> Mis Tickets
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="../Ticket/crearTicket.php">
                                    <i class="fas fa-plus-circle"></i> Nuevo Ticket
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="gestionPerfilUsuario.php">
                                    <i class="fas fa-user-cog"></i> Editar Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="clienteTecnico.php">
                                    <i class="fas fa-comments"></i> Comunicación
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="col-md-9">
                    <main class="main-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="main-title">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Panel de Usuario</span>
                            </h2>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-sm" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="notifications-badge"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </button>
                                <div class="dropdown-menu notification-dropdown" aria-labelledby="notificationsDropdown" style="width: 400px;">
                                    <div class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                                        <h6 class="mb-0">Notificaciones</h6>
                                        <span class="badge bg-primary rounded-pill"><?php echo $unread_count; ?></span>
                                    </div>
                                    <div class="notification-list" style="max-height: 400px; overflow-y: auto;">
                                        <?php foreach ($notifications as $notification): ?>
                                            <div class="notification-item <?php echo $notification['read_at'] === null ? 'unread' : ''; ?>" data-notification-id="<?php echo uniqid(); ?>">
                                                <div class="notification-content p-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="notification-title fw-bold"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                            <div class="notification-message text-muted"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                            <div class="notification-time" data-server-time="<?php echo $notification['created_at']; ?>"><?php echo date('d/m/Y H:i:s', strtotime($notification['created_at'])); ?></div>
                                                        </div>
                                                        <div class="notification-actions mt-2">
                                                            <?php if ($notification['read_at'] === null): ?>
                                                                <button class="btn btn-sm btn-primary mark-read-btn">Marcar como leído</button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-primary mark-read-btn" disabled>Leído</button>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-danger delete-notification-btn">Eliminar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($notifications)): ?>
                                            <div class="notification-item p-3 text-center">
                                                <div class="notification-content">
                                                    <div class="notification-message text-muted">No hay notificaciones nuevas</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>


                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="dashboard-container section-container">
                            <h3 class="dashboard-title">
                                <i class="fas fa-chart-bar"></i>
                                <span>Resumen de Tickets</span>
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="summary-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="summary-title">Tickets Abiertos</div>
                                                <div class="summary-value"><?= $counts['open'] + $counts['in_progress'] ?></div>
                                            </div>
                                            <i class="fas fa-exclamation-circle card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="summary-title">Tickets Resueltos</div>
                                                <div class="summary-value"><?= $counts['resolved'] + $counts['closed'] ?></div>
                                            </div>
                                            <i class="fas fa-check-circle card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="summary-title">Total Tickets</div>
                                                <div class="summary-value"><?= count($tickets) ?></div>
                                            </div>
                                            <i class="fas fa-ticket-alt card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
                                <h3 class="dashboard-title">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span>Tickets Recientes</span>
                                </h3>
                                <div>
                                    <a href="../Ticket/crearTicket.php" class="btn-new-ticket me-2">
                                        <i class="fas fa-plus"></i>
                                        <span>Nuevo Ticket</span>
                                    </a>
                                    <form action="../../export/export_pdf.php" method="post" class="d-inline">
                                        <button type="submit" class="btn-view">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </button>
                                    </form>
                                    <form action="../../export/export_csv.php" method="post" class="d-inline ms-2">
                                        <button type="submit" class="btn-view">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="tickets-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($ticketsRecientes) > 0): ?>
                                            <?php foreach ($ticketsRecientes as $ticket): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($ticket['id']) ?></td>
                                                <td><?= htmlspecialchars($ticket['title']) ?></td>
                                                <td><?= htmlspecialchars($ticket['priority']) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= htmlspecialchars(str_replace(' ', '_', strtolower($ticket['status']))) ?>">
                                                        <?= htmlspecialchars($ticket['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></td>
                                                <td>
                                                    <a href="../Ticket/ver_ticket.php?id=<?= $ticket['id'] ?>" class="btn-view">
                                                        <i class="fas fa-eye"></i>
                                                        <span>Ver</span>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="no-tickets">
                                                    <i class="fas fa-info-circle"></i> No hay tickets recientes
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Convertir todas las fechas a la hora local del usuario
            document.querySelectorAll('.notification-time').forEach(timeElement => {
                const serverTime = timeElement.getAttribute('data-server-time');
                if (serverTime) {
                    const serverDate = new Date(serverTime);
                    const localTime = serverDate.toLocaleString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    timeElement.textContent = localTime;
                }
            });

            // Marcar notificación como leída
            document.querySelectorAll('.mark-read-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const notificationCard = this.closest('.notification-item');
                    notificationCard.classList.remove('unread');
                    this.disabled = true;
                    
                    // Actualizar contador de notificaciones no leídas
                    const badge = document.querySelector('.notifications-badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 0) {
                            badge.textContent = currentCount - 1;
                            if (currentCount === 1) {
                                badge.style.display = 'none';
                            }
                        }
                    }

                    // Actualizar la sesión
                    updateSessionNotifications();
                });
            });

            // Eliminar notificación
            document.querySelectorAll('.delete-notification-btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('¿Estás seguro de que quieres eliminar esta notificación?')) {
                        const notificationCard = this.closest('.notification-item');
                        const notificationId = notificationCard.dataset.notificationId;
                        
                        // Eliminar del DOM
                        notificationCard.remove();
                        
                        // Actualizar contador de notificaciones no leídas
                        const badge = document.querySelector('.notifications-badge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent);
                            if (currentCount > 0) {
                                badge.textContent = currentCount - 1;
                                if (currentCount === 1) {
                                    badge.style.display = 'none';
                                }
                            }
                        }

                        // Actualizar la sesión
                        updateSessionNotifications();
                    }
                });
            });

            // Función para crear un ticket
            window.createTicket = function(ticketData) {
                fetch('ajax/create_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(ticketData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar la interfaz
                        updateSessionNotifications();
                        // Actualizar el contador de notificaciones
                        const badge = document.querySelector('.notifications-badge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent);
                            badge.textContent = currentCount + 1;
                            badge.style.display = 'block';
                        }
                        
                        // Mostrar mensaje de éxito
                        alert('Ticket creado exitosamente con ID: ' + data.ticket_id);
                    } else {
                        alert('Error al crear el ticket: ' + data.message);
                    }
                })
                .catch(error => console.error('Error creando ticket:', error));
            };

            // Función para actualizar las notificaciones en sesión
            function updateSessionNotifications() {
                // Obtener todas las notificaciones del DOM
                const notifications = Array.from(document.querySelectorAll('.notification-item'))
                    .map(notification => {
                        const isUnread = notification.classList.contains('unread');
                        return {
                            title: notification.querySelector('.notification-title').textContent,
                            message: notification.querySelector('.notification-message').textContent,
                            created_at: notification.querySelector('.notification-time').textContent,
                            read_at: isUnread ? null : new Date().toISOString()
                        };
                    });

                // Actualizar la sesión
                fetch('ajax/update_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ notifications })
                })
                .catch(error => console.error('Error actualizando notificaciones:', error));
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript -->
    <script>
        // Menú desplegable de usuario
        const userMenu = document.querySelector('.user-menu');
        const dropdownMenu = document.querySelector('.dropdown-menu');

        userMenu.addEventListener('click', () => {
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });

        // Tema oscuro/claro
        const themeButton = document.getElementById('theme-button');
        const body = document.body;

        // Verificar preferencia guardada
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
        }

        themeButton.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDarkMode = body.classList.contains('dark-mode');
            
            if (isDarkMode) {
                themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                themeButton.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
                localStorage.removeItem('darkMode');
            }
        });
    </script>
</body>
</html>