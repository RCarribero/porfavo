<?php
class Ticket {
    private $db;
    
    public function __construct() {
        // Usar la función global getDB() de tu database.php
        require_once __DIR__ . '/../config/database.php';
        $this->db = getDB();
    }
    
    /**
     * Obtener todos los estados posibles de tickets
     * @return array Lista de estados
     */
    public function getAllStatuses() {
        return [
            'open' => 'Abierto',
            'in_progress' => 'En Progreso',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado'
        ];
    }
    
    /**
     * Crear un nuevo ticket
     * @param array $data Datos del ticket
     * @return int|bool ID del ticket creado o false en caso de error
     */
    public function create($data) {
        // Modificado para incluir el campo assigned_to
        $sql = "INSERT INTO tickets (title, description, status, priority, category_id, user_id, assigned_to, created_at, updated_at) 
                VALUES (:title, :description, :status, :priority, :category_id, :user_id, :assigned_to, NOW(), NOW())";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            
            // Asignar técnico si está disponible, de lo contrario será NULL
            $assigned_to = isset($data['assigned_to']) ? $data['assigned_to'] : null;
            $stmt->bindParam(':assigned_to', $assigned_to);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al crear ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener un ticket por su ID
     * @param int $id ID del ticket
     * @return array|bool Datos del ticket o false si no existe
     */
    public function getById($id) {
        // Adaptado a la estructura sin campo assigned_to
        $sql = "SELECT t.*, c.name as category_name, u.username as client_name
                FROM tickets t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar un ticket existente
     * @param int $id ID del ticket
     * @param array $data Datos a actualizar
     * @return bool Resultado de la operación
     */
    public function update($id, $data) {
        $sql = "UPDATE tickets SET ";
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key != 'id') {
                $sql .= "$key = :$key, ";
                $params[":$key"] = $value;
            }
        }
        
        $sql .= "updated_at = NOW() WHERE id = :id";
        $params[':id'] = $id;
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error al actualizar ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Asignar un ticket a un técnico mediante comentario del sistema
     * @param int $ticketId ID del ticket
     * @param int $technicianId ID del técnico
     * @param int $currentUserId ID del usuario que realiza la asignación
     * @return bool Resultado de la operación
     */
    public function assignToTechnician($ticketId, $technicianId, $currentUserId) {
        // En lugar de usar un campo assigned_to, registramos la asignación como un comentario
        $sql = "INSERT INTO comments (ticket_id, user_id, comment, is_system, created_at) 
                VALUES (:ticket_id, :user_id, :comment, 1, NOW())";
        
        try {
            // Obtener nombre del técnico
            $techSql = "SELECT username FROM users WHERE id = :id";
            $techStmt = $this->db->prepare($techSql);
            $techStmt->bindParam(':id', $technicianId, PDO::PARAM_INT);
            $techStmt->execute();
            $techInfo = $techStmt->fetch(PDO::FETCH_ASSOC);
            
            $techName = $techInfo ? $techInfo['username'] : 'desconocido';
            $comment = "Ticket asignado a técnico: " . $techName;
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al asignar ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cambiar el estado de un ticket
     * @param int $ticketId ID del ticket
     * @param string $status Nuevo estado
     * @return bool Resultado de la operación
     */
    public function changeStatus($ticketId, $status) {
        $sql = "UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $ticketId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al cambiar estado del ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los tickets con filtros opcionales
     * @param array $filters Filtros a aplicar
     * @return array Lista de tickets
     */
    public function getAll($filters = []) {
        // Adaptado a la estructura sin campo assigned_to
        $sql = "SELECT t.*, c.name as category_name, u.username as client_name
                FROM tickets t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE 1=1 ";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['status'])) {
            $sql .= "AND t.status = :status ";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= "AND t.priority = :priority ";
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= "AND t.category_id = :category_id ";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= "AND t.user_id = :user_id ";
            $params[':user_id'] = $filters['user_id'];
        }
        
        // Filtro para técnico asignado
        if (!empty($filters['assigned_to'])) {
            $sql .= "AND t.assigned_to = :assigned_to ";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        // Ordenar por fecha de creación descendente (más recientes primero)
        $sql .= "ORDER BY t.created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener tickets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de tickets por estado
     * @return array Estadísticas de tickets
     */
    public function getStatsByStatus() {
        $sql = "SELECT status, COUNT(*) as count FROM tickets GROUP BY status";
        
        try {
            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [];
            foreach ($results as $row) {
                $stats[$row['status']] = $row['count'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Eliminar un ticket
     * @param int $id ID del ticket
     * @return bool Resultado de la operación
     */
    public function delete($id) {
        $sql = "DELETE FROM tickets WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar ticket: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar tickets por término de búsqueda
     * @param string $term Término de búsqueda
     * @return array Resultados de la búsqueda
     */
    public function search($term) {
        // Modificado para incluir el técnico asignado
        $sql = "SELECT t.*, c.name as category_name, u1.username as client_name,
                u2.username as technician_name
                FROM tickets t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN users u1 ON t.user_id = u1.id 
                LEFT JOIN users u2 ON t.assigned_to = u2.id 
                WHERE t.title LIKE :term OR t.description LIKE :term 
                ORDER BY t.created_at DESC";
        
        $term = "%$term%";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':term', $term);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar tickets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generar reporte personalizado de tickets
     * @param string $startDate Fecha de inicio (YYYY-MM-DD)
     * @param string $endDate Fecha de fin (YYYY-MM-DD)
     * @param int $technician ID del técnico (opcional)
     * @param int $category ID de la categoría (opcional)
     * @param string $status Estado del ticket (opcional)
     * @return array Resultados del reporte
     */
    public function getCustomReport($startDate, $endDate, $technician = '', $category = '', $status = '') {
        try {
            $sql = "SELECT t.id, t.title, t.status, t.priority, c.name as category_name, 
                    t.created_at, t.updated_at, u1.username as client_name, 
                    u2.username as technician_name 
                    FROM tickets t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    LEFT JOIN users u1 ON t.user_id = u1.id 
                    LEFT JOIN users u2 ON t.assigned_to = u2.id 
                    WHERE t.created_at BETWEEN :start_date AND :end_date";
            
            $params = [
                ':start_date' => $startDate . ' 00:00:00',
                ':end_date' => $endDate . ' 23:59:59'
            ];
            
            if (!empty($technician)) {
                $sql .= " AND t.assigned_to = :technician";
                $params[':technician'] = $technician;
            }
            
            if (!empty($category)) {
                $sql .= " AND t.category_id = :category";
                $params[':category'] = $category;
            }
            
            if (!empty($status)) {
                $sql .= " AND t.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY t.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getCustomReport: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Añadir un comentario a un ticket
     * @param array $data Datos del comentario
     * @return bool Resultado de la operación
     */
    public function addComment($data) {
        // Adaptado para trabajar sin la columna is_system
        // Modificamos el contenido del comentario para indicar si es del sistema
        $comment = $data['comment'];
        if (isset($data['is_system']) && $data['is_system']) {
            $comment = '[SISTEMA] ' . $comment;
        }
        
        $sql = "INSERT INTO comments (ticket_id, user_id, comment, created_at) 
                VALUES (:ticket_id, :user_id, :comment, NOW())";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':ticket_id', $data['ticket_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':comment', $comment);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al añadir comentario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los comentarios de un ticket
     * @param int $ticketId ID del ticket
     * @return array Lista de comentarios
     */
    public function getComments($ticketId) {
        $sql = "SELECT c.*, u.username 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.ticket_id = :ticket_id 
                ORDER BY c.created_at ASC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':ticket_id', $ticketId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener comentarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Añadir un archivo adjunto a un ticket
     * @param array $data Datos del archivo adjunto
     * @return bool Resultado de la operación
     */
    public function addAttachment($data) {
        $sql = "INSERT INTO attachments (ticket_id, filename, filepath, filesize, created_at) 
                VALUES (:ticket_id, :filename, :filepath, :filesize, NOW())";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':ticket_id', $data['ticket_id']);
            $stmt->bindParam(':filename', $data['filename']);
            $stmt->bindParam(':filepath', $data['filepath']);
            $stmt->bindParam(':filesize', $data['filesize']);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al añadir archivo adjunto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los archivos adjuntos de un ticket
     * @param int $ticketId ID del ticket
     * @return array Lista de archivos adjuntos
     */
    public function getAttachments($ticketId) {
        $sql = "SELECT * FROM attachments 
                WHERE ticket_id = :ticket_id 
                ORDER BY created_at DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':ticket_id', $ticketId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener archivos adjuntos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener un archivo adjunto por su ID
     * @param int $id ID del archivo adjunto
     * @return array|bool Datos del archivo adjunto o false si no existe
     */
    public function getAttachmentById($id) {
        $sql = "SELECT * FROM attachments WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener archivo adjunto: " . $e->getMessage());
            return false;
        }
    }
}
?>
