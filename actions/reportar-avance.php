<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['jefe_obra']);
csrf_verify();

$me = current_user();
$obraId = (int)($_POST['obra_id'] ?? 0);
$partidaId = (int)($_POST['partida_id'] ?? 0);
$porcentaje = (float)($_POST['porcentaje'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');
$origen = in_array($_POST['origen'] ?? '', ['manual', 'voz', 'qr'], true) ? $_POST['origen'] : 'manual';

if ($porcentaje < 0 || $porcentaje > 100) {
    json_response(['ok' => false, 'error' => 'El porcentaje debe estar entre 0 y 100.']);
}

$pdo = db();

// Verifica que la obra y la partida pertenezcan a este jefe de obra.
$check = $pdo->prepare('SELECT id, nombre, cliente_user_id FROM obras WHERE id = ? AND jefe_obra_user_id = ?');
$check->execute([$obraId, $me['id']]);
$obraRow = $check->fetch();
if (!$obraRow) {
    json_response(['ok' => false, 'error' => 'No tienes permiso sobre esta obra.']);
}

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO reportes_avance (obra_id, partida_id, user_id, porcentaje, comentario, origen) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$obraId, $partidaId ?: null, $me['id'], $porcentaje, $comentario ?: null, $origen]);

    if ($partidaId) {
        $pdo->prepare('UPDATE partidas SET avance_pct = ? WHERE id = ? AND obra_id = ?')->execute([$porcentaje, $partidaId, $obraId]);
    }

    $avgStmt = $pdo->prepare('SELECT COALESCE(AVG(avance_pct),0) FROM partidas WHERE obra_id = ?');
    $avgStmt->execute([$obraId]);
    $nuevoAvance = round((float)$avgStmt->fetchColumn(), 2);

    $pdo->prepare('UPDATE obras SET avance_pct = ?, updated_at = now() WHERE id = ?')->execute([$nuevoAvance, $obraId]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('reportar-avance error: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'No se pudo guardar el reporte.']);
}

audit_log("Reportó avance ($porcentaje%) en obra #$obraId", 'reportes');

if ($obraRow['cliente_user_id']) {
    notificar(
        $obraRow['cliente_user_id'],
        'avance',
        'fa-camera',
        'Nuevo avance en tu obra',
        "{$obraRow['nombre']} — reportado {$porcentaje}% de avance.",
        '/MEGAENAMBLER2/dashboard/cliente.php#timeline'
    );
}

json_response(['ok' => true, 'nuevo_avance' => $nuevoAvance]);
