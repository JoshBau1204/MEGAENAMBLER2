<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

function fail_to_login(string $msg): void
{
    header('Location: /MEGAENAMBLER2/login.php?error=' . urlencode($msg));
    exit;
}

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';

if (!$state || !hash_equals($_SESSION['google_oauth_state'] ?? '', $state)) {
    fail_to_login('No se pudo verificar la solicitud de Google. Intenta de nuevo.');
}
unset($_SESSION['google_oauth_state']);

if (isset($_GET['error']) || !$code) {
    fail_to_login('Acceso con Google cancelado.');
}

$cfg = MEGA_SECRETS['google_oauth'];

// --- 1) Intercambiar el code por un access_token ---
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'client_id' => $cfg['client_id'],
        'client_secret' => $cfg['client_secret'],
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $cfg['redirect_uri'],
    ]),
    CURLOPT_TIMEOUT => 15,
]);
$tokenResponse = curl_exec($ch);
$tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tokenData = json_decode($tokenResponse, true);
if ($tokenHttpCode !== 200 || empty($tokenData['access_token'])) {
    error_log('Google OAuth token error: ' . $tokenResponse);
    fail_to_login('No se pudo completar el acceso con Google.');
}

// --- 2) Obtener el perfil del usuario ---
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenData['access_token']],
    CURLOPT_TIMEOUT => 15,
]);
$profileResponse = curl_exec($ch);
curl_close($ch);
$profile = json_decode($profileResponse, true);

if (empty($profile['email'])) {
    fail_to_login('Google no devolvió un correo válido.');
}

// --- 3) Buscar o crear el usuario ---
$user = find_user_by_email($profile['email']);

if ($user) {
    if (empty($user['google_id'])) {
        db()->prepare('UPDATE users SET google_id = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?')
            ->execute([$profile['sub'], $profile['picture'] ?? null, $user['id']]);
    }
} else {
    $roleStmt = db()->prepare('SELECT id FROM roles WHERE slug = ?');
    $roleStmt->execute(['cliente']);
    $roleId = $roleStmt->fetchColumn();

    $stmt = db()->prepare('
        INSERT INTO users (nombre, email, google_id, avatar_url, role_id, estado, two_factor_enabled)
        VALUES (?, ?, ?, ?, ?, \'activo\', FALSE) RETURNING id
    ');
    $stmt->execute([$profile['name'] ?? $profile['email'], $profile['email'], $profile['sub'], $profile['picture'] ?? null, $roleId]);
    $newId = $stmt->fetchColumn();
    audit_log('Nuevo registro vía Google: ' . $profile['email'], 'usuarios', $newId);
    $user = find_user_by_id($newId);
}

if ($user['estado'] !== 'activo') {
    fail_to_login('Tu cuenta está inactiva. Contacta al administrador.');
}

// Google ya verificó la identidad — no exigimos 2FA adicional en este acceso.
login_user($user);
audit_log('Inicio de sesión con Google', 'seguridad');

header('Location: /MEGAENAMBLER2/dashboard/' . str_replace('_', '-', $user['role_slug']) . '.php');
exit;
