<?php
class Report {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = new Database();
    }
    
    // Método para obtener un ticket específico por ID
    public function getTicketById($ticketId) {
        try {
            $sql = "SELECT t.id, t.title, t.status, t.priority, c.name as category_name, 
                    t.created_at, t.updated_at, u1.username as client_name, 
                    (SELECT u2.username FROM users u2 
                     JOIN comments cm ON u2.id = cm.user_id 
                     WHERE cm.ticket_id = t.id AND u2.role = 'tech' 
                     ORDER BY cm.created_at ASC LIMIT 1) as technician_name 
                    FROM tickets t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    LEFT JOIN users u1 ON t.user_id = u1.id
                    WHERE t.id = ?";
            
            $params = [$ticketId];
            $result = $this->db->query($sql, $params);
            
            if ($result && count($result) > 0) {
                return $result[0]; // Devolver el primer (y único) resultado
            }
            
            return null; // Devolver null si no se encuentra el ticket
        } catch (Exception $e) {
            error_log("Error en getTicketById: " . $e->getMessage());
            return null;
        }
    }
    
    // NUEVO MÉTODO: Obtener todos los datos para el dashboard
    public function getReportData() {
        // KPIs
        $kpis = $this->getKPIs();
        
        // Añadir datos de tickets por estado
        $kpis['tickets_by_status'] = $this->getTicketsByStatus();
        
        // Tendencias
        $trends = $this->getTicketTrends('month', 6);
        
        // Estadísticas por prioridad
        $priorityStats = $this->getTicketsByPriority();
        
        return [
            'kpis' => $kpis,
            'trends' => $trends,
            'priorityStats' => $priorityStats
        ];
    }
    
    // Método para obtener tickets por estado
    public function getTicketsByStatus() {
        try {
            $sql = "SELECT status, COUNT(*) as total FROM tickets GROUP BY status ORDER BY total DESC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsByStatus: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener KPIs principales para el dashboard
    public function getKPIs() {
        $kpis = [
            'total_tickets' => $this->getTotalTickets(),
            'open_tickets' => $this->getTicketCountByStatus('open'),
            'closed_tickets' => $this->getTicketCountByStatus('closed'),
            'avg_resolution_time' => $this->getAvgResolutionTime(),
            'tickets_by_category' => $this->getTicketsByCategoryForDashboard(),
            'tickets_by_technician' => $this->getTicketsByTechnicianForDashboard()
        ];
        
        return $kpis;
    }
    
    // Obtener total de tickets
    private function getTotalTickets() {
        try {
            $sql = "SELECT COUNT(*) as total FROM tickets";
            $result = $this->db->query($sql);
            return $result[0]['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error en getTotalTickets: " . $e->getMessage());
            return 0;
        }
    }
    
    // Obtener tickets por estado (RENOMBRADO para evitar conflicto)
    private function getTicketCountByStatus($status) {
        try {
            $sql = "SELECT COUNT(*) as total FROM tickets WHERE status = ?";
            $result = $this->db->query($sql, [$status]);
            return $result[0]['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error en getTicketCountByStatus: " . $e->getMessage());
            return 0;
        }
    }
    
    // Obtener tiempo promedio de resolución
    private function getAvgResolutionTime() {
        try {
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_time 
                    FROM tickets 
                    WHERE status IN ('resolved', 'closed')";
            $result = $this->db->query($sql);
            return round($result[0]['avg_time'] ?? 0, 2);
        } catch (Exception $e) {
            error_log("Error en getAvgResolutionTime: " . $e->getMessage());
            return 0;
        }
    }
    
    // Obtener tickets por categoría (RENOMBRADO para evitar conflicto)
    private function getTicketsByCategoryForDashboard() {
        try {
            $sql = "SELECT c.name, COUNT(t.id) as total 
                    FROM tickets t
                    JOIN categories c ON t.category_id = c.id
                    GROUP BY t.category_id 
                    ORDER BY total DESC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsByCategoryForDashboard: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener tickets por técnico (MODIFICADO para usar la tabla comments)
    private function getTicketsByTechnicianForDashboard() {
        try {
            $sql = "SELECT u.username as name, COUNT(DISTINCT cm.ticket_id) as total 
                    FROM users u
                    JOIN comments cm ON u.id = cm.user_id
                    WHERE u.role = 'tech'
                    GROUP BY u.id 
                    ORDER BY total DESC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsByTechnicianForDashboard: " . $e->getMessage());
            return [];
        }
    }
    
    // Método para obtener datos de reportes personalizados - VERSIÓN CORREGIDA
    public function getCustomReport($startDate, $endDate, $technician = '', $category = '', $status = '') {
        try {
            // Modificado para usar la tabla comments en lugar de technician_id
            $sql = "SELECT t.id, t.title, t.status, t.priority, c.name as category_name, 
                    t.created_at, t.updated_at, u1.username as client_name, 
                    (SELECT u2.username FROM users u2 
                     JOIN comments cm ON u2.id = cm.user_id 
                     WHERE cm.ticket_id = t.id AND u2.role = 'tech' 
                     ORDER BY cm.created_at ASC LIMIT 1) as technician_name 
                    FROM tickets t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    LEFT JOIN users u1 ON t.user_id = u1.id
                    WHERE t.created_at BETWEEN ? AND ?";
            
            $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
            
            if (!empty($technician)) {
                // Modificado para filtrar por técnico usando la tabla comments
                $sql .= " AND EXISTS (SELECT 1 FROM comments cm 
                          WHERE cm.ticket_id = t.id AND cm.user_id = ? AND 
                          EXISTS (SELECT 1 FROM users u WHERE u.id = cm.user_id AND u.role = 'tech'))";
                $params[] = $technician;
            }
            
            if (!empty($category)) {
                $sql .= " AND t.category_id = ?";
                $params[] = $category;
            }
            
            if (!empty($status)) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY t.id DESC";
            
            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error en getCustomReport: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener tendencias de tickets por período
    public function getTicketTrends($period = 'month', $limit = 6) {
        try {
            $format = '';
            switch ($period) {
                case 'day':
                    $format = '%Y-%m-%d';
                    break;
                case 'week':
                    $format = '%Y-%u'; // Año-Semana
                    break;
                case 'month':
                default:
                    $format = '%Y-%m';
                    break;
            }
            
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '$format') as period,
                        COUNT(*) as total_tickets,
                        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets
                    FROM tickets
                    GROUP BY period
                    ORDER BY period DESC
                    LIMIT $limit";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketTrends: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener distribución de tickets por prioridad
    public function getTicketsByPriority() {
        try {
            $sql = "SELECT 
                        priority,
                        COUNT(*) as total
                    FROM tickets
                    GROUP BY priority
                    ORDER BY FIELD(priority, 'high', 'medium', 'low')";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsByPriority: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener lista de técnicos para el formulario de informe
    public function getTechnicians() {
        try {
            // Modificado para obtener técnicos que han comentado en tickets
            $sql = "SELECT DISTINCT u.id, u.username, u.username as full_name
                    FROM users u
                    JOIN comments cm ON u.id = cm.user_id
                    WHERE u.role = 'tech'
                    ORDER BY u.username";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTechnicians: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener lista de categorías para el formulario de informe
    public function getCategories() {
        try {
            $sql = "SELECT id, name
                    FROM categories
                    ORDER BY name";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getCategories: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener tiempos de resolución por técnico (MODIFICADO para usar comments)
    public function getResolutionTimesByTechnician() {
        try {
            $sql = "SELECT 
                        u.username as technician_name,
                        AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) as avg_resolution_time,
                        COUNT(DISTINCT t.id) as total_tickets
                    FROM tickets t
                    JOIN comments cm ON t.id = cm.ticket_id
                    JOIN users u ON cm.user_id = u.id
                    WHERE u.role = 'tech' AND t.status IN ('resolved', 'closed')
                    GROUP BY u.id
                    ORDER BY avg_resolution_time ASC";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getResolutionTimesByTechnician: " . $e->getMessage());
            return [];
        }
    }
    
    // NUEVOS MÉTODOS PARA REPORTES DE RENDIMIENTO (RENOMBRADOS para evitar conflictos)
    
    // Obtener tickets agrupados por estado para gráficos
    public function getTicketsStatusChart() {
        try {
            $sql = "SELECT status, COUNT(*) as count FROM tickets GROUP BY status";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsStatusChart: " . $e->getMessage());
            return [];
        }
    }

    // Obtener tickets agrupados por categoría para gráficos
    public function getTicketsCategoryChart() {
        try {
            $sql = "SELECT c.name as category, COUNT(*) as count 
                    FROM tickets t 
                    JOIN categories c ON t.category_id = c.id 
                    GROUP BY t.category_id";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsCategoryChart: " . $e->getMessage());
            return [];
        }
    }

    // Obtener tickets agrupados por técnico para gráficos (MODIFICADO para usar comments)
    public function getTicketsTechnicianChart() {
        try {
            $sql = "SELECT u.username as technician, COUNT(DISTINCT cm.ticket_id) as count 
                    FROM users u 
                    JOIN comments cm ON u.id = cm.user_id 
                    WHERE u.role = 'tech'
                    GROUP BY u.id";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getTicketsTechnicianChart: " . $e->getMessage());
            return [];
        }
    }

    // Obtener tiempo promedio de resolución para gráficos
    public function getAverageResolutionTimeChart() {
        try {
            $sql = "SELECT 
                        AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours 
                    FROM tickets 
                    WHERE status IN ('resolved', 'closed')";
            $result = $this->db->query($sql);
            return $result[0] ?? ['avg_hours' => 0];
        } catch (Exception $e) {
            error_log("Error en getAverageResolutionTimeChart: " . $e->getMessage());
            return ['avg_hours' => 0];
        }
    }
    
    // MÉTODO FALTANTE: Versión alternativa para obtener tickets por técnico para gráficos
    public function getTicketsTechnicianChartAlternative($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                        u.username as technician,
                        COUNT(DISTINCT t.id) as ticket_count
                    FROM 
                        users u
                    LEFT JOIN 
                        comments c ON u.id = c.user_id
                    LEFT JOIN 
                        tickets t ON c.ticket_id = t.id
                    WHERE 
                        u.role = 'tech'";
            
            $params = [];
            
            // Agregar filtros de fecha si están presentes
            if ($startDate && $endDate) {
                $sql .= " AND t.created_at BETWEEN ? AND ?";
                $params[] = $startDate . ' 00:00:00';
                $params[] = $endDate . ' 23:59:59';
            }
            
            $sql .= " GROUP BY u.id, u.username
                      ORDER BY ticket_count DESC";
            
            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error en getTicketsTechnicianChartAlternative: " . $e->getMessage());
            return [];
        }
    }
}
?>
