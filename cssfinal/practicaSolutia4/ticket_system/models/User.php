<?php
class User {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = new Database();
    }
    
    /**
     * Obtener todos los técnicos
     * @return array Lista de técnicos
     */
    public function getAllTechnicians() {
        try {
            $sql = "SELECT id, username, email 
                    FROM users 
                    WHERE role = 'tech' 
                    ORDER BY username";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getAllTechnicians: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener todos los usuarios
    public function getAllUsers() {
        try {
            $sql = "SELECT id, username, email, role, created_at 
                    FROM users 
                    ORDER BY username";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getAllUsers: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener un usuario por ID
    public function getUserById($id) {
        try {
            $sql = "SELECT id, username, email, role, created_at 
                    FROM users 
                    WHERE id = ?";
            
            $result = $this->db->query($sql, [$id]);
            return $result[0] ?? null;
        } catch (Exception $e) {
            error_log("Error en getUserById: " . $e->getMessage());
            return null;
        }
    }
    
    // Crear un nuevo usuario
    public function createUser($userData) {
        try {
            $sql = "INSERT INTO users (username, password, email, role) 
                    VALUES (?, ?, ?, ?)";
            
            $params = [
                $userData['username'],
                password_hash($userData['password'], PASSWORD_DEFAULT),
                $userData['email'],
                $userData['role']
            ];
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error en createUser: " . $e->getMessage());
            return false;
        }
    }
    
    // Actualizar un usuario existente
    public function updateUser($id, $userData) {
        try {
            // Si la contraseña está vacía, no la actualizamos
            if (empty($userData['password'])) {
                $sql = "UPDATE users 
                        SET username = ?, email = ?, role = ? 
                        WHERE id = ?";
                
                $params = [
                    $userData['username'],
                    $userData['email'],
                    $userData['role'],
                    $id
                ];
            } else {
                $sql = "UPDATE users 
                        SET username = ?, password = ?, email = ?, role = ? 
                        WHERE id = ?";
                
                $params = [
                    $userData['username'],
                    password_hash($userData['password'], PASSWORD_DEFAULT),
                    $userData['email'],
                    $userData['role'],
                    $id
                ];
            }
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error en updateUser: " . $e->getMessage());
            return false;
        }
    }
    
    // Eliminar un usuario
    public function deleteUser($id) {
        try {
            // Primero verificamos si el usuario tiene tickets asignados
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE user_id = ?";
            $result = $this->db->query($sql, [$id]);
            
            if ($result[0]['count'] > 0) {
                return false; // No podemos eliminar un usuario con tickets asignados
            }
            
            $sql = "DELETE FROM users WHERE id = ?";
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Error en deleteUser: " . $e->getMessage());
            return false;
        }
    }
    
    // Verificar si un nombre de usuario ya existe
    public function usernameExists($username, $excludeId = null) {
        try {
            if ($excludeId) {
                $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?";
                $result = $this->db->query($sql, [$username, $excludeId]);
            } else {
                $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
                $result = $this->db->query($sql, [$username]);
            }
            
            return $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Error en usernameExists: " . $e->getMessage());
            return false;
        }
    }
    
    // Verificar si un email ya existe
    public function emailExists($email, $excludeId = null) {
        try {
            if ($excludeId) {
                $sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?";
                $result = $this->db->query($sql, [$email, $excludeId]);
            } else {
                $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
                $result = $this->db->query($sql, [$email]);
            }
            
            return $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Error en emailExists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Autenticar usuario con nombre de usuario/email y contraseña
     * @param string $usernameOrEmail Nombre de usuario o email
     * @param string $password Contraseña
     * @return array|false Datos del usuario o false si falla la autenticación
     */
    public function authenticate($usernameOrEmail, $password) {
        try {
            // Buscar usuario por nombre de usuario o email
            // Eliminamos la referencia a la columna 'active' que no existe en la estructura de BD
            $sql = "SELECT id, username, email, password, role 
                    FROM users 
                    WHERE (username = ? OR email = ?)";
            
            $result = $this->db->query($sql, [$usernameOrEmail, $usernameOrEmail]);
            
            if (empty($result)) {
                return false;
            }
            
            $user = $result[0];
            
            // Verificar contraseña
            if (password_verify($password, $user['password'])) {
                // No devolver la contraseña
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear un nuevo usuario con verificación por correo
     * @param array $userData Datos del usuario
     * @return bool
     */
    public function createUserWithVerification($userData) {
        try {
            // Eliminamos la columna 'active' que no existe en la estructura de BD
            $sql = "INSERT INTO users (username, password, email, role, activation_token, token_expiry) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $tokenExpiry = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24 horas
            
            $params = [
                $userData['username'],
                password_hash($userData['password'], PASSWORD_DEFAULT),
                $userData['email'],
                $userData['role'],
                $userData['activation_token'],
                $tokenExpiry
            ];
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error en createUserWithVerification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activar cuenta de usuario mediante token
     * @param string $token Token de activación
     * @return bool
     */
    public function activateAccount($token) {
        try {
            // Verificar si el token es válido y no ha caducado
            // Eliminamos la columna 'active' que no existe en la estructura de BD
            $sql = "SELECT id FROM users 
                    WHERE activation_token = ? 
                    AND token_expiry > NOW()";
            
            $result = $this->db->query($sql, [$token]);
            
            if (empty($result)) {
                return false;
            }
            
            // Actualizar la cuenta (quitamos la referencia a active)
            $sql = "UPDATE users 
                    SET activation_token = NULL, token_expiry = NULL 
                    WHERE id = ?";
            
            $this->db->query($sql, [$result[0]['id']]);
            return true;
        } catch (Exception $e) {
            error_log("Error en activateAccount: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guardar token de 'recordarme'
     * @param int $userId ID del usuario
     * @param string $token Token
     * @return bool
     */
    public function saveRememberToken($userId, $token) {
        try {
            $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
            $this->db->query($sql, [$token, $userId]);
            return true;
        } catch (Exception $e) {
            error_log("Error en saveRememberToken: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener usuario por token 'recordarme'
     * @param string $token Token
     * @return array|null Datos del usuario o null
     */
    public function getUserByRememberToken($token) {
        try {
            // Eliminamos la columna 'active' que no existe en la estructura de BD
            $sql = "SELECT id, username, email, role 
                    FROM users 
                    WHERE remember_token = ?";
            
            $result = $this->db->query($sql, [$token]);
            return $result[0] ?? null;
        } catch (Exception $e) {
            error_log("Error en getUserByRememberToken: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener usuario por email
     * @param string $email Email
     * @return array|null Datos del usuario o null
     */
    public function getUserByEmail($email) {
        try {
            $sql = "SELECT id, username, email, role 
                    FROM users 
                    WHERE email = ?";
            
            $result = $this->db->query($sql, [$email]);
            return $result[0] ?? null;
        } catch (Exception $e) {
            error_log("Error en getUserByEmail: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear token para resetear contraseña
     * @param int $userId ID del usuario
     * @param string $token Token
     * @param string $expiry Fecha de caducidad
     * @return bool
     */
    public function createPasswordResetToken($userId, $token, $expiry) {
        try {
            $sql = "UPDATE users 
                    SET reset_token = ?, reset_token_expiry = ? 
                    WHERE id = ?";
            
            $this->db->query($sql, [$token, $expiry, $userId]);
            return true;
        } catch (Exception $e) {
            error_log("Error en createPasswordResetToken: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar token de reseteo de contraseña
     * @param string $token Token
     * @return bool
     */
    public function validateResetToken($token) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM users 
                    WHERE reset_token = ? 
                    AND reset_token_expiry > NOW()";
            
            $result = $this->db->query($sql, [$token]);
            return $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Error en validateResetToken: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Resetear contraseña
     * @param string $token Token
     * @param string $password Nueva contraseña
     * @return bool
     */
    public function resetPassword($token, $password) {
        try {
            // Verificar si el token es válido
            $sql = "SELECT id FROM users 
                    WHERE reset_token = ? 
                    AND reset_token_expiry > NOW()";
            
            $result = $this->db->query($sql, [$token]);
            
            if (empty($result)) {
                return false;
            }
            
            // Actualizar contraseña
            $sql = "UPDATE users 
                    SET password = ?, reset_token = NULL, reset_token_expiry = NULL 
                    WHERE id = ?";
            
            $this->db->query(
                $sql, 
                [password_hash($password, PASSWORD_DEFAULT), $result[0]['id']]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error en resetPassword: " . $e->getMessage());
            return false;
        }
    }
}
?>
