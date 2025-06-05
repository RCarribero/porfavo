<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar sesión ANTES de incluir TCPDF
if (!isset($_SESSION['id'])) {
    header('Location: ../../views/session/login.php');
    exit();
}

// Incluir TCPDF después de verificar la sesión
require_once __DIR__ . '/../lib/tcpdf.php';

// Configurar zona horaria
date_default_timezone_set('Europe/Madrid');

// Definir constantes TCPDF solo si no existen
if (!defined('K_PATH_MAIN')) {
    define('K_PATH_MAIN', __DIR__ . '/../lib/');
}
if (!defined('K_PATH_URL')) {
    define('K_PATH_URL', '');
}
if (!defined('PDF_HEADER_LOGO')) {
    define('PDF_HEADER_LOGO', '');
}
if (!defined('PDF_HEADER_LOGO_WIDTH')) {
    define('PDF_HEADER_LOGO_WIDTH', 30);
}
if (!defined('PDF_HEADER_TITLE')) {
    define('PDF_HEADER_TITLE', 'SOLUTIA - Sistema de Tickets');
}
if (!defined('PDF_HEADER_STRING')) {
    define('PDF_HEADER_STRING', '');
}
if (!defined('PDF_MARGIN_LEFT')) {
    define('PDF_MARGIN_LEFT', 15);
}
if (!defined('PDF_MARGIN_RIGHT')) {
    define('PDF_MARGIN_RIGHT', 15);
}
if (!defined('PDF_MARGIN_TOP')) {
    define('PDF_MARGIN_TOP', 27);
}
if (!defined('PDF_MARGIN_BOTTOM')) {
    define('PDF_MARGIN_BOTTOM', 25);
}
if (!defined('PDF_MARGIN_HEADER')) {
    define('PDF_MARGIN_HEADER', 5);
}
if (!defined('PDF_MARGIN_FOOTER')) {
    define('PDF_MARGIN_FOOTER', 16);
}
if (!defined('PDF_FONT_NAME_MAIN')) {
    define('PDF_FONT_NAME_MAIN', 'helvetica');
}
if (!defined('PDF_FONT_SIZE_MAIN')) {
    define('PDF_FONT_SIZE_MAIN', 10);
}
if (!defined('PDF_FONT_NAME_DATA')) {
    define('PDF_FONT_NAME_DATA', 'helvetica');
}
if (!defined('PDF_FONT_SIZE_DATA')) {
    define('PDF_FONT_SIZE_DATA', 8);
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}

// Obtener tickets
$tickets = [];
if (isset($_SESSION['filtered_tickets']) && !empty($_SESSION['filtered_tickets'])) {
    $tickets = $_SESSION['filtered_tickets'];
} else {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $_SESSION['id']]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Error al obtener los tickets: ' . $e->getMessage());
    }
}

// Clase TCPDF personalizada
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->SetY(15);
        $this->Cell(0, 10, 'SOLUTIA - Sistema de Tickets', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(15);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

try {
    // Crear documento PDF
    $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Configurar documento
    $pdf->SetCreator('SOLUTIA');
    $pdf->SetAuthor('SOLUTIA');
    $pdf->SetTitle('Reporte de Tickets');
    $pdf->SetSubject('Reporte de Tickets');

    // Configurar márgenes
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 20);

    // Añadir página
    $pdf->AddPage();

    // Título del reporte
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 15, 'Reporte de Tickets', 0, 1, 'C');
    $pdf->Ln(10);

    // Fecha de generación
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1, 'R');
    $pdf->Ln(10);

    // Verificar si hay tickets
    if (empty($tickets)) {
        $pdf->SetFont('helvetica', '', 14);
        $pdf->Cell(0, 20, 'No hay tickets para mostrar', 0, 1, 'C');
    } else {
        // Encabezado de la tabla
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        
        // Anchos de columna ajustados para que quepan en la página
        $w = array(15, 40, 50, 25, 25, 30);
        $headers = array('ID', 'Título', 'Descripción', 'Prioridad', 'Estado', 'Fecha');
        
        for($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($w[$i], 8, $headers[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();

        // Contenido de la tabla
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(240, 248, 255);
        $pdf->SetTextColor(0, 0, 0);
        
        $fill = false;
        foreach ($tickets as $ticket) {
            // Verificar si necesitamos una nueva página
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repetir encabezados
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->SetFillColor(52, 152, 219);
                $pdf->SetTextColor(255, 255, 255);
                for($i = 0; $i < count($headers); $i++) {
                    $pdf->Cell($w[$i], 8, $headers[$i], 1, 0, 'C', 1);
                }
                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetTextColor(0, 0, 0);
            }
            
            // Truncar texto largo
            $title = strlen($ticket['title']) > 30 ? substr($ticket['title'], 0, 27) . '...' : $ticket['title'];
            $description = strlen($ticket['description']) > 40 ? substr($ticket['description'], 0, 37) . '...' : $ticket['description'];
            
            $pdf->Cell($w[0], 8, $ticket['id'], 1, 0, 'C', $fill);
            $pdf->Cell($w[1], 8, $title, 1, 0, 'L', $fill);
            $pdf->Cell($w[2], 8, $description, 1, 0, 'L', $fill);
            $pdf->Cell($w[3], 8, $ticket['priority'], 1, 0, 'C', $fill);
            $pdf->Cell($w[4], 8, $ticket['status'], 1, 0, 'C', $fill);
            $pdf->Cell($w[5], 8, date('d/m/Y', strtotime($ticket['created_at'])), 1, 0, 'C', $fill);
            $pdf->Ln();
            
            $fill = !$fill;
        }
    }

    // Resumen al final
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Resumen:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'Total de tickets: ' . count($tickets), 0, 1, 'L');
    
    // Contar por estados
    $counts = array();
    foreach ($tickets as $ticket) {
        $status = $ticket['status'];
        $counts[$status] = isset($counts[$status]) ? $counts[$status] + 1 : 1;
    }
    
    foreach ($counts as $status => $count) {
        $pdf->Cell(0, 8, ucfirst(str_replace('_', ' ', $status)) . ': ' . $count, 0, 1, 'L');
    }

    // Limpiar cualquier salida previa
    ob_clean();
    
    // Generar PDF
    $filename = 'reporte_tickets_' . date('Y-m-d_H-i') . '.pdf';
    $pdf->Output($filename, 'I');
    
} catch (Exception $e) {
    // Log del error
    error_log('Error PDF: ' . $e->getMessage());
    
    // Mostrar error amigable
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h3>Error al generar el PDF</h3>
            <p>Se ha producido un error al generar el archivo PDF. Por favor, inténtelo de nuevo.</p>
            <p><strong>Error técnico:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p><a href="javascript:history.back()">← Volver atrás</a></p>
        </div>
    </body>
    </html>';
}
?>