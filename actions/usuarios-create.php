<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_role(['superadmin']);
csrf_verify();

$nombre = trim($_POST['nombre'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));
$roleId = (int)($_POST['role_id'] ?? 0);

if (strlen($nombre) < 3) json_response(['ok' => false, 'error' => 'Ingresa un nombre válido.']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['ok' => false, 'error' => 'Correo no válido.']);
if (find_user_by_email($email)) json_response(['ok' => false, 'error' => 'Ya existe un usuario con ese correo.']);

$roleCheck = db()->prepare('SELECT slug FROM roles WHERE id = ?');
$roleCheck->execute([$roleId]);
$roleSlug = $roleCheck->fetchColumn();
if (!$roleSlug || $roleSlug === 'superadmin') {
    json_response(['ok' => false, 'error' => 'Rol no válido.']);
}

$tempPassword = bin2hex(random_bytes(5));
$hash = password_hash($tempPassword, PASSWORD_BCRYPT);

$stmt = db()->prepare('INSERT INTO users (nombre, email, password_hash, role_id, estado) VALUES (?, ?, ?, ?, \'activo\') RETURNING id');
$stmt->execute([$nombre, $email, $hash, $roleId]);
$newId = $stmt->fetchColumn();

audit_log("Usuario invitado: $email ($roleSlug)", 'usuarios', $newId);

$html = "
  <div style='font-family:Arial,sans-serif;max-width:460px;margin:auto;padding:24px'>
    <h2 style='color:#d91e2c'>MegaEnsambler</h2>
    <p>Hola {$nombre},</p>
    <p>Se creó una cuenta para ti en la plataforma MegaEnsambler. Estas son tus credenciales temporales:</p>
    <p><b>Correo:</b> {$email}<br><b>Contraseña temporal:</b> <span style='font-family:monospace;background:#f5f7fb;padding:4px 8px;border-radius:6px'>{$tempPassword}</span></p>
    <p style='color:#67758a;font-size:13px'>Te recomendamos cambiarla apenas inicies sesión.</p>
  </div>";
$sent = send_email($email, $nombre, 'Tu acceso a MegaEnsambler', $html);

$response = ['ok' => true];
if (!$sent) $response['dev_password'] = $tempPassword;
json_response($response);
