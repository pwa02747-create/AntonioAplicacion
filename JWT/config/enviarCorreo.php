<?php

/**
 * Envía un correo
 *
 * @param array|string $para      Email y Alias del destinatario
 * @param string       $asunto    Asunto del mensaje
 * @param string       $mensaje   Mensaje
 * @return void Envío de un correo
 */
function enviarCorreo($para, $asunto, $mensaje) {
    $para_alias = $para_email = $para;

    if (is_array($para)) {
        $para_alias = $para[0];
        $para_email = $para[1];
    }

    $de_alias = "Nombre Remitente";
    $de_email = "correoremitente@gmail.com";

    $mensaje = '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $asunto . '</title>
    </head>
    <body>
        ' . $mensaje . '
    </body>
    </html>';

    require_once "PHPMailer/src/Exception.php";
    require_once "PHPMailer/src/PHPMailer.php";
    require_once "PHPMailer/src/SMTP.php";

    // use PHPMailer\PHPMailer\PHPMailer;
    // use PHPMailer\PHPMailer\SMTP;
    // use PHPMailer\PHPMailer\Exception;
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPDebug  = 0;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;

        $mail->Host     = "smtp.hostinger.com";
        $mail->Port     = 465;
        $mail->Username = $de_email;
        $mail->Password = "";

        $mail->setFrom($de_email, $de_alias);
        $mail->addAddress($para_email, $para_alias);
        // $mail->addReplyTo("info@example.com", "Information");
        // $mail->addCC("cc@example.com");
        // $mail->addBCC("bcc@example.com");

        //Attachments
        // $mail->addAttachment("/var/tmp/file.tar.gz");
        // $mail->addAttachment("/tmp/image.jpg", "new.jpg");

        //Content
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;
        // $mail->AltBody = "This is the body in plain text for non-HTML mail clients";

        $mail->addBCC($de_email);        
        $mail->send();

        return true;
    }
    catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";

        return false;
    }
}

# enviarCorreo("dfraga547@gmail.com", "Prueba " . uniqid(), "<p>Hola Mundo!</p>");

?>
