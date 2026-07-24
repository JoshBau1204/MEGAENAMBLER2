<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    audit_log('Cierre de sesión', 'seguridad');
}
logout_user();
header('Location: /MEGAENAMBLER2/login.php?logged_out=1');
exit;
