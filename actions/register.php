<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
csrf_verify();

$nombre = trim($_POST['nombre'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if (strlen($nombre) < 3) {
    json_response(['ok' => false, 'error' => 'Ingresa tu nombre completo.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Correo electrónico no válido.']);
}
if (strlen($password) < 8) {
    json_response(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.']);
}
if (find_user_by_email($email)) {
    json_response(['ok' => false, 'error' => 'Ya existe una cuenta con ese correo. Intenta iniciar sesión.']);
}

$roleStmt = db()->prepare('SELECT id FROM roles WHERE slug = ?');
$roleStmt->execute(['cliente']);
$roleId = $roleStmt->fetchColumn();

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = db()->prepare('INSERT INTO users (nombre, email, password_hash, role_id, estado) VALUES (?, ?, ?, ?, \'activo\') RETURNING id');
$stmt->execute([$nombre, $email, $hash, $roleId]);
$newId = $stmt->fetchColumn();

audit_log("Nuevo registro: $email", 'usuarios', $newId);

$user = find_user_by_id($newId);

$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
db()->prepare('INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, now() + interval \'10 minutes\')')
    ->execute([$user['id'], $code]);
$_SESSION['pending_2fa_user_id'] = $user['id'];

$sent = send_otp_email($user['email'], $user['nombre'], $code);

$response = ['ok' => true, 'requires_2fa' => true, 'channel' => $sent ? 'email' : 'dev'];
if (!$sent) {
    $response['dev_code'] = $code;
}
json_response($response);
