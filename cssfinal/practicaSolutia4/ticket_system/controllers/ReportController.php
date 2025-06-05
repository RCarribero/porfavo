<?php
class ReportController {
    private $reportModel;
    private $userModel;
    private $categoryModel;
    private $ticketModel;
    
    public function __construct() {
        require_once 'ticket_system/models/Report.php';
        require_once 'ticket_system/models/User.php';
        require_once 'ticket_system/models/Category.php';
        require_once 'ticket_system/models/Ticket.php';
        
        $this->reportModel = new Report();
        $this->userModel = new User();
        $this->categoryModel = new Category();
        $this->ticketModel = new Ticket();
    }
    
    // Método para mostrar el dashboard de reportes
    public function index() {
        // Obtener datos para el dashboard
        $data = $this->reportModel->getReportData();
        $kpis = $data['kpis'];
        $trends = $data['trends'];
        $priorityStats = $data['priorityStats'];
        
        // Agregar scripts para gráficos
        $scripts = [
            'assets/js/chart.min.js',
            'assets/js/dashboard-charts.js'
        ];
        
        // Cargar la vista con ruta relativa
        require_once 'ticket_system/views/reports/dashboard.php';
    }
    
    // Método para mostrar el formulario de reportes personalizados
    public function custom() {
        // Valores predeterminados
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        $selectedTechnician = '';
        $selectedCategory = '';
        $selectedStatus = '';
        $report = []; // Inicializar $report como array vacío
        
        // Obtener listas para los filtros
        // Modificado para usar un método que no dependa de technician_id
        $technicians = $this->userModel->getAllTechnicians();
        $categories = $this->reportModel->getCategories();
        
        // Si se envió el formulario, generar el reporte
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $startDate = $_POST['start_date'] ?? $startDate;
            $endDate = $_POST['end_date'] ?? $endDate;
            $selectedTechnician = $_POST['technician'] ?? '';
            $selectedCategory = $_POST['category'] ?? '';
            $selectedStatus = $_POST['status'] ?? '';
            
            $report = $this->reportModel->getCustomReport(
                $startDate, 
                $endDate, 
                $selectedTechnician, 
                $selectedCategory, 
                $selectedStatus
            );
        }
        
