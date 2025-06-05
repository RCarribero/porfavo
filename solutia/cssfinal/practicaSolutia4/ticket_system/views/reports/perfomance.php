<?php
// Incluir header
require_once __DIR__ . '/../partials/header.php';


// Asegurar que todas las variables están definidas
$ticketsByStatus = $ticketsByStatus ?? [];
$ticketsByCategory = $ticketsByCategory ?? [];
$ticketsByTechnician = $ticketsByTechnician ?? [];
$avgResolutionTime = $avgResolutionTime ?? ['avg_hours' => 0];
?>


<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gráficos de Rendimiento</h1>
        <a href="index.php?controller=report&action=custom" class="btn btn-secondary">
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
                    <canvas id="statusChart"></canvas>
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
                    <canvas id="categoryChart"></canvas>
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
.dark-mode .graph-container .card-header h5 {
    color: white;
}
.dark-mode .graph-container .card-body {
    color: white;
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

