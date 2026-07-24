<?php
/**
 * Bootstrap de configuración. Incluir siempre al inicio de cada entrypoint.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // los errores van al log, no a pantalla (producción-safe)
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('MEGA_ROOT', dirname(__DIR__));
$secrets = require __DIR__ . '/secrets.php';
define('MEGA_SECRETS', $secrets);

/**
 * Conexión PDO singleton a PostgreSQL.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $cfg = MEGA_SECRETS['db'];
        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']}";
        try {
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            die('<div style="font-family:sans-serif;max-width:600px;margin:80px auto;padding:30px;border:1px solid #eee;border-radius:12px">
                <h2 style="color:#d91e2c">No se pudo conectar a la base de datos</h2>
                <p>Verifica que PostgreSQL esté corriendo y que <code>config/secrets.php</code> tenga las credenciales correctas.</p></div>');
        }
    }
    return $pdo;
}
