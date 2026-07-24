<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['proveedor']);
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$me = current_user();

if (!in_array($estado, ['pendiente', 'preparacion', 'camino', 'entregado'], true)) {
    json_response(['ok' => false, 'error' => 'Estado no válido.']);
}

$stmt = db()->prepare('
    UPDATE materiales_pedidos SET estado = ?, updated_at = now()
    WHERE id = ? AND proveedor_user_id = ?
    RETURNING material, obra_id
');
$stmt->execute([$estado, $id, $me['id']]);
$row = $stmt->fetch();

if (!$row) json_response(['ok' => false, 'error' => 'Pedido no encontrado.']);

audit_log("Actualizó pedido '{$row['material']}' a estado: $estado", 'materiales');

if (in_array($estado, ['camino', 'entregado'], true)) {
    $obraStmt = db()->prepare('SELECT nombre, jefe_obra_user_id FROM obras WHERE id = ?');
    $obraStmt->execute([$row['obra_id']]);
    $obra = $obraStmt->fetch();
    if ($obra && $obra['jefe_obra_user_id']) {
        $estadoTxt = $estado === 'entregado' ? 'fue entregado' : 'está en camino';
        notificar($obra['jefe_obra_user_id'], 'material', 'fa-truck-fast', 'Actualización de pedido', "{$row['material']} {$estadoTxt} en {$obra['nombre']}.", '/MEGAENAMBLER2/dashboard/jefe-obra.php#materiales');
    }
}

json_response(['ok' => true]);
