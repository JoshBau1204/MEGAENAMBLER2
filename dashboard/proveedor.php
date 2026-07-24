<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['proveedor']);

$me = current_user();
$baseUrl = '..'; $pageTitle = 'Portal Proveedor';
$role = 'proveedor'; $activePage = 'dashboard';
$userName = $me['nombre']; $userInitials = initials($me['nombre']); $roleColor = $me['role_color'];
$pageHeading = 'Panel de Proveedor'; $pageSubheading = 'Pedidos y entregas asignadas';

$pdo = db();
$stmt = $pdo->prepare("
    SELECT mp.*, o.nombre AS obra_nombre FROM materiales_pedidos mp
    JOIN obras o ON o.id = mp.obra_id
    WHERE mp.proveedor_user_id = ? ORDER BY mp.created_at DESC
");
$stmt->execute([$me['id']]);
$pedidos = $stmt->fetchAll();

$pedidosActivos = count(array_filter($pedidos, fn($p) => $p['estado'] !== 'entregado'));
$navBadges = ['pedidos' => $pedidosActivos];
$entregados = count(array_filter($pedidos, fn($p) => $p['estado'] === 'entregado'));

$stmt = $pdo->prepare('SELECT * FROM comprobantes WHERE proveedor_user_id = ? ORDER BY created_at DESC');
$stmt->execute([$me['id']]);
$comprobantes = $stmt->fetchAll();
$facturadoMes = array_sum(array_column($comprobantes, 'monto'));
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
          <div class="stat-icon" style="background:#f59e0b18;color:#f59e0b"><i class="fa-solid fa-clipboard-list"></i></div>
          <div class="stat-value"><span data-counter="<?= $pedidosActivos ?>">0</span></div>
          <div class="stat-label">Pedidos activos</div>
        </div>
        <div class="stat-card" data-reveal data-delay="70">
          <div class="stat-icon" style="background:#06b6d418;color:#06b6d4"><i class="fa-solid fa-truck-fast"></i></div>
          <div class="stat-value"><span data-counter="<?= $entregados ?>">0</span></div>
          <div class="stat-label">Entregas completadas</div>
        </div>
        <div class="stat-card" data-reveal data-delay="140">
          <div class="stat-icon" style="background:#22a35a18;color:#22a35a"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-value"><span data-counter="<?= count($pedidos)?round($entregados/count($pedidos)*100):0 ?>" data-suffix="%">0</span></div>
          <div class="stat-label">Cumplimiento de entregas</div>
        </div>
        <div class="stat-card" data-reveal data-delay="210">
          <div class="stat-icon" style="background:#7c6cf618;color:#7c6cf6"><i class="fa-solid fa-sack-dollar"></i></div>
          <div class="stat-value"><?= money($facturadoMes) ?></div>
          <div class="stat-label">Facturado total</div>
        </div>
      </div>

      <!-- Pedidos: tablero kanban (arrastra las tarjetas entre columnas) -->
      <section id="pedidos" class="mb-10">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h2 class="font-head font-bold text-[20px] text-navy-900">Pedidos Asignados</h2>
            <p class="text-[12.5px] text-slate-500">Arrastra una tarjeta a otra columna para actualizar su estado</p>
          </div>
        </div>
        <?php
          $columnas = ['pendiente' => ['Pendiente','#f59e0b'], 'preparacion' => ['En preparación','#2563eb'], 'camino' => ['En camino','#06b6d4'], 'entregado' => ['Entregado','#22a35a']];
          $pedidosPorEstado = ['pendiente'=>[],'preparacion'=>[],'camino'=>[],'entregado'=>[]];
          foreach ($pedidos as $p) { $pedidosPorEstado[$p['estado']][] = $p; }
        ?>
        <div class="kanban-board">
          <?php foreach($columnas as $key => [$label, $color]): ?>
          <div class="kanban-col" data-estado="<?= $key ?>" ondragover="allowDrop(event)" ondrop="dropCard(event)">
            <div class="kanban-col-head">
              <span class="font-head font-bold text-[12.5px] text-navy-800"><i class="fa-solid fa-circle text-[7px] mr-1.5" style="color:<?= $color ?>"></i><?= $label ?></span>
              <span class="badge badge-slate"><?= count($pedidosPorEstado[$key]) ?></span>
            </div>
            <?php if (!$pedidosPorEstado[$key]): ?>
              <div class="text-center text-slate-400 text-[12px] py-6 border-2 border-dashed border-slate-200 rounded-xl">Sin pedidos aquí</div>
            <?php endif; ?>
            <?php foreach($pedidosPorEstado[$key] as $p): ?>
            <div class="kanban-card" draggable="true" data-id="<?= $p['id'] ?>" ondragstart="dragCard(event)">
              <div class="font-semibold text-navy-900 text-[13px] mb-1"><?= e($p['material']) ?></div>
              <div class="text-[11.5px] text-slate-500 mb-2"><?= e($p['obra_nombre']) ?></div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-400"><?= e($p['cantidad']) ?></span>
                <?php if ($p['eta']): ?><span class="badge badge-slate !text-[10px]"><?= e($p['eta']) ?></span><?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Facturación -->
      <section id="facturacion" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Facturación</h2></div>
        <div class="table-wrap card">
          <table class="data-table">
            <thead><tr><th>Comprobante</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>
              <?php if (!$comprobantes): ?><tr><td colspan="4" class="text-center text-slate-400 py-8">Sin comprobantes registrados.</td></tr><?php endif; ?>
              <?php foreach($comprobantes as $c): ?>
              <tr>
                <td class="font-semibold text-navy-900"><?= e($c['serie_numero']) ?></td>
                <td><?= money($c['monto']) ?></td>
                <td><span class="badge <?= estado_badge_class($c['estado_sunat']) ?>"><?= ucfirst($c['estado_sunat']) ?></span></td>
                <td class="text-slate-500"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

    </main>
  </div>
</div>

<?php include '../partials/scripts.php'; ?>
<script>
  const csrf = '<?= csrf_token() ?>';
  const estadoLabels = { pendiente:'Pendiente', preparacion:'En preparación', camino:'En camino', entregado:'Entregado' };

  function allowDrop(e){ e.preventDefault(); }
  function dragCard(e){ e.dataTransfer.setData('text/plain', e.target.dataset.id); e.target.style.opacity = '.4'; }

  document.addEventListener('dragend', (e) => { if (e.target.classList.contains('kanban-card')) e.target.style.opacity = '1'; });

  async function dropCard(e){
    e.preventDefault();
    const id = e.dataTransfer.getData('text/plain');
    const card = document.querySelector(`.kanban-card[data-id="${id}"]`);
    const col = e.currentTarget;
    const nuevoEstado = col.dataset.estado;
    if (!card || card.closest('.kanban-col') === col) return;

    const oldCol = card.closest('.kanban-col');
    col.appendChild(card);
    updateColCounts();

    const fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('id', id);
    fd.append('estado', nuevoEstado);
    const res = await fetch('../actions/pedido-actualizar-estado.php', { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) {
      oldCol.appendChild(card);
      updateColCounts();
      MEGA.toast(data.error || 'No se pudo mover el pedido', 'error');
      return;
    }
    MEGA.toast(`Movido a "${estadoLabels[nuevoEstado]}"`, 'success');
  }

  function updateColCounts(){
    document.querySelectorAll('.kanban-col').forEach(col => {
      const count = col.querySelectorAll('.kanban-card').length;
      col.querySelector('.badge').textContent = count;
    });
  }
</script>
</body>
</html>
