<?php
// Incluir header
require_once dirname(__FILE__) . '/../partials/header.php';

// Asegurarse de que las variables estén definidas
if (!isset($kpis)) $kpis = [];
if (!isset($trends)) $trends = [];
if (!isset($priorityStats)) $priorityStats = [];
?>

<!-- Agregar script de Chart.js para los gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Estilos base */
    .chart-container {
        height: 350px;
        position: relative;
    }

    /* SOLUCIÓN DEFINITIVA: Estilos inline para las tarjetas KPI */
    .kpi-card {
        border-radius: 8px !important;
        border: 1px solid rgba(0,0,0,0.1) !important;
        transition: all 0.3s ease !important;
        margin-bottom: 1rem !important;
    }
    
    .kpi-card:hover {
        transform: translateY(-5px) !important;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
    
    .kpi-card .card-body {
        padding: 1.5rem !important;
    }
    
    .kpi-card .card-title {
        font-size: 1.1rem !important;
        font-weight: 500 !important;
        margin-bottom: 0.5rem !important;
        color: white !important;
    }
    
    .kpi-card h2 {
        font-size: 2.2rem !important;
        font-weight: 700 !important;
        margin: 0 !important;
        color: white !important;
    }

    /* Colores fijos para las tarjetas KPI */
    .kpi-primary {
        background-color: #4a90e2 !important;
        border-color: #4a90e2 !important;
    }
    
    .kpi-warning {
        background-color: #f39c12 !important;
        border-color: #f39c12 !important;
    }
    
    .kpi-success {
        background-color: #27ae60 !important;
        border-color: #27ae60 !important;
    }
    
    .kpi-info {
        background-color: #3498db !important;
        border-color: #3498db !important;
    }

    /* Resto de estilos del dashboard */
    body.dark-mode {
        background-color: #1a1a1a;
    }
    
    body.dark-mode .card:not(.kpi-card) {
        background-color: #2d2d2d;
        border-color: #444;
    }
    
    body.dark-mode .card-header:not(.kpi-card) {
        background-color: #2d2d2d;
        border-bottom-color: #444;
        color: #ffffff;
    }
    
    body.dark-mode .card-body:not(.kpi-card) {
        background-color: #2d2d2d;
        color: #ffffff;
    }

    /* Estilos para el botón */
    .btn-orange {
        background-color: #4a90e2 !important;
        border-color: #4a90e2 !important;
        color: white !important;
        padding: 0.8rem 1.5rem;
        font-weight: 500;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
    }
    
    .btn-orange:hover {
        background-color: #357abd !important;
        border-color: #357abd !important;
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    body.dark-mode .btn-orange {
        background-color: #ff9800 !important;
        border-color: #ff9800 !important;
        color: white !important;
    }
    
    body.dark-mode .btn-orange:hover {
        background-color: #f57c00 !important;
        border-color: #f57c00 !important;
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(255,255,255,0.1);
    }

    /* Títulos y texto */
    h1 {
        font-size: 2rem;
        font-weight: 500;
        color: var(--text-color);
        margin-bottom: 1.5rem;
        position: relative;
    }

    h1::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 80px;
        height: 4px;
        background: var(--gradient-start);
        opacity: 0.8;
    }

    body.dark-mode h1 {
        color: #ffffff;
    }

    h1 i {
        color: var(--text-color);
    }

    body.dark-mode h1 i {
        color: #ffffff !important;
    }
</style>

<div class="container mt-4">
    <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard de Tickets</h1>

    <?php
    // Carga automática de datos si están vacíos
    if (empty($kpis) || empty($trends) || empty($priorityStats)) {
        include_once dirname(__DIR__, 2) . '/config/database.php';
        $data = getReportData();
        $kpis = $data['kpis'];
        $trends = $data['trends'];
        $priorityStats = $data['priorityStats'];
    }
    ?>

    <!-- KPIs principales con estilos fijos -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card kpi-card kpi-primary">
                <div class="card-body text-center">
                    <h5 class="card-title">Total de Tickets</h5>
                    <h2>
                        <?php
                        echo (isset($kpis['total_tickets']) && is_numeric($kpis['total_tickets'])) ? (int)$kpis['total_tickets'] : 0;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card kpi-warning">
                <div class="card-body text-center">
                    <h5 class="card-title">Tickets Abiertos</h5>
                    <h2>
                        <?php
                        echo (isset($kpis['open_tickets']) && is_numeric($kpis['open_tickets'])) ? (int)$kpis['open_tickets'] : 0;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card kpi-success">
                <div class="card-body text-center">
                    <h5 class="card-title">Tickets Cerrados</h5>
                    <h2>
                        <?php
                        echo (isset($kpis['closed_tickets']) && is_numeric($kpis['closed_tickets'])) ? (int)$kpis['closed_tickets'] : 0;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card kpi-info">
                <div class="card-body text-center">
                    <h5 class="card-title">Tiempo Promedio</h5>
                    <h2>
                        <?php
                        echo (isset($kpis['avg_resolution_time']) && is_numeric($kpis['avg_resolution_time'])) ? $kpis['avg_resolution_time'] : 0;
                        ?> hrs
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tickets por Estado -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie me-2"></i>Tickets por Estado</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Forzar la recuperación de tickets_by_status si está vacío
                    if (!isset($kpis['tickets_by_status']) || !is_array($kpis['tickets_by_status']) || count($kpis['tickets_by_status']) === 0) {
                        // Intento de recuperar directamente de la base de datos
                        $db = new Database();
                        $sql = "SELECT status, COUNT(*) as total FROM tickets GROUP BY status ORDER BY total DESC";
                        $kpis['tickets_by_status'] = $db->query($sql);
                    }
                    
                    // Verificación actualizada
                    if (isset($kpis['tickets_by_status']) && is_array($kpis['tickets_by_status']) && count($kpis['tickets_by_status']) > 0): 
                    ?>
                        <canvas id="statusChart" class="chart-container"></canvas>
                        <div class="mt-3">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kpis['tickets_by_status'] as $status): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                switch ($status['status']) {
                                                    case 'open': echo 'Abierto'; break;
                                                    case 'in_progress': echo 'En Progreso'; break;
                                                    case 'resolved': echo 'Resuelto'; break;
                                                    case 'closed': echo 'Cerrado'; break;
                                                    default: echo htmlspecialchars($status['status']); break;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo (int)$status['total']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No hay datos de tickets por estado.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Tickets por Prioridad -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Tickets por Prioridad</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($priorityStats)): ?>
                        <canvas id="priorityChart" class="chart-container"></canvas>
                        <div class="mt-3">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Prioridad</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($priorityStats as $p): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                switch ($p['priority']) {
                                                    case 'urgent': echo 'Urgente'; break;
                                                    case 'high': echo 'Alta'; break;
                                                    case 'medium': echo 'Media'; break;
                                                    case 'low': echo 'Baja'; break;
                                                    default: echo htmlspecialchars($p['priority']); break;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo (int)$p['total']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No hay datos de tickets por prioridad.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line me-2"></i>Tendencia de Tickets (Últimos 6 meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" class="chart-container"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Informes</h5>
                </div>
                <div class="card-body">
                    <p>Para generar informes personalizados con filtros avanzados, haga clic en el siguiente botón:</p>
                    <a href="index.php?controller=report&action=custom" class="btn btn-orange">
                        <i class="fas fa-file-alt"></i> Informes Personalizados
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuración para modo oscuro
    const darkMode = document.body.classList.contains('dark-mode');
    
    // Colores para los gráficos
    const gridColor = darkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const textColor = darkMode ? '#ffffff' : '#666';
    const backgroundColor = darkMode ? '#2d2d2d' : '#ffffff';
    
    // Datos para los gráficos
    const statusLabels = [<?php 
        if (isset($kpis['tickets_by_status']) && is_array($kpis['tickets_by_status'])) {
            foreach ($kpis['tickets_by_status'] as $status) {
                $statusLabel = '';
                switch ($status['status']) {
                    case 'open': $statusLabel = 'Abierto'; break;
                    case 'in_progress': $statusLabel = 'En Progreso'; break;
                    case 'resolved': $statusLabel = 'Resuelto'; break;
                    case 'closed': $statusLabel = 'Cerrado'; break;
                    default: $statusLabel = $status['status']; break;
                }
                echo "'" . $statusLabel . "',";
            }
        } else {
            echo "'Abierto','En Progreso','Resuelto','Cerrado'";
        }
    ?>];
    
    const statusValues = [<?php 
        if (isset($kpis['tickets_by_status']) && is_array($kpis['tickets_by_status'])) {
            foreach ($kpis['tickets_by_status'] as $status) {
                echo ($status['total'] ?? 0) . ",";
            }
        } else {
            echo "0,0,0,0";
        }
    ?>];
    
    const priorityLabels = [<?php 
        if (isset($priorityStats) && is_array($priorityStats)) {
            foreach ($priorityStats as $p) {
                echo "'" . ucfirst($p['priority'] ?? 'Desconocido') . "',";
            }
        } else {
            echo "'Baja','Media','Alta','Urgente'";
        }
    ?>];
    
    const priorityValues = [<?php 
        if (isset($priorityStats) && is_array($priorityStats)) {
            foreach ($priorityStats as $p) {
                echo ($p['total'] ?? 0) . ",";
            }
        } else {
            echo "0,0,0,0";
        }
    ?>];
    
    const trendLabels = [<?php 
        if (isset($trends) && is_array($trends)) {
            foreach ($trends as $trend) {
                echo "'" . ($trend['period'] ?? '') . "',";
            }
        }
    ?>];
    
    const trendTickets = [<?php 
        if (isset($trends) && is_array($trends)) {
            foreach ($trends as $trend) {
                echo ($trend['total_tickets'] ?? 0) . ",";
            }
        }
    ?>];
    
    const trendClosed = [<?php 
        if (isset($trends) && is_array($trends)) {
            foreach ($trends as $trend) {
                echo ($trend['closed_tickets'] ?? 0) . ",";
            }
        }
    ?>];
    
    // Crear gráficos
    window.onload = function() {
        // Gráfico de estados
        if (statusLabels.length > 0) {
            new Chart(document.getElementById('statusChart'), {
                type: 'pie',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        label: 'Tickets por Estado',
                        data: statusValues,
                        backgroundColor: [
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderColor: darkMode ? '#444' : '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribución por Estado',
                            color: textColor
                        }
                    }
                }
            });
        }
        
        // Gráfico de prioridades
        if (priorityLabels.length > 0) {
            new Chart(document.getElementById('priorityChart'), {
                type: 'bar',
                data: {
                    labels: priorityLabels,
                    datasets: [{
                        label: 'Tickets por Prioridad',
                        data: priorityValues,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderColor: darkMode ? '#444' : '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Distribución por Prioridad',
                            color: textColor
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        y: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de tendencias
        if (trendLabels.length > 0) {
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Total Tickets',
                        data: trendTickets,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        borderWidth: 2
                    }, {
                        label: 'Tickets Cerrados',
                        data: trendClosed,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tendencia de Tickets',
                            color: textColor
                        },
                        legend: {
                            labels: {
                                color: textColor
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    }
                }
            });
        }
    };
</script>

<?php
// Incluir footer
require_once dirname(__FILE__) . '/../partials/footer.php';
?>