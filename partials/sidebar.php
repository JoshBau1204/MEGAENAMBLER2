<?php
/**
 * Sidebar dinámico por rol.
 * Variables esperadas: $role, $activePage, $baseUrl, $userName, $userInitials
 */
if(!isset($baseUrl)) $baseUrl = '..';
if(!isset($role)) $role = 'gerente';
if(!isset($activePage)) $activePage = '';

$roleMeta = [
  'superadmin' => ['label' => 'Super Administrador', 'color' => '#d91e2c', 'icon' => 'fa-crown'],
  'gerente'    => ['label' => 'Gerente General',      'color' => '#7c6cf6', 'icon' => 'fa-chart-line'],
  'jefe-obra'  => ['label' => 'Jefe de Obra',          'color' => '#06b6d4', 'icon' => 'fa-helmet-safety'],
  'cliente'    => ['label' => 'Cliente',               'color' => '#22a35a', 'icon' => 'fa-house-user'],
  'proveedor'  => ['label' => 'Proveedor',              'color' => '#f59e0b', 'icon' => 'fa-truck-field'],
  'contador'   => ['label' => 'Contador',               'color' => '#2563eb', 'icon' => 'fa-file-invoice-dollar'],
];

$nav = [
  'superadmin' => [
    ['sec' => 'General'],
    ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-gauge-high','href'=>'superadmin.php'],
    ['key'=>'obras','label'=>'Obras','icon'=>'fa-building','href'=>'superadmin.php#obras','badge'=>'18'],
    ['key'=>'usuarios','label'=>'Usuarios & Roles','icon'=>'fa-users-gear','href'=>'superadmin.php#usuarios'],
    ['key'=>'permisos','label'=>'Permisos','icon'=>'fa-user-shield','href'=>'superadmin.php#permisos'],
    ['sec' => 'Plataforma'],
    ['key'=>'integraciones','label'=>'Integraciones','icon'=>'fa-plug','href'=>'superadmin.php#integraciones'],
    ['key'=>'ia','label'=>'IA Predictiva','icon'=>'fa-brain','href'=>'superadmin.php#ia','badge'=>'Beta'],
    ['key'=>'facturacion','label'=>'Planes & Facturación','icon'=>'fa-credit-card','href'=>'superadmin.php#facturacion'],
    ['key'=>'apariencia','label'=>'Apariencia de la web','icon'=>'fa-palette','href'=>'superadmin.php#apariencia'],
    ['sec' => 'Seguridad'],
    ['key'=>'auditoria','label'=>'Auditoría / Logs','icon'=>'fa-file-shield','href'=>'superadmin.php#auditoria'],
    ['key'=>'seguridad','label'=>'Seguridad & 2FA','icon'=>'fa-lock','href'=>'superadmin.php#seguridad'],
    ['key'=>'soporte','label'=>'Soporte','icon'=>'fa-headset','href'=>'superadmin.php#soporte'],
  ],
  'gerente' => [
    ['sec' => 'General'],
    ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-gauge-high','href'=>'gerente.php'],
    ['key'=>'obras','label'=>'Todas las Obras','icon'=>'fa-building','href'=>'gerente.php#obras','badge'=>'12'],
    ['key'=>'mapa','label'=>'Mapa de Obras','icon'=>'fa-map-location-dot','href'=>'gerente.php#mapa'],
    ['key'=>'prediccion','label'=>'Predicción IA','icon'=>'fa-brain','href'=>'gerente.php#prediccion','badge'=>'3'],
    ['sec' => 'Gestión'],
    ['key'=>'finanzas','label'=>'Finanzas & Márgenes','icon'=>'fa-sack-dollar','href'=>'gerente.php#finanzas'],
    ['key'=>'valorizaciones','label'=>'Valorizaciones','icon'=>'fa-file-invoice','href'=>'gerente.php#valorizaciones'],
    ['key'=>'equipo','label'=>'Equipo & Jefes de Obra','icon'=>'fa-people-group','href'=>'gerente.php#equipo'],
    ['key'=>'reportes','label'=>'Reportes','icon'=>'fa-chart-pie','href'=>'gerente.php#reportes'],
    ['key'=>'gamificacion','label'=>'Tabla de Líderes','icon'=>'fa-trophy','href'=>'gerente.php#gamificacion'],
  ],
  'jefe-obra' => [
    ['sec' => 'General'],
    ['key'=>'dashboard','label'=>'Mis Obras','icon'=>'fa-gauge-high','href'=>'jefe-obra.php'],
    ['key'=>'reportar','label'=>'Reportar Avance','icon'=>'fa-microphone','href'=>'jefe-obra.php#reportar'],
    ['key'=>'ar','label'=>'Plano vs Real (AR)','icon'=>'fa-camera','href'=>'jefe-obra.php#ar','badge'=>'IA'],
    ['key'=>'qr','label'=>'Escaneo QR','icon'=>'fa-qrcode','href'=>'jefe-obra.php#qr'],
    ['sec' => 'Obra'],
    ['key'=>'cronograma','label'=>'Cronograma','icon'=>'fa-calendar-days','href'=>'jefe-obra.php#cronograma'],
    ['key'=>'materiales','label'=>'Materiales & Pedidos','icon'=>'fa-boxes-stacked','href'=>'jefe-obra.php#materiales'],
    ['key'=>'equipo','label'=>'Mi Equipo','icon'=>'fa-hard-hat','href'=>'jefe-obra.php#equipo'],
    ['key'=>'logros','label'=>'Mis Logros','icon'=>'fa-medal','href'=>'jefe-obra.php#logros','badge'=>'5'],
  ],
  'cliente' => [
    ['sec' => 'Mi Proyecto'],
    ['key'=>'dashboard','label'=>'Mi Obra','icon'=>'fa-house-chimney','href'=>'cliente.php'],
    ['key'=>'timeline','label'=>'Línea de Tiempo','icon'=>'fa-images','href'=>'cliente.php#timeline'],
    ['key'=>'planos','label'=>'Planos & Documentos','icon'=>'fa-file-lines','href'=>'cliente.php#planos'],
    ['sec' => 'Pagos'],
    ['key'=>'valorizaciones','label'=>'Valorizaciones','icon'=>'fa-money-check-dollar','href'=>'cliente.php#valorizaciones'],
    ['key'=>'comprobantes','label'=>'Comprobantes SUNAT','icon'=>'fa-receipt','href'=>'cliente.php#comprobantes'],
    ['sec' => 'Comunicación'],
    ['key'=>'whatsapp','label'=>'Asistente WhatsApp','icon'=>'fa-comment-dots','href'=>'cliente.php#whatsapp','badge'=>'Bot'],
    ['key'=>'soporte','label'=>'Soporte','icon'=>'fa-headset','href'=>'cliente.php#soporte'],
  ],
  'proveedor' => [
    ['sec' => 'General'],
    ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-gauge-high','href'=>'proveedor.php'],
    ['key'=>'pedidos','label'=>'Pedidos Asignados','icon'=>'fa-clipboard-list','href'=>'proveedor.php#pedidos','badge'=>'4'],
    ['key'=>'entregas','label'=>'Entregas','icon'=>'fa-truck-fast','href'=>'proveedor.php#entregas'],
    ['sec' => 'Cuenta'],
    ['key'=>'facturacion','label'=>'Facturación','icon'=>'fa-file-invoice-dollar','href'=>'proveedor.php#facturacion'],
    ['key'=>'historial','label'=>'Historial','icon'=>'fa-clock-rotate-left','href'=>'proveedor.php#historial'],
    ['key'=>'perfil','label'=>'Perfil de Empresa','icon'=>'fa-id-card','href'=>'proveedor.php#perfil'],
  ],
  'contador' => [
    ['sec' => 'Financiero'],
    ['key'=>'dashboard','label'=>'Dashboard','icon'=>'fa-gauge-high','href'=>'contador.php'],
    ['key'=>'valorizaciones','label'=>'Valorizaciones','icon'=>'fa-file-invoice','href'=>'contador.php#valorizaciones'],
    ['key'=>'facturacion','label'=>'Facturación SUNAT','icon'=>'fa-file-invoice-dollar','href'=>'contador.php#facturacion'],
    ['key'=>'adelantos','label'=>'Adelantos','icon'=>'fa-hand-holding-dollar','href'=>'contador.php#adelantos'],
    ['sec' => 'Control'],
    ['key'=>'caja','label'=>'Caja Chica','icon'=>'fa-vault','href'=>'contador.php#caja'],
    ['key'=>'reportes','label'=>'Reportes Contables','icon'=>'fa-chart-column','href'=>'contador.php#reportes'],
  ],
];

