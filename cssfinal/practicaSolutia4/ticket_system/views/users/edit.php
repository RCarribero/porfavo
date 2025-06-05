<?php
// Incluir header
require_once __DIR__ . '/../partials/header.php';

// Obtener ID del usuario desde la URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si no hay ID de usuario, mostrar un error
if ($userId === 0) {
    echo '<div class="alert alert-danger">Error: No se proporcionó un ID de usuario válido.</div>';
    echo '<div class="mt-3"><a href="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=index" class="btn btn-primary">Volver a la lista de usuarios</a></div>';
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

// Consultar datos del usuario directamente desde la base de datos
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

try {
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo '<div class="alert alert-danger">Error: No se encontró el usuario con ID ' . htmlspecialchars($userId) . '.</div>';
        echo '<div class="mt-3"><a href="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=index" class="btn btn-primary">Volver a la lista de usuarios</a></div>';
        require_once __DIR__ . '/../partials/footer.php';
        exit;
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error de base de datos: ' . $e->getMessage() . '</div>';
    echo '<div class="mt-3"><a href="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=index" class="btn btn-primary">Volver a la lista de usuarios</a></div>';
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

$errors = [];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Usuario</h1>
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
            <form action="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=update" method="post">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : $user['username']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : $user['email']; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">Dejar en blanco para mantener la contraseña actual. Si se cambia, debe tener al menos 6 caracteres.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" disabled>Seleccionar rol</option>
                            <option value="admin" <?php echo ((isset($_POST['role']) ? $_POST['role'] : $user['role']) == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="tech" <?php echo ((isset($_POST['role']) ? $_POST['role'] : $user['role']) == 'tech') ? 'selected' : ''; ?>>Técnico</option>
                            <option value="client" <?php echo ((isset($_POST['role']) ? $_POST['role'] : $user['role']) == 'client') ? 'selected' : ''; ?>>Cliente</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="/porfavo/solutia/cssfinal/practicaSolutia4/index.php?controller=user&action=index" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Actualizar Usuario
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
