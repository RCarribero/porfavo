<?php
class TicketController {
    // Dashboard del cliente o técnico
    public function dashboard() {
        // Obtener estadísticas según el rol del usuario
        $stats = [];
        
        if ($_SESSION['user_role'] == 'client') {
            // Para clientes, mostrar solo sus tickets
            $filters = ['user_id' => $_SESSION['user_id']];
            $tickets = $this->ticketModel->getAll($filters);
            
            // Contar tickets por estado
            $open = 0;
            $resolved = 0;
            $total = count($tickets);
            
            foreach ($tickets as $ticket) {
                if ($ticket['status'] == 'open' || $ticket['status'] == 'in_progress') {
                    $open++;
                } elseif ($ticket['status'] == 'resolved') {
                    $resolved++;
                }
            }
            
            $stats = [
                'open' => $open,
                'resolved' => $resolved,
                'total' => $total
            ];
            
            // Ordenar tickets por fecha (más recientes primero) y limitar a 5
            usort($tickets, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Limitar a los 5 más recientes
            $tickets = array_slice($tickets, 0, 5);
        } else {
            // Para técnicos y administradores, mostrar estadísticas generales
            $allTickets = $this->ticketModel->getAll();
            
            // Si es técnico, intentamos identificar tickets asignados mediante comentarios
            // (ya que no tenemos el campo assigned_to en la tabla)
            if ($_SESSION['user_role'] == 'tech') {
                // Obtenemos todos los tickets y luego los filtraremos en la vista según el técnico
                // actual y los comentarios del sistema que indiquen asignación
            }
            
            // Contar tickets por estado
            $stats = [
                'open' => 0,
                'in_progress' => 0,
                'resolved' => 0,
                'closed' => 0,
                'total' => count($allTickets)
            ];
            
            foreach ($allTickets as $ticket) {
                if (isset($stats[$ticket['status']])) {
                    $stats[$ticket['status']]++;
                }
            }
            
            // Obtener los tickets más recientes
            usort($allTickets, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Limitar a los 5 más recientes
            $tickets = array_slice($allTickets, 0, 5);
        }
        
        require_once __DIR__ . '/../views/tickets/dashboard.php';
    }
    private $ticketModel;
    private $categoryModel;
    private $userModel;
    
    public function __construct() {
        require_once __DIR__ . '/../models/Ticket.php';
        require_once __DIR__ . '/../models/Category.php';
        require_once __DIR__ . '/../models/User.php';
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();
        $this->userModel = new User();
    }
    
    // Mostrar lista de tickets
    public function index() {
        // Obtener los filtros de la URL
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? ''
        ];
        
        // Para clientes, mostrar solo sus tickets
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'client') {
            $filters['user_id'] = $_SESSION['user_id'];
        }
        
        // Para técnicos, mostrar tickets asignados a ellos por defecto
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'tech' && empty($filters['assigned_to'])) {
            $filters['assigned_to'] = $_SESSION['user_id'];
        }
        
        $tickets = $this->ticketModel->getAll($filters);
        $categories = $this->categoryModel->getAll();
        $technicians = $this->userModel->getAllTechnicians();
        $statuses = $this->ticketModel->getAllStatuses();
        
