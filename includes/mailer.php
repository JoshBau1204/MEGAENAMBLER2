<?php
/**
 * Envío de correo vía Gmail SMTP (gratis, con App Password).
 * Si gmail_smtp.enabled = false en config/secrets.php, no se envía nada:
 * la función retorna false y quien la llama debe mostrar el código en pantalla
 * (modo desarrollo) en vez de fallar.
 */

require_once MEGA_ROOT . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function send_email(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $cfg = MEGA_SECRETS['gmail_smtp'];
    if (empty($cfg['enabled']) || empty($cfg['username']) || empty($cfg['app_password'])) {
        error_log("MAILER (modo simulado, Gmail no configurado) -> $toEmail | $subject");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['username'];
        $mail->Password = $cfg['app_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $cfg['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($cfg['username'], $cfg['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('MAILER ERROR: ' . $mail->ErrorInfo);
        return false;
    }
}

function send_otp_email(string $toEmail, string $toName, string $code): bool
{
    $html = "
      <div style='font-family:Arial,sans-serif;max-width:420px;margin:auto;padding:24px'>
        <h2 style='color:#d91e2c'>MegaEnsambler</h2>
        <p>Hola {$toName},</p>
        <p>Tu código de verificación es:</p>
        <div style='font-size:32px;font-weight:bold;letter-spacing:8px;background:#f5f7fb;padding:16px;border-radius:12px;text-align:center'>{$code}</div>
        <p style='color:#67758a;font-size:13px;margin-top:16px'>Este código expira en 10 minutos. Si no solicitaste este acceso, ignora este correo.</p>
      </div>";
    return send_email($toEmail, $toName, "Tu código de verificación: {$code}", $html);
}
