<?php

// Cargar Composer y PHPMailer
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Crear una instancia de PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
    $mail->SMTPAuth = true;
    $mail->Username = 'correosistematickets@gmail.com'; // Tu correo Gmail
    $mail->Password = 'dfvh dxja brej vaqp'; // Tu contraseña de Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilitar encriptación TLS
    $mail->Port = 587; // Puerto SMTP para Gmail

    // Configuración del remitente y destinatario
    $mail->setFrom('correosistematickets@gmail.com', 'Solutia');
    $mail->addAddress('davidsanchezacosta0@gmail.com', 'Jaime');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Prueba de PHPMailer';
    $mail->Body = '<h1>¡Hola!</h1><p>SOMOS UNOS MAQUINAS.</p>';

    // Enviar el correo
    $mail->send();
    echo 'El correo se ha enviado correctamente.';
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}