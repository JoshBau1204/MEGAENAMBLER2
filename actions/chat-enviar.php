<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/gemini.php';
require_role(['cliente']);
csrf_verify();

$me = current_user();
$obraId = (int)($_POST['obra_id'] ?? 0);
$mensaje = trim($_POST['mensaje'] ?? '');

if ($mensaje === '') json_response(['ok' => false, 'error' => 'Escribe un mensaje.']);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM obras WHERE id = ? AND cliente_user_id = ?');
$stmt->execute([$obraId, $me['id']]);
$obra = $stmt->fetch();
if (!$obra) json_response(['ok' => false, 'error' => 'No tienes acceso a esta obra.']);

$stmt = $pdo->prepare('SELECT * FROM partidas WHERE obra_id = ? ORDER BY orden');
$stmt->execute([$obraId]);
$partidas = $stmt->fetchAll();

$pdo->prepare('INSERT INTO chat_mensajes (obra_id, user_id, remitente, mensaje) VALUES (?, ?, \'cliente\', ?)')
    ->execute([$obraId, $me['id'], $mensaje]);

$respuesta = gemini_asistente_obra($obra, $partidas, $mensaje);

$pdo->prepare('INSERT INTO chat_mensajes (obra_id, user_id, remitente, mensaje) VALUES (?, ?, \'bot\', ?)')
    ->execute([$obraId, $me['id'], $respuesta]);

json_response(['ok' => true, 'respuesta' => $respuesta]);
