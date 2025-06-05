<?php
require_once dirname(__FILE__) . '/../partials/header.php';
?>

<div class="container mt-4">
    <style>
        /* Estilos para el formulario en modo oscuro */
        body.dark-mode .card {
            background-color: #1a1a1a !important;
            border-color: #444444 !important;
        }
        
        body.dark-mode .card-body {
            background-color: #1a1a1a !important;
        }
        
        /* Form inputs y selects */
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #2d2d2d !important;
            color: #ffffff !important;
            border-color: #444444 !important;
        }
        
        body.dark-mode .form-control::placeholder {
            color: #999999 !important;
        }
        
        /* Labels y textos */
        body.dark-mode .form-label,
        body.dark-mode .form-text {
            color: #ffffff !important;
        }
        
        /* Botones */
        body.dark-mode .btn-primary {
            background-color: #ff8c42 !important;
            border-color: #ff8c42 !important;
        }
        
        body.dark-mode .btn-primary:hover {
            background-color: #e67e3c !important;
            border-color: #e67e3c !important;
        }
        
        body.dark-mode .btn-secondary {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }
        
        /* Alertas */
        body.dark-mode .alert-danger {
            background-color: #58151c !important;
            border-color: #842029 !important;
            color: #f8d7da !important;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Crear Nueva Categoría</h1>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form action="index.php?controller=category&action=store" method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                </div>
                
                <!-- CAMBIO: Eliminado el checkbox de 'active' -->
                
                <div class="d-flex justify-content-between">
                    <a href="index.php?controller=category&action=index" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
require_once __DIR__ . '/../partials/footer.php';
?>
