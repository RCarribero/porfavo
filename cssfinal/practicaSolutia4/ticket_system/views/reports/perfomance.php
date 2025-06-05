<?php
// Iniciar sesión antes de cualquier salida HTML
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar mensajes de error previos
if (isset($_SESSION['error'])) {
    $previousError = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Verificar permisos - Corregido para usar 'role' en lugar de 'user_role'
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['admin', 'manager', 'supervisor'];

if (!in_array($userRole, $allowedRoles)) {
    // Esta parte no se ejecutará gracias a la corrección, pero la mantenemos por seguridad
    $_SESSION['error'] = "No tienes permisos para acceder a los informes de rendimiento.";
    header('Location: /solutia/cssfinal/practicaSolutia4/ticket_system/dashboard.php');
    exit;
}

// Incluir header
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../config/database.php';

// Cargar datos de tickets si no están disponibles
if (empty($ticketsByStatus) || empty($ticketsByCategory) || empty($ticketsByTechnician)) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Obtener tickets por estado
        if (empty($ticketsByStatus)) {
            $statusQuery = "SELECT status, COUNT(*) as count FROM tickets GROUP BY status";
            $statusStmt = $conn->prepare($statusQuery);
            $statusStmt->execute();
            $ticketsByStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Obtener tickets por categoría
        if (empty($ticketsByCategory)) {
            $categoryQuery = "SELECT c.name as category, COUNT(*) as count 
                             FROM tickets t 
                             JOIN categories c ON t.category_id = c.id 
                             GROUP BY t.category_id";
            $categoryStmt = $conn->prepare($categoryQuery);
            $categoryStmt->execute();
            $ticketsByCategory = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Obtener tickets por técnico
        if (empty($ticketsByTechnician)) {
            $techQuery = "SELECT CONCAT(u.first_name, ' ', u.last_name) as technician, COUNT(*) as count 
                         FROM tickets t 
                         JOIN users u ON t.assigned_to = u.id 
                         WHERE t.assigned_to IS NOT NULL 
                         GROUP BY t.assigned_to";
            $techStmt = $conn->prepare($techQuery);
            $techStmt->execute();
            $ticketsByTechnician = $techStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Obtener tiempo promedio de resolución
        if (empty($avgResolutionTime) || !isset($avgResolutionTime['avg_hours'])) {
            $timeQuery = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours 
                         FROM tickets 
                         WHERE status = 'resolved' AND resolved_at IS NOT NULL";
            $timeStmt = $conn->prepare($timeQuery);
            $timeStmt->execute();
            $avgResolutionTime = $timeStmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no hay tickets resueltos, establecer a cero
            if ($avgResolutionTime['avg_hours'] === null) {
                $avgResolutionTime['avg_hours'] = 0;
            }
        }
    } catch (Exception $e) {
        // Error silencioso - no mostramos mensaje de error ya que eliminamos el panel de debug
    }
}

// Asegurar que todas las variables están definidas
$ticketsByStatus = $ticketsByStatus ?? [];
$ticketsByCategory = $ticketsByCategory ?? [];
$ticketsByTechnician = $ticketsByTechnician ?? [];
$avgResolutionTime = $avgResolutionTime ?? ['avg_hours' => 0];
?>


<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gráficos de Rendimiento</h1>
        <a href="custom_report.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Informes
        </a>
    </div>
   
    <div class="row">
        <!-- Gráfico de tickets por estado -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 graph-container">
                <div class="card-header">
                    <h5 class="mb-0">Tickets por Estado</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ticketsByStatus)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-exclamation-circle"></i> No hay datos de tickets disponibles por estado.
                        </div>
                    <?php else: ?>
                        <canvas id="statusChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de tickets por categoría -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 graph-container">
                <div class="card-header">
                    <h5 class="mb-0">Tickets por Categoría</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ticketsByCategory)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-exclamation-circle"></i> No hay datos de tickets disponibles por categoría.
                        </div>
                    <?php else: ?>
                        <canvas id="categoryChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Gráfico de tickets por técnico -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 graph-container">
                <div class="card-header">
                    <h5 class="mb-0">Tickets por Técnico</h5>
                </div>
                <div class="card-body">
                    <canvas id="technicianChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Tiempo promedio de resolución -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 graph-container">
                <div class="card-header">
                    <h5 class="mb-0">Tiempo Promedio de Resolución</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <div class="text-center">
                        <h2 class="display-4">
                            <?php echo round($avgResolutionTime['avg_hours'], 1); ?>
                        </h2>
                        <p class="lead">horas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Estilos para modo oscuro -->
<style>
.dark-mode .graph-container {
    background-color: #121212 !important;
    color: white;
    border-color: #444;
}

/* Mejorar la visualización de los gráficos en modo oscuro */
.dark-mode .card-header {
    background-color: #2c2c2c !important;
    border-color: #444 !important;
}

.dark-mode .display-4 {
    color: #ff8c42 !important;
}

.dark-mode .lead {
    color: #e0e0e0 !important;
}
</style>


<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDarkMode = document.body.classList.contains('dark-mode');


    // Datos para el gráfico de estados
    const statusData = {
        labels: <?php echo json_encode(!empty($ticketsByStatus) ? array_map(function($status) {
            switch ($status['status']) {
                case 'open': return 'Abierto';
                case 'in_progress': return 'En progreso';
                case 'resolved': return 'Resuelto';
                default: return $status['status'];
            }
        }, $ticketsByStatus) : []); ?>,
        datasets: [{
            label: 'Tickets por Estado',
            data: <?php echo json_encode(!empty($ticketsByStatus) ? array_column($ticketsByStatus, 'count') : []); ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    };


    // Datos para el gráfico de categorías
    const categoryData = {
        labels: <?php echo json_encode(!empty($ticketsByCategory) ? array_column($ticketsByCategory, 'category') : []); ?>,
        datasets: [{
            label: 'Tickets por Categoría',
            data: <?php echo json_encode(!empty($ticketsByCategory) ? array_column($ticketsByCategory, 'count') : []); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };


    // Datos para el gráfico de técnicos
    const technicianData = {
        labels: <?php echo json_encode(!empty($ticketsByTechnician) ? array_column($ticketsByTechnician, 'technician') : []); ?>,
        datasets: [{
            label: 'Tickets por Técnico',
            data: <?php echo json_encode(!empty($ticketsByTechnician) ? array_column($ticketsByTechnician, 'count') : []); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.7)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };


    // Crear gráficos
    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: statusData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });


    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: categoryData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });


    new Chart(document.getElementById('technicianChart'), {
        type: 'bar',
        data: technicianData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>


<?php
// Incluir footer
require_once __DIR__ . '/../partials/footer.php';
?>

