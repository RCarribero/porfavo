<?php
// Incluir header
require_once __DIR__ . '/../partials/header.php';

// Asegurar que $report está definido
$report = $report ?? [];
$startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $endDate ?? date('Y-m-d');
$selectedTechnician = $selectedTechnician ?? '';
$selectedCategory = $selectedCategory ?? '';
$selectedStatus = $selectedStatus ?? '';
?>

<style>
    :root {
        --color-primary: #3498db;
        --color-primary-dark: #2c3e50;
        --color-bg: #f8f9fa;
        --color-text: #343a40;
        --color-card: #ffffff;
        --color-border: #dee2e6;
        --color-success: #28a745;
        --color-danger: #dc3545;
        --color-warning: #ffc107;
        --color-info: #3498db;
        --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    }

    body.dark-mode {
        --color-primary: #ff8c42;
        --color-primary-dark: #2c3e50;
        --color-bg: #121212;
        --color-text: #f8f9fa;
        --color-card: #1e1e1e;
        --color-border: #444;
        --shadow-sm: 0 2px 4px rgba(255,255,255,0.1);
        --shadow-md: 0 4px 6px rgba(255,255,255,0.1);
        --shadow-lg: 0 10px 15px rgba(255,255,255,0.1);
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background-color: var(--color-bg);
        color: var(--color-text);
        transition: all 0.3s ease;
    }

    /* Estilos para la tabla en modo oscuro */
    body.dark-mode .table {
        background-color: #000000 !important;
        color: #ffffff !important;
    }
    
    body.dark-mode .table th,
    body.dark-mode .table td,
    body.dark-mode .table thead th {
        color: #ffffff !important;
        border-color: #444444 !important;
        background-color: #000000 !important;
    }
    
    body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) {
        --bs-table-accent-bg: #1a1a1a !important;
        background-color: #1a1a1a !important;
    }
    
    body.dark-mode .table-striped > tbody > tr:nth-of-type(even) {
        --bs-table-accent-bg: #2d2d2d !important;
        background-color: #2d2d2d !important;
    }
    
    body.dark-mode .table-hover > tbody > tr:hover {
        --bs-table-accent-bg: #3d3d3d !important;
        background-color: #3d3d3d !important;
    }
    
    /* Asegurar que el fondo de la tarjeta también sea oscuro */
    body.dark-mode .card {
        background-color: #1a1a1a !important;
        border-color: #444444 !important;
    }
    
    body.dark-mode .card-body {
        background-color: #1a1a1a !important;
    }
    
    /* Texto alrededor de la tabla en blanco */
    body.dark-mode .container h1,
    body.dark-mode .container .btn,
    body.dark-mode .container .btn i,
    body.dark-mode .alert,
    body.dark-mode .alert a,
    body.dark-mode .dataTables_info,
    body.dark-mode .dataTables_paginate {
        color: #ffffff !important;
    }

    /* Texto de la tabla en modo oscuro */
    body.dark-mode .dataTables_length,
    body.dark-mode .dataTables_filter,
    body.dark-mode .dataTables_length select,
    body.dark-mode .dataTables_filter input {
        color: #ffffff !important;
    }

    /* Estilos para el botón de selección de entradas */
    .dataTables_length select {
        background-color: var(--color-bg);
        border: 1px solid var(--color-border);
        color: var(--color-text);
        padding: 8px 16px;
        border-radius: 4px;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    /* Ajustar el tamaño y espaciado del icono del botón de selección */
    .dataTables_length select::after {
        content: "\f078"; /* Icono de Font Awesome para el menú desplegable */
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        font-size: 1rem;
    }

    body.dark-mode .dataTables_length select {
        background-color: #1e1e1e;
        border-color: #444;
        color: #f8f9fa;
    }

    .dataTables_length select:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }

    body.dark-mode .dataTables_length select:focus {
        border-color: #ff8c42;
        box-shadow: 0 0 0 0.2rem rgba(255, 140, 66, 0.25);
    }

    body.dark-mode .dataTables_length label,
    body.dark-mode .dataTables_filter label {
        color: #ffffff !important;
    }

    body.dark-mode {
        --color-primary: #ff8c42;
        --color-primary-dark: #2c3e50;
        --color-bg: #121212;
        --color-text: #f8f9fa;
        --color-card: #1e1e1e;
        --color-border: #444;
        --shadow-sm: 0 2px 4px rgba(255,255,255,0.1);
        --shadow-md: 0 4px 6px rgba(255,255,255,0.1);
        --shadow-lg: 0 10px 15px rgba(255,255,255,0.1);
    }

    body.dark-mode .card-header h6 {
        color: var(--color-primary);
    }

    body.dark-mode .form-group label,
    body.dark-mode .form-group .form-label {
        color: #f8f9fa !important;
    }

    .report-container {
        background-color: var(--color-card);
        border-radius: 15px;
        padding: 35px;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
        border: 1px solid var(--color-border);
        margin-bottom: 40px;
    }

    /* Add margin to the title section */
    .container-fluid > .d-flex {
        margin-top: 40px;
    }

    .h3 {
        color: #000000 !important;
        font-weight: 600;
    }

    .h3 i,
    .h3 .fas {
        color: #000000 !important;
    }

    .h3 .text-primary {
        color: #000000 !important;
    }

    body.dark-mode .h3 {
        color: #f8f9fa !important;
    }

    body.dark-mode .h3 i,
    body.dark-mode .h3 .fas {
        color: #f8f9fa !important;
    }

    body.dark-mode .h3 .text-primary {
        color: #f8f9fa !important;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-control {
        padding: 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        background-color: var(--color-bg);
        border: 1px solid var(--color-border);
        color: var(--color-text);
    }

    /* Estilos específicos para los campos de fecha */
    .form-control.date-input {
        position: relative;
        padding-right: 35px; /* Ajustar el padding para el icono */
    }

    /* Ocultar el icono nativo del navegador */
    .form-control.date-input::-webkit-calendar-picker-indicator {
        display: none;
    }

    .form-control.date-input::after {
        content: "\f073"; /* Icono del calendario de Font Awesome */
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--color-text);
    }

    body.dark-mode .form-control.date-input::after {
        color: #f8f9fa;
    }

    .form-control:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }

    body.dark-mode .form-control {
        background-color: #1e1e1e;
        border-color: #444;
        color: #f8f9fa;
    }

    body.dark-mode .form-control:focus {
        border-color: #ff8c42;
        box-shadow: 0 0 0 0.2rem rgba(255, 140, 66, 0.25);
    }

    /* Iconos del calendario en modo oscuro */
    body.dark-mode .form-control input[type="date"] {
        color: #f8f9fa;
    }

    body.dark-mode .form-control input[type="date"]::-webkit-calendar-picker-indicator {
        filter: brightness(0) invert(1);
        -webkit-filter: brightness(0) invert(1);
        -moz-filter: brightness(0) invert(1);
        -o-filter: brightness(0) invert(1);
        -ms-filter: brightness(0) invert(1);
    }

    body.dark-mode .form-control input[type="date"]::-ms-calendar-picker-indicator {
        filter: brightness(0) invert(1);
    }

    /* Iconos del calendario en modo oscuro */
    body.dark-mode .form-control::before,
    body.dark-mode .form-control::after {
        color: #f8f9fa !important;
    }

    body.dark-mode .form-control input[type="date"]::-webkit-calendar-picker-indicator {
        color: #f8f9fa !important;
        filter: brightness(0) invert(1);
    }

    .card {
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        background-color: var(--color-card);
        border-left: 5px solid var(--color-primary);
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .table-responsive {
        margin-top: 30px;
        overflow-x: auto;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background-color: var(--color-primary);
        color: white;
        border: none;
        padding: 15px;
        font-weight: 600;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
    }

    .card {
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        background-color: var(--color-card);
        border-left: 5px solid var(--color-primary);
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--color-primary);
    }

    .card-header {
        padding: 15px;
        border-bottom: 1px solid var(--color-border);
    }

    .card-header h6 {
        color: var(--color-primary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    body.dark-mode .card-header h6 {
        color: #ff8c42 !important;
    }

    .btn-primary {
        background-color: var(--color-primary);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-size: 1rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--color-primary);
    }

    .btn-primary:hover {
        background-color: var(--color-primary-dark);
        transform: translateY(-3px);
        color: white;
        box-shadow: var(--shadow-lg);
    }

    .btn-primary i {
        font-size: 1.2rem;
        margin-right: 8px;
    }

    body.dark-mode .btn-primary {
        background-color: #ff8c42;
        border-color: #ff8c42;
    }

    body.dark-mode .btn-primary:hover {
        background-color: #ff8c42;
        border-color: #ff8c42;
    }

    .btn-secondary {
        background-color: var(--color-bg);
        color: var(--color-text);
        border: 1px solid var(--color-border);
        padding: 0.5rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: var(--color-primary);
        color: white;
        border-color: var(--color-primary);
    }

    .btn-secondary i {
        color: var(--color-text);
        transition: all 0.3s ease;
    }

    .btn-secondary:hover i {
        color: white;
    }

    .h3 {
        color: var(--color-primary);
        font-weight: 400;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-chart-bar me-2"></i>Informes Personalizados</h1>
        <button type="button" class="btn btn-primary" onclick="window.location.href='../reports/dashboard.php'">
            <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
        </button>
    </div>
    
    <!-- Filtros de Informe Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-filter me-2"></i>
            Filtros de Informe
        </h6>
    </div>
        <div class="card-body">
            <form method="post" action="../reports/custom_report.php" id="reportForm">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="start_date">Fecha Inicio</label>
                            <input type="date" class="form-control date-input" id="start_date" name="start_date" 
                                value="<?php echo $startDate; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="end_date">Fecha Fin</label>
                            <input type="date" class="form-control date-input" id="end_date" name="end_date" 
                                value="<?php echo $endDate; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="technician">Técnico</label>
                            <select class="form-control" id="technician" name="technician">
                                <option value="">Todos</option>
                                <?php foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>" <?php echo ($selectedTechnician == $tech['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tech['username']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="category">Categoría</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Todas</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($selectedCategory == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Estado</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="open" <?php echo ($selectedStatus == 'open') ? 'selected' : ''; ?>>Abierto</option>
                                <option value="in_progress" <?php echo ($selectedStatus == 'in_progress') ? 'selected' : ''; ?>>En Progreso</option>
                                <option value="resolved" <?php echo ($selectedStatus == 'resolved') ? 'selected' : ''; ?>>Resuelto</option>
                                <option value="closed" <?php echo ($selectedStatus == 'closed') ? 'selected' : ''; ?>>Cerrado</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Generar Informe
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($report)): ?>
    <!-- Resultados del Informe Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-chart-line me-2"></i>
            Resultados del Informe
        </h6>
            <div class="dropdown no-arrow">
                <a href="index.php?controller=report&action=export&format=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&technician=<?php echo $selectedTechnician; ?>&category=<?php echo $selectedCategory; ?>&status=<?php echo $selectedStatus; ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Exportar CSV
                </a>
                <a href="index.php?controller=report&action=export&format=pdf&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&technician=<?php echo $selectedTechnician; ?>&category=<?php echo $selectedCategory; ?>&status=<?php echo $selectedStatus; ?>" class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                </a>
                <a href="index.php?controller=report&action=performance" class="btn btn-sm btn-primary">
                    <i class="bi bi-graph-up"></i> Ver Gráficos
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Categoría</th>
                            <th>Creado</th>
                            <th>Actualizado</th>
                            <th>Cliente</th>
                            <th>Técnico</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $this->getStatusColor($ticket['status']); ?>">
                                    <?php echo $this->getStatusLabel($ticket['status']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($ticket['priority']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['category_name']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></td>
                            <td><?php echo htmlspecialchars($ticket['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['technician_name'] ?? 'Sin asignar'); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="index.php?controller=report&action=export&format=csv&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-success" title="Exportar a CSV">
                                        <i class="bi bi-file-earmark-excel"></i>
                                    </a>
                                    <a href="index.php?controller=report&action=export&format=pdf&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-danger" title="Exportar a PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-info">
            No se encontraron resultados para los filtros seleccionados.
        </div>
    <?php endif; ?>
</div>

<script>
    $(document).ready(function() {
        const table = $('#reportTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            },
            order: [[0, 'desc']],
            initComplete: function() {
                // Customize DataTables elements
                const container = this.api().table().container();
                
                // Style length menu ("Mostrar X entradas")
                $(container).find('.dataTables_length label').css('color', function() {
                    return $(document.body).hasClass('dark-mode') ? 'var(--color-text)' : '#000';
                });
                
                // Style search input ("Buscar:")
                $(container).find('.dataTables_filter label').css('color', function() {
                    return $(document.body).hasClass('dark-mode') ? 'var(--color-text)' : '#000';
                });
                
                // Style search input text
                $(container).find('.dataTables_filter input[type="search"]').css('color', function() {
                    return $(document.body).hasClass('dark-mode') ? 'var(--color-text)' : '#000';
                });
                
                // Style search placeholder
                $(container).find('.dataTables_filter input[type="search"]').attr('placeholder', function() {
                    return 'Buscar...';
                });
                
                // Listen for dark mode changes
                $(document.body).on('themeChange', function() {
                    const isDark = $(document.body).hasClass('dark-mode');
                    $(container).find('.dataTables_length label, .dataTables_filter label').css('color', isDark ? 'var(--color-text)' : '#000');
                    $(container).find('.dataTables_filter input[type="search"]').css('color', isDark ? 'var(--color-text)' : '#000');
                });
            }
        });

        // Add dark mode class when body has dark-mode class
        if (document.body.classList.contains('dark-mode')) {
            table.table().container().classList.add('dark-mode');
        }

        // Listen for dark mode toggle
        document.body.addEventListener('themeChange', function() {
            if (document.body.classList.contains('dark-mode')) {
                table.table().container().classList.add('dark-mode');
            } else {
                table.table().container().classList.remove('dark-mode');
            }
        });
    });
</script>

<?php
// Incluir footer
require_once __DIR__ . '/../partials/footer.php';
?>