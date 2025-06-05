<?php
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarNotificacionTicket($email, $nombre, $titulo, $categoria, $prioridad, $descripcion) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'correosistematickets@gmail.com';
        $mail->Password = 'dfvh dxja brej vaqp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración del correo
        $mail->setFrom('correosistematickets@gmail.com', 'Sistema de Tickets Solutia');
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = 'Ticket Editado - Solutia';

        // Cuerpo del correo
        $body = "
            <h2>Ticket Editado</h2>
            <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
            <p>Se ha editado el ticket correctamente :</p>
            <ul>
                <li><strong>Título:</strong> " . htmlspecialchars($titulo) . "</li>
                <li><strong>Categoría:</strong> " . htmlspecialchars($categoria) . "</li>
                <li><strong>Prioridad:</strong> " . htmlspecialchars($prioridad) . "</li>
                <li><strong>Descripción:</strong> " . nl2br(htmlspecialchars($descripcion)) . "</li>
            </ul>
            <p>El equipo de soporte de Solutia revisará su ticket y se pondrá en contacto con usted lo antes posible.</p>
            <p>Gracias por usar nuestro sistema de tickets.</p>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Ticket Editado\n\nTítulo: $titulo\nCategoría: $categoria\nPrioridad: $prioridad\nDescripción: $descripcion";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
