<?php
require_once __DIR__ . '/../config/config.php';

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$cfg = MEGA_SECRETS['google_oauth'];
$params = http_build_query([
    'client_id' => $cfg['client_id'],
    'redirect_uri' => $cfg['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
