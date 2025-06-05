<?php
class UserController {
    private $userModel;
    private $loginAttempts = [];
    
    public function __construct() {
        require_once __DIR__ . '/../models/User.php';
        $this->userModel = new User();
    }
    
    // Mostrar lista de usuarios
    public function index() {
        $users = $this->userModel->getAllUsers();
        require_once 'ticket_system/views/users/index.php';
    }
    
    // Mostrar formulario para crear usuario
    public function create() {
        $errors = [];
        require_once 'ticket_system/views/users/create.php';
    }
    
    // Procesar la creación de un usuario
    public function store() {
        $errors = [];
        
        // Validar datos
        if (empty($_POST['username'])) {
            $errors[] = "El nombre de usuario es obligatorio";
        } elseif ($this->userModel->usernameExists($_POST['username'])) {
            $errors[] = "El nombre de usuario ya está en uso";
        }
        
        if (empty($_POST['email'])) {
            $errors[] = "El correo electrónico es obligatorio";
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del correo electrónico no es válido";
        } elseif ($this->userModel->emailExists($_POST['email'])) {
            $errors[] = "El correo electrónico ya está en uso";
        }
        
        if (empty($_POST['password'])) {
            $errors[] = "La contraseña es obligatoria";
        } elseif (strlen($_POST['password']) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if (empty($_POST['role'])) {
            $errors[] = "El rol es obligatorio";
        }
        
        // Si hay errores, volver al formulario
        if (!empty($errors)) {
            require_once 'ticket_system/views/users/create.php';
            return;
        }
        
        // Crear usuario
        $userData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'email' => $_POST['email'],
            'role' => $_POST['role']
            // Eliminamos la columna 'active' que no existe en la BD
        ];
        
        if ($this->userModel->createUser($userData)) {
            $_SESSION['success_message'] = "Usuario creado correctamente";
            header('Location: index.php?controller=user&action=index');
            exit;
        } else {
            $errors[] = "Error al crear el usuario";
            require_once 'ticket_system/views/users/create.php';
        }
    }
    
    // Mostrar formulario para editar usuario
    public function edit($id = null) {
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        $user = $this->userModel->getUserById($id);
        
        if (!$user) {
            $_SESSION['error_message'] = "Usuario no encontrado";
            header('Location: index.php?controller=user&action=index');
            exit;
        }
        
        $errors = [];
        // Asegurarse de que la ruta sea correcta
        require_once __DIR__ . '/../views/users/edit.php';
    }
    
    // Procesar la actualización de un usuario
    public function update() {
        $id = $_POST['id'] ?? null;
        $user = $this->userModel->getUserById($id);
        
        if (!$user) {
            $_SESSION['error_message'] = "Usuario no encontrado";
            header('Location: index.php?controller=user&action=index');
            exit;
        }
        
        $errors = [];
        
        // Validar datos
        if (empty($_POST['username'])) {
            $errors[] = "El nombre de usuario es obligatorio";
        } elseif ($this->userModel->usernameExists($_POST['username'], $id)) {
            $errors[] = "El nombre de usuario ya está en uso";
        }
        
        if (empty($_POST['email'])) {
            $errors[] = "El correo electrónico es obligatorio";
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del correo electrónico no es válido";
        } elseif ($this->userModel->emailExists($_POST['email'], $id)) {
            $errors[] = "El correo electrónico ya está en uso";
        }
        
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if (empty($_POST['role'])) {
            $errors[] = "El rol es obligatorio";
        }
        
        // Si hay errores, volver al formulario
        if (!empty($errors)) {
            require_once 'ticket_system/views/users/edit.php';
            return;
        }
        
        // Actualizar usuario
        $userData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'] ?? '',
            'email' => $_POST['email'],
            'role' => $_POST['role']
            // Eliminamos la columna 'active' que no existe en la BD
        ];
        
