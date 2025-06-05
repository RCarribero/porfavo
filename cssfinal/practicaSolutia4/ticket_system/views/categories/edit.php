<?php
// Incluir header
require_once 'ticket_system/views/partials/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Categoría</h1>
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
            <form action="index.php?controller=category&action=update" method="post">
                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : $category['name']; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? $_POST['description'] : $category['description']; ?></textarea>
                </div>
                
                <!-- CAMBIO: Eliminado el checkbox de 'active' -->
                
                <div class="d-flex justify-content-between">
                    <a href="index.php?controller=category&action=index" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Actualizar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
require_once 'ticket_system/views/partials/footer.php';
?>
