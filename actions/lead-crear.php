<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
csrf_verify();

$email = trim(strtolower($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Ingresa un correo válido.']);
}

db()->prepare('INSERT INTO leads (email, origen) VALUES (?, \'landing\')')->execute([$email]);

$html = "
  <div style='font-family:Arial,sans-serif;max-width:460px;margin:auto;padding:24px'>
    <h2 style='color:#d91e2c'>Nueva solicitud de demo</h2>
    <p>Alguien solicitó una demo desde la web pública de MegaEnsambler:</p>
    <p style='font-size:16px'><b>{$email}</b></p>
    <p style='color:#67758a;font-size:13px'>Recibido el " . date('d/m/Y H:i') . "</p>
  </div>";
send_email('expmega123@gmail.com', 'Grupo MegaEnsambler', 'Nueva solicitud de demo — ' . $email, $html);

json_response(['ok' => true]);
