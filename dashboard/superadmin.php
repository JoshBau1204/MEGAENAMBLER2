<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['superadmin']);

$me = current_user();
$baseUrl = '..'; $pageTitle = 'Panel Super Admin';
$role = 'superadmin'; $activePage = 'dashboard';
$userName = $me['nombre']; $userInitials = initials($me['nombre']); $roleColor = $me['role_color'];
$pageHeading = 'Panel de Control General'; $pageSubheading = 'Visión completa de la plataforma';

$pdo = db();

// ---- KPIs ----
$totalObras = $pdo->query("SELECT COUNT(*) FROM obras")->fetchColumn();
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$facturacionMes = $pdo->query("SELECT COALESCE(SUM(monto_ejecutado),0) FROM obras")->fetchColumn();
$obrasRiesgoAlto = $pdo->query("SELECT COUNT(*) FROM obras WHERE riesgo_ia = 'alto'")->fetchColumn();
$navBadges = ['obras' => $totalObras];

// ---- Obras ----
$obras = $pdo->query("
    SELECT o.*, jo.nombre AS jefe_nombre, cl.nombre AS cliente_nombre
    FROM obras o
    LEFT JOIN users jo ON jo.id = o.jefe_obra_user_id
    LEFT JOIN users cl ON cl.id = o.cliente_user_id
    ORDER BY o.created_at DESC
")->fetchAll();

// ---- Usuarios ----
$usuarios = $pdo->query("
    SELECT u.*, r.slug AS role_slug, r.nombre AS role_nombre, r.color_hex AS role_color
    FROM users u JOIN roles r ON r.id = u.role_id
    ORDER BY u.created_at DESC
")->fetchAll();
$rolesList = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$jefesDisponibles = $pdo->query("SELECT u.id, u.nombre FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'jefe_obra'")->fetchAll();
$proveedoresDisponibles = $pdo->query("SELECT u.id, u.nombre FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'proveedor'")->fetchAll();
$obrasParaMaterial = array_map(fn($o) => ['id' => $o['id'], 'nombre' => $o['nombre']], $obras);

// ---- Integraciones ----
$integraciones = $pdo->query("SELECT * FROM integraciones ORDER BY id")->fetchAll();

// ---- Auditoría ----
$logs = $pdo->query("
    SELECT a.*, u.nombre AS user_nombre
    FROM auditoria a LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC LIMIT 12
")->fetchAll();

// ---- Site settings ----
$settingsRows = $pdo->query("SELECT key_name, value FROM site_settings")->fetchAll();
$settings = [];
foreach ($settingsRows as $r) $settings[$r['key_name']] = $r['value'];

// ---- Gráfico: reportes de avance por mes (últimos 7 meses) ----
$reportesPorMes = $pdo->query("
    SELECT to_char(date_trunc('month', created_at), 'Mon') AS mes, COUNT(*) AS total
    FROM reportes_avance
    WHERE created_at > now() - interval '7 months'
    GROUP BY date_trunc('month', created_at)
    ORDER BY date_trunc('month', created_at)
")->fetchAll();

// ---- Gráfico: distribución de usuarios por rol ----
$usuariosPorRol = $pdo->query("
    SELECT r.nombre, r.color_hex, COUNT(u.id) AS total
    FROM roles r LEFT JOIN users u ON u.role_id = r.id
    GROUP BY r.id ORDER BY r.id
")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<?php include '../partials/head.php'; ?>
</head>
<body class="font-body text-navy-800">

<div class="app-shell">
  <?php include '../partials/sidebar.php'; ?>

  <div class="main-col">
    <?php include '../partials/topbar.php'; ?>

    <main class="content">

      <!-- Franja de estado del sistema (centro de comando) -->
      <?php
        $integracionesActivas = count(array_filter($integraciones, fn($i) => $i['activo']));
        $pgVersion = explode(' ', $pdo->query('SELECT version()')->fetchColumn())[1] ?? '?';
      ?>
      <div class="command-strip mb-6" data-reveal>
        <span class="status-pulse"></span>
        <span>SISTEMA OPERATIVO</span>
        <span class="sep">│</span>
        <span>PostgreSQL <?= e($pgVersion) ?></span>
        <span class="sep">│</span>
        <span>PHP <?= phpversion() ?></span>
        <span class="sep">│</span>
        <span><?= $integracionesActivas ?>/<?= count($integraciones) ?> integraciones activas</span>
        <span class="sep">│</span>
        <span><?= $totalUsuarios ?> usuarios · <?= $totalObras ?> obras</span>
        <span class="sep">│</span>
        <span><?= date('d/m/Y H:i:s') ?></span>
      </div>

      <?php if ($obrasRiesgoAlto > 0): ?>
      <div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-4 border-l-4" style="border-left-color:#d91e2c" data-reveal>
        <div class="stat-icon bg-brand-50 text-brand-600 mb-0"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="flex-1 min-w-[200px]">
          <div class="font-semibold text-navy-900 text-sm">La IA detectó <?= $obrasRiesgoAlto ?> obra<?= $obrasRiesgoAlto == 1 ? '' : 's' ?> con riesgo alto de retraso</div>
          <div class="text-[12.5px] text-slate-500">Revisa el módulo de Predicción IA para ver el detalle y acciones sugeridas.</div>
        </div>
        <a href="gerente.php#prediccion" class="btn btn-outline btn-sm">Revisar ahora</a>
      </div>
      <?php endif; ?>

      <!-- KPIs -->
      <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="stat-card" data-reveal>
          <div class="stat-icon" style="background:#d91e2c18;color:#d91e2c"><i class="fa-solid fa-building"></i></div>
          <div class="stat-value"><span data-counter="<?= $totalObras ?>">0</span></div>
          <div class="stat-label">Obras registradas</div>
        </div>
        <div class="stat-card" data-reveal data-delay="70">
          <div class="stat-icon" style="background:#7c6cf618;color:#7c6cf6"><i class="fa-solid fa-users"></i></div>
          <div class="stat-value"><span data-counter="<?= $totalUsuarios ?>">0</span></div>
          <div class="stat-label">Usuarios en el sistema</div>
        </div>
        <div class="stat-card" data-reveal data-delay="140">
          <div class="stat-icon" style="background:#22a35a18;color:#22a35a"><i class="fa-solid fa-sack-dollar"></i></div>
          <div class="stat-value"><?= money($facturacionMes) ?></div>
          <div class="stat-label">Monto total ejecutado</div>
        </div>
        <div class="stat-card" data-reveal data-delay="210">
          <div class="stat-icon" style="background:#06b6d418;color:#06b6d4"><i class="fa-solid fa-server"></i></div>
          <div class="stat-value">99.98%</div>
          <div class="stat-label">Disponibilidad del sistema</div>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 card p-6" data-reveal>
          <div class="flex items-center justify-between mb-5">
            <div>
              <h3 class="font-head font-bold text-navy-900">Actividad de la plataforma</h3>
              <p class="text-[12.5px] text-slate-500">Reportes de avance registrados por mes</p>
            </div>
          </div>
          <canvas id="chartActivity" height="230"></canvas>
        </div>
        <div class="card p-6" data-reveal data-delay="100">
          <h3 class="font-head font-bold text-navy-900 mb-1">Distribución por rol</h3>
          <p class="text-[12.5px] text-slate-500 mb-5"><?= $totalUsuarios ?> usuarios activos</p>
          <canvas id="chartRoles" height="230"></canvas>
        </div>
      </div>

      <!-- ===================== OBRAS ===================== -->
      <section id="obras" class="mb-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
          <div>
            <h2 class="font-head font-bold text-[20px] text-navy-900">Gestión de Obras</h2>
            <p class="text-[12.5px] text-slate-500">Todas las obras registradas en la plataforma</p>
          </div>
          <div class="flex gap-2">
            <div class="relative">
              <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input id="searchObras" type="text" placeholder="Buscar obra…" class="bg-slate-100 rounded-full pl-9 pr-4 py-2 text-[13px] outline-none border border-transparent focus:border-brand-500 focus:bg-white transition">
            </div>
            <button data-open-modal="modalMaterial" class="btn btn-outline btn-sm"><i class="fa-solid fa-boxes-stacked"></i> Solicitar material</button>
            <button data-open-modal="modalObra" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Nueva Obra</button>
          </div>
        </div>

        <div class="table-wrap card">
          <table class="data-table" id="tableObras">
            <thead><tr>
              <th>Obra</th><th>Jefe de Obra</th><th>Cliente</th><th>Avance</th><th>Riesgo IA</th><th>Estado</th>
            </tr></thead>
            <tbody>
              <?php if (!$obras): ?>
                <tr><td colspan="6" class="text-center text-slate-400 py-8">Aún no hay obras registradas. Crea la primera con "Nueva Obra".</td></tr>
              <?php endif; ?>
              <?php foreach($obras as $o): ?>
              <tr>
                <td class="font-semibold text-navy-900"><?= e($o['nombre']) ?></td>
                <td><?= e($o['jefe_nombre'] ?? '— Sin asignar') ?></td>
                <td><?= e($o['cliente_nombre'] ?? '— Sin asignar') ?></td>
                <td class="w-36">
                  <div class="flex items-center gap-2">
                    <div class="progress flex-1"><div class="progress-bar" style="width:<?= $o['avance_pct'] ?>%"></div></div>
                    <span class="text-[12px] font-semibold"><?= (int)$o['avance_pct'] ?>%</span>
                  </div>
                </td>
                <td><span class="badge" style="<?= riesgo_badge_style($o['riesgo_ia']) ?>"><?= riesgo_label($o['riesgo_ia']) ?></span></td>
                <td><span class="badge" style="background:<?= estado_obra_color($o['estado']) ?>18;color:<?= estado_obra_color($o['estado']) ?>"><span class="badge-dot"></span> <?= estado_obra_label($o['estado']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===================== USUARIOS Y ROLES ===================== -->
      <section id="usuarios" class="mb-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
          <div>
            <h2 class="font-head font-bold text-[20px] text-navy-900">Usuarios & Roles</h2>
            <p class="text-[12.5px] text-slate-500">Gestiona accesos de todo el equipo, clientes y proveedores</p>
          </div>
          <button data-open-modal="modalUsuario" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Invitar usuario</button>
        </div>

        <div class="table-wrap card">
          <table class="data-table">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Acceso</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
              <?php foreach($usuarios as $u): ?>
              <tr>
                <td>
                  <div class="flex items-center gap-2.5">
                    <img src="<?= e(avatar_url($u)) ?>" class="w-8 h-8 rounded-full avatar">
                    <div><div class="font-semibold text-navy-900 text-[13.5px]"><?= e($u['nombre']) ?></div><div class="text-[12px] text-slate-400"><?= e($u['email']) ?></div></div>
                  </div>
                </td>
                <td><span class="badge" style="background:<?= $u['role_color'] ?>18;color:<?= $u['role_color'] ?>"><?= e($u['role_nombre']) ?></span></td>
                <td class="text-slate-500"><?= $u['google_id'] ? '<i class="fa-brands fa-google mr-1"></i>Google' : '<i class="fa-solid fa-key mr-1"></i>Contraseña' ?></td>
                <td class="text-slate-500"><?= time_ago($u['last_login_at']) ?></td>
                <td><span class="badge <?= estado_badge_class($u['estado']) ?>"><span class="badge-dot"></span> <?= ucfirst($u['estado']) ?></span></td>
                <td>
                  <?php if ($u['id'] != $me['id']): ?>
                  <div class="switch <?= $u['estado']=='activo'?'on':'' ?>" onclick="toggleUsuario(<?= $u['id'] ?>, this)"></div>
                  <?php else: ?>
                  <span class="text-[11px] text-slate-400">(tú)</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===================== PERMISOS ===================== -->
      <section id="permisos" class="mb-10">
        <div class="mb-5">
          <h2 class="font-head font-bold text-[20px] text-navy-900">Matriz de Permisos</h2>
          <p class="text-[12.5px] text-slate-500">Visibilidad restringida por rol — igual que en un banco</p>
        </div>
        <div class="table-wrap card">
          <table class="data-table">
            <thead><tr>
              <th>Módulo</th><th class="text-center">Super Admin</th><th class="text-center">Gerente</th><th class="text-center">Jefe de Obra</th><th class="text-center">Cliente</th><th class="text-center">Proveedor</th><th class="text-center">Contador</th>
            </tr></thead>
            <tbody>
              <?php
                $modulos = [
                  ['Todas las obras',[1,1,0,0,0,0]],
                  ['Costos y márgenes',[1,1,0,0,0,1]],
                  ['Planos técnicos',[1,1,1,0,0,0]],
                  ['Valorizaciones',[1,1,0,1,0,1]],
                  ['Pedidos de materiales',[1,1,1,0,1,0]],
                  ['Configuración global',[1,0,0,0,0,0]],
                  ['Facturación SUNAT',[1,0,0,1,1,1]],
                ];
              ?>
              <?php foreach($modulos as $m): ?>
              <tr>
                <td class="font-medium text-navy-800"><?= $m[0] ?></td>
                <?php foreach($m[1] as $v): ?>
                  <td class="text-center"><?= $v ? '<i class="fa-solid fa-circle-check text-green-500"></i>' : '<i class="fa-solid fa-circle-xmark text-slate-300"></i>' ?></td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===================== INTEGRACIONES ===================== -->
      <section id="integraciones" class="mb-10">
        <div class="mb-5">
          <h2 class="font-head font-bold text-[20px] text-navy-900">Integraciones</h2>
          <p class="text-[12.5px] text-slate-500">Conecta servicios externos gratuitos o de bajo costo</p>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
          <?php
            $intIcons = [
              'whatsapp' => ['fa-brands fa-whatsapp','#22a35a'],
              'sunat' => ['fa-solid fa-file-invoice-dollar','#2563eb'],
              'google_maps' => ['fa-solid fa-map-location-dot','#d91e2c'],
              'gemini_ai' => ['fa-solid fa-brain','#7c6cf6'],
              'google_oauth' => ['fa-brands fa-google','#06b6d4'],
              'gmail_smtp' => ['fa-solid fa-envelope','#f59e0b'],
            ];
          ?>
          <?php foreach($integraciones as $i => $it): $icon = $intIcons[$it['slug']] ?? ['fa-solid fa-plug', '#67758a']; ?>
          <div class="card p-6" data-reveal data-delay="<?= $i*60 ?>">
            <div class="flex items-start justify-between mb-4">
              <div class="stat-icon mb-0" style="background:<?= $icon[1] ?>18;color:<?= $icon[1] ?>"><i class="<?= $icon[0] ?>"></i></div>
              <div class="switch <?= $it['activo']?'on':'' ?>" onclick="toggleIntegracion(<?= $it['id'] ?>, this)"></div>
            </div>
            <h4 class="font-head font-bold text-navy-900 mb-1.5 text-[15px]"><?= e($it['nombre']) ?></h4>
            <p class="text-[13px] text-slate-500 leading-relaxed"><?= e($it['descripcion']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- ===================== APARIENCIA DE LA WEB ===================== -->
      <section id="apariencia" class="mb-10">
        <div class="mb-5">
          <h2 class="font-head font-bold text-[20px] text-navy-900">Apariencia de la Web Pública</h2>
          <p class="text-[12.5px] text-slate-500">Edita el contenido del sitio sin tocar código</p>
        </div>
        <form id="formApariencia" class="grid lg:grid-cols-3 gap-6">
          <?= csrf_field() ?>
          <div class="lg:col-span-2 card p-6 space-y-5">
            <div>
              <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Título principal (Hero)</label>
              <input type="text" name="hero_title" value="<?= e($settings['hero_title'] ?? '') ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
            </div>
            <div>
              <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Nombre de la empresa</label>
              <input type="text" name="empresa_nombre" value="<?= e($settings['empresa_nombre'] ?? '') ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
            </div>
            <div class="flex justify-end pt-2">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Publicar cambios</button>
            </div>
          </div>
          <div class="card p-6">
            <h4 class="font-head font-bold text-navy-900 mb-4 text-[14.5px]">Logo de la empresa</h4>
            <div class="flex items-center gap-4">
              <img src="../assets/img/logo.png" class="w-16 h-16 rounded-xl border border-slate-200 p-1">
              <span class="text-[12.5px] text-slate-500">Reemplázalo copiando el nuevo archivo en <code class="bg-slate-100 px-1 rounded">/assets/img/logo.png</code></span>
            </div>
          </div>
        </form>
      </section>

      <!-- ===================== AUDITORÍA ===================== -->
      <section id="auditoria" class="mb-10">
        <div class="mb-5">
          <h2 class="font-head font-bold text-[20px] text-navy-900">Auditoría / Registro de actividad</h2>
          <p class="text-[12.5px] text-slate-500">Cada acción crítica queda registrada de forma inmutable</p>
        </div>
        <div class="table-wrap card">
          <table class="data-table">
            <thead><tr><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Fecha</th><th>IP</th></tr></thead>
            <tbody>
              <?php foreach($logs as $l): ?>
              <tr>
                <td class="font-medium text-navy-800"><?= e($l['user_nombre'] ?? 'Sistema') ?></td>
                <td><?= e($l['accion']) ?></td>
                <td><span class="badge badge-slate"><?= e($l['modulo']) ?></span></td>
                <td class="text-slate-500"><?= time_ago($l['created_at']) ?></td>
                <td class="text-slate-400 font-mono text-[12px]"><?= e($l['ip'] ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

    </main>
  </div>
</div>

<!-- Modal: Nueva obra -->
<div id="modalObra" class="modal-backdrop">
  <div class="modal-box p-7">
    <form id="formObra">
      <?= csrf_field() ?>
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-head font-bold text-lg text-navy-900">Registrar nueva obra</h3>
        <button type="button" data-close-modal class="btn-icon btn-ghost !w-9 !h-9"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="space-y-4">
        <input type="text" name="nombre" required placeholder="Nombre de la obra" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <input type="text" name="ubicacion" placeholder="Ubicación" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <input type="number" name="monto_contratado" placeholder="Monto contratado (S/)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <select name="jefe_obra_user_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
          <option value="">Asignar Jefe de Obra…</option>
          <?php foreach($jefesDisponibles as $j): ?><option value="<?= $j['id'] ?>"><?= e($j['nombre']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div id="obraError" class="hidden mt-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
      <div class="flex gap-3 mt-7">
        <button type="button" data-close-modal class="btn btn-ghost flex-1 justify-center">Cancelar</button>
        <button type="submit" class="btn btn-primary flex-1 justify-center">Crear obra</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Nuevo usuario -->
<div id="modalUsuario" class="modal-backdrop">
  <div class="modal-box p-7">
    <form id="formUsuario">
      <?= csrf_field() ?>
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-head font-bold text-lg text-navy-900">Invitar usuario</h3>
        <button type="button" data-close-modal class="btn-icon btn-ghost !w-9 !h-9"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="space-y-4">
        <input type="text" name="nombre" required placeholder="Nombre completo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <input type="email" name="email" required placeholder="Correo electrónico" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <select name="role_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
          <?php foreach($rolesList as $r): if ($r['slug']==='superadmin') continue; ?>
            <option value="<?= $r['id'] ?>"><?= e($r['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="usuarioError" class="hidden mt-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
      <div id="usuarioDevPass" class="hidden mt-4 p-3 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 text-[13px]"></div>
      <div class="flex gap-3 mt-7">
        <button type="button" data-close-modal class="btn btn-ghost flex-1 justify-center">Cancelar</button>
        <button type="submit" class="btn btn-primary flex-1 justify-center">Enviar invitación</button>
      </div>
    </form>
  </div>
</div>

<?php include '../partials/modal-material.php'; ?>

<?php include '../partials/scripts.php'; ?>
<script>
  chartDefaults();
  tableSearch('searchObras','tableObras');
  new Chart(document.getElementById('chartActivity'), {
    type:'bar',
    data:{
      labels: <?= json_encode(array_column($reportesPorMes, 'mes')) ?>,
      datasets:[{label:'Reportes de avance', data: <?= json_encode(array_map('intval', array_column($reportesPorMes, 'total'))) ?>, backgroundColor:'#d91e2c', borderRadius:6}]
    },
    options:{scales:{y:{beginAtZero:true}}}
  });
  new Chart(document.getElementById('chartRoles'), {
    type:'doughnut',
    data:{
      labels: <?= json_encode(array_column($usuariosPorRol, 'nombre')) ?>,
      datasets:[{data: <?= json_encode(array_map('intval', array_column($usuariosPorRol, 'total'))) ?>, backgroundColor: <?= json_encode(array_column($usuariosPorRol, 'color_hex')) ?>, borderWidth:0}]
    },
    options:{cutout:'68%'}
  });

  document.getElementById('formObra').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('obraError');
    errBox.classList.add('hidden');
    const res = await fetch('../actions/obras-create.php', { method:'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (!data.ok) { errBox.textContent = data.error; errBox.classList.remove('hidden'); return; }
    MEGA.toast('Obra registrada correctamente','success');
    setTimeout(()=>location.reload(), 700);
  });

  document.getElementById('formUsuario').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('usuarioError');
    const devBox = document.getElementById('usuarioDevPass');
    errBox.classList.add('hidden'); devBox.classList.add('hidden');
    const res = await fetch('../actions/usuarios-create.php', { method:'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (!data.ok) { errBox.textContent = data.error; errBox.classList.remove('hidden'); return; }
    if (data.dev_password) {
      devBox.innerHTML = '<i class="fa-solid fa-flask"></i> Gmail no configurado — contraseña temporal: <b class="font-mono">'+data.dev_password+'</b>';
      devBox.classList.remove('hidden');
      MEGA.toast('Usuario creado. Copia la contraseña temporal.','success');
      setTimeout(()=>location.reload(), 3500);
    } else {
      MEGA.toast('Invitación enviada por correo','success');
      setTimeout(()=>location.reload(), 900);
    }
  });

  async function toggleUsuario(id, el){
    el.classList.toggle('on');
    const fd = new FormData();
    fd.append('csrf_token', '<?= csrf_token() ?>');
    fd.append('user_id', id);
    const res = await fetch('../actions/usuarios-toggle.php', { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { el.classList.toggle('on'); MEGA.toast(data.error || 'No se pudo actualizar','error'); }
    else MEGA.toast('Estado actualizado','success');
  }

  async function toggleIntegracion(id, el){
    el.classList.toggle('on');
    const fd = new FormData();
    fd.append('csrf_token', '<?= csrf_token() ?>');
    fd.append('integracion_id', id);
    const res = await fetch('../actions/integraciones-toggle.php', { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { el.classList.toggle('on'); MEGA.toast(data.error || 'No se pudo actualizar','error'); }
  }

  document.getElementById('formApariencia').addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch('../actions/settings-update.php', { method:'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (data.ok) MEGA.toast('Cambios publicados en el sitio web','success');
    else MEGA.toast(data.error || 'No se pudo guardar','error');
  });
</script>
</body>
</html>
