<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['superadmin', 'gerente', 'jefe_obra']);
csrf_verify();

$me = current_user();
$obraId = (int)($_POST['obra_id'] ?? 0);
$material = trim($_POST['material'] ?? '');
$cantidad = trim($_POST['cantidad'] ?? '');
$proveedorId = !empty($_POST['proveedor_user_id']) ? (int)$_POST['proveedor_user_id'] : null;
$eta = trim($_POST['eta'] ?? '');

if (strlen($material) < 2) json_response(['ok' => false, 'error' => 'Indica el material.']);
if ($cantidad === '') json_response(['ok' => false, 'error' => 'Indica la cantidad.']);

$pdo = db();

// Si es jefe de obra, solo puede pedir materiales para SUS obras.
if ($me['role_slug'] === 'jefe_obra') {
    $check = $pdo->prepare('SELECT id FROM obras WHERE id = ? AND jefe_obra_user_id = ?');
    $check->execute([$obraId, $me['id']]);
    if (!$check->fetch()) {
        json_response(['ok' => false, 'error' => 'No tienes permiso sobre esa obra.']);
    }
}

$obraStmt = $pdo->prepare('SELECT nombre FROM obras WHERE id = ?');
$obraStmt->execute([$obraId]);
$obraNombre = $obraStmt->fetchColumn();
if (!$obraNombre) json_response(['ok' => false, 'error' => 'Obra no encontrada.']);

$stmt = $pdo->prepare('
    INSERT INTO materiales_pedidos (obra_id, proveedor_user_id, material, cantidad, estado, eta)
    VALUES (?, ?, ?, ?, \'pendiente\', ?) RETURNING id
');
$stmt->execute([$obraId, $proveedorId, $material, $cantidad, $eta ?: null]);
$pedidoId = $stmt->fetchColumn();

audit_log("Solicitó material '{$material}' ({$cantidad}) para {$obraNombre}", 'materiales');

if ($proveedorId) {
    notificar($proveedorId, 'material', 'fa-boxes-stacked', 'Nuevo pedido asignado', "{$material} — {$cantidad} para {$obraNombre}", '/MEGAENAMBLER2/dashboard/proveedor.php#pedidos');
}

json_response(['ok' => true, 'id' => $pedidoId]);
