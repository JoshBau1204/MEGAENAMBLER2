<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['gerente', 'contador', 'superadmin']);
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$me = current_user();

$map = ['aprobar' => 'aprobada', 'rechazar' => 'rechazada', 'pagar' => 'pagada'];
if (!isset($map[$accion])) {
    json_response(['ok' => false, 'error' => 'Acción no válida.']);
}

$stmt = db()->prepare('
    UPDATE valorizaciones SET estado = ?, aprobado_por = ?, updated_at = now()
    WHERE id = ?
    RETURNING contratista, numero, monto, obra_id
');
$stmt->execute([$map[$accion], $me['id'], $id]);
$row = $stmt->fetch();

if (!$row) json_response(['ok' => false, 'error' => 'Valorización no encontrada.']);

audit_log("Valorización {$row['numero']} ({$row['contratista']}) marcada como {$map[$accion]}", 'finanzas');

$obraStmt = db()->prepare('SELECT nombre, cliente_user_id FROM obras WHERE id = ?');
$obraStmt->execute([$row['obra_id']]);
$obra = $obraStmt->fetch();

if ($obra) {
    $textos = [
        'aprobada' => ['fa-circle-check', 'Valorización aprobada'],
        'rechazada' => ['fa-circle-xmark', 'Valorización rechazada'],
        'pagada' => ['fa-sack-dollar', 'Valorización pagada'],
    ];
    [$icono, $titulo] = $textos[$map[$accion]];

    if ($obra['cliente_user_id']) {
        notificar($obra['cliente_user_id'], 'valorizacion', $icono, $titulo, "{$row['numero']} de {$obra['nombre']} — {$row['contratista']}", '/MEGAENAMBLER2/dashboard/cliente.php#valorizaciones');
    }

    if ($map[$accion] === 'aprobada') {
        $contadores = db()->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'contador'")->fetchAll(PDO::FETCH_COLUMN);
        notificar_muchos($contadores, 'valorizacion', 'fa-file-invoice-dollar', 'Nueva valorización lista para pagar', "{$row['numero']} — {$row['contratista']} (S/ {$row['monto']})", '/MEGAENAMBLER2/dashboard/contador.php#valorizaciones');
    }
}

json_response(['ok' => true, 'estado' => $map[$accion]]);