        // Cargar la vista
        require_once __DIR__ . '/../views/reports/custom_report.php';
    }
    
    // Método actualizado para mostrar el formulario de informes personalizados
    public function customReport() {
        // Valores predeterminados
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        $selectedTechnician = '';
        $selectedCategory = '';
        $selectedStatus = '';
        $report = []; // Inicializar $report como array vacío
        
        // Obtener listas para los filtros
        $technicians = $this->userModel->getAllTechnicians();
        $categories = $this->categoryModel->getAllCategories();
        $statuses = $this->ticketModel->getAllStatuses();
        
        // Cargar la vista
        require_once __DIR__ . '/../views/reports/custom_report.php';
    }
    
    // Nuevo método para generar informes personalizados
    public function generateCustomReport() {
        // Obtener parámetros del formulario
        $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
        $selectedTechnician = isset($_POST['technician']) ? $_POST['technician'] : '';
        $selectedCategory = isset($_POST['category']) ? $_POST['category'] : '';
        $selectedStatus = isset($_POST['status']) ? $_POST['status'] : '';
        
        // Obtener datos del informe
        $report = $this->reportModel->getCustomReport($startDate, $endDate, $selectedTechnician, $selectedCategory, $selectedStatus);
        
        // Obtener listas para los filtros
        $technicians = $this->userModel->getAllTechnicians();
        $categories = $this->categoryModel->getAllCategories();
        $statuses = $this->ticketModel->getAllStatuses();
        
        // Cargar la vista con los resultados
        require_once __DIR__ . '/../views/reports/custom_report.php';
    }
    
    // Método para manejar la exportación
    public function export() {
        // Verificar que el usuario tiene permisos
        if (!$this->checkPermission('view_reports')) {
            $this->redirect('index.php?controller=auth&action=login');
        }
        
        // Obtener parámetros
        $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $technician = isset($_GET['technician']) ? $_GET['technician'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $ticketId = isset($_GET['ticket_id']) ? $_GET['ticket_id'] : '';
        
        // Obtener datos del reporte
        if (!empty($ticketId)) {
            // Si se proporciona un ID de ticket, exportar solo ese ticket
            $ticket = $this->reportModel->getTicketById($ticketId);
            if ($ticket) {
                $reportData = [$ticket]; // Convertir el ticket único en un array para procesarlo
            } else {
                $reportData = [];
            }
        } else {
            // De lo contrario, exportar según los filtros
            $reportData = $this->reportModel->getCustomReport($startDate, $endDate, $technician, $category, $status);
        }
        
        if ($format === 'csv') {
            $this->exportToCsv($reportData);
        } else if ($format === 'pdf') {
            $this->exportToPdf($reportData);
        }
    }
    
    // Nuevo método para exportar informes
    public function exportReport() {
        // Obtener parámetros de la URL
        $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $selectedTechnician = isset($_GET['technician']) ? $_GET['technician'] : '';
        $selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
        $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';
        $ticketId = isset($_GET['ticket_id']) ? $_GET['ticket_id'] : '';
        
        // Obtener datos del informe
        if (!empty($ticketId)) {
            // Si se proporciona un ID de ticket, exportar solo ese ticket
            $ticket = $this->reportModel->getTicketById($ticketId);
            if ($ticket) {
                $reportData = [$ticket]; // Convertir el ticket único en un array para procesarlo
            } else {
                $reportData = [];
            }
        } else {
            // De lo contrario, exportar según los filtros
            $reportData = $this->reportModel->getCustomReport($startDate, $endDate, $selectedTechnician, $selectedCategory, $selectedStatus);
        }
        
        if ($format == 'csv') {
            // Configurar cabeceras para descarga CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=informe_tickets_' . date('Y-m-d') . '.csv');
            
            // Crear archivo CSV
            $output = fopen('php://output', 'w');
            
            // Escribir encabezados
            fputcsv($output, ['ID', 'Título', 'Categoría', 'Estado', 'Técnico', 'Fecha Creación', 'Tiempo Resolución']);
            
            // Escribir datos
            foreach ($reportData as $ticket) {
                fputcsv($output, [
                    $ticket['id'],
                    $ticket['title'],
                    $ticket['category_name'],
                    isset($ticket['status_name']) ? $ticket['status_name'] : $this->getStatusLabel($ticket['status']),
                    isset($ticket['technician_name']) ? $ticket['technician_name'] : 'Sin asignar',
                    date('d/m/Y H:i', strtotime($ticket['created_at'])),
                    isset($ticket['resolution_time']) ? $ticket['resolution_time'] : 'N/A'
                ]);
            }
            
            fclose($output);
            exit;
            
        } elseif ($format == 'pdf') {
            // Usar TCPDF en lugar de FPDF
            require_once __DIR__ . '/../lib/tcpdf.php';
            
            // Crear nuevo documento PDF con TCPDF
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            
            // Configurar información del documento
            $pdf->SetCreator('Sistema de Tickets');
            $pdf->SetAuthor('Sistema de Tickets');
            $pdf->SetTitle('Informe de Tickets');
            $pdf->SetSubject('Informe de Tickets');
            
            // Establecer márgenes
            $pdf->SetMargins(10, 10, 10);
            
            // Establecer auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 10);
            
            // Agregar página
            $pdf->AddPage();
            
            // Configurar fuentes
            $pdf->SetFont('helvetica', 'B', 16);
            
            // Título del informe
            $pdf->Cell(0, 10, 'Informe de Tickets', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            
            if (!empty($ticketId)) {
                $pdf->Cell(0, 10, 'Ticket ID: ' . $ticketId, 0, 1);
            } else {
                $pdf->Cell(0, 10, 'Periodo: ' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)), 0, 1);
            }
            
            // Encabezados de tabla
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(10, 7, 'ID', 1, 0, 'C');
            $pdf->Cell(50, 7, 'Título', 1, 0, 'C');
            $pdf->Cell(30, 7, 'Categoría', 1, 0, 'C');
            $pdf->Cell(30, 7, 'Estado', 1, 0, 'C');
            $pdf->Cell(30, 7, 'Técnico', 1, 0, 'C');
            $pdf->Cell(30, 7, 'Fecha Creación', 1, 0, 'C');
            $pdf->Cell(20, 7, 'T. Resolución', 1, 1, 'C');
            
            // Datos de la tabla
            $pdf->SetFont('helvetica', '', 9);
            foreach ($reportData as $ticket) {
                $pdf->Cell(10, 6, $ticket['id'], 1, 0, 'C');
                $pdf->Cell(50, 6, substr($ticket['title'], 0, 25), 1);
                $pdf->Cell(30, 6, $ticket['category_name'], 1);
                $pdf->Cell(30, 6, isset($ticket['status_name']) ? $ticket['status_name'] : $this->getStatusLabel($ticket['status']), 1);
                $pdf->Cell(30, 6, isset($ticket['technician_name']) ? $ticket['technician_name'] : 'Sin asignar', 1);
                $pdf->Cell(30, 6, date('d/m/Y', strtotime($ticket['created_at'])), 1, 0, 'C');
                $pdf->Cell(20, 6, isset($ticket['resolution_time']) ? $ticket['resolution_time'] : 'N/A', 1, 1, 'C');
            }
            
            // Cerrar y generar PDF
            $pdf->Output('informe_tickets_' . date('Y-m-d') . '.pdf', 'D');
            exit;
        }
    }

    // Método para exportar a CSV
    private function exportToCsv($data) {
        // Configurar encabezados para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_tickets_' . date('Y-m-d') . '.csv');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // Escribir encabezados UTF-8 BOM para Excel
        fputs($output, "\xEF\xBB\xBF");
        
        // Encabezados de columnas
        fputcsv($output, ['ID', 'Título', 'Estado', 'Prioridad', 'Categoría', 'Creado', 'Actualizado', 'Cliente', 'Técnico']);
        
        // Datos - Corregido para manejar posibles campos nulos
        foreach ($data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $this->getStatusLabel($row['status']),
                ucfirst($row['priority']),
                $row['category_name'],
                date('d/m/Y H:i', strtotime($row['created_at'])),
                date('d/m/Y H:i', strtotime($row['updated_at'])),
                isset($row['client_name']) ? $row['client_name'] : 'Sin cliente', 
                isset($row['technician_name']) ? $row['technician_name'] : 'Sin asignar'
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Método para exportar a PDF usando TCPDF
    private function exportToPdf($data) {
        // Incluir biblioteca TCPDF
        require_once __DIR__ . '/../lib/tcpdf.php';
        
        // Crear nuevo documento PDF con TCPDF
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configurar información del documento
        $pdf->SetCreator('Sistema de Tickets');
        $pdf->SetAuthor('Sistema de Tickets');
        $pdf->SetTitle('Reporte de Tickets');
        $pdf->SetSubject('Reporte de Tickets');
        
        // Establecer márgenes
        $pdf->SetMargins(10, 10, 10);
        
        // Establecer auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 10);
        
        // Agregar página
        $pdf->AddPage();
        
        // Configurar fuentes
        $pdf->SetFont('helvetica', 'B', 16);
        
        // Título del reporte
        $pdf->Cell(0, 10, 'Reporte de Tickets', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        
        // Verificar si es un ticket individual
        if (count($data) == 1) {
            $pdf->Cell(0, 10, 'Detalle de Ticket #' . $data[0]['id'], 0, 1);
        } else {
            $pdf->Cell(0, 10, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1);
        }
        
        // Encabezados de tabla
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(10, 7, 'ID', 1, 0, 'C');
        $pdf->Cell(50, 7, 'Título', 1, 0, 'C');
        $pdf->Cell(25, 7, 'Estado', 1, 0, 'C');
        $pdf->Cell(20, 7, 'Prioridad', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Categoría', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Creado', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Cliente', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Técnico', 1, 1, 'C');
        
        // Datos de la tabla
        $pdf->SetFont('helvetica', '', 9);
        foreach ($data as $row) {
            $pdf->Cell(10, 6, $row['id'], 1, 0, 'C');
            // Limitar longitud del título para evitar desbordamiento
            $pdf->Cell(50, 6, substr($row['title'], 0, 25), 1);
            $pdf->Cell(25, 6, $this->getStatusLabel($row['status']), 1);
            $pdf->Cell(20, 6, ucfirst($row['priority']), 1);
            $pdf->Cell(30, 6, $row['category_name'], 1);
            $pdf->Cell(30, 6, date('d/m/Y', strtotime($row['created_at'])), 1, 0, 'C');
            $pdf->Cell(30, 6, isset($row['client_name']) ? $row['client_name'] : 'Sin cliente', 1);
            $pdf->Cell(30, 6, isset($row['technician_name']) ? $row['technician_name'] : 'Sin asignar', 1, 1);
        }
        
        // Cerrar y generar PDF
        $pdf->Output('reporte_tickets_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    // Método para mostrar la página de gráficos de rendimiento
    public function performance() {
        // Verificar que el usuario tiene permisos
        if (!$this->checkPermission('view_reports')) {
            $this->redirect('index.php?controller=auth&action=login');
        }
        
        // Obtener datos para gráficos
        $reportModel = new Report();
        
        // Tickets por estado
        $ticketsByStatus = $reportModel->getTicketsStatusChart();
        
        // Tickets por categoría
        $ticketsByCategory = $reportModel->getTicketsCategoryChart();
        
        // Tickets por técnico - MODIFICADO para no usar technician_id
        // Ahora debe usar un método alternativo para obtener esta información
        $ticketsByTechnician = $reportModel->getTicketsTechnicianChartAlternative();
        
        // Tiempo promedio de resolución
        $avgResolutionTime = $reportModel->getAverageResolutionTimeChart();
        
        // Cargar vista
        require_once __DIR__ . '/../views/reports/perfomance.php';
    }
    
    // Método auxiliar para obtener etiqueta de estado
    public function getStatusLabel($status) {
        $labels = [
            'open' => 'Abierto',
            'in_progress' => 'En Progreso',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado'
        ];
        
        return $labels[$status] ?? $status;
    }
    
    // Método auxiliar para obtener color de estado
    public function getStatusColor($status) {
        $colors = [
            'open' => 'warning',
            'in_progress' => 'info',
            'resolved' => 'success',
            'closed' => 'secondary'
        ];
        
        return $colors[$status] ?? 'primary';
    }
    
    // Método para verificar permisos (debe implementarse según tu sistema de autenticación)
    private function checkPermission($permission) {
        // Implementar verificación de permisos según tu sistema
        // Por ahora, simplemente devuelve true para que funcione el código
        return true;
    }
    
    // Método para redireccionar
    private function redirect($url) {
        header("Location: $url");
        exit;
    }
}
?>
