<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
csrf_verify();

$userId = $_SESSION['pending_2fa_user_id'] ?? null;
$code = trim($_POST['code'] ?? '');

if (!$userId) {
    json_response(['ok' => false, 'error' => 'Sesión expirada. Inicia sesión de nuevo.']);
}

$stmt = db()->prepare('
    SELECT * FROM two_factor_codes
    WHERE user_id = ? AND code = ? AND used = FALSE AND expires_at > now()
    ORDER BY id DESC LIMIT 1
');
$stmt->execute([$userId, $code]);
$row = $stmt->fetch();

if (!$row) {
    audit_log('Código 2FA inválido o expirado', 'seguridad', $userId);
    json_response(['ok' => false, 'error' => 'Código incorrecto o expirado.']);
}

db()->prepare('UPDATE two_factor_codes SET used = TRUE WHERE id = ?')->execute([$row['id']]);

$user = find_user_by_id($userId);
unset($_SESSION['pending_2fa_user_id']);
login_user($user);
audit_log('Inicio de sesión verificado (2FA)', 'seguridad');

json_response(['ok' => true, 'redirect' => '/MEGAENAMBLER2/dashboard/' . str_replace('_', '-', $user['role_slug']) . '.php']);
