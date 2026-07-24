<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['superadmin']);
csrf_verify();

$id = (int)($_POST['integracion_id'] ?? 0);
$stmt = db()->prepare("UPDATE integraciones SET activo = NOT activo WHERE id = ? RETURNING activo, nombre");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) json_response(['ok' => false, 'error' => 'Integración no encontrada.']);

audit_log(($row['activo'] ? 'Activó' : 'Desactivó') . " integración: {$row['nombre']}", 'integraciones');
json_response(['ok' => true, 'activo' => $row['activo']]);
