<?php
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

// Verificar si el correo electrónico está en la URL
if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $email = $_GET['email'];
    
    // Verificar si el correo electrónico existe en la base de datos
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // El usuario existe, mostrar formulario de cambio de contraseña
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
                $new_password = $_POST['new_password'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Actualizar la contraseña en la base de datos
                $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
                $update_stmt->execute(['password' => $hashed_password, 'email' => $email]);

                // Redirigir al login con mensaje de éxito
                header('Location: ../../views/sesion/login.php?success=1');
                exit();
            }
        }

        // Mostrar formulario de cambio de contraseña con estilo consistente
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cambiar Contraseña - Sistema de Tickets</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

                .login-box {
                    background-color: var(--color-card);
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                    padding: 2rem;
                    max-width: 400px;
                    margin: 2rem auto 0;
                    transition: all 0.3s ease;
                }

                .login-box h1 {
                    color: var(--color-primary);
                    text-align: center;
                    margin-bottom: 2rem;
                    font-weight: 600;
                }

                .login-box h1 i {
                    color: var(--color-primary);
                }

                body.dark-mode .login-box {
                    box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
                }

                .form-control {
                    background-color: var(--color-card);
                    border: 1px solid var(--color-border);
                    color: var(--color-text);
                    transition: all 0.3s ease;
                }

                body.dark-mode .form-control {
                    background-color: #3c3c3c;
                    border-color: #555;
                }

                .btn-primary {
                    background-color: var(--color-primary);
                    border-color: var(--color-primary);
                    transition: all 0.3s ease;
                    width: 100%;
                }

                .btn-primary:hover {
                    background-color: var(--color-primary-dark);
                    border-color: var(--color-primary-dark);
                }

                body.dark-mode .btn-primary {
                    background-color: #ff8c42;
                    border-color: #ff8c42;
                }

                body.dark-mode .btn-primary:hover {
                    background-color: #e67e22;
                    border-color: #e67e22;
                }

                .form-group {
                    margin-bottom: 1.5rem;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 0.5rem;
                    color: var(--color-text);
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
            </style>
        </head>
        <body>
            <div class="container">
                <div class="login-box">
                    <h1><i class="fas fa-key"></i> Cambiar Contraseña</h1>
                    <form method="POST">
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña:</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                    </form>
                    <div class="back-link">
                        <a href="../../views/sesion/login.php">Volver al login</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - Sistema de Tickets</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body>
            <div class="container">
                <div class="alert alert-danger mt-5">
                    <i class="fas fa-exclamation-circle"></i>
                    No se encontró un usuario con ese correo electrónico.
                </div>
                <div class="text-center mt-3">
                    <a href="olvidarContra.php" class="btn btn-primary">
                        <i class="fas fa-undo"></i> Volver a intentar
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
} else {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Sistema de Tickets</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <div class="alert alert-danger mt-5">
                <i class="fas fa-exclamation-circle"></i>
                Acceso no autorizado. Asegúrate de que el enlace es correcto.
            </div>
            <div class="text-center mt-3">
                <a href="olvidarContra.php" class="btn btn-primary">
                    <i class="fas fa-undo"></i> Solicitar nuevo enlace
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
