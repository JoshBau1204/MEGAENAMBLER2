<?php
/**
 * Sistema de notificaciones entre roles.
 * Cuando un rol sube/aprueba/cambia algo, el rol relacionado recibe una notificación real.
 */

function notificar(int $userId, string $tipo, string $icono, string $titulo, ?string $mensaje = null, ?string $link = null): void
{
    db()->prepare('INSERT INTO notificaciones (user_id, tipo, icono, titulo, mensaje, link) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$userId, $tipo, $icono, $titulo, $mensaje, $link]);
}

function notificar_muchos(array $userIds, string $tipo, string $icono, string $titulo, ?string $mensaje = null, ?string $link = null): void
{
    foreach (array_unique(array_filter($userIds)) as $uid) {
        notificar((int)$uid, $tipo, $icono, $titulo, $mensaje, $link);
    }
}

function obtener_notificaciones(int $userId, int $limit = 8): array
{
    $stmt = db()->prepare('SELECT * FROM notificaciones WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function contar_no_leidas(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notificaciones WHERE user_id = ? AND leida = FALSE');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
