<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
csrf_verify();

$me = current_user();
db()->prepare('UPDATE notificaciones SET leida = TRUE WHERE user_id = ? AND leida = FALSE')->execute([$me['id']]);

json_response(['ok' => true]);