$meta = $roleMeta[$role] ?? $roleMeta['gerente'];
$items = $nav[$role] ?? [];
if(!isset($userName)) $userName = 'Usuario Demo';
if(!isset($userInitials)) $userInitials = 'UD';

// Los dashboards pueden pasar $navBadges = ['clave' => valor real] para
// reemplazar los contadores estáticos de la maqueta por datos reales.
if (!empty($navBadges) && is_array($navBadges)) {
    foreach ($items as &$item) {
        if (isset($item['key'], $navBadges[$item['key']])) {
            $item['badge'] = (string) $navBadges[$item['key']];
        }
    }
    unset($item);
}
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Logo">
    <div class="leading-tight">
      <div class="font-head font-bold text-[15px] text-white">MegaEnsambler</div>
      <div class="text-[11px] text-white/45 tracking-wide">BIM COORDINATION</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach($items as $item): ?>
      <?php if(isset($item['sec'])): ?>
        <div class="sidebar-section-label"><?= $item['sec'] ?></div>
      <?php else: ?>
        <a href="<?= $item['href'] ?>" class="nav-link <?= $activePage === $item['key'] ? 'active' : '' ?>">
          <i class="fa-solid <?= $item['icon'] ?>"></i>
          <span><?= $item['label'] ?></span>
          <?php if(isset($item['badge'])): ?><span class="nav-badge"><?= $item['badge'] ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="flex items-center gap-3 mb-3 px-1">
      <div class="w-10 h-10 rounded-full flex items-center justify-center font-head font-bold text-sm text-white flex-shrink-0" style="background:<?= $meta['color'] ?>">
        <?= $userInitials ?>
      </div>
      <div class="leading-tight overflow-hidden">
        <div class="text-white text-[13.5px] font-semibold truncate"><?= $userName ?></div>
        <div class="text-white/45 text-[11.5px] truncate"><i class="fa-solid <?= $meta['icon'] ?> mr-1"></i><?= $meta['label'] ?></div>
      </div>
    </div>
    <button type="button" data-open-modal="logoutModal" class="btn btn-ghost btn-sm w-full !text-white/70 hover:!bg-white/10 justify-center">
      <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
    </button>
  </div>
</aside>

<?php include __DIR__ . '/logout-modal.php'; ?>
