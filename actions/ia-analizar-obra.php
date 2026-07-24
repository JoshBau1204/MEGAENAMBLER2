<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/gemini.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['gerente', 'superadmin']);
csrf_verify();

$obraId = (int)($_POST['obra_id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM obras WHERE id = ?');
$stmt->execute([$obraId]);
$obra = $stmt->fetch();
if (!$obra) json_response(['ok' => false, 'error' => 'Obra no encontrada.']);

$stmt = $pdo->prepare('SELECT * FROM partidas WHERE obra_id = ? ORDER BY orden');
$stmt->execute([$obraId]);
$partidas = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM reportes_avance WHERE obra_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$obraId]);
$reportes = $stmt->fetchAll();

$resultado = gemini_analizar_riesgo_obra($obra, $partidas, $reportes);

$pdo->prepare('UPDATE obras SET riesgo_ia = ?, riesgo_ia_analisis = ?, riesgo_ia_actualizado_at = now() WHERE id = ?')
    ->execute([$resultado['nivel'], $resultado['analisis'], $obraId]);

audit_log("IA analizó riesgo de obra #$obraId → {$resultado['nivel']}", 'ia_predictiva');

if ($resultado['nivel'] === 'alto' && $obra['riesgo_ia'] !== 'alto') {
    $gerentes = $pdo->query("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug IN ('gerente','superadmin')")->fetchAll(PDO::FETCH_COLUMN);
    notificar_muchos($gerentes, 'riesgo', 'fa-triangle-exclamation', 'Riesgo alto detectado por IA', "{$obra['nombre']}: " . mb_substr((string)$resultado['analisis'], 0, 120), '/MEGAENAMBLER2/dashboard/gerente.php#prediccion');
}

json_response(['ok' => true, 'nivel' => $resultado['nivel'], 'analisis' => $resultado['analisis']]);
