<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Filtrado
$estado = $_GET['status'] ?? '';
$categoria = $_GET['category'] ?? '';
$fecha_inicio = $_GET['start_date'] ?? '';
$fecha_fin = $_GET['end_date'] ?? '';
$orderBy = $_GET['orderby'] ?? 'created_at';
$orderDir = $_GET['dir'] ?? 'desc';
$allowedFields = ['title', 'category_name', 'created_at'];
$allowedDir = ['asc', 'desc'];

if (!in_array($orderBy, $allowedFields)) {
    $orderBy = 'created_at';
}
if (!in_array($orderDir, $allowedDir)) {
    $orderDir = 'desc';
}

$sql = "SELECT t.id, t.title, t.description, t.created_at, t.status, c.name AS category_name 
        FROM tickets t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = :user_id";

$params = ['user_id' => $_SESSION['id']];

if (!empty($estado)) {
    $sql .= " AND t.status = :status";
    $params['status'] = $estado;
}
if (!empty($categoria)) {
    $sql .= " AND c.name = :category";
    $params['category'] = $categoria;
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql .= " AND DATE(t.created_at) BETWEEN :start_date AND :end_date";
    $params['start_date'] = $fecha_inicio;
    $params['end_date'] = $fecha_fin;
}

$sql .= " ORDER BY $orderBy $orderDir";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para alternar dirección de orden
function toggleDir($currentDir) {
    return $currentDir === 'asc' ? 'desc' : 'asc';
}

function linkWithOrder($field, $label, $currentField, $currentDir) {
    $newDir = ($field === $currentField) ? toggleDir($currentDir) : 'asc';
    $query = $_GET;
    $query['orderby'] = $field;
    $query['dir'] = $newDir;
    $url = htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($query);
    return "<a href=\"$url\" class=\"text-white\">$label <i class=\"fas fa-sort\"></i></a>";
}

