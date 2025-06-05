<?php
// Iniciar sesión para poder usar $_SESSION
session_start();

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../config/database.php';
    $db = new Database();
    $pdo = $db->getConnection();
    
    $errors = [];
    
    // Validación básica
    if (empty($_POST['username'])) {
        $errors[] = "El nombre de usuario es obligatorio";
    }
    if (empty($_POST['email'])) {
        $errors[] = "El correo electrónico es obligatorio";
    }
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    if (empty($_POST['role'])) {
        $errors[] = "El rol es obligatorio";
    }
    
    // Si no hay errores, intentar guardar
    if (empty($errors)) {
        try {
            // Verificar si el usuario/email ya existe
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $checkStmt->execute([$_POST['username'], $_POST['email']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "El nombre de usuario o correo electrónico ya está en uso";
            } else {
                // Insertar directamente
                $sql = "INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $_POST['username'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['email'],
                    $_POST['role']
                ]);
                
                if ($result) {
                    $userId = $pdo->lastInsertId();
                    $_SESSION['success_message'] = "Usuario creado correctamente con ID: $userId";
                    header('Location:index.php?controller=user&action=index');
                    exit;
                    exit;
                } else {
                    $errors[] = "Error al crear el usuario en la base de datos";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error del sistema: " . $e->getMessage();
        }
    }
}

// Corregir la ruta de inclusión del header - usar ruta relativa
require_once dirname(__FILE__) . '/../partials/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Crear Nuevo Usuario</h1>
        <!-- Eliminado el badge de "Versión simplificada" -->
    </div>
    
    <!-- Mostrar errores si existen -->
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
    
    <!-- Mostrar mensaje de éxito si existe -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <!-- Formulario que envía a la misma página -->
            <form action="" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" selected disabled>Seleccionar rol</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="tech" <?php echo (isset($_POST['role']) && $_POST['role'] == 'tech') ? 'selected' : ''; ?>>Técnico</option>
                            <option value="client" <?php echo (isset($_POST['role']) && $_POST['role'] == 'client') ? 'selected' : ''; ?>>Cliente</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Usuario Directamente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

<?php
// Corregir la ruta de inclusión del footer - usar ruta relativa
require_once dirname(__FILE__) . '/../partials/footer.php';
?>
