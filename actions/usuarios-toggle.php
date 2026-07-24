<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['superadmin']);
csrf_verify();

$userId = (int)($_POST['user_id'] ?? 0);
$me = current_user();
if ($userId === $me['id']) {
    json_response(['ok' => false, 'error' => 'No puedes desactivar tu propia cuenta.']);
}

$stmt = db()->prepare("UPDATE users SET estado = CASE WHEN estado = 'activo' THEN 'inactivo' ELSE 'activo' END WHERE id = ? RETURNING estado");
$stmt->execute([$userId]);
$nuevoEstado = $stmt->fetchColumn();

if ($nuevoEstado === false) {
    json_response(['ok' => false, 'error' => 'Usuario no encontrado.']);
}

audit_log("Usuario #$userId cambiado a estado: $nuevoEstado", 'usuarios', $userId);
json_response(['ok' => true, 'estado' => $nuevoEstado]);
