<?php
class Category {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = new Database();
    }
    
    // Obtener todas las categorías
    public function getAllCategories() {
        try {
            // CAMBIO: Eliminada la columna 'active' de la consulta SELECT
            $sql = "SELECT id, name, description 
                    FROM categories 
                    ORDER BY name";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Error en getAllCategories: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtener una categoría por ID
    public function getCategoryById($id) {
        try {
            // CAMBIO: Eliminada la columna 'active' de la consulta SELECT
            $sql = "SELECT id, name, description 
                    FROM categories 
                    WHERE id = ?";
            
            $result = $this->db->query($sql, [$id]);
            return $result[0] ?? null;
        } catch (Exception $e) {
            error_log("Error en getCategoryById: " . $e->getMessage());
            return null;
        }
    }
    
    // Crear una nueva categoría
    public function createCategory($categoryData) {
        try {
            // CAMBIO: Eliminada la columna 'active' de la consulta INSERT
            $sql = "INSERT INTO categories (name, description) 
                    VALUES (?, ?)";
            
            $params = [
                $categoryData['name'],
                $categoryData['description']
                // CAMBIO: Eliminado el parámetro 'active'
            ];
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error en createCategory: " . $e->getMessage());
            return false;
        }
    }
    
    // Actualizar una categoría existente
    public function updateCategory($id, $categoryData) {
        try {
            // CAMBIO: Eliminada la columna 'active' de la consulta UPDATE
            $sql = "UPDATE categories 
                    SET name = ?, description = ? 
                    WHERE id = ?";
            
            $params = [
                $categoryData['name'],
                $categoryData['description'],
                // CAMBIO: Eliminado el parámetro 'active'
                $id
            ];
            
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Error en updateCategory: " . $e->getMessage());
            return false;
        }
    }
    
    // Eliminar una categoría
    public function deleteCategory($id) {
        try {
            // Primero verificamos si la categoría tiene tickets asociados
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE category_id = ?";
            $result = $this->db->query($sql, [$id]);
            
            if ($result[0]['count'] > 0) {
                return false; // No podemos eliminar una categoría con tickets asociados
            }
            
            $sql = "DELETE FROM categories WHERE id = ?";
            $this->db->query($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Error en deleteCategory: " . $e->getMessage());
            return false;
        }
    }
    
    // Verificar si un nombre de categoría ya existe
    public function nameExists($name, $excludeId = null) {
        try {
            if ($excludeId) {
                $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND id != ?";
                $result = $this->db->query($sql, [$name, $excludeId]);
            } else {
                $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ?";
                $result = $this->db->query($sql, [$name]);
            }
            
            return $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Error en nameExists: " . $e->getMessage());
            return false;
        }
    }
}
?>
