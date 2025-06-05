<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!isset($_SESSION['id'])) {
    header("Location: ../sesion/login.php");
    exit();
}

$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Configurar timezone para España
    date_default_timezone_set('Europe/Madrid');
    
    $title = trim($_POST['title']);
    $category_id = $_POST['category'];
    $description = trim($_POST['description']);
    $priority = 'low'; // ✅ Prioridad por defecto
    $attachment_path = null;

    if (empty($title) || empty($category_id) || empty($description)) {
        $_SESSION['error_message'] = "Todos los campos son obligatorios.";
        header("Location: crearTicket.php");
        exit();
    }

    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = "uploads/";

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $_SESSION['error_message'] = "Error al crear la carpeta de subida.";
                header("Location: crearTicket.php");
                exit();
            }
        }

        $filename = basename($_FILES["attachment"]["name"]);
        $target_file = $upload_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $attachment_path = $target_file;
        } else {
            $_SESSION['error_message'] = "Error al subir el archivo.";
            header("Location: crearTicket.php");
            exit();
        }
    }

    // Obtener categorías
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($categories)) {
            throw new Exception("No se encontraron categorías en la base de datos");
        }
    } catch (PDOException $e) {
        die("Error al obtener las categorías: " . $e->getMessage());
    }

    try {
        if (!$pdo) {
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }

        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['size'] > 5242880) { // 5MB
            $_SESSION['error_message'] = "El archivo es demasiado grande. Máximo permitido: 5MB";
            header("Location: crearTicket.php");
            exit();
        }

        if (empty($title) || empty($category_id) || empty($description)) {
            throw new Exception("Faltan campos requeridos");
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, category_id, title, description, priority, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, 'open', NOW())");

        if (!$stmt) {
            throw new Exception("Error al preparar la consulta");
        }

        $result = $stmt->execute([$user_id, $category_id, $title, $description, $priority]);

        if (!$result) {
            throw new Exception("Error al ejecutar la consulta: " . json_encode($stmt->errorInfo()));
        }

        $ticket_id = $pdo->lastInsertId();

        if (!$ticket_id) {
            throw new Exception("No se pudo obtener el ID del ticket");
        }

        if ($attachment_path) {
            $stmt = $pdo->prepare("INSERT INTO attachments (ticket_id, filename, filepath, filesize) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$ticket_id, $filename, $attachment_path, $_FILES['attachment']['size']]);

            if (!$result) {
                throw new Exception("Error al guardar el archivo adjunto");
            }
        }

        // Crear notificación para el usuario
        $notification = [
            'title' => 'Nuevo Ticket Creado',
            'message' => "Se ha creado un nuevo ticket: " . $title . "\nFecha y hora: " . date('d/m/Y H:i'),
            'created_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null
        ];

        // Agregar la notificación a la sesión
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        $_SESSION['notifications'][] = $notification;

        $pdo->commit();

        // Enviar correo de notificación
        require_once __DIR__ . '/../../lib/notificacionesTicket/enviarCorreo.php';

        $stmtUser = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $stmtCat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmtCat->execute([$category_id]);
        $catData = $stmtCat->fetch(PDO::FETCH_ASSOC);

        $correoEnviado = enviarNotificacionTicket(
            $userData['email'],
            $userData['username'],
            $title,
            $catData ? $catData['name'] : '',
            $priority,
            $description
        );

        if (!$correoEnviado) {
            $_SESSION['error_message'] = "El ticket se creó pero no se pudo enviar el correo de notificación.";
        } else {
            $_SESSION['success_message'] = "Se ha creado el ticket con éxito";
        }

        header("Location: ../cliente/dashboard.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al crear ticket: " . $e->getMessage());
        $_SESSION['error_message'] = "Error al crear el ticket: " . $e->getMessage();
        header("Location: crearTicket.php");
        exit();
    }
}
// Obtener categorías
try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($categories)) {
        throw new Exception("No se encontraron categorías en la base de datos");
    }
} catch (PDOException $e) {
    die("Error al obtener las categorías: " . $e->getMessage());
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Ticket - Sistema de Tickets</title>
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
            --header-bg: #fff;
            --sidebar-bg: #f8f9fa;
            --sidebar-hover: #e9ecef;
            --text-muted: #6c757d;
        }

        /* Tema oscuro */
        body.dark-mode {
            --color-bg: #1a1a1a;
            --color-text: #f8f9fa;
            --color-card: #2d2d2d;
            --color-border: #444;
            --header-bg: #1e1e1e;
            --sidebar-bg: #252525;
            --sidebar-hover: #333;
            --text-muted: #adb5bd;
        }
        
        body.dark-mode .card,
        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: var(--color-card);
            color: var(--color-text);
            border-color: var(--color-border);
        }
        
        body.dark-mode .header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--color-border);
        }
        
        body.dark-mode .sidebar {
            background-color: var(--sidebar-bg);
        }
        
        body.dark-mode .nav-link:hover {
            background-color: var(--sidebar-hover);
        }
        
        body.dark-mode .text-muted {
            color: var(--text-muted) !important;
        }

        /* Estilos para el botón de tema */
        #theme-button {
            background-color: transparent;
            color: var(--color-text);
            border: 1px solid var(--color-border);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.25rem;
        }

        #theme-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        body.dark-mode #theme-button {
            color: #fff;
            border-color: #555;
        }
        
        body.dark-mode #theme-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
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

        /* Ajustes específicos para modo oscuro */
        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: #3d3d3d;
            color: #fff;
            border-color: #555;
        }

        body.dark-mode .form-control::placeholder {
            color: #aaa;
        }

        body.dark-mode .sidebar {
            background-color: #252525;
            border-right: 1px solid #444;
        }

        body.dark-mode .sidebar .nav-link {
            color: #e0e0e0;
        }

        body.dark-mode .sidebar .nav-link:hover,
        body.dark-mode .sidebar .nav-link.active {
            background-color: #3d3d3d;
            color: #e67e22;
        }

        body.dark-mode .form-container {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        body.dark-mode .header {
            background: #252525;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            border-bottom: 2px solid #e67e22;
        }

        /* Header mejorado */
        .header {
            background: var(--color-card);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
            border-bottom: 2px solid var(--color-primary);
            transition: all 0.3s ease;
        }

        .user-menu {
            cursor: pointer;
            transition: all 0.3s;
            color: var(--color-primary);
        }

        .user-menu:hover {
            opacity: 0.8;
        }

        .user-menu {
            position: relative;
            z-index: 1050; /* Aseguramos que el menú esté por encima de otros elementos */
        }
        
        .user-menu .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 1051; /* Un z-index mayor que el del contenedor */
            min-width: 200px;
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            font-size: 1rem;
            color: var(--color-text);
            text-align: left;
            list-style: none;
            background-color: var(--color-card);
            background-clip: padding-box;
            border: 1px solid var(--color-border);
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .user-menu .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.5rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: var(--color-text);
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            transition: all 0.2s ease-in-out;
        }
        
        .user-menu .dropdown-item:hover {
            background-color: var(--sidebar-hover);
            color: var(--color-primary);
        }

        /* Animación de entrada del formulario */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Formulario */
        .form-container {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            animation: fadeInUp 0.6s ease-out forwards;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-primary-dark));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.6s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .form-container.animate::before {
            transform: scaleX(1);
        }

        .form-title {
            color: var(--color-primary);
            margin-bottom: 30px;
            font-weight: 700;
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            font-size: 1.5rem;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-primary-dark));
            border-radius: 2px;
            transition: width 0.4s ease;
        }

        .form-container:hover .form-title::after {
            width: 100px;
        }

        .form-title i {
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .form-container:hover .form-title i {
            transform: rotate(10deg) scale(1.1);
            color: var(--color-primary-dark);
        }

        .form-group {
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .form-group:hover {
            transform: translateX(5px);
        }

        .form-group label {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--color-primary);
            transition: all 0.3s ease;
        }

        .form-group:hover label {
            color: var(--color-primary-dark);
        }

        .form-group i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .form-group:hover i {
            transform: scale(1.2);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease, box-shadow 0.2s ease;
            background-color: var(--color-bg);
            color: var(--color-text);
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            transform: translateY(-1px);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--color-border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-cancel:active {
            transform: translateY(-1px);
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: white;
            position: relative;
            z-index: 1;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background-color: var(--color-primary-dark);
            transition: width 0.4s cubic-bezier(0.65, 0, 0.35, 1);
            z-index: -1;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:hover::before {
            width: 100%;
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-primary:hover i {
            animation: bounce 0.6s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        /* Navbar lateral */
        .sidebar {
            background-color: var(--sidebar-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: fit-content;
            position: relative;
            z-index: 1; /* Aseguramos que el sidebar esté por debajo del menú desplegable */
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

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* File input personalizado */
        .custom-file-upload {
            display: inline-block;
            padding: 12px 20px;
            cursor: pointer;
            background-color: var(--color-bg);
            border: 2px dashed var(--color-border);
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .custom-file-upload:hover {
            background-color: rgba(52, 152, 219, 0.05);
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-file-upload i {
            margin-right: 8px;
            color: var(--color-primary);
            transition: all 0.3s ease;
        }

        .custom-file-upload:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        .custom-file-upload::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .custom-file-upload:hover::after {
            transform: translateX(100%);
        }

        /* Estilos para el botón de tema */
        #theme-button {
            background-color: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text);
            transition: all 0.3s ease;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #theme-button:hover {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--color-primary);
        }

        body.dark-mode {
            --color-bg: #1a1a1a;
            --color-text: #e0e0e0;
            --color-card: #2d2d2d;
            --color-border: #444;
            --color-primary: #e67e22;
        }

        body.dark-mode .header {
            background-color: #1a1a1a;
            border-bottom: 1px solid #444;
        }

        body.dark-mode .sidebar {
            background-color: #2d2d2d;
            border-right: 1px solid #444;
        }

        body.dark-mode .form-container {
            background-color: #2d2d2d;
            border: 1px solid #444;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: #3d3d3d;
            border-color: #555;
            color: #fff;
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
                        <button id="theme-button" class="btn btn-sm" title="Cambiar tema">
                            <i class="fas fa-moon"></i> <span class="theme-text">Modo Oscuro</span>
                        </button>
                        <div class="user-menu position-relative">
                            <span class="d-flex align-items-center gap-2">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?> ▼
                            </span>
                            <div class="dropdown-menu position-absolute end-0 mt-2 shadow" 
                                 style="display: none; min-width: 180px; background-color: var(--color-card);">
                                <a href="../cliente/gestionPerfilUsuario.php" class="dropdown-item d-flex align-items-center gap-2">
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
                                <a class="nav-link d-flex align-items-center gap-2" href="../cliente/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="../cliente/misTickets.php">
                                    <i class="fas fa-ticket-alt"></i> Mis Tickets
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active d-flex align-items-center gap-2" href="crearTicket.php">
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
                                <i class="fas fa-plus-circle"></i>
                                <span>Crear Nuevo Ticket</span>
                            </h2>
                            <a href="../cliente/misTickets.php" class="btn btn-primary">
                                <i class="fas fa-ticket-alt me-1"></i> Ver Mis Tickets
                            </a>
                        </div>
                        
                        <div class="form-container">
                            <h3 class="form-title"><i class="fas fa-ticket-alt me-2"></i>Complete los datos del ticket</h3>
                            
                            <form action="crearTicket.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="title"><i class="fas fa-heading me-2"></i>Título:</label>
                                    <input type="text" id="title" name="title" class="form-control" placeholder="Ingrese un título descriptivo" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="category"><i class="fas fa-tag me-2"></i>Categoría:</label>
                                            <select id="category" name="category" class="form-control" required>
                                                <option value="">Selecciona una categoría</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description"><i class="fas fa-align-left me-2"></i>Descripción:</label>
                                    <textarea id="description" name="description" class="form-control" 
                                              placeholder="Describa el problema con detalle..." required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-paperclip me-2"></i>Adjuntar archivo (opcional):</label>
                                    <div class="custom-file-upload">
                                        <input type="file" id="attachment" name="attachment" class="form-control-file">
                                        <label for="attachment" class="custom-file-label">
                                            <i class="fas fa-cloud-upload-alt me-2"></i>
                                            <span id="file-name">Seleccionar archivo</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-cancel" onclick="window.location.href='dashboard.php'">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-1"></i> Crear Ticket
                                    </button>
                                </div>
                            </form>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para el manejo del tema oscuro y menú desplegable -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejo del tema oscuro
            const themeButton = document.getElementById('theme-button');
            
            // Obtener referencias a los elementos del botón
            const themeIcon = themeButton.querySelector('i');
            const themeText = themeButton.querySelector('.theme-text');
            
            // Verificar preferencia guardada
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
                themeIcon.className = 'fas fa-sun';
            }
            
            // Manejador del botón de tema
            themeButton.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                
                if (document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('darkMode', 'enabled');
                    themeIcon.className = 'fas fa-sun';
                } else {
                    localStorage.setItem('darkMode', 'disabled');
                    themeIcon.className = 'fas fa-moon';
                }
            });

            // Manejo del menú desplegable
            const userMenu = document.querySelector('.user-menu');
            const dropdownMenu = userMenu.querySelector('.dropdown-menu');
            
            userMenu.addEventListener('click', function(e) {
                e.preventDefault();
                const isOpen = dropdownMenu.style.display === 'block';
                
                // Cerrar todos los menús desplegables abiertos
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu !== dropdownMenu) menu.style.display = 'none';
                });
                
                // Alternar el menú actual
                dropdownMenu.style.display = isOpen ? 'none' : 'block';
            });

            // Cerrar el menú al hacer clic fuera de él
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target)) {
                    dropdownMenu.style.display = 'none';
                }
            });
        });
    </script>
    
    <!-- JavaScript -->
    <script>
        // Esperar a que el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Menú desplegable de usuario
            const userMenu = document.querySelector('.user-menu');
            const dropdownMenu = document.querySelector('.dropdown-menu');

            if (userMenu && dropdownMenu) {
                userMenu.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                });

                // Cerrar menú al hacer clic fuera
                document.addEventListener('click', () => {
                    dropdownMenu.style.display = 'none';
                });
            }


            // Tema oscuro/claro
            const themeButton = document.getElementById('theme-button');
            const body = document.body;

            if (themeButton) {
                // Verificar preferencia guardada
                if (localStorage.getItem('darkMode') === 'enabled') {
                    body.classList.add('dark-mode');
                    themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
                } else {
                    themeButton.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
                }

                // Manejar clic en el botón de tema
                themeButton.addEventListener('click', (e) => {
                    e.stopPropagation();
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
            }

            // Activar animación del contenedor
            const container = document.querySelector('.form-container');
            if (container) {
                setTimeout(() => {
                    container.classList.add('animate');
                }, 100);
            }

            // Mostrar nombre del archivo seleccionado
            const fileInput = document.getElementById('attachment');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileName = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
                    const fileNameElement = document.getElementById('file-name');
                    if (fileNameElement) {
                        fileNameElement.textContent = fileName;
                        
                        // Animación al seleccionar archivo
                        if (this.files[0]) {
                            fileNameElement.style.color = '#28a745';
                            fileNameElement.style.fontWeight = '600';
                            
                            // Resetear la animación
                            fileNameElement.style.animation = 'none';
                            void fileNameElement.offsetWidth; // Trigger reflow
                            fileNameElement.style.animation = 'fadeIn 0.5s ease-out';
                            
                            // Resetear el color después de la animación
                            setTimeout(() => {
                                fileNameElement.style.color = '';
                                fileNameElement.style.fontWeight = '';
                    }, 2000);
                }
            });
        });

        // Animación para el mensaje de éxito/error
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        // Aplicar animación a los mensajes de alerta
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach((alert, index) => {
            alert.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s forwards`;
        });
    </script>
</body>
</html>

