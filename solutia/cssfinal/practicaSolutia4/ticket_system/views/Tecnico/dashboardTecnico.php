<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();


// Verificar autenticación
if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit();
}

// Verificar rol de técnico o admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'tech' && $user['role'] !== 'admin') {
    header('Location: ../cliente/dashboard.php');
    exit();
}

// Obtener tickets asignados al técnico (en este modelo, todos los tickets son visibles para los técnicos)
$sql = "SELECT t.*, u.username as cliente, c.name as categoria 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        JOIN categories c ON t.category_id = c.id
        ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC";
$tickets = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los técnicos para reasignación
$techs = [];
$stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'tech'");
$techs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para filtros
$categories = [];
$stmt = $pdo->query("SELECT id, name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar cambios de estado o comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_ticket'])) {
        $ticket_id = $_POST['ticket_id'];
        $status = $_POST['status'];
        $comment = $_POST['comment'] ?? '';
        $assigned_to = $_POST['assigned_to'] ?? $_SESSION['id'];
        
        // Actualizar ticket
        $sql = "UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :ticket_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $status, 'ticket_id' => $ticket_id]);
        
        // Añadir comentario si existe
        if (!empty($comment)) {
            $sql = "INSERT INTO comments (ticket_id, user_id, comment) 
                    VALUES (:ticket_id, :user_id, :comment)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'ticket_id' => $ticket_id,
                'user_id' => $_SESSION['id'],
                'comment' => $comment
            ]);
        }
        
        header("Location: ../Tecnico/dashboardTecnico.php?updated=" . $ticket_id);
        exit();

    }
    
    // Búsqueda de tickets
    if (isset($_POST['search'])) {
        $search = '%' . $_POST['search'] . '%';
        $sql = "SELECT t.*, u.username as cliente, c.name as categoria 
                FROM tickets t 
                JOIN users u ON t.user_id = u.id 
                JOIN categories c ON t.category_id = c.id
                WHERE (t.id LIKE :search OR t.title LIKE :search OR t.description LIKE :search)
                ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => $search]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Filtros avanzados
    if (isset($_POST['apply_filters'])) { 
        $status_filter = $_POST['status_filter'] ?? [];
        $priority_filter = $_POST['priority_filter'] ?? [];
        $category_filter = $_POST['category_filter'] ?? [];
        
        $sql = "SELECT t.*, u.username as cliente, c.name as categoria 
                FROM tickets t 
                JOIN users u ON t.user_id = u.id 
                JOIN categories c ON t.category_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($status_filter)) {
            $sql .= " AND t.status IN (".implode(',', array_fill(0, count($status_filter), '?')).")";
            $params = array_merge($params, $status_filter);
        }
        
        if (!empty($priority_filter)) {
            $sql .= " AND t.priority IN (".implode(',', array_fill(0, count($priority_filter), '?')).")";
            $params = array_merge($params, $priority_filter);
        }
        
        if (!empty($category_filter)) {
            $sql .= " AND t.category_id IN (".implode(',', array_fill(0, count($category_filter), '?')).")";
            $params = array_merge($params, $category_filter);
        }
        
        $sql .= " ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Técnico - Sistema de Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            /* Colores base */
            --primary-color: #3498db; /* Azul modo claro */
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --background-color: #ffffff; /* Fondo blanco para modo claro */
            --text-color: #343a40;
            --shadow-color: rgba(0,0,0,0.1);
            --card-bg: #f8f9fa;
            --border-color: #dee2e6;
            
            /* Estados */
            --status-open: #e74c3c;
            --status-in-progress: #f39c12;
            --status-resolved: #2ecc71;
            --status-closed: #7f8c8d;
            
            /* Prioridades */
            --priority-urgent: #ff3333;
            --priority-high: #ff9900;
            --priority-medium: #ffff00;
            --priority-low: #33cc33;
            
            /* Espaciado */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            
            /* Radios */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-full: 9999px;
        }

        /* Modo oscuro */
        body.dark-mode {
            --primary-color: #ffa726; /* Naranja modo oscuro */
            --secondary-color: #ff7043;
            --accent-color: #ff4081;
            --background-color: #121212; /* Fondo oscuro */
            --text-color: #ffffff;
            --shadow-color: rgba(255,255,255,0.1);
            --card-bg: #1e1e1e;
            --border-color: #333;
            
            --status-open: #ff4081;
            --status-in-progress: #ffd740;
            --status-resolved: #4caf50;
            --status-closed: #9e9e9e;
            
            --priority-urgent: #ff1744;
            --priority-high: #ffa726;
            --priority-medium: #ffd740;
            --priority-low: #66bb6a;
        }

        /* Base styles */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Botones */
        button, .btn {
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        button[type="submit"], .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .btn-danger {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        /* Formularios */
        form {
            margin-bottom: var(--space-lg);
        }

        input[type="text"], input[type="checkbox"] {
            padding: var(--space-sm);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            transition: border-color 0.2s ease;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        input[type="text"]:focus, input[type="checkbox"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        label {
            display: block;
            margin-bottom: var(--space-sm);
            color: var(--text-color);
            font-weight: 500;
        }

        /* Tabla */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: var(--space-md);
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        .table td {
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .dark-mode .table th {
            background-color: var(--primary-color);
            color: white;
        }

        .dark-mode .table td {
            background-color: #1e1e1e;
            color: white;
        }

        /* Estados */
        .status-open {
            color: var(--status-open);
        }

        .status-in-progress {
            color: var(--status-in-progress);
        }

        .status-resolved {
            color: var(--status-resolved);
        }

        .status-closed {
            color: var(--status-closed);
        }

        /* Prioridades */
        .priority-urgent {
            background-color: var(--priority-urgent);
            color: white;
        }

        .priority-high {
            background-color: var(--priority-high);
            color: white;
        }

        .priority-medium {
            background-color: var(--priority-medium);
            color: #333;
        }

        .priority-low {
            background-color: var(--priority-low);
            color: white;
        }

        /* Cards */
        .card {
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 6px var(--shadow-color);
            transition: transform 0.2s ease;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px var(--shadow-color);
        }

        .card-body {
            padding: var(--space-lg);
        }

        .card-header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-md) var(--space-lg);
            color: var(--text-color);
        }

        .dark-mode .card-header {
            color: white;
        }

        .card-total-tickets {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        }

        .card-in-progress {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        }

        .card-urgent {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        }

        .dark-mode .card-total-tickets {
            background: linear-gradient(135deg, #1e1e1e, #121212);
            color: white;
        }

        .dark-mode .card-in-progress {
            background: linear-gradient(135deg, #1e1e1e, #121212);
            color: white;
        }

        .dark-mode .card-urgent {
            background: linear-gradient(135deg, #1e1e1e, #121212);
            color: white;
        }

        .dark-mode .h4, .dark-mode .h5 {
            color: white;
        }

        /* Dashboard cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
        }

        /* Padding para el contenido principal */
        .main-content {
            padding-top: 5rem;
        }

        /* Filtros */
        .filters {
            margin-bottom: var(--space-xl);
            padding: var(--space-lg);
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }

        .filters h3 {
            margin-bottom: var(--space-lg);
            color: var(--text-color);
            font-weight: 600;
        }

        /* Navegación */
        nav {
            margin-bottom: var(--space-xl);
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        nav ul li {
            display: inline-block;
            margin-right: var(--space-lg);
        }

        nav a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            background-color: var(--primary-color);
            color: white;
        }

        nav a:hover, nav a.active {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px var(--shadow-color);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
            width: 100%;
            position: fixed;
            left: 0;
            right: 0;
            padding: 0 2rem;
            min-height: 70px;
        }

        .header .logo {
            margin-left: 2rem;
            padding: 0.5rem;
        }

        .header .logo img {
            height: 50px;
        }

        .header-right {
            margin-right: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .header .theme-toggle button {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }

        .header .user-menu span {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .header .nav-links {
            margin-right: var(--space-md);
        }

        .logo img {
            height: 40px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }

        /* Theme toggle button */
        .header .nav-links {
            display: flex;
            gap: 1rem;
            margin-right: auto;
        }

        .header .nav-links a {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
            text-decoration: none;
        }

        .header .nav-links a:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .header .nav-links a.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Dark mode adjustments */
        .dark-mode .header .nav-links a {
            background-color: var(--primary-color);
            color: white;
        }

        .dark-mode .header .nav-links a:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .dark-mode .header .nav-links a.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header .nav-links {
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .header .nav-links a {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* User menu */
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu > span {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            cursor: pointer;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-full);
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            min-width: 200px;
            background-color: var(--card-bg);
            box-shadow: 0 4px 6px var(--shadow-color);
            border-radius: var(--radius-md);
            padding: var(--space-sm) 0;
            z-index: 1;
            border: 1px solid var(--border-color);
        }

        .user-menu:hover .user-dropdown {
            display: block;
        }

        .user-dropdown a {
            display: block;
            padding: var(--space-sm) var(--space-md);
            color: var(--text-color);
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .user-dropdown a:hover {
            background-color: var(--background-color);
        }

        /* Table responsive */
        .table-responsive {
            overflow-x: auto;
        }

        /* Status badges */
        .status-badge {
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge-new {
            background-color: var(--primary-color);
            color: white;
        }

        .status-badge-in-progress {
            background-color: var(--status-in-progress);
            color: white;
        }

        .status-badge-resolved {
            background-color: var(--status-resolved);
            color: white;
        }

        .status-badge-closed {
            background-color: var(--status-closed);
            color: white;
        }

        /* Waiting indicators */
        .waiting-long {
            border-left: 4px solid var(--priority-urgent);
        }

        .waiting-medium {
            border-left: 4px solid var(--priority-high);
        }

        .waiting-short {
            border-left: 4px solid var(--priority-low);
        }

        /* Form check */
        .form-check-input {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        .form-check-label {
            color: var(--text-color);
        }

        /* Input group */
        .input-group-text {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        /* Alert colors */
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Dark mode alert adjustments */
        .dark-mode .alert-success {
            background-color: #155724;
            color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .dark-mode .alert-danger {
            background-color: #721c24;
            color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        /* Progress bars */
        .progress {
            background-color: var(--card-bg);
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 0 var(--space-sm);
            }

            .header {
                flex-direction: column;
                gap: var(--space-md);
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body class="light-mode">
    <header class="header">
        <div class="logo">
            <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" alt="Logo del Sistema">
        </div>
        <div class="nav-links">
            <a href="../Tecnico/dashboardTecnico.php" class="active">Panel Técnico</a>
            <a href="../Tecnico/gestionPerfilTecnico.php">Editar Perfil</a>
        </div>
        <div class="header-right">
            <div class="theme-toggle">
                <button id="theme-button" class="btn btn-primary">Modo Oscuro</button>
            </div>
            <div class="user-menu">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($user['role']); ?>)</span>
                <div class="user-dropdown">
                    <a class="dropdown-item" href="../../../index.php?controller=user&action=logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>
    <div class="container mt-5">

        <main class="main-content">
            <!-- Resumen General -->
            <div class="dashboard-summary mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card h-100 card-total-tickets">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-ticket-alt me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">Total Tickets</h5>
                                    </div>
                                    <div class="badge bg-primary rounded-pill px-3 py-2"><?php echo count($tickets); ?></div>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Total</span>
                                    <span class="text-primary fw-bold"><?php echo count($tickets); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 card-in-progress">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-spinner me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">En Progreso</h5>
                                    </div>
                                    <div class="badge bg-warning rounded-pill px-3 py-2"><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'in_progress'; })); ?></div>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Progreso</span>
                                    <span class="text-warning fw-bold"><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'in_progress'; })); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 card-urgent">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-exclamation-triangle me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">Urgentes</h5>
                                    </div>
                                    <div class="badge bg-danger rounded-pill px-3 py-2"><?php echo count(array_filter($tickets, function($t) { return $t['priority'] == 'urgent'; })); ?></div>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Urgentes</span>
                                    <span class="text-danger fw-bold"><?php echo count(array_filter($tickets, function($t) { return $t['priority'] == 'urgent'; })); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Herramientas de Gestión -->
            <div class="management-tools mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Herramientas de Gestión</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="../Tecnico/gestionPerfilTecnico.php" class="btn btn-secondary w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-cog me-2 fs-4"></i>
                                    <span>Gestionar Perfil</span>
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="../Tecnico/dashboardTecnico.php" class="btn btn-primary w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-tachometer-alt me-2 fs-4"></i>
                                    <span>Panel Principal</span>
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="../Tecnico/ver_comentarios.php" class="btn btn-info w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-comments me-2 fs-4"></i>
                                    <span>Ver Comentarios</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Búsqueda -->
            <div class="filters mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h4>
                    </div>
                    <div class="card-body">
                        <!-- Formulario de búsqueda -->
                        <form method="post" class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Buscar por ID, título o contenido">
                                    <button class="btn btn-primary" type="submit">Buscar</button>
                                    <button class="btn btn-secondary" type="button" onclick="window.location.href='dashboardTecnico.php'">Limpiar</button>
                                </div>
                            </div>
                        </form>

                        <!-- Formulario de filtros -->
                        <form method="post" class="row g-3">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Estado</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $statuses = ['open' => 'Abierto', 'in_progress' => 'En Progreso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado'];
                                        foreach ($statuses as $value => $label): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="status_filter[]" value="<?php echo $value; ?>">
                                                <label class="form-check-label"><?php echo $label; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Prioridad</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $priorities = ['urgent' => 'Urgente', 'high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
                                        foreach ($priorities as $value => $label): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="priority_filter[]" value="<?php echo $value; ?>">
                                                <label class="form-check-label"><?php echo $label; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Categorías</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="category_filter[]" value="<?php echo $category['id']; ?>">
                                                <label class="form-check-label"><?php echo htmlspecialchars($category['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <button type="submit" name="apply_filters" class="btn btn-primary w-100">Aplicar Filtros</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de Tickets -->
            <div class="tickets-list">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Tickets</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['updated'])): ?>
                            <div class="alert alert-success mb-3">Ticket #<?php echo htmlspecialchars($_GET['updated']); ?> actualizado correctamente.</div>
                        <?php endif; ?>
                        <?php if (isset($_GET['deleted'])): ?>
                            <div class="alert alert-success mb-3">Ticket eliminado correctamente.</div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error']) && $_GET['error'] == 'delete'): ?>
                            <div class="alert alert-danger mb-3">Error al eliminar el ticket.</div>
                        <?php endif; ?>
                        
                        <?php if (count($tickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Cliente</th>
                                        <th>Categoría</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Tiempo Espera</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): 
                                        $created = new DateTime($ticket['created_at']);
                                        $now = new DateTime();
                                        $interval = $now->diff($created);
                                        $waiting_days = $interval->days;
                                        
                                        $waiting_class = '';
                                        if ($waiting_days > 3) $waiting_class = 'waiting-long';
                                        elseif ($waiting_days > 1) $waiting_class = 'waiting-medium';
                                        else $waiting_class = 'waiting-short';
                                        
                                        $priority_class = 'priority-' . $ticket['priority'];
                                    ?>
                                    <tr class="<?php echo $priority_class; ?> <?php echo $waiting_class; ?>">
                                        <td><?php echo htmlspecialchars($ticket['id']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['priority']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                        <td><?php echo $waiting_days; ?> días</td>
                                        <td>
                                            <div class="btn-group">
                                                <a class="btn btn-primary btn-sm" href="../Tecnico/detallesTecnico.php?id=<?php echo $ticket['id']; ?>">
                                                    <i class="fas fa-eye"></i> Detalles
                                                </a>
                                                <a class="btn btn-secondary btn-sm" href="../Tecnico/ver_comentarios.php?ticket_id=<?php echo $ticket['id']; ?>">
                                                    <i class="fas fa-comments"></i> Comentarios
                                                </a>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <form method="post" action="../Ticket/eliminar_ticket.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de querer eliminar este ticket?');">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay tickets encontrados con los filtros actuales.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tema oscuro
            const themeButton = document.getElementById('theme-button');
            const body = document.body;
            
            // Verificar si hay un tema guardado en localStorage
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark-mode') {
                body.classList.add('dark-mode');
                body.classList.remove('light-mode');
                themeButton.innerHTML = 'Modo Claro';
            } else {
                body.classList.add('light-mode');
                body.classList.remove('dark-mode');
                themeButton.innerHTML = 'Modo Oscuro';
            }
            
            themeButton.addEventListener('click', function() {
                const isDark = body.classList.contains('dark-mode');
                if (isDark) {
                    body.classList.remove('dark-mode');
                    body.classList.add('light-mode');
                } else {
                    body.classList.remove('light-mode');
                    body.classList.add('dark-mode');
                }
                
                // Guardar el tema en localStorage
                localStorage.setItem('theme', isDark ? 'light-mode' : 'dark-mode');
                
                this.innerHTML = isDark ? 'Modo Oscuro' : 'Modo Claro';
            });

            // Mostrar/ocultar detalles de ticket
            document.querySelectorAll('.ticket-details-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const details = this.nextElementSibling;
                    details.classList.toggle('show');
                });
            });

            // Actualizar estado de ticket
            document.querySelectorAll('.update-status-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const ticketId = this.dataset.ticketId;
                    const status = this.querySelector('select[name="status"]').value;
                    const comment = this.querySelector('textarea[name="comment"]')?.value || '';
                    
                    fetch('../api/updateTicketStatus.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ticketId: ticketId,
                            status: status,
                            comment: comment
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error al actualizar el ticket');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al actualizar el ticket');
                    });
                });
            });

            // Filtros avanzados
            const filtersForm = document.querySelector('.filters form');
            if (filtersForm) {
                filtersForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const filters = {};
                    
                    for (let [key, value] of formData.entries()) {
                        if (value) {
                            filters[key] = value;
                        }
                    }
                    
                    // Aquí implementar la lógica de filtrado
                    console.log('Filtros aplicados:', filters);
                });
            }
        });
    </script>
</body>
</html>
<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>