        if ($this->userModel->updateUser($id, $userData)) {
            $_SESSION['success_message'] = "Usuario actualizado correctamente";
            header('Location: index.php?controller=user&action=index');
            exit;
        } else {
            $errors[] = "Error al actualizar el usuario";
            require_once 'ticket_system/views/users/edit.php';
        }
    }
    
    // Eliminar un usuario
    public function delete() {
        $id = $_POST['id'] ?? ($_GET['id'] ?? null);
        
        if (!$id) {
            $_SESSION['error_message'] = "ID de usuario no especificado";
            header('Location: index.php?controller=user&action=index');
            exit;
        }
        
        if ($this->userModel->deleteUser($id)) {
            $_SESSION['success_message'] = "Usuario eliminado correctamente";
        } else {
            $_SESSION['error_message'] = "No se puede eliminar el usuario porque tiene tickets asignados";
        }
        
        header('Location: index.php?controller=user&action=index');
        exit;
    }

    // =================================================================
    // AUTENTICACIÓN Y REGISTRO DE USUARIOS
    // =================================================================

    /**
     * Mostrar formulario de registro
     */
    public function register() {
        $errors = [];
        require_once 'ticket_system/views/auth/register.php';
    }

    /**
     * Procesar el registro de un nuevo usuario
     */
    public function registerProcess() {
        $errors = [];
        
        // Validar datos
        if (empty($_POST['username'])) {
            $errors[] = "El nombre de usuario es obligatorio";
        } elseif ($this->userModel->usernameExists($_POST['username'])) {
            $errors[] = "El nombre de usuario ya está en uso";
        }
        
        if (empty($_POST['email'])) {
            $errors[] = "El correo electrónico es obligatorio";
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del correo electrónico no es válido";
        } elseif ($this->userModel->emailExists($_POST['email'])) {
            $errors[] = "El correo electrónico ya está en uso";
        }
        
        if (empty($_POST['password'])) {
            $errors[] = "La contraseña es obligatoria";
        } elseif (strlen($_POST['password']) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if (empty($_POST['confirm_password'])) {
            $errors[] = "La confirmación de contraseña es obligatoria";
        } elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = "Las contraseñas no coinciden";
        }
        
        // Si hay errores, volver al formulario
        if (!empty($errors)) {
            require_once 'ticket_system/views/auth/register.php';
            return;
        }
        
        // Crear usuario con rol 'cliente' por defecto
        $activationToken = bin2hex(random_bytes(32));
        $userData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'email' => $_POST['email'],
            'role' => 'cliente',
            'activation_token' => $activationToken
            // Eliminamos la columna 'active' que no existe en la BD
        ];
        
        if ($this->userModel->createUserWithVerification($userData)) {
            // Enviar correo de verificación
            $this->sendVerificationEmail($_POST['email'], $activationToken);
            
            $_SESSION['success_message'] = "Registro completado. Por favor, verifica tu correo electrónico para activar tu cuenta.";
            header('Location: index.php?controller=user&action=login');
            exit;
        } else {
            $errors[] = "Error al registrar el usuario";
            require_once 'ticket_system/views/auth/register.php';
        }
    }

    /**
     * Activar cuenta de usuario
     */
    public function activateAccount() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $_SESSION['error_message'] = "Token de activación no válido";
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        if ($this->userModel->activateAccount($token)) {
            $_SESSION['success_message'] = "Cuenta activada correctamente. Ya puedes iniciar sesión.";
        } else {
            $_SESSION['error_message'] = "El token de activación no es válido o ha caducado";
        }
        
        header('Location: index.php?controller=user&action=login');
        exit;
    }

    /**
     * Mostrar formulario de inicio de sesión
     */
    public function login() {
        $errors = [];
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesar inicio de sesión
     */
    public function loginProcess() {
        $errors = [];
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($username)) {
            $errors[] = "El nombre de usuario o correo electrónico es obligatorio";
        }
        
        if (empty($password)) {
            $errors[] = "La contraseña es obligatoria";
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        if ($this->isAccountLocked($ipAddress)) {
            $errors[] = "Cuenta bloqueada temporalmente debido a múltiples intentos fallidos.";
            require_once __DIR__ . '/../views/auth/login.php';
            return;
        }
        
        if (empty($errors)) {
            $user = $this->userModel->authenticate($username, $password);
            
            if ($user) {
                // Eliminamos la verificación de 'active' ya que no existe en la BD
                // La cuenta se considera siempre activa
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                $this->loginAttempts[$ipAddress] = [];
                
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $this->userModel->saveRememberToken($user['id'], $token);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                }
                
                // Redirigir según el rol
                switch ($user['role']) {
                    case 'admin':
                        header('Location: /pruebafinal/index.php?controller=admin&action=dashboard');
                        break;
                    case 'tech':
                        header('Location: /pruebafinal/index.php?controller=tech&action=dashboard');
                        break;
                    default:
                        header('Location: /pruebafinal/index.php?controller=client&action=dashboard');
                        break;
                }
                exit;
            } else {
                $this->recordFailedLogin($ipAddress);
                $errors[] = "Nombre de usuario o contraseña incorrectos";
            }
        }
        
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        // Eliminar todas las variables de sesión
        $_SESSION = [];
        
        // Eliminar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Eliminar la cookie de 'recordarme'
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        
        // Destruir la sesión
        session_destroy();
        
        // Redireccionar al inicio
        header('Location: index.php');
        exit;
    }

    /**
     * Mostrar formulario de solicitud de recuperación de contraseña
     */
    public function forgotPassword() {
        $errors = [];
        require_once 'ticket_system/views/auth/forgot_password.php';
    }

    /**
     * Procesar solicitud de recuperación de contraseña
     */
    public function resetPasswordRequest() {
        $errors = [];
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            $errors[] = "El correo electrónico es obligatorio";
            require_once 'ticket_system/views/auth/forgot_password.php';
            return;
        }
        
        // Comprobar si el correo existe
        $user = $this->userModel->getUserByEmail($email);
        
        if (!$user) {
            // Por seguridad, no revelamos si el correo existe o no
            $_SESSION['success_message'] = "Si el correo existe en nuestra base de datos, recibirás un enlace para restablecer tu contraseña.";
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        // Generar token con caducidad (24 horas)
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + (24 * 3600));
        
        if ($this->userModel->createPasswordResetToken($user['id'], $token, $expiry)) {
            // Enviar correo con el enlace
            $this->sendPasswordResetEmail($email, $token);
            
            $_SESSION['success_message'] = "Hemos enviado un enlace a tu correo electrónico para restablecer tu contraseña.";
            header('Location: index.php?controller=user&action=login');
            exit;
        } else {
            $errors[] = "Ha ocurrido un error al procesar tu solicitud";
            require_once 'ticket_system/views/auth/forgot_password.php';
        }
    }

    /**
     * Mostrar formulario para establecer nueva contraseña
     */
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        $errors = [];
        
        if (empty($token) || !$this->userModel->validateResetToken($token)) {
            $_SESSION['error_message'] = "El enlace no es válido o ha caducado";
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        require_once 'ticket_system/views/auth/reset_password.php';
    }

    /**
     * Procesar el cambio de contraseña
     */
    public function setNewPassword() {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $errors = [];
        
        if (empty($token) || !$this->userModel->validateResetToken($token)) {
            $_SESSION['error_message'] = "El enlace no es válido o ha caducado";
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        if (empty($password)) {
            $errors[] = "La contraseña es obligatoria";
        } elseif (strlen($password) < 6) {
            $errors[] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if (empty($confirm_password)) {
            $errors[] = "La confirmación de contraseña es obligatoria";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Las contraseñas no coinciden";
        }
        
        if (!empty($errors)) {
            require_once 'ticket_system/views/auth/reset_password.php';
            return;
        }
        
        if ($this->userModel->resetPassword($token, $password)) {
            $_SESSION['success_message'] = "Tu contraseña ha sido actualizada correctamente";
            header('Location: index.php?controller=user&action=login');
            exit;
        } else {
            $errors[] = "Ha ocurrido un error al actualizar tu contraseña";
            require_once 'ticket_system/views/auth/reset_password.php';
        }
    }

    /**
     * Enviar correo de verificación
     */
    private function sendVerificationEmail($email, $token) {
        $subject = "Activación de cuenta - Sistema de Tickets";
        
        $activationLink = "http://" . $_SERVER['HTTP_HOST'] . 
                          "/index.php?controller=user&action=activateAccount&token=" . $token;
        
        $message = "<html><body>";
        $message .= "<h1>Activación de cuenta</h1>";
        $message .= "<p>Gracias por registrarte en nuestro sistema de tickets. Para activar tu cuenta, haz clic en el siguiente enlace:</p>";
        $message .= "<p><a href='$activationLink'>Activar mi cuenta</a></p>";
        $message .= "<p>Si no puedes hacer clic en el enlace, copia y pega la siguiente URL en tu navegador:</p>";
        $message .= "<p>$activationLink</p>";
        $message .= "<p>Este enlace caducará en 24 horas.</p>";
        $message .= "</body></html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sistema de Tickets <noreply@example.com>\r\n";
        
        mail($email, $subject, $message, $headers);
    }

    /**
     * Enviar correo de restablecimiento de contraseña
     */
    private function sendPasswordResetEmail($email, $token) {
        $subject = "Restablecimiento de contraseña - Sistema de Tickets";
        
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . 
                     "/index.php?controller=user&action=resetPassword&token=" . $token;
        
        $message = "<html><body>";
        $message .= "<h1>Restablecimiento de contraseña</h1>";
        $message .= "<p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para establecer una nueva:</p>";
        $message .= "<p><a href='$resetLink'>Restablecer mi contraseña</a></p>";
        $message .= "<p>Si no puedes hacer clic en el enlace, copia y pega la siguiente URL en tu navegador:</p>";
        $message .= "<p>$resetLink</p>";
        $message .= "<p>Este enlace caducará en 24 horas.</p>";
        $message .= "<p>Si no has solicitado este cambio, ignora este correo.</p>";
        $message .= "</body></html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sistema de Tickets <noreply@example.com>\r\n";
        
        mail($email, $subject, $message, $headers);
    }

    /**
     * Registrar intento fallido de inicio de sesión
     */
    private function recordFailedLogin($ipAddress) {
        if (!isset($this->loginAttempts[$ipAddress])) {
            $this->loginAttempts[$ipAddress] = [];
        }
        
        $this->loginAttempts[$ipAddress][] = time();
    }

    /**
     * Verificar si una cuenta está bloqueada temporalmente
     */
    private function isAccountLocked($ipAddress) {
        if (!isset($this->loginAttempts[$ipAddress])) {
            return false;
        }
        
        // Eliminar intentos antiguos (más de 15 minutos)
        $now = time();
        $this->loginAttempts[$ipAddress] = array_filter(
            $this->loginAttempts[$ipAddress],
            function($time) use ($now) {
                return $now - $time < 900; // 15 minutos
            }
        );
        
        // Si hay 5 o más intentos recientes, bloquear la cuenta
        return count($this->loginAttempts[$ipAddress]) >= 5;
    }
}
?>