// Mensaje de éxito si existe
$mensaje = $_GET['mensaje'] ?? '';
$tipoMensaje = $_GET['tipo_mensaje'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets - Sistema de Tickets</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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

        .tickets-container {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        body.dark-mode .tickets-container {
            background-color: #2c2c2c;
        }

        .tickets-title {
            color: var(--color-primary);
            margin-bottom: 25px;
            font-weight: 700;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-form label {
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 8px;
            display: block;
        }

        .filter-form .form-control,
        .filter-form .form-select {
            background-color: var(--color-card);
            color: var(--color-text);
            border: 1px solid var(--color-border);
            padding: 10px 15px;
            border-radius: 6px;
        }

        body.dark-mode .filter-form .form-control,
        body.dark-mode .filter-form .form-select {
            background-color: #3c3c3c;
            border-color: #555;
        }

        .btn-filter {
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

        .btn-filter:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .btn-clear {
            background-color: #6c757d;
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

        .btn-clear:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            color: white;
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

        body.dark-mode .tickets-table tbody tr:hover {
            background-color: rgba(255, 140, 66, 0.05);
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
            color: white;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 5px;
        }

        .btn-view {
            background-color: transparent;
            border: 1px solid var(--color-primary);
            color: var(--color-primary);
        }

        .btn-view:hover {
            background-color: var(--color-primary);
            color: white;
        }

        .btn-edit {
            background-color: transparent;
            border: 1px solid var(--color-warning);
            color: var(--color-warning);
        }

        .btn-edit:hover {
            background-color: var(--color-warning);
            color: white;
        }

        .btn-delete {
            background-color: transparent;
            border: 1px solid var(--color-danger);
            color: var(--color-danger);
        }

        .btn-delete:hover {
            background-color: var(--color-danger);
            color: white;
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

        .no-tickets {
            padding: 20px;
            text-align: center;
            color: var(--color-text);
            opacity: 0.7;
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
                                <a href="gestionPerfilUsuario.php" class="dropdown-item d-flex align-items-center gap-2">
                                    <i class="fas fa-user-cog"></i> Mi Perfil
                                </a>
                                <a href="logout.php" class="dropdown-item d-flex align-items-center gap-2 text-danger">
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
                                <a class="nav-link d-flex align-items-center gap-2" href="../cliente/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active d-flex align-items-center gap-2" href="misTickets.php">
                                    <i class="fas fa-ticket-alt"></i> Mis Tickets
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="../Ticket/crearTicket.php">
                                    <i class="fas fa-plus-circle"></i> Nuevo Ticket
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="../cliente/gestionPerfilUsuario.php">
                                    <i class="fas fa-user-cog"></i> Editar Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="../cliente/clienteTecnico.php">
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
                                <i class="fas fa-ticket-alt"></i>
                                <span>Mis Tickets</span>
                            </h2>
                            <a href="../Ticket/crearTicket.php" class="btn-new-ticket">
                                <i class="fas fa-plus"></i>
                                <span>Nuevo Ticket</span>
                            </a>
                        </div>
                        
                        <?php if ($mensaje): ?>
                            <div class="alert <?= $tipoMensaje === 'success' ? 'alert-success' : 'alert-danger' ?>">
                                <i class="fas <?= $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                <?= htmlspecialchars($mensaje) ?>
                            </div>
                        <?php endif; ?>

                        <div class="tickets-container">
                            <h3 class="tickets-title">
                                <i class="fas fa-filter"></i>
                                <span>Filtrar Tickets</span>
                            </h3>
                            
                            <form method="GET" class="filter-form">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="status">Estado:</label>
                                        <select name="status" id="status" class="form-select">
                                            <option value="">Todos</option>
                                            <option value="open" <?= $estado === 'open' ? 'selected' : '' ?>>Abierto</option>
                                            <option value="in_progress" <?= $estado === 'in_progress' ? 'selected' : '' ?>>En progreso</option>
                                            <option value="resolved" <?= $estado === 'resolved' ? 'selected' : '' ?>>Resuelto</option>
                                            <option value="closed" <?= $estado === 'closed' ? 'selected' : '' ?>>Cerrado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="category">Categoría:</label>
                                        <select name="category" id="category" class="form-select">
                                            <option value="">Todas</option>
                                            <option value="Hardware" <?= $categoria === 'Hardware' ? 'selected' : '' ?>>Hardware</option>
                                            <option value="Software" <?= $categoria === 'Software' ? 'selected' : '' ?>>Software</option>
                                            <option value="Red" <?= $categoria === 'Red' ? 'selected' : '' ?>>Red</option>
                                            <option value="Otros" <?= $categoria === 'Otros' ? 'selected' : '' ?>>Otros</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start_date">Desde:</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" 
                                               value="<?= htmlspecialchars($fecha_inicio) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date">Hasta:</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" 
                                               value="<?= htmlspecialchars($fecha_fin) ?>">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn-filter">
                                            <i class="fas fa-filter"></i>
                                            <span>Filtrar</span>
                                        </button>
                                        <a href="misTickets.php" class="btn-clear">
                                            <i class="fas fa-sync-alt"></i>
                                            <span>Limpiar</span>
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <h3 class="tickets-title mt-4">
                                <i class="fas fa-list"></i>
                                <span>Lista de Tickets</span>
                            </h3>
                            
                            <div class="table-responsive">
                                <table class="tickets-table">
                                    <thead>
                                        <tr>
                                            <th><?= linkWithOrder('title', 'Título', $orderBy, $orderDir) ?></th>
                                            <th>Descripción</th>
                                            <th><?= linkWithOrder('category_name', 'Categoría', $orderBy, $orderDir) ?></th>
                                            <th><?= linkWithOrder('created_at', 'Fecha', $orderBy, $orderDir) ?></th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($tickets) > 0): ?>
                                            <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ticket['title']) ?></td>
                                                <td><?= htmlspecialchars(substr($ticket['description'], 0, 50)) ?>...</td>
                                                <td><?= htmlspecialchars($ticket['category_name']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= htmlspecialchars($ticket['status']) ?>">
                                                        <?= htmlspecialchars($ticket['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <a href="../Ticket/ver_ticket.php?id=<?= $ticket['id'] ?>" 
                                                           class="btn-action btn-view" 
                                                           title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($ticket['status'] === 'open' || $ticket['status'] === 'in_progress'): ?>
                                                            <a href="../Ticket/editar_ticket.php?id=<?= $ticket['id'] ?>" 
                                                               class="btn-action btn-edit" 
                                                               title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="no-tickets">
                                                    <i class="fas fa-info-circle"></i> No se encontraron tickets con los filtros actuales
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>