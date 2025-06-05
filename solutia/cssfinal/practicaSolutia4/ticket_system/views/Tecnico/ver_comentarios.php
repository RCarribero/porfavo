<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Validar si existe el parámetro 'ticket_id' en la URL
if (!isset($_GET['ticket_id']) || !is_numeric($_GET['ticket_id'])) {
    die("Error: No se proporcionó un 'ticket_id' válido.");
}

$ticket_id = intval($_GET['ticket_id']);

// Procesar la eliminación de comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $delete_id = intval($_POST['delete_comment_id']);
    $delete_sql = "DELETE FROM comments WHERE id = :id";

    try {
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute(['id' => $delete_id]);
        header("Location: ../Tecnico/ver_comentarios.php?ticket_id=" . $ticket_id);
        exit();
    } catch (PDOException $e) {
        die("Error al eliminar el comentario: " . $e->getMessage());
    }
}

// Agregar un nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_comment'])) {
    $comment = trim($_POST['new_comment']);
    $user_id = $_SESSION['id']; // Usar el ID del usuario autenticado

    if (!empty($comment)) {
        $insert_sql = "INSERT INTO comments (ticket_id, user_id, comment, created_at) VALUES (:ticket_id, :user_id, :comment, NOW())";

        try {
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([
                'ticket_id' => $ticket_id,
                'user_id' => $user_id,
                'comment' => $comment
            ]);
            header("Location: ../Tecnico/ver_comentarios.php?ticket_id=" . $ticket_id);
            exit();
        } catch (PDOException $e) {
            die("Error al agregar el comentario: " . $e->getMessage());
        }
    } else {
        $mensaje_error = "El comentario no puede estar vacío.";
    }
}

// Obtener los comentarios relacionados con el ticket
$sql = "SELECT c.id, c.user_id, u.username, c.comment, c.created_at 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.ticket_id = :ticket_id
        ORDER BY c.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $ticket_id]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener los comentarios: " . $e->getMessage());
}

// Obtener información del ticket
$ticket_sql = "SELECT t.title, t.status, u.username as cliente 
               FROM tickets t
               JOIN users u ON t.user_id = u.id
               WHERE t.id = :ticket_id";
$ticket_stmt = $pdo->prepare($ticket_sql);
$ticket_stmt->execute(['ticket_id' => $ticket_id]);
$ticket = $ticket_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentarios del Ticket #<?= htmlspecialchars($ticket_id) ?> - Sistema de Tickets</title>
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
        .form-control, .form-control:focus {
            padding: var(--space-sm);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            transition: border-color 0.2s ease;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        textarea.form-control {
            min-height: 120px;
        }

        /* Cards */
        .card {
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 6px var(--shadow-color);
            transition: transform 0.2s ease;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            margin-bottom: var(--space-md);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px var(--shadow-color);
        }

        .card-body {
            padding: var(--space-lg);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
        }

        /* Comentarios */
        .comment-list {
            margin: var(--space-lg) 0;
        }

        .comment-item {
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-md) 0;
            margin-bottom: var(--space-md);
        }

        .comment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .comment-author {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: var(--space-xs);
        }

        .comment-content {
            margin-bottom: var(--space-sm);
            white-space: pre-wrap;
            color: var(--text-color);
        }

        .comment-meta {
            font-size: 0.875rem;
            color: var(--text-color);
            opacity: 0.7;
        }

        /* Dark mode comments */
        .dark-mode .comment-content {
            color: white;
        }

        /* Formulario de comentarios */
        .form-label {
            color: var(--text-color);
        }

        /* Placeholder en modo oscuro */
        ::placeholder {
            color: var(--text-color);
            opacity: 0.5;
        }

        .dark-mode ::placeholder {
            color: white;
            opacity: 0.5;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md) 0;
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px var(--shadow-color);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .logo img {
            height: 40px;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        /* Nav links */
        .nav-links {
            display: flex;
            gap: var(--space-md);
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            background-color: var(--primary-color);
            color: white;
        }

        .nav-links a:hover, .nav-links a.active {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }

        /* Theme toggle button */
        #theme-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        #theme-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-color);
            opacity: 0.9;
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

        /* Main content */
        .main-content {
            padding: var(--space-md) 0;
        }

        /* Ticket info */
        .ticket-info {
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            margin-bottom: var(--space-lg);
            box-shadow: 0 4px 6px var(--shadow-color);
            border: 1px solid var(--border-color);
        }

        .ticket-info h2 {
            color: var(--primary-color);
            margin-bottom: var(--space-md);
        }

        .ticket-meta {
            display: flex;
            gap: var(--space-lg);
            margin-bottom: var(--space-md);
        }

        .ticket-meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        /* Alertas */
        .alert {
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-bottom: var(--space-md);
        }

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
                margin-top: var(--space-md);
            }

            .nav-links {
                margin-top: var(--space-md);
                justify-content: center;
            }

            .ticket-meta {
                flex-direction: column;
                gap: var(--space-sm);
            }
        }
    </style>
</head>
<body class="light-mode">
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" alt="Logo del Sistema">
            </div>
            <div class="nav-links">
                <a href="../Tecnico/dashboardTecnico.php">Panel Técnico</a>
                <a href="../Tecnico/gestionPerfilTecnico.php">Editar Perfil</a>
            </div>
            <div class="header-right">
                <div class="theme-toggle">
                    <button id="theme-button" class="btn btn-primary">Modo Oscuro</button>
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

        <main class="main-content">
            <!-- Información del ticket -->
            <div class="ticket-info">
                <h2>Ticket #<?= htmlspecialchars($ticket_id) ?>: <?= htmlspecialchars($ticket['title']) ?></h2>
                <div class="ticket-meta">
                    <div class="ticket-meta-item">
                        <i class="fas fa-user"></i>
                        <span>Cliente: <?= htmlspecialchars($ticket['cliente']) ?></span>
                    </div>
                    <div class="ticket-meta-item">
                        <i class="fas fa-info-circle"></i>
                        <span>Estado: <?= htmlspecialchars($ticket['status']) ?></span>
                    </div>
                </div>
                <a href="../Tecnico/detallesTecnico.php?id=<?= htmlspecialchars($ticket_id) ?>" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> Ver detalles del ticket
                </a>
            </div>

            <!-- Historial de comentarios -->
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-comments me-2"></i>Historial de Comentarios</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($comments)): ?>
                        <div class="comment-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-author">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($comment['username']) ?>
                                    </div>
                                    <div class="comment-content">
                                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                    </div>
                                    <div class="comment-meta">
                                        <i class="far fa-clock"></i> <?= htmlspecialchars($comment['created_at']) ?>
                                    </div>
                                    <form method="POST" action="ver_comentarios.php?ticket_id=<?= htmlspecialchars($ticket_id) ?>" class="mt-2" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este comentario?');">
                                        <input type="hidden" name="delete_comment_id" value="<?= htmlspecialchars($comment['id']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay comentarios disponibles para este ticket.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulario para nuevo comentario -->
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Agregar un Comentario</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($mensaje_error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="../Tecnico/ver_comentarios.php?ticket_id=<?= htmlspecialchars($ticket_id) ?>">
                        <div class="form-group mb-3">
                            <label for="new_comment" class="form-label">Nuevo comentario:</label>
                            <textarea name="new_comment" id="new_comment" class="form-control" rows="4" placeholder="Escribe tu comentario aquí..." required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Enviar Comentario
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

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
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>