<?php
class CategoryController {
    private $categoryModel;
    
    public function __construct() {
        require_once 'ticket_system/models/Category.php';
        $this->categoryModel = new Category();
    }
    
    // Mostrar lista de categorías
    public function index() {
        $categories = $this->categoryModel->getAllCategories();
        require_once 'ticket_system/views/categories/index.php';
    }
    
    // Mostrar formulario para crear categoría
    public function create() {
        $errors = [];
        require_once 'ticket_system/views/categories/create.php';
    }
    
    // Procesar la creación de una categoría
    public function store() {
        $errors = [];
        
        // Validar datos
        if (empty($_POST['name'])) {
            $errors[] = "El nombre de la categoría es obligatorio";
        } elseif ($this->categoryModel->nameExists($_POST['name'])) {
            $errors[] = "El nombre de la categoría ya está en uso";
        }
        
        // Si hay errores, volver al formulario
        if (!empty($errors)) {
            require_once 'ticket_system/views/categories/create.php';
            return;
        }
        
        // Crear categoría
        $categoryData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? ''
            // CAMBIO: Eliminada la propiedad 'active'
        ];
        
        if ($this->categoryModel->createCategory($categoryData)) {
            $_SESSION['success_message'] = "Categoría creada correctamente";
            header('Location: index.php?controller=category&action=index');
            exit;
        } else {
            $errors[] = "Error al crear la categoría";
            require_once 'ticket_system/views/categories/create.php';
        }
    }
    
    // Mostrar formulario para editar categoría
    public function edit($id = null) {
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        $category = $this->categoryModel->getCategoryById($id);
        
        if (!$category) {
            $_SESSION['error_message'] = "Categoría no encontrada";
            header('Location: index.php?controller=category&action=index');
            exit;
        }
        
        $errors = [];
        require_once 'ticket_system/views/categories/edit.php';
    }
    
    // Procesar la actualización de una categoría
    public function update() {
        $id = $_POST['id'] ?? null;
        $category = $this->categoryModel->getCategoryById($id);
        
        if (!$category) {
            $_SESSION['error_message'] = "Categoría no encontrada";
            header('Location: index.php?controller=category&action=index');
            exit;
        }
        
        $errors = [];
        
        // Validar datos
        if (empty($_POST['name'])) {
            $errors[] = "El nombre de la categoría es obligatorio";
        } elseif ($this->categoryModel->nameExists($_POST['name'], $id)) {
            $errors[] = "El nombre de la categoría ya está en uso";
        }
        
        // Si hay errores, volver al formulario
        if (!empty($errors)) {
            require_once 'ticket_system/views/categories/edit.php';
            return;
        }
        
        // Actualizar categoría
        $categoryData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? ''
            // CAMBIO: Eliminada la propiedad 'active'
        ];
        
        if ($this->categoryModel->updateCategory($id, $categoryData)) {
            $_SESSION['success_message'] = "Categoría actualizada correctamente";
            header('Location: index.php?controller=category&action=index');
            exit;
        } else {
            $errors[] = "Error al actualizar la categoría";
            require_once 'ticket_system/views/categories/edit.php';
        }
    }
    
    // Eliminar una categoría
    public function delete() {
        $id = $_POST['id'] ?? ($_GET['id'] ?? null);
        
        if (!$id) {
            $_SESSION['error_message'] = "ID de categoría no especificado";
            header('Location: index.php?controller=category&action=index');
            exit;
        }
        
        if ($this->categoryModel->deleteCategory($id)) {
            $_SESSION['success_message'] = "Categoría eliminada correctamente";
        } else {
            $_SESSION['error_message'] = "No se puede eliminar la categoría porque tiene tickets asociados";
        }
        
        header('Location: index.php?controller=category&action=index');
        exit;
    }
}
?>