<?php
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
$host = '192.167.1.248';
$dbname = 'ticket_system';
$user = 'force4-8';
$pass = 'force_1453';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Buscar usuario por correo electrónico
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Simulación: aquí deberías generar un token y guardarlo en la base de datos

        // Envío de correo usando PHPMailer
        // require 'vendor/autoload.php';
        // use PHPMailer\PHPMailer\PHPMailer;
        // use PHPMailer\PHPMailer\Exception;

        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'correosistematickets@gmail.com'; // Cambia esto si usas otro correo
            $mail->Password = 'dfvh dxja brej vaqp'; // Tu clave de app de Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinatario y contenido
            $mail->setFrom('correosistematickets@gmail.com', 'Solutia');
            $mail->addAddress($email, $user['username']);
            $mail->isHTML(true);
            $mail->Subject = 'Recuperacion de clave - Solutia';

$mail->isHTML(true);


// Alternativa en texto plano
$mail->Body = "<h3>Hola {$user['username']},</h3><p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p><p><a href='http://" . $_SERVER['HTTP_HOST'] . "/solutia/cssfinal/practicaSolutia4/ticket_system/lib/enviarCorreosRecuperarContra/reset_password.php?email=" . urlencode($email) . "'>Restablecer contraseña</a></p>";


            $mail->send();
            $success = "Se ha enviado un correo de recuperación a tu email.";
        } catch (Exception $e) {
            $error = "Error al enviar el correo: {$mail->ErrorInfo}";
        }
    } else {
        $error = "No se encontró un usuario con ese correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Sistema de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #3498db;
            --color-primary-dark: #2980b9;
            --color-bg: #f8f9fa;
            --color-text: #343a40;
            --color-card: #ffffff;
            --color-border: #dee2e6;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            transition: all 0.3s ease;
        }

        .container {
            padding: 2rem;
        }

        .recover-box {
            background-color: var(--color-card);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 400px;
            margin: 2rem auto 0;
            transition: all 0.3s ease;
        }

        body.dark-mode .recover-box {
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
        }

        .recover-box h1 {
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-text);
        }

        .form-group label i {
            color: var(--color-text);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--color-border);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .recover-button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .recover-button:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--color-primary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: var(--color-primary-dark);
        }

        body.dark-mode {
            --color-primary: #ff8c42;
            --color-bg: #121212;
            --color-text: #f8f9fa;
            --color-card: #1e1e1e;
            --color-border: #444;
            --color-success: #28a745;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        .navbar {
            background-color: var(--color-card) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        body.dark-mode .navbar {
            box-shadow: 0 2px 4px rgba(255,255,255,0.1);
        }

        .navbar-brand {
            padding-left: 11.75rem;
        }

        .navbar-brand img {
            filter: brightness(1);
            transition: filter 0.3s ease;
        }

        body.dark-mode .navbar-brand img {
            filter: brightness(0.8);
        }

        #theme-button {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: absolute;
            top: 1rem;
            right: 1rem;
        }

        #theme-button:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        body.dark-mode #theme-button {
            background-color: #ff8c42;
        }

        body.dark-mode #theme-button:hover {
            background-color: #e67e22;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" 
                     alt="Logo" style="height: 40px;">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="d-flex align-items-center justify-content-end">
                    <button id="theme-button" class="btn btn-sm">
                        <i class="fas fa-moon"></i> Modo Oscuro
                    </button>
                </div>
            </div>
        </div>
    </nav>

        <div class="recover-box">
            <h1><i class="fas fa-key me-2"></i>Recuperar Contraseña</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope me-2"></i>Correo electrónico:
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="recover-button">Enviar</button>
            </form>

            <div class="back-link">
                <a href="../../views/sesion/login.php">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación en tiempo real
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            if (!email) {
                e.preventDefault();
                alert('Por favor ingrese su correo electrónico');
            }
        });
    </script>

    <script>
        // Tema oscuro/claro
        const themeButton = document.getElementById('theme-button');
        const body = document.body;

        // Verificar preferencia guardada
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
        }

        themeButton.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDarkMode = body.classList.contains('dark-mode');
            
            if (isDarkMode) {
                themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                themeButton.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>
</body>
</html>
