<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'ticket_system';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Error en la consulta: " . $exception->getMessage();
            return [];
        }
    }
}

// Función auxiliar para obtener datos de la base de datos
function getReportData() {
    $db = new Database();
    
    // Datos para el dashboard
    $kpis = [
        'total_tickets' => 0,
        'open_tickets' => 0,
        'closed_tickets' => 0,
        'avg_resolution_time' => 0,
        'tickets_by_category' => [],
        'tickets_by_technician' => []
    ];
    
    // Obtener total de tickets
    $sql = "SELECT COUNT(*) as total FROM tickets";
    $result = $db->query($sql);
    if (!empty($result)) {
        $kpis['total_tickets'] = $result[0]['total'];
    }
    
    // Obtener tickets abiertos
    $sql = "SELECT COUNT(*) as total FROM tickets WHERE status = 'open'";
    $result = $db->query($sql);
    if (!empty($result)) {
        $kpis['open_tickets'] = $result[0]['total'];
    }
    
    // Obtener tickets cerrados
    $sql = "SELECT COUNT(*) as total FROM tickets WHERE status = 'closed'";
    $result = $db->query($sql);
    if (!empty($result)) {
        $kpis['closed_tickets'] = $result[0]['total'];
    }
    
    // Obtener tiempo promedio de resolución
    $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_time 
            FROM tickets 
            WHERE status IN ('resolved', 'closed')";
    $result = $db->query($sql);
    if (!empty($result) && $result[0]['avg_time'] !== null) {
        $kpis['avg_resolution_time'] = round($result[0]['avg_time'], 2);
    }
    
    // Obtener tickets por categoría
    $sql = "SELECT c.name, COUNT(t.id) as total 
            FROM tickets t
            JOIN categories c ON t.category_id = c.id
            GROUP BY t.category_id 
            ORDER BY total DESC";
    $kpis['tickets_by_category'] = $db->query($sql);
    
    // Obtener tickets por técnico
    $sql = "SELECT u.username, COUNT(t.id) as total 
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            WHERE u.role = 'tech'
            GROUP BY t.user_id 
            ORDER BY total DESC";
    $kpis['tickets_by_technician'] = $db->query($sql);
    
    // Obtener datos de tendencia
    $sql = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as period,
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as closed_tickets
            FROM tickets
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY period
            ORDER BY period ASC";
    $trends = $db->query($sql);
    
    // Obtener tickets por prioridad
    $sql = "SELECT priority, COUNT(*) as total FROM tickets GROUP BY priority ORDER BY 
            CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END";
    $priorityStats = $db->query($sql);
    
    return [
        'kpis' => $kpis,
        'trends' => $trends,
        'priorityStats' => $priorityStats
    ];
}

// Función para obtener datos para informes personalizados
function getCustomReport($startDate, $endDate, $technician = null, $category = null, $status = null) {
    $db = new Database();
    $params = [];
    
    $sql = "SELECT t.id, t.title, t.description, t.status, t.priority, 
                c.name as category_name, 
                t.created_at, t.updated_at,
                u.username as client_name
            FROM tickets t
            JOIN categories c ON t.category_id = c.id
            JOIN users u ON t.user_id = u.id
            WHERE t.created_at BETWEEN ? AND ?";
    
    $params[] = $startDate . " 00:00:00";
    $params[] = $endDate . " 23:59:59";
    
    if ($technician) {
        $sql .= " AND u.id = ? AND u.role = 'tech'";
        $params[] = $technician;
    }
    
    if ($category) {
        $sql .= " AND t.category_id = ?";
        $params[] = $category;
    }
    
    if ($status) {
        $sql .= " AND t.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    return $db->query($sql, $params);
}

// Función para obtener técnicos
function getTechnicians() {
    $db = new Database();
    $sql = "SELECT id, username FROM users WHERE role = 'tech' ORDER BY username";
    return $db->query($sql);
}

// Función para obtener categorías
function getCategories() {
    $db = new Database();
    $sql = "SELECT id, name FROM categories ORDER BY name";
    return $db->query($sql);
}

// Función global para mantener compatibilidad con código existente
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}
?>
