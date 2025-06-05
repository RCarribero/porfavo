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
$sql_ticket = "SELECT t.*, c.name AS category_name 
               FROM tickets t 
               JOIN categories c ON t.category_id = c.id 
               WHERE t.id = :id AND t.user_id = :user_id";
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
    header("Location: ../Ticket/ver_ticket.php?id=$ticket_id&mensaje=Comentario+agregado+correctamente&tipo_mensaje=success");
    exit();
}

// Obtener comentarios
$sql_comments = "SELECT c.comment, c.created_at, u.username 
                 FROM comments c 
                 JOIN users u ON c.user_id = u.id 
                 WHERE c.ticket_id = :ticket_id 
                 ORDER BY c.created_at ASC";
$stmt = $pdo->prepare($sql_comments);
$stmt->execute(['ticket_id' => $ticket_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener archivos adjuntos
$sql_attachments = "SELECT * FROM attachments WHERE ticket_id = :ticket_id";
$stmt = $pdo->prepare($sql_attachments);
$stmt->execute(['ticket_id' => $ticket_id]);
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mensaje de éxito si existe
$mensaje = $_GET['mensaje'] ?? '';
$tipoMensaje = $_GET['tipo_mensaje'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Ticket - Sistema de Tickets</title>
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

        .ticket-container {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        body.dark-mode .ticket-container {
            background-color: #2c2c2c;
        }

        .ticket-title {
            color: var(--color-primary);
            margin-bottom: 20px;
            font-weight: 700;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .ticket-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ticket-meta-label {
            font-weight: 600;
            color: var(--color-primary);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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

        .priority-high {
            color: #dc3545;
            font-weight: 600;
        }

        .priority-medium {
            color: #fd7e14;
            font-weight: 600;
        }

        .priority-low {
            color: #28a745;
            font-weight: 600;
        }

        .comments-container {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        body.dark-mode .comments-container {
            background-color: #2c2c2c;
        }

        .comments-title {
            color: var(--color-primary);
            margin-bottom: 20px;
            font-weight: 700;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .comment {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: rgba(52, 152, 219, 0.05);
            border-left: 3px solid var(--color-primary);
        }

        body.dark-mode .comment {
            background-color: rgba(255, 140, 66, 0.05);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .comment-user {
            font-weight: 600;
            color: var(--color-primary);
        }

        .comment-date {
            color: var(--color-text);
            opacity: 0.7;
        }

        .comment-content {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .comment-form-container {
            background-color: var(--color-card);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        body.dark-mode .comment-form-container {
            background-color: #2c2c2c;
        }

        .comment-form-title {
            color: var(--color-primary);
            margin-bottom: 20px;
            font-weight: 700;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--color-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: var(--color-card);
            color: var(--color-text);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        body.dark-mode .form-control {
            background-color: #3c3c3c;
            border-color: #555;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .btn-submit {
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

        .btn-submit:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .btn-back {
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
            text-decoration: none;
        }

        .btn-back:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
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

        .no-comments {
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
                                <a class="nav-link d-flex align-items-center gap-2" href="../cliente/misTickets.php">
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
                                <span>Detalles del Ticket</span>
                            </h2>
                            <a href="javascript:history.back()" class="btn-back">
                                <i class="fas fa-arrow-left"></i>
                                <span>Volver</span>
                            </a>
                        </div>
                        
                        <?php if ($mensaje): ?>
                            <div class="alert <?= $tipoMensaje === 'success' ? 'alert-success' : 'alert-danger' ?>">
                                <i class="fas <?= $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                <?= htmlspecialchars($mensaje) ?>
                            </div>
                        <?php endif; ?>

                        <div class="ticket-container">
                            <h3 class="ticket-title">
                                <i class="fas fa-info-circle"></i>
                                <span><?= htmlspecialchars($ticket['title']) ?></span>
                            </h3>
                            
                            <div class="ticket-meta">
                                <div class="ticket-meta-item">
                                    <span class="ticket-meta-label"><i class="fas fa-tag"></i> Categoría:</span>
                                    <span><?= htmlspecialchars($ticket['category_name']) ?></span>
                                </div>
                                <div class="ticket-meta-item">
                                    <span class="ticket-meta-label"><i class="fas fa-flag"></i> Prioridad:</span>
                                    <span class="priority-<?= strtolower($ticket['priority']) ?>">
                                        <?= htmlspecialchars($ticket['priority']) ?>
                                    </span>
                                </div>
                                <div class="ticket-meta-item">
                                    <span class="ticket-meta-label"><i class="fas fa-calendar"></i> Creado:</span>
                                    <span><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></span>
                                </div>
                                <div class="ticket-meta-item">
                                    <span class="ticket-meta-label"><i class="fas fa-check-circle"></i> Estado:</span>
                                    <span class="status-badge status-<?= htmlspecialchars($ticket['status']) ?>">
                                        <?= htmlspecialchars($ticket['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="ticket-description">
                                <h4><i class="fas fa-align-left"></i> Descripción</h4>
                                <p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                            </div>

                            <?php if (!empty($attachments)): ?>
                                <div class="ticket-attachments">
                                    <h4><i class="fas fa-paperclip"></i> Archivos adjuntos</h4>
                                    <ul class="list-unstyled">
                                        <?php foreach ($attachments as $file): ?>
                                            <li class="mb-2">
                                                <a href="<?= htmlspecialchars($file['filepath']) ?>" 
                                                   download="<?= htmlspecialchars($file['filename']) ?>" 
                                                   class="text-decoration-none">
                                                    <i class="fas fa-file-alt"></i>
                                                    <span class="me-2"><?= htmlspecialchars($file['filename']) ?></span>
                                                    <span class="text-muted"><?= round($file['filesize'] / 1024, 2) ?> KB</span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="comments-container">
                            <h3 class="comments-title">
                                <i class="fas fa-comments"></i>
                                <span>Comentarios</span>
                            </h3>
                            
                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <span class="comment-user">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($comment['username']) ?>
                                        </span>
                                        <span class="comment-date">
                                            <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="comment-content">
                                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-comments">
                                    <i class="fas fa-info-circle"></i> No hay comentarios aún
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="comment-form-container">
                            <h3 class="comment-form-title">
                                <i class="fas fa-comment-dots"></i>
                                <span>Agregar Comentario</span>
                            </h3>
                            
                            <form method="POST">
                                <div class="form-group">
                                    <label for="comment"><i class="fas fa-pen"></i> Tu comentario:</label>
                                    <textarea id="comment" name="comment" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Enviar Comentario</span>
                                </button>
                            </form>
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
