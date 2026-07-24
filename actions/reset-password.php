<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
csrf_verify();

$token = trim($_POST['token'] ?? '');
$password = (string)($_POST['password'] ?? '');

if (strlen($password) < 8) {
    json_response(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.']);
}

$stmt = db()->prepare('SELECT * FROM password_resets WHERE token = ? AND used = FALSE AND expires_at > now() LIMIT 1');
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    json_response(['ok' => false, 'error' => 'El enlace es inválido o ya expiró. Solicita uno nuevo.']);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
db()->prepare('UPDATE users SET password_hash = ?, updated_at = now() WHERE id = ?')->execute([$hash, $reset['user_id']]);
db()->prepare('UPDATE password_resets SET used = TRUE WHERE id = ?')->execute([$reset['id']]);

audit_log('Contraseña restablecida', 'seguridad', $reset['user_id']);

json_response(['ok' => true]);
