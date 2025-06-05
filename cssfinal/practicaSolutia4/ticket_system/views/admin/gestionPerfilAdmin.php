<?php
// Iniciar sesión si no está iniciada
session_start();

// Verificar que el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../sesion/login.php');
    exit;
}

// Incluir archivo de configuración de rutas
require_once '../../config/paths.php';

// Asegurarse de que SYSTEM_URL esté definido
if (!defined('SYSTEM_URL')) {
    die('Error: SYSTEM_URL no está definido. Revise la configuración.');
}

// Intentar obtener correo electrónico del usuario desde la base de datos
$userEmail = "admin@ejemplo.com"; // Valor predeterminado

try {
    // Usar la clase Database existente
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Preparar consulta SQL para obtener el correo electrónico usando PDO
    $userId = $_SESSION['user_id'] ?? $_SESSION['id']; // Usar cualquier ID disponible
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && !empty($user['email'])) {
        $userEmail = $user['email'];
    }
    
} catch (Exception $e) {
    // Capturar cualquier error, pero continuar con la página
    // Se usará el valor predeterminado de $userEmail
}

// Incluir el encabezado
include_once '../partials/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mi Perfil</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuario</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre completo</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? ''); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo electrónico</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($userEmail); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rol</label>
                        <p class="form-control-plaintext">Administrador</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
include_once '../partials/footer.php';
?>
