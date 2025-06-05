<?php
require_once dirname(__FILE__) . '/../partials/header.php';
?>

<style>
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
    body.dark-mode .dataTables_length label,
    body.dark-mode .dataTables_filter label {
        color: #ffffff !important;
    }
    
    /* Inputs y selects */
    body.dark-mode .dataTables_wrapper select,
    body.dark-mode .dataTables_wrapper input[type="search"] {
        background-color: #2d2d2d !important;
        color: #ffffff !important;
        border-color: #444444 !important;
    }
    
    /* Placeholder de búsqueda */
    body.dark-mode .dataTables_wrapper input[type="search"]::placeholder {
        color: #999999 !important;
    }
    
    /* Estilos para los iconos en modo claro/oscuro */
    .table th i {
        color: #000000; /* Negro en modo claro */
    }
    
    body.dark-mode .table th i {
        color: #ffffff !important; /* Blanco en modo oscuro */
    }
    
    h1 i {
        color: #000000; /* Negro en modo claro */
    }
    
    body.dark-mode h1 i {
        color: #ffffff !important; /* Blanco en modo oscuro */
    }
    
    /* Botones de editar en modo oscuro */
    body.dark-mode .btn-primary {
        background-color: #ff8c42 !important;
        border-color: #ff8c42 !important;
    }
    
    body.dark-mode .btn-primary:hover {
        background-color: #e67e3c !important;
        border-color: #e67e3c !important;
    }
    
    /* Números de paginación en modo oscuro */
    body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #ff8c42 !important;
        border-color: #ff8c42 !important;
        color: white !important;
    }
    
    body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button:not(.disabled):hover {
        color: #ff8c42 !important;
        background: transparent !important;
        border-color: #ff8c42 !important;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tags me-2"></i>Gestión de Categorías</h1>
        <a href="index.php?controller=category&action=create" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Nueva Categoría
        </a>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="alert alert-info">
                    No hay categorías registradas.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="categoriesTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                <th><i class="fas fa-tag me-1"></i>Nombre</th>
                                <th><i class="fas fa-align-left me-1"></i>Descripción</th>
                                <th><i class="fas fa-tools me-1"></i>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                    <!-- CAMBIO: Eliminar la celda que mostraría el estado activo/inactivo -->
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="index.php?controller=category&action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary me-1">
                                            <i class="fas fa-edit me-1"></i> Editar
                                        </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $category['id']; ?>">
                                            <i class="fas fa-trash-alt me-1"></i> Eliminar
                                        </button>
                                        </div>
                                        
                                        <!-- Modal de confirmación para eliminar -->
                                        <div class="modal fade" id="deleteModal<?php echo $category['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $category['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $category['id']; ?>">Confirmar eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ¿Está seguro de que desea eliminar la categoría <strong><?php echo htmlspecialchars($category['name']); ?></strong>?
                                                        <p class="text-danger mt-2">
                                                            <i class="bi bi-exclamation-triangle"></i> Esta acción no se puede deshacer. Si la categoría tiene tickets asociados, no podrá ser eliminada.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <form action="index.php?controller=category&action=delete" method="post" style="display: inline;">
                                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
require_once __DIR__ . '/../partials/footer.php';
?>
