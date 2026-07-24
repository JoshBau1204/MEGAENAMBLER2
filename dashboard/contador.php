<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['contador', 'superadmin']);

$me = current_user();
$baseUrl = '..'; $pageTitle = 'Módulo Contador';
$role = 'contador'; $activePage = 'dashboard';
$userName = $me['nombre']; $userInitials = initials($me['nombre']); $roleColor = $me['role_color'];
$pageHeading = 'Módulo Financiero'; $pageSubheading = 'Control contable y facturación electrónica';

$pdo = db();
$valorizaciones = $pdo->query("
    SELECT v.*, o.nombre AS obra_nombre FROM valorizaciones v JOIN obras o ON o.id = v.obra_id
    ORDER BY v.created_at DESC
")->fetchAll();

$comprobantes = $pdo->query("
    SELECT c.*, cu.nombre AS cliente_nombre, pu.nombre AS proveedor_nombre
    FROM comprobantes c
    LEFT JOIN users cu ON cu.id = c.cliente_user_id
    LEFT JOIN users pu ON pu.id = c.proveedor_user_id
    ORDER BY c.created_at DESC
")->fetchAll();

$totalValorizaciones = count($valorizaciones);
$totalComprobantes = count($comprobantes);
$montoComprobantes = array_sum(array_column($comprobantes, 'monto'));
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

      <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="stat-card" data-reveal>
          <div class="stat-icon" style="background:#2563eb18;color:#2563eb"><i class="fa-solid fa-file-invoice"></i></div>
          <div class="stat-value"><span data-counter="<?= $totalValorizaciones ?>">0</span></div>
          <div class="stat-label">Valorizaciones registradas</div>
        </div>
        <div class="stat-card" data-reveal data-delay="70">
          <div class="stat-icon" style="background:#22a35a18;color:#22a35a"><i class="fa-solid fa-receipt"></i></div>
          <div class="stat-value"><span data-counter="<?= $totalComprobantes ?>">0</span></div>
          <div class="stat-label">Comprobantes emitidos</div>
        </div>
        <div class="stat-card" data-reveal data-delay="140">
          <div class="stat-icon" style="background:#f59e0b18;color:#f59e0b"><i class="fa-solid fa-sack-dollar"></i></div>
          <div class="stat-value"><?= money($montoComprobantes) ?></div>
          <div class="stat-label">Monto total facturado</div>
        </div>
        <div class="stat-card" data-reveal data-delay="210">
          <div class="stat-icon" style="background:#7c6cf618;color:#7c6cf6"><i class="fa-solid fa-vault"></i></div>
          <div class="stat-value">S/ 4,850</div>
          <div class="stat-label">Caja chica disponible</div>
        </div>
      </div>

      <!-- Valorizaciones -->
      <section id="valorizaciones" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Valorizaciones</h2><p class="text-[12.5px] text-slate-500">% avance × monto contratado</p></div>
        <div class="table-wrap card">
          <table class="data-table ledger-table">
            <thead><tr><th>Contratista</th><th>Obra</th><th class="text-right">Monto</th><th>Estado</th></tr></thead>
            <tbody>
              <?php if (!$valorizaciones): ?><tr><td colspan="4" class="text-center text-slate-400 py-8">Sin valorizaciones registradas.</td></tr><?php endif; ?>
              <?php foreach($valorizaciones as $v): ?>
              <tr>
                <td class="font-semibold text-navy-900"><?= e($v['contratista']) ?></td>
                <td><?= e($v['obra_nombre']) ?></td>
                <td class="num"><?= money($v['monto']) ?></td>
                <td>
                  <span class="badge <?= estado_badge_class($v['estado']) ?>"><?= ucfirst($v['estado']) ?></span>
                  <?php if ($v['estado'] === 'aprobada'): ?>
                    <button class="btn btn-outline btn-sm ml-2" onclick="marcarPagada(<?= $v['id'] ?>, this)">Marcar pagada</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if ($valorizaciones): ?>
            <tfoot><tr class="ledger-total-row"><td colspan="2">Total valorizado</td><td class="num"><?= money(array_sum(array_column($valorizaciones,'monto'))) ?></td><td></td></tr></tfoot>
            <?php endif; ?>
          </table>
        </div>
      </section>

      <!-- Facturación SUNAT -->
      <section id="facturacion" class="mb-10">
        <div class="flex items-center justify-between mb-5">
          <h2 class="font-head font-bold text-[20px] text-navy-900">Facturación Electrónica — SUNAT</h2>
        </div>
        <div class="grid lg:grid-cols-3 gap-5">
          <div class="card p-6 lg:col-span-2">
            <div class="table-wrap">
              <table class="data-table ledger-table">
                <thead><tr><th>Comprobante</th><th>Cliente/Proveedor</th><th class="text-right">Monto</th><th>Estado SUNAT</th></tr></thead>
                <tbody>
                  <?php foreach($comprobantes as $c): ?>
                  <tr>
                    <td class="font-semibold text-navy-900"><?= e($c['serie_numero']) ?></td>
                    <td><?= e($c['cliente_nombre'] ?? $c['proveedor_nombre'] ?? '—') ?></td>
                    <td class="num"><?= money($c['monto']) ?></td>
                    <td><span class="badge <?= estado_badge_class($c['estado_sunat']) ?>"><i class="fa-solid fa-circle-check"></i> <?= ucfirst($c['estado_sunat']) ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <?php if ($comprobantes): ?>
                <tfoot><tr class="ledger-total-row"><td colspan="2">Total facturado</td><td class="num"><?= money($montoComprobantes) ?></td><td></td></tr></tfoot>
                <?php endif; ?>
              </table>
            </div>
          </div>
          <div class="card p-6 text-center flex flex-col justify-center items-center" style="background:var(--gradient-dark)">
            <i class="fa-solid fa-plug-circle-check text-4xl text-green-400 mb-3"></i>
            <div class="text-white font-head font-bold mb-1">Conexión SUNAT</div>
            <div class="text-slate-300 text-[12.5px]">Actívala desde el panel de Super Admin → Integraciones</div>
          </div>
        </div>
      </section>

      <!-- Caja chica -->
      <section id="caja" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Caja Chica</h2></div>
        <div class="card p-8 text-center text-slate-400">
          <i class="fa-solid fa-vault text-3xl mb-3"></i><br>
          Módulo de caja chica listo para activarse — próxima iteración.
        </div>
      </section>

    </main>
  </div>
</div>

<?php include '../partials/scripts.php'; ?>
<script>
  const csrf = '<?= csrf_token() ?>';
  async function marcarPagada(id, btn){
    btn.disabled = true;
    const fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('id', id);
    fd.append('accion', 'pagar');
    const res = await fetch('../actions/valorizacion-actualizar.php', { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { MEGA.toast(data.error, 'error'); btn.disabled = false; return; }
    MEGA.toast('Valorización marcada como pagada', 'success');
    setTimeout(()=>location.reload(), 600);
  }
</script>
</body>
</html>