        require_once __DIR__ . '/../views/tickets/index.php';
    }
    
    // Mostrar detalle de un ticket
    public function view() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $_SESSION['error_message'] = "ID de ticket no especificado";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        $ticket = $this->ticketModel->getById($id);
        
        if (!$ticket) {
            $_SESSION['error_message'] = "Ticket no encontrado";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        // Verificar permisos (solo el propietario, técnicos y administradores pueden ver tickets)
        if ($_SESSION['user_role'] == 'client' && $ticket['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "No tienes permiso para ver este ticket";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        // Obtener comentarios del ticket
        $comments = $this->ticketModel->getComments($id);
        
        // Obtener adjuntos del ticket
        $attachments = $this->ticketModel->getAttachments($id);
        
        $technicians = $this->userModel->getAllTechnicians();
        $statuses = $this->ticketModel->getAllStatuses();
        
        require_once __DIR__ . '/../views/tickets/view.php';
    }
    
    // Mostrar formulario para crear ticket
    public function create() {
        $categories = $this->categoryModel->getAll();
        $errors = [];
        
        require_once __DIR__ . '/../views/tickets/create.php';
    }
    
    // Procesar la creación de un ticket
    public function store() {
        $errors = [];
        
        // Validar datos
        if (empty($_POST['title'])) {
            $errors[] = "El título es obligatorio";
        }
        
        if (empty($_POST['description'])) {
            $errors[] = "La descripción es obligatoria";
        }
        
        if (empty($_POST['category_id'])) {
            $errors[] = "La categoría es obligatoria";
        }
        
        if (empty($_POST['priority'])) {
            $errors[] = "La prioridad es obligatoria";
        }
        
        // Si hay errores, volver al formulario
        if (!empty($errors)) {
            $categories = $this->categoryModel->getAll();
            require_once __DIR__ . '/../views/tickets/create.php';
            return;
        }
        
        // Crear ticket
        $ticketData = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'status' => 'open',
            'priority' => $_POST['priority'],
            'category_id' => $_POST['category_id'],
            'user_id' => $_SESSION['user_id']
            // No incluimos assigned_to porque no existe en la estructura de la BD
        ];
        
        $ticketId = $this->ticketModel->create($ticketData);
        
        if (!$ticketId) {
            $errors[] = "Error al crear el ticket";
            $categories = $this->categoryModel->getAll();
            require_once __DIR__ . '/../views/tickets/create.php';
            return;
        }
        
        // Procesar archivos adjuntos si hay
        if (!empty($_FILES['attachments']['name'][0])) {
            $this->processAttachments($ticketId);
        }
        
        // Enviar notificación por correo (implementar después)
        $this->sendEmailNotification('new_ticket', $ticketId);
        
        $_SESSION['success_message'] = "Ticket creado correctamente con ID: " . $ticketId;
        header('Location: index.php?controller=ticket&action=view&id=' . $ticketId);
        exit;
    }
    
    // Cambiar el estado de un ticket
    public function changeStatus() {
        $ticketId = $_POST['ticket_id'] ?? null;
        $status = $_POST['status'] ?? null;
        
        if (!$ticketId || !$status) {
            $_SESSION['error_message'] = "Datos incompletos para cambiar el estado";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        // Verificar que el usuario tiene permisos (solo técnicos y admin)
        if ($_SESSION['user_role'] != 'tech' && $_SESSION['user_role'] != 'admin') {
            $_SESSION['error_message'] = "No tienes permiso para cambiar el estado del ticket";
            header('Location: index.php?controller=ticket&action=view&id=' . $ticketId);
            exit;
        }
        
        if ($this->ticketModel->changeStatus($ticketId, $status)) {
            // Registrar el cambio de estado en los comentarios
            $commentData = [
                'ticket_id' => $ticketId,
                'user_id' => $_SESSION['user_id'],
                'comment' => 'Cambió el estado del ticket a: ' . $status,
                'is_system' => 1
            ];
            $this->ticketModel->addComment($commentData);
            
            // Enviar notificación por correo
            $this->sendEmailNotification('status_change', $ticketId);
            
            $_SESSION['success_message'] = "Estado del ticket actualizado correctamente";
        } else {
            $_SESSION['error_message'] = "Error al cambiar el estado del ticket";
        }
        
        header('Location: index.php?controller=ticket&action=view&id=' . $ticketId);
        exit;
    }
    
    // Asignar un ticket a un técnico
    public function assign() {
        $ticketId = $_POST['ticket_id'] ?? null;
        $technicianId = $_POST['technician_id'] ?? null;
        
        if (!$ticketId || !$technicianId) {
            $_SESSION['error_message'] = "Datos incompletos para asignar el ticket";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        // Verificar que el usuario tiene permisos (solo técnicos y admin)
        if ($_SESSION['user_role'] != 'tech' && $_SESSION['user_role'] != 'admin') {
            $_SESSION['error_message'] = "No tienes permiso para asignar el ticket";
            header('Location: index.php?controller=ticket&action=view&id=' . $ticketId);
            exit;
        }
        
        // Obtener el nombre del técnico para el mensaje
        $technician = $this->userModel->getUserById($technicianId);
        $technicianName = $technician ? $technician['username'] : 'desconocido';
        
        // Registramos la asignación como un comentario del sistema
        // ya que no tenemos campo assigned_to en la tabla tickets
        $commentData = [
            'ticket_id' => $ticketId,
            'user_id' => $_SESSION['user_id'],
            'comment' => "Ticket asignado a técnico: " . $technicianName,
            'is_system' => 1
        ];
        
        if ($this->ticketModel->addComment($commentData)) {
            // Cambiar el estado a "en progreso" cuando se asigna
            $this->ticketModel->changeStatus($ticketId, 'in_progress');
            
            // Enviar notificación por correo
            $this->sendEmailNotification('ticket_assigned', $ticketId);
            
            $_SESSION['success_message'] = "Ticket asignado correctamente a " . $technicianName;
        } else {
            $_SESSION['error_message'] = "Error al asignar el ticket";
        }
        
        header('Location: index.php?controller=ticket&action=view&id=' . $ticketId);
        exit;
    }
    
    // Añadir un comentario a un ticket
    public function addComment() {
        $ticketId = $_POST['ticket_id'] ?? null;
        $comment = $_POST['comment'] ?? null;
        
        if (!$ticketId || !$comment) {
            $_SESSION['error_message'] = "Datos incompletos para añadir el comentario";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        $ticket = $this->ticketModel->getById($ticketId);
        
        // Verificar permisos (solo el propietario, técnicos y administradores pueden comentar)
        if ($_SESSION['user_role'] == 'client' && $ticket['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "No tienes permiso para comentar en este ticket";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        $commentData = [
            'ticket_id' => $ticketId,
            'user_id' => $_SESSION['user_id'],
            'comment' => $comment,
            'is_system' => 0
        ];
        
        if ($this->ticketModel->addComment($commentData)) {
            // Enviar notificación por correo
            $this->sendEmailNotification('new_comment', $ticketId);
            
            $_SESSION['success_message'] = "Comentario añadido correctamente";
        } else {
            $_SESSION['error_message'] = "Error al añadir el comentario";
        }
        
        header('Location: index.php?controller=ticket&action=view&id=' . $ticketId);
        exit;
    }
    
    // Descargar un archivo adjunto
    public function downloadAttachment() {
        $attachmentId = $_GET['id'] ?? null;
        
        if (!$attachmentId) {
            $_SESSION['error_message'] = "ID de archivo no especificado";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        $attachment = $this->ticketModel->getAttachmentById($attachmentId);
        
        if (!$attachment) {
            $_SESSION['error_message'] = "Archivo no encontrado";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        $ticket = $this->ticketModel->getById($attachment['ticket_id']);
        
        // Verificar permisos (solo el propietario, técnicos y administradores pueden descargar)
        if ($_SESSION['user_role'] == 'client' && $ticket['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "No tienes permiso para descargar este archivo";
            header('Location: index.php?controller=ticket&action=index');
            exit;
        }
        
        $filePath = __DIR__ . '/../uploads/' . $attachment['filepath'];
        
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            $_SESSION['error_message'] = "El archivo no existe en el servidor";
            header('Location: index.php?controller=ticket&action=view&id=' . $attachment['ticket_id']);
            exit;
        }
    }
    
    // Procesar archivos adjuntos
    private function processAttachments($ticketId) {
        $uploadDir = __DIR__ . '/../uploads/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Asegurarse de que el directorio de subidas exista
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['attachments']['name'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $fileType = $_FILES['attachments']['type'][$key];
                
                // Validar tipo y tamaño
                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['error_message'] = "Tipo de archivo no permitido: " . $fileName;
                    continue;
                }
                
                if ($fileSize > $maxSize) {
                    $_SESSION['error_message'] = "El archivo es demasiado grande: " . $fileName;
                    continue;
                }
                
                // Generar un nombre de archivo único
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueName = uniqid() . '_ticket_' . $ticketId . '.' . $fileExt;
                $targetFile = $uploadDir . $uniqueName;
                
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    // Guardar la información en la base de datos
                    $attachmentData = [
                        'ticket_id' => $ticketId,
                        'filename' => $fileName,
                        'filepath' => $uniqueName,
                        'filesize' => $fileSize
                    ];
                    
                    $this->ticketModel->addAttachment($attachmentData);
                }
            }
        }
    }
    
    // Enviar notificación por correo
    private function sendEmailNotification($type, $ticketId) {
        // Obtener información del ticket
        $ticket = $this->ticketModel->getById($ticketId);
        
        if (!$ticket) {
            return false;
        }
        
        // Obtener información del cliente
        $client = $this->userModel->getUserById($ticket['user_id']);
        
        // Obtener información del técnico asignado (si existe)
        $technician = null;
        if ($ticket['assigned_to']) {
            $technician = $this->userModel->getUserById($ticket['assigned_to']);
        }
        
        // Configurar destinatarios según el tipo de notificación
        $recipients = [];
        $subject = '';
        $message = '';
        
        switch ($type) {
            case 'new_ticket':
                $subject = 'Nuevo Ticket Creado: ' . $ticket['title'];
                $message = "Se ha creado un nuevo ticket:\n\n";
                $message .= "ID: " . $ticket['id'] . "\n";
                $message .= "Título: " . $ticket['title'] . "\n";
                $message .= "Descripción: " . $ticket['description'] . "\n";
                $message .= "Prioridad: " . $ticket['priority'] . "\n";
                $message .= "Estado: " . $ticket['status'] . "\n";
                
                // Notificar a todos los técnicos y administradores
                $techs = $this->userModel->getAllTechnicians();
                foreach ($techs as $tech) {
                    $recipients[] = $tech['email'];
                }
                break;
                
            case 'status_change':
                $subject = 'Estado de Ticket Actualizado: ' . $ticket['title'];
                $message = "El estado del ticket ha sido actualizado:\n\n";
                $message .= "ID: " . $ticket['id'] . "\n";
                $message .= "Título: " . $ticket['title'] . "\n";
                $message .= "Nuevo Estado: " . $ticket['status'] . "\n";
                
                // Notificar al cliente y al técnico asignado
                $recipients[] = $client['email'];
                if ($technician) {
                    $recipients[] = $technician['email'];
                }
                break;
                
            case 'ticket_assigned':
                $subject = 'Ticket Asignado: ' . $ticket['title'];
                $message = "El ticket ha sido asignado a un técnico:\n\n";
                $message .= "ID: " . $ticket['id'] . "\n";
                $message .= "Título: " . $ticket['title'] . "\n";
                $message .= "Técnico Asignado: " . ($technician ? $technician['username'] : 'Desconocido') . "\n";
                
                // Notificar al cliente y al técnico asignado
                $recipients[] = $client['email'];
                if ($technician) {
                    $recipients[] = $technician['email'];
                }
                break;
                
            case 'new_comment':
                $subject = 'Nuevo Comentario en Ticket: ' . $ticket['title'];
                $message = "Se ha añadido un nuevo comentario al ticket:\n\n";
                $message .= "ID: " . $ticket['id'] . "\n";
                $message .= "Título: " . $ticket['title'] . "\n";
                
                // Obtener el último comentario
                $comments = $this->ticketModel->getComments($ticketId);
                if (!empty($comments)) {
                    $lastComment = end($comments);
                    $commentAuthor = $this->userModel->getUserById($lastComment['user_id']);
                    $message .= "Autor: " . ($commentAuthor ? $commentAuthor['username'] : 'Desconocido') . "\n";
                    $message .= "Comentario: " . $lastComment['comment'] . "\n";
                }
                
                // Notificar al cliente y al técnico asignado
                $recipients[] = $client['email'];
                if ($technician) {
                    $recipients[] = $technician['email'];
                }
                break;
        }
        
        // Enviar correos (simulado)
        // En un entorno real, aquí usaríamos PHPMailer u otra librería
        error_log("Notificación por correo - Tipo: " . $type);
        error_log("Asunto: " . $subject);
        error_log("Destinatarios: " . implode(", ", $recipients));
        error_log("Mensaje: " . $message);
        
        return true;
    }
}
?>
