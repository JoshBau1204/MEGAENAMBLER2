<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
csrf_verify();

$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['ok' => false, 'error' => 'Completa correo y contraseña.']);
}

$user = find_user_by_email($email);

if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
    audit_log("Intento de acceso fallido: $email", 'seguridad', $user['id'] ?? null);
    json_response(['ok' => false, 'error' => 'Correo o contraseña incorrectos.']);
}

if ($user['estado'] !== 'activo') {
    json_response(['ok' => false, 'error' => 'Tu cuenta está inactiva. Contacta al administrador.']);
}

// -------- Sin 2FA: login directo --------
if (!$user['two_factor_enabled']) {
    login_user($user);
    audit_log('Inicio de sesión (sin 2FA)', 'seguridad');
    json_response(['ok' => true, 'redirect' => role_dashboard_url($user['role_slug'])]);
}

// -------- Con 2FA: generar código y enviarlo --------
$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
db()->prepare('INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, now() + interval \'10 minutes\')')
    ->execute([$user['id'], $code]);

$_SESSION['pending_2fa_user_id'] = $user['id'];

$sent = send_otp_email($user['email'], $user['nombre'], $code);
audit_log('Código 2FA generado', 'seguridad', $user['id']);

$response = ['ok' => true, 'requires_2fa' => true, 'channel' => $sent ? 'email' : 'dev'];
if (!$sent) {
    // Gmail SMTP no configurado aún: mostramos el código en pantalla para no bloquear la demo.
    $response['dev_code'] = $code;
}
json_response($response);

function role_dashboard_url(string $slug): string
{
    return '/MEGAENAMBLER2/dashboard/' . str_replace('_', '-', $slug) . '.php';
}
