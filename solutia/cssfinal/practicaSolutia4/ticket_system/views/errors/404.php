<?php
session_start();

// Determinar la URL de redirección según el rol del usuario
function getDashboardUrl() {
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                return '../admin/dashboard.php';
            case 'tech':
                return '../Tecnico/dashboardTecnico.php';
            case 'cliente':
                return '../cliente/dashboard.php';
            default:
                return '../sesion/login.php';
        }
    } else {
        return '../sesion/login.php';
    }
}

$dashboardUrl = getDashboardUrl();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Página no encontrada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #e74c3c;
            --text-color: #343a40;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .error-icon {
            font-size: 100px;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .error-title {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .error-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #666;
            line-height: 1.6;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .animated {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }

        /* Modo oscuro */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212;
                color: #f8f9fa;
            }
            
            .error-container {
                background-color: #1e1e1e;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }
            
            .error-title {
                color: #f8f9fa;
            }
            
            .error-message {
                color: #adb5bd;
            }
            
            .btn-primary {
                background-color: #ff8c42;
                border-color: #ff8c42;
            }
            
            .btn-primary:hover {
                background-color: #e67e22;
                border-color: #e67e22;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon animated">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="error-title">Error 404</h1>
        <div class="error-message">
            <p>Lo sentimos, la página que estás buscando no se ha podido encontrar.</p>
            <p>Puede que haya sido movida, eliminada o nunca haya existido.</p>
        </div>
        <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-home me-2"></i>Volver al Dashboard
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
