<?php
/** Helpers de presentación: dinero, badges de estado y riesgo, tiempos relativos. */

function money(?float $amount): string
{
    return 'S/ ' . number_format((float)$amount, 0, '.', ',');
}

function money2(?float $amount): string
{
    return 'S/ ' . number_format((float)$amount, 2, '.', ',');
}

/** Formatea una fecha en español sin depender de setlocale() (poco confiable en Windows). */
function fecha_es(?string $datetime, bool $conDia = true): string
{
    if (!$datetime) return 'Por definir';
    $meses = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $ts = strtotime($datetime);
    $mes = $meses[(int)date('n', $ts)];
    return $conDia ? date('d', $ts) . ' de ' . $mes . ', ' . date('Y', $ts) : $mes . ' ' . date('Y', $ts);
}

function estado_obra_label(string $slug): string
{
    return [
        'en_tiempo' => 'En tiempo',
        'retrasada' => 'Retrasada',
        'por_finalizar' => 'Por finalizar',
        'completada' => 'Completada',
    ][$slug] ?? ucfirst($slug);
}

function estado_obra_color(string $slug): string
{
    return [
        'en_tiempo' => '#22a35a',
        'retrasada' => '#d91e2c',
        'por_finalizar' => '#06b6d4',
        'completada' => '#7c6cf6',
    ][$slug] ?? '#67758a';
}

function riesgo_label(string $slug): string
{
    return ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'][$slug] ?? ucfirst($slug);
}

function riesgo_badge_style(string $slug): string
{
    return [
        'alto' => 'background:#fff0f1;color:#d91e2c',
        'medio' => 'background:#fff8ea;color:#b45309',
        'bajo' => 'background:#eafbf1;color:#15803d',
    ][$slug] ?? 'background:#eef1f6;color:#67758a';
}

function estado_badge_class(string $estado): string
{
    return [
        'pendiente' => 'badge-amber',
        'preparacion' => 'badge-blue',
        'camino' => 'badge-blue',
        'entregado' => 'badge-green',
        'aprobada' => 'badge-blue',
        'rechazada' => 'badge-red',
        'pagada' => 'badge-green',
        'aceptado' => 'badge-green',
        'enviado' => 'badge-blue',
        'activo' => 'badge-green',
        'inactivo' => 'badge-slate',
    ][$estado] ?? 'badge-slate';
}

function time_ago(?string $datetime): string
{
    if (!$datetime) return '—';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'hace instantes';
    if ($diff < 3600) return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 2592000) { $d = floor($diff / 86400); return 'hace ' . $d . ' día' . ($d == 1 ? '' : 's'); }
    return date('d/m/Y', strtotime($datetime));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $ini = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
    if (count($parts) > 1) $ini .= strtoupper(mb_substr(end($parts), 0, 1));
    return $ini ?: '?';
}

function avatar_url(array $user): string
{
    if (!empty($user['avatar_url'])) return $user['avatar_url'];
    $color = ltrim($user['role_color'] ?? '#7c6cf6', '#');
    return 'https://ui-avatars.com/api/?name=' . urlencode($user['nombre']) . '&background=' . $color . '&color=fff&bold=true';
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
