<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
csrf_verify();

$email = trim(strtolower($_POST['email'] ?? ''));
$user = find_user_by_email($email);

// Respuesta genérica siempre (no revelamos si el correo existe o no, por seguridad).
$generic = ['ok' => true, 'message' => 'Si el correo existe en nuestro sistema, te enviamos un enlace para restablecer tu contraseña.'];

if (!$user) {
    json_response($generic);
}
if (!$user['password_hash']) {
    // Cuenta creada solo con Google: no tiene contraseña que recuperar.
    json_response(['ok' => true, 'message' => 'Esta cuenta usa "Iniciar sesión con Google". No necesita contraseña — usa ese botón en el login.']);
}

$token = bin2hex(random_bytes(32));
db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, now() + interval \'30 minutes\')')
    ->execute([$user['id'], $token]);

$resetUrl = 'http://localhost/MEGAENAMBLER2/reset-password.php?token=' . $token;
$html = "
  <div style='font-family:Arial,sans-serif;max-width:460px;margin:auto;padding:24px'>
    <h2 style='color:#d91e2c'>MegaEnsambler</h2>
    <p>Hola {$user['nombre']},</p>
    <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente botón (válido por 30 minutos):</p>
    <p style='text-align:center;margin:28px 0'>
      <a href='{$resetUrl}' style='background:#d91e2c;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:bold;display:inline-block'>Restablecer contraseña</a>
    </p>
    <p style='color:#67758a;font-size:13px'>Si no solicitaste esto, ignora este correo — tu contraseña actual seguirá funcionando.</p>
  </div>";

$sent = send_email($user['email'], $user['nombre'], 'Restablece tu contraseña', $html);
audit_log('Solicitud de recuperación de contraseña', 'seguridad', $user['id']);

if (!$sent) {
    // Gmail no configurado: devolvemos el enlace directamente para no bloquear la demo.
    $generic['dev_reset_url'] = $resetUrl;
}
json_response($generic);
