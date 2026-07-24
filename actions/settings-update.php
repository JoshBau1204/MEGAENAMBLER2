<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['superadmin']);
csrf_verify();

$allowedKeys = ['hero_title', 'empresa_nombre', 'brand_color'];
$stmt = db()->prepare('INSERT INTO site_settings (key_name, value) VALUES (?, ?) ON CONFLICT (key_name) DO UPDATE SET value = EXCLUDED.value');

foreach ($allowedKeys as $key) {
    if (isset($_POST[$key])) {
        $stmt->execute([$key, trim($_POST[$key])]);
    }
}

audit_log('Actualizó la configuración del sitio público', 'apariencia');
json_response(['ok' => true]);
