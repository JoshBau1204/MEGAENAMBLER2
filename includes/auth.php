<?php
/**
 * Helpers de autenticación, autorización y auditoría.
 * Requiere que config/config.php ya haya sido incluido (sesión + db()).
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/** Corta la ejecución y redirige al login si no hay sesión activa. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /MEGAENAMBLER2/login.php');
        exit;
    }
}

/** Exige uno de los roles indicados (slugs). Redirige a su propio panel si no cumple. */
function require_role(array $allowedSlugs): void
{
    require_login();
    $user = current_user();
    if (!in_array($user['role_slug'], $allowedSlugs, true)) {
        header('Location: /MEGAENAMBLER2/dashboard/' . str_replace('_', '-', $user['role_slug']) . '.php');
        exit;
    }
}

function login_user(array $userRow): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $userRow['id'],
        'nombre' => $userRow['nombre'],
        'email' => $userRow['email'],
        'role_id' => $userRow['role_id'],
        'role_slug' => $userRow['role_slug'],
        'role_nombre' => $userRow['role_nombre'],
        'role_color' => $userRow['role_color'],
        'avatar_url' => $userRow['avatar_url'],
    ];
    db()->prepare('UPDATE users SET last_login_at = now() WHERE id = ?')->execute([$userRow['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path']);
    }
    session_destroy();
}

/** Devuelve el usuario (con datos de rol) por email, o null. */
function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('
        SELECT u.*, r.slug AS role_slug, r.nombre AS role_nombre, r.color_hex AS role_color
        FROM users u JOIN roles r ON r.id = u.role_id
        WHERE u.email = ? LIMIT 1
    ');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('
        SELECT u.*, r.slug AS role_slug, r.nombre AS role_nombre, r.color_hex AS role_color
        FROM users u JOIN roles r ON r.id = u.role_id
        WHERE u.id = ? LIMIT 1
    ');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Registra una acción en la tabla de auditoría. */
function audit_log(string $accion, string $modulo, ?int $userId = null): void
{
    $userId = $userId ?? (current_user()['id'] ?? null);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    db()->prepare('INSERT INTO auditoria (user_id, accion, modulo, ip) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $accion, $modulo, $ip]);
}

/* ---------------------------- CSRF ---------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
    }
}

/* ------------------------ Respuestas JSON ------------------------ */

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
