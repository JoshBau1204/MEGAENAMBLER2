<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['superadmin', 'gerente']);
csrf_verify();

$nombre = trim($_POST['nombre'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$monto = (float)($_POST['monto_contratado'] ?? 0);
$jefeId = !empty($_POST['jefe_obra_user_id']) ? (int)$_POST['jefe_obra_user_id'] : null;

if (strlen($nombre) < 3) {
    json_response(['ok' => false, 'error' => 'Ingresa un nombre válido para la obra.']);
}

$stmt = db()->prepare('
    INSERT INTO obras (nombre, ubicacion, monto_contratado, jefe_obra_user_id, fecha_inicio)
    VALUES (?, ?, ?, ?, CURRENT_DATE) RETURNING id
');
$stmt->execute([$nombre, $ubicacion ?: null, $monto, $jefeId]);
$obraId = $stmt->fetchColumn();

// Partidas base por defecto para el cronograma
$partidasBase = ['Cimentación', 'Estructura', 'Instalaciones', 'Muros y tabiquería', 'Acabados'];
$ins = db()->prepare('INSERT INTO partidas (obra_id, nombre, avance_pct, orden) VALUES (?, ?, 0, ?)');
foreach ($partidasBase as $i => $p) {
    $ins->execute([$obraId, $p, $i + 1]);
}

audit_log("Nueva obra registrada: $nombre", 'obras');

if ($jefeId) {
    notificar($jefeId, 'obra', 'fa-building', 'Se te asignó una nueva obra', $nombre, '/MEGAENAMBLER2/dashboard/jefe-obra.php?obra=' . $obraId);
}

json_response(['ok' => true, 'id' => $obraId]);
