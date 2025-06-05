<?php
session_start();
include("../../config/database.php");
$database = new Database();
$pdo = $database->getConnection();

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    
    // Validar que los campos no estén vacíos
    if (empty($username) || empty($password) || empty($email)) {
        $error_message = "Todos los campos son obligatorios.";
    } else {
        // Hash de la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario en la base de datos
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
            $stmt->execute([
                'username' => $username,
                'password' => $hashed_password,
                'email' => $email
            ]);

            // Redirigir al login después del registro exitoso
            header("Location: ../sesion/login.php");
            exit();

        } catch (PDOException $e) {
            $error_message = "Error al registrar el usuario: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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
            --color-text: #ffffff;
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

        .navbar-brand img {
            filter: brightness(1);
            transition: filter 0.3s ease;
        }

        body.dark-mode .navbar-brand img {
            filter: brightness(0.8);
        }

        .register-box {
            background-color: var(--color-card);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 400px;
            margin: 2rem auto 0;
            transition: all 0.3s ease;
        }

        body.dark-mode .register-box {
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
            color: var(--color-text);
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            transition: all 0.3s ease;
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

        .register-box h1 {
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

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .register-button {
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

        .register-button:hover {
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

        .input-group-text {
            background-color: rgba(52, 152, 219, 0.1);
            border: none;
            color: var(--color-primary);
        }

        .form-control {
            border-left: none !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <div class="navbar-brand">
                <img src="https://camaradesevilla.com/wp-content/uploads/2024/07/S00-logo-Grupo-Solutia-v01-1.png" 
                     alt="Logo" style="max-height: 40px;">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <button id="theme-button" class="btn btn-sm">
                        <i class="fas fa-moon"></i> Modo Oscuro
                    </button>

                    <style>
                        #theme-button {
                            background-color: var(--color-primary);
                            color: white;
                            padding: 0.5rem 1rem;
                            border-radius: 5px;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                        }

                        #theme-button:hover {
                            background-color: var(--color-primary-dark);
                            transform: translateY(-2px);
                        }

                        body.dark-mode #theme-button {
                            background-color: #ff8c42;
                        }

                        body.dark-mode #theme-button:hover {
                            background-color: #e67e22;
                        }
                    </style>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">

        <div class="register-box">
            <h1><i class="fas fa-user-plus me-2"></i>Registro</h1>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Usuario:
                    </label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Correo electrónico:
                    </label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-key me-2"></i>Contraseña:
                    </label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="register-button">Registrarse</button>
            </form>

            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left me-2"></i>Ya tienes cuenta? Inicia sesión
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación en tiempo real
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !email || !password) {
                e.preventDefault();
                alert('Por favor complete todos los campos');
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