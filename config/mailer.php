<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envía un correo vía SMTP usando las credenciales del .env.
 * Devuelve false sin lanzar excepción si el SMTP no está configurado o
 * falla el envío (el llamador decide si eso es crítico o no).
 */
function enviarCorreo(string $destinatario, string $asunto, string $cuerpoHtml, ?string $replyTo = null, ?string $replyToNombre = null): bool
{
    $smtpUser = $_ENV['SMTP_USER'] ?? '';
    if ($destinatario === '' || $smtpUser === '') {
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($_ENV['SMTP_FROM'] ?? $smtpUser, $_ENV['SMTP_FROM_NAME'] ?? 'Sitio web ASOVEGU');
        $mail->addAddress($destinatario);
        if ($replyTo !== null && $replyTo !== '') {
            $mail->addReplyTo($replyTo, $replyToNombre ?? '');
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHtml;
        $mail->AltBody = strip_tags(str_replace(['</tr>', '</p>', '<br>'], "\n", $cuerpoHtml));

        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Error enviando correo: ' . $e->getMessage());
        return false;
    }
}
