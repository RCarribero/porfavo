<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id'])) {
    header('Location: ../sesion/login.php');
    exit;
}

// Verificar que sea administrador o técnico
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Consulta de rol simplificada
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin' && $user['role'] !== 'tech') {
    header('Location: ../cliente/dashboard.php');
    exit();
}

// Obtener todos los usuarios si eres admin o tech
$users = [];
if ($user && ($user['role'] === 'admin' || $user['role'] === 'tech')) {
    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
        <h1><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios</h1>
        <!-- Corregido: URL relativa al controlador frontal -->
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
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
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-user me-1"></i>Usuario</th>
                            <th><i class="fas fa-envelope me-1"></i>Email</th>
                            <th><i class="fas fa-user-tag me-1"></i>Rol</th>
                            <th><i class="far fa-calendar-alt me-1"></i>Fecha de Creación</th>
                            <th><i class="fas fa-tools me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay usuarios registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php 
                                            $roles = [
                                                'admin' => 'Administrador',
                                                'tech' => 'Técnico',
                                                'client' => 'Cliente'
                                            ];
                                            echo $roles[$user['role']] ?? $user['role']; 
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <!-- Ruta corregida con ID en la URL -->
                                            <a href="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit me-1"></i> Editar
                                            </a>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                                            </button>
                                        </div>
                                        
                                        <!-- Modal de confirmación para eliminar -->
                                        <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Confirmar eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ¿Estás seguro de que deseas eliminar al usuario <strong><?php echo htmlspecialchars($user['username']); ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <!-- Corregir la URL de eliminación -->
                                                        <form action="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=delete" method="post">
                                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <!-- Corregido: URL relativa al controlador frontal -->
        <a href="../../index.php?controller=admin&action=dashboard" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Panel de Administración
        </a>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            }
        });
    });
</script>

<?php
// Incluir footer
require_once __DIR__ . '/../partials/footer.php';
?>