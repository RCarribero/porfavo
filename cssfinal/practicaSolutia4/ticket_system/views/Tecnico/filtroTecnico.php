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

// Obtener categorías para filtros
$categories = [];
$stmt = $pdo->query("SELECT id, name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tickets según filtros
$tickets = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_filters'])) {
    $status_filter = $_POST['status_filter'] ?? [];
    $priority_filter = $_POST['priority_filter'] ?? [];
    $category_filter = $_POST['category_filter'] ?? [];
    $search = $_POST['search'] ?? '';
    
    $sql = "SELECT t.*, u.username as cliente, c.name as categoria 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            JOIN categories c ON t.category_id = c.id
            WHERE 1=1";
            
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (t.id LIKE :search OR t.title LIKE :search OR t.description LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    if (!empty($status_filter)) {
        $sql .= " AND t.status IN (" . implode(',', array_fill(0, count($status_filter), '?')) . ")";
        $params = array_merge($params, $status_filter);
    }
    
    if (!empty($priority_filter)) {
        $sql .= " AND t.priority IN (" . implode(',', array_fill(0, count($priority_filter), '?')) . ")";
        $params = array_merge($params, $priority_filter);
    }
    
    if (!empty($category_filter)) {
        $sql .= " AND t.category_id IN (" . implode(',', array_fill(0, count($category_filter), '?')) . ")";
        $params = array_merge($params, $category_filter);
    }
    
    $sql .= " ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si no hay filtros aplicados, mostrar todos los tickets
    $sql = "SELECT t.*, u.username as cliente, c.name as categoria 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            JOIN categories c ON t.category_id = c.id
            ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC";
    $tickets = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Incluir el HTML proporcionado
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Técnico - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/estilodashboard.css">
    <style>
        .priority-urgent { background-color: #ffcccc; }
        .priority-high { background-color: #ffdddd; }
        .priority-medium { background-color: #fff3cd; }
        .priority-low { background-color: #d4edda; }
        .waiting-long { border-left: 4px solid #dc3545; }
        .waiting-medium { border-left: 4px solid #ffc107; }
        .waiting-short { border-left: 4px solid #28a745; }
        .ticket-details { display: none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .filters { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .filter-group { margin-bottom: 10px; }
        .history-item { margin-bottom: 5px; padding: 5px; border-bottom: 1px solid #eee; }
        .comment-item { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .attachment-item { display: inline-block; margin-right: 10px; }
        .btn-danger { background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-danger:hover { background-color: #c82333; }
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
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($user['role']); ?>)</span>
                    <div class="user-dropdown">
                        <a class="dropdown-item" href="../Tecnico/gestionPerfilTecnico.php">
                            <i class="fas fa-user-cog"></i> Editar Perfil
                        </a>
                        <a class="dropdown-item" href="/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=logout">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <nav class="navbar">
            <ul>
                <li><a href="../Tecnico/dashboardTecnico.php">Dashboard</a></li>
                <li><a href="../Tecnico/filtroTecnico.php" class="active">Filtros Avanzados</a></li>
            </ul>
        </nav>

        <div class="filters">
            <h3>Filtros Avanzados</h3>
            <form method="post">
                <div class="filter-group">
                    <label for="search">Buscar:</label>
                    <input type="text" id="search" name="search" placeholder="ID, título o contenido">
                    <button type="submit">Buscar</button>
                    <a href="../Tecnico/dashboardTecnico.php" class="button">Limpiar</a>
                </div>
                
                <div class="filter-group">
                    <label>Estado:</label>
                    <?php 
                    $statuses = ['open' => 'Abierto', 'in_progress' => 'En Progreso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado'];
                    foreach ($statuses as $value => $label): ?>
                        <label><input type="checkbox" name="status_filter[]" value="<?php echo $value; ?>" checked> <?php echo $label; ?></label>
                    <?php endforeach; ?>
                </div>
                
                <div class="filter-group">
                    <label>Prioridad:</label>
                    <?php 
                    $priorities = ['urgent' => 'Urgente', 'high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'];
                    foreach ($priorities as $value => $label): ?>
                        <label><input type="checkbox" name="priority_filter[]" value="<?php echo $value; ?>" checked> <?php echo $label; ?></label>
                    <?php endforeach; ?>
                </div>
                
                <div class="filter-group">
                    <label>Categorías:</label>
                    <?php foreach ($categories as $category): ?>
                        <label><input type="checkbox" name="category_filter[]" value="<?php echo $category['id']; ?>" checked> <?php echo htmlspecialchars($category['name']); ?></label>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="apply_filters">Aplicar Filtros</button>
            </form>
        </div>

        <div class="tickets-list">
            <h2>Tickets</h2>
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Ticket #<?php echo htmlspecialchars($_GET['updated']); ?> actualizado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Ticket eliminado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'delete'): ?>
                <div class="alert alert-danger">Error al eliminar el ticket.</div>
            <?php endif; ?>
            
            <?php if (count($tickets) > 0): ?>
            <table>
                <thead>
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
                            <a class="btn btn-primary" href="../Tecnico/detallesTecnico.php?id=<?php echo $ticket['id']; ?>">Detalles</a>
                            <a href="../Tecnico/ver_comentarios.php?ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-secondary">Ver Comentarios</a>
                            <?php if ($user['role'] === 'admin'): ?>
                                <form method="post" action="../Ticket/eliminar_ticket.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de querer eliminar este ticket?');">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <button type="submit" class="btn-danger">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No hay tickets encontrados con los filtros actuales.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeButton = document.getElementById('theme-button');
            const body = document.body;
            
            // Check for saved theme preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
                themeButton.textContent = 'Modo Claro';
            } else {
                themeButton.textContent = 'Modo Oscuro';
                localStorage.setItem('darkMode', 'disabled');
            }
        });
        
        function toggleDetails(ticketId) {
            const details = document.getElementById(`details-${ticketId}`);
            details.style.display = details.style.display === 'none' ? 'table-row' : 'none';
        }
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
