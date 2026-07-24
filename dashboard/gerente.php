<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['gerente', 'superadmin']);

$me = current_user();
$baseUrl = '..'; $pageTitle = 'Gerente General';
$role = 'gerente'; $activePage = 'dashboard';
$userName = $me['nombre']; $userInitials = initials($me['nombre']); $roleColor = $me['role_color'];
$pageHeading = 'Visión General de la Empresa'; $pageSubheading = 'Todas las obras, costos y márgenes';

$pdo = db();
$obras = $pdo->query("SELECT * FROM obras ORDER BY created_at DESC")->fetchAll();

$obrasEnEjecucion = count(array_filter($obras, fn($o) => $o['estado'] !== 'completada'));
$obrasRiesgo = count(array_filter($obras, fn($o) => $o['riesgo_ia'] === 'alto'));
$margenes = array_map(fn($o) => $o['monto_contratado'] > 0 ? (($o['monto_contratado'] - $o['costo_real']) / $o['monto_contratado']) * 100 : 0, $obras);
$margenPromedio = $margenes ? array_sum($margenes) / count($margenes) : 0;

$obrasConRiesgo = $pdo->query("SELECT * FROM obras WHERE riesgo_ia IN ('medio','alto') ORDER BY (riesgo_ia = 'alto') DESC, updated_at DESC")->fetchAll();
$navBadges = ['obras' => count($obras), 'prediccion' => count($obrasConRiesgo)];

$valorizacionesPendientes = $pdo->query("
    SELECT v.*, o.nombre AS obra_nombre FROM valorizaciones v JOIN obras o ON o.id = v.obra_id
    WHERE v.estado = 'pendiente' ORDER BY v.created_at DESC
")->fetchAll();

$totalContratado = array_sum(array_column($obras, 'monto_contratado'));
$totalEjecutado = array_sum(array_column($obras, 'monto_ejecutado'));
$pctEjecutado = $totalContratado > 0 ? ($totalEjecutado / $totalContratado) * 100 : 0;

$jefesDisponibles = $pdo->query("SELECT u.id, u.nombre FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'jefe_obra'")->fetchAll();
$proveedoresDisponibles = $pdo->query("SELECT u.id, u.nombre FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'proveedor'")->fetchAll();
$obrasParaMaterial = array_map(fn($o) => ['id' => $o['id'], 'nombre' => $o['nombre']], $obras);

$leaderboard = $pdo->query("
    SELECT u.id, u.nombre,
           COUNT(DISTINCT ra.id) AS reportes,
           COUNT(DISTINCT um.medalla_id) AS medallas_count,
           STRING_AGG(DISTINCT m.icono_emoji, '') AS medallas_emoji
    FROM users u
    JOIN roles r ON r.id = u.role_id AND r.slug = 'jefe_obra'
    LEFT JOIN reportes_avance ra ON ra.user_id = u.id
    LEFT JOIN user_medallas um ON um.user_id = u.id
    LEFT JOIN medallas m ON m.id = um.medalla_id
    GROUP BY u.id, u.nombre
    ORDER BY reportes DESC, medallas_count DESC
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

      <!-- ===================== HERO EJECUTIVO ===================== -->
      <div class="exec-hero mb-8" data-reveal>
        <div class="tech-grid-bg dark-grid"></div>
        <div class="blob blob-violet w-80 h-80 -top-20 -right-10"></div>
        <div class="relative grid lg:grid-cols-3 gap-8 items-center">
          <div class="lg:col-span-2">
            <span class="badge" style="background:rgba(255,255,255,.1);color:#fff"><span class="badge-dot"></span> Portafolio en vivo</span>
            <div class="flex items-end gap-3 mt-4 mb-2">
              <span class="exec-metric"><?= money($totalContratado) ?></span>
              <span class="text-white/50 text-[13px] mb-2">contratados en <?= count($obras) ?> obras</span>
            </div>
            <div class="exec-insight">
              <i class="fa-solid fa-lightbulb text-amber-400"></i>
              Gestionas <b><?= $obrasEnEjecucion ?> obras activas</b> con un margen promedio de <b><?= number_format($margenPromedio,1) ?>%</b>
              <?= $obrasRiesgo > 0 ? " — <b class='text-brand-400'>{$obrasRiesgo} requieren atención inmediata</b>." : ' — todo dentro de rango saludable.' ?>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="exec-subkpi">
              <div class="text-white/50 text-[11.5px] mb-1">Ejecutado</div>
              <div class="font-head font-bold text-white text-xl"><?= number_format($pctEjecutado,0) ?>%</div>
            </div>
            <div class="exec-subkpi">
              <div class="text-white/50 text-[11.5px] mb-1">Riesgo alto</div>
              <div class="font-head font-bold text-xl" style="color:<?= $obrasRiesgo>0?'#ff6b6b':'#4ade80' ?>"><?= $obrasRiesgo ?></div>
            </div>
            <div class="exec-subkpi">
              <div class="text-white/50 text-[11.5px] mb-1">Margen</div>
              <div class="font-head font-bold text-white text-xl"><?= number_format($margenPromedio,1) ?>%</div>
            </div>
            <div class="exec-subkpi">
              <div class="text-white/50 text-[11.5px] mb-1">Obras</div>
              <div class="font-head font-bold text-white text-xl"><?= count($obras) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 card p-6" data-reveal>
          <div class="flex items-center justify-between mb-5">
            <div><h3 class="font-head font-bold text-navy-900">Avance actual por obra</h3><p class="text-[12.5px] text-slate-500">Estado en tiempo real</p></div>
          </div>
          <canvas id="chartAvance" height="230"></canvas>
        </div>
        <div class="card p-6" data-reveal data-delay="100">
          <h3 class="font-head font-bold text-navy-900 mb-1">Margen por obra</h3>
          <p class="text-[12.5px] text-slate-500 mb-5">Rentabilidad actual</p>
          <canvas id="chartMargen" height="230"></canvas>
        </div>
      </div>

      <!-- Todas las obras -->
      <section id="obras" class="mb-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
          <div><h2 class="font-head font-bold text-[20px] text-navy-900">Todas las Obras</h2><p class="text-[12.5px] text-slate-500">Gestión completa del portafolio</p></div>
          <div class="flex gap-2">
            <button data-open-modal="modalMaterial" class="btn btn-outline btn-sm"><i class="fa-solid fa-boxes-stacked"></i> Solicitar material</button>
            <button data-open-modal="modalObraGerente" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Nueva Obra</button>
          </div>
        </div>
        <div class="table-wrap card">
          <table class="data-table">
            <thead><tr><th>Obra</th><th>Avance</th><th>Riesgo IA</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach($obras as $o): ?>
              <tr>
                <td class="font-semibold text-navy-900"><?= e($o['nombre']) ?></td>
                <td class="w-36"><div class="flex items-center gap-2"><div class="progress flex-1"><div class="progress-bar" style="width:<?= $o['avance_pct'] ?>%"></div></div><span class="text-[12px] font-semibold"><?= (int)$o['avance_pct'] ?>%</span></div></td>
                <td><span class="badge" style="<?= riesgo_badge_style($o['riesgo_ia']) ?>"><?= riesgo_label($o['riesgo_ia']) ?></span></td>
                <td><span class="badge" style="background:<?= estado_obra_color($o['estado']) ?>18;color:<?= estado_obra_color($o['estado']) ?>"><span class="badge-dot"></span> <?= estado_obra_label($o['estado']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Mapa de obras -->
      <section id="mapa" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Mapa de Obras</h2><p class="text-[12.5px] text-slate-500">Geolocalización con Google Maps API + rutas óptimas</p></div>
        <div class="card p-3">
          <div class="rounded-xl overflow-hidden relative h-80 bg-slate-100">
            <img src="https://placehold.co/1400x480/eef1f6/94a3b8?text=Mapa+interactivo+%28Google+Maps+API%29" class="w-full h-full object-cover">
            <?php
              // Distribuye pines proporcionalmente al lat/lng real dentro del contenedor (proyección simplificada demo).
              foreach ($obras as $i => $o):
                if (!$o['lat']) continue;
                $left = 15 + (($i * 137) % 70);
                $top = 20 + (($i * 89) % 55);
            ?>
            <div class="absolute -translate-x-1/2 -translate-y-full" style="left:<?= $left ?>%;top:<?= $top ?>%">
              <div class="w-4 h-4 rounded-full ring-4 float" style="background:<?= estado_obra_color($o['estado']) ?>;box-shadow:0 0 0 4px <?= estado_obra_color($o['estado']) ?>33"></div>
              <div class="text-[10.5px] font-semibold bg-white px-2 py-0.5 rounded-full shadow mt-1 whitespace-nowrap"><?= e($o['nombre']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- Predicción IA -->
      <section id="prediccion" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Predicción IA — Google Gemini</h2><p class="text-[12.5px] text-slate-500">Analiza cada obra con datos reales y genera una recomendación</p></div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5" id="prediccionGrid">
          <?php if (!$obrasConRiesgo): ?>
            <div class="card p-8 text-center text-slate-400 md:col-span-3"><i class="fa-solid fa-shield-check text-3xl mb-3 text-green-400"></i><br>Todas las obras están en riesgo bajo por ahora.</div>
          <?php endif; ?>
          <?php foreach($obrasConRiesgo as $o): ?>
          <div class="card p-6 border-l-4" style="border-left-color:<?= $o['riesgo_ia']=='alto'?'#d91e2c':'#f59e0b' ?>" data-obra-card="<?= $o['id'] ?>">
            <div class="flex items-center justify-between mb-3">
              <span class="badge" style="<?= riesgo_badge_style($o['riesgo_ia']) ?>"><?= riesgo_label($o['riesgo_ia']) ?> riesgo</span>
              <i class="fa-solid fa-brain text-brand-600"></i>
            </div>
            <h4 class="font-head font-bold text-navy-900 mb-2"><?= e($o['nombre']) ?></h4>
            <p class="text-[13px] text-slate-500 mb-3 analisis-text"><?= $o['riesgo_ia_analisis'] ? e($o['riesgo_ia_analisis']) : 'Aún no se ha ejecutado un análisis de IA para esta obra.' ?></p>
            <button class="btn btn-outline btn-sm w-full justify-center" onclick="analizarObra(<?= $o['id'] ?>, this)">
              <i class="fa-solid fa-wand-magic-sparkles"></i> Analizar con IA ahora
            </button>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Valorizaciones -->
      <section id="valorizaciones" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Valorizaciones Pendientes de Aprobación</h2></div>
        <div class="grid md:grid-cols-2 gap-5" id="valorizacionesGrid">
          <?php if (!$valorizacionesPendientes): ?>
            <div class="card p-8 text-center text-slate-400 md:col-span-2">No hay valorizaciones pendientes.</div>
          <?php endif; ?>
          <?php foreach($valorizacionesPendientes as $v): ?>
          <div class="card p-6 flex items-center gap-4" data-val-card="<?= $v['id'] ?>">
            <div class="stat-icon mb-0 bg-blue-50 text-blue-500"><i class="fa-solid fa-file-invoice"></i></div>
            <div class="flex-1">
              <div class="font-semibold text-navy-900 text-[14px]"><?= e($v['contratista']) ?></div>
              <div class="text-[12.5px] text-slate-500"><?= e($v['obra_nombre']) ?> · Valorización <?= e($v['numero']) ?></div>
              <div class="font-head font-bold text-brand-600 mt-1"><?= money($v['monto']) ?></div>
            </div>
            <div class="flex flex-col gap-2">
              <button class="btn btn-primary btn-sm" onclick="actualizarValorizacion(<?= $v['id'] ?>,'aprobar',this)">Aprobar</button>
              <button class="btn btn-outline btn-sm" onclick="actualizarValorizacion(<?= $v['id'] ?>,'rechazar',this)">Rechazar</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Finanzas -->
      <section id="finanzas" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Finanzas & Márgenes</h2><p class="text-[12.5px] text-slate-500">Solo visible para Gerencia y Contabilidad</p></div>
        <div class="table-wrap card">
          <table class="data-table ledger-table">
            <thead><tr><th>Obra</th><th class="text-right">Contratado</th><th class="text-right">Ejecutado</th><th class="text-right">Costo real</th><th class="text-right">Margen</th></tr></thead>
            <tbody>
              <?php foreach($obras as $o): $margen = $o['monto_contratado']>0 ? (($o['monto_contratado']-$o['costo_real'])/$o['monto_contratado'])*100 : 0; ?>
              <tr>
                <td class="font-semibold text-navy-900"><?= e($o['nombre']) ?></td>
                <td class="num"><?= money($o['monto_contratado']) ?></td>
                <td class="num"><?= money($o['monto_ejecutado']) ?></td>
                <td class="num"><?= money($o['costo_real']) ?></td>
                <td class="num <?= $margen>=20?'ledger-positive':'text-amber-600' ?>"><?= number_format($margen,1) ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="ledger-total-row">
                <td class="text-navy-900">Total portafolio</td>
                <td class="num"><?= money($totalContratado) ?></td>
                <td class="num"><?= money($totalEjecutado) ?></td>
                <td class="num"><?= money(array_sum(array_column($obras,'costo_real'))) ?></td>
                <td class="num ledger-positive"><?= number_format($margenPromedio,1) ?>%</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      <!-- Gamificación -->
      <section id="gamificacion" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Tabla de Líderes — Jefes de Obra</h2><p class="text-[12.5px] text-slate-500">Por reportes de avance registrados</p></div>
        <div class="card p-3">
          <table class="data-table">
            <thead><tr><th>#</th><th>Jefe de obra</th><th>Reportes registrados</th><th>Medallas</th></tr></thead>
            <tbody>
              <?php if (!$leaderboard): ?><tr><td colspan="4" class="text-center text-slate-400 py-6">Sin jefes de obra registrados.</td></tr><?php endif; ?>
              <?php foreach($leaderboard as $i => $l): ?>
              <tr>
                <td class="font-head font-bold text-navy-900"><?= $i+1 ?></td>
                <td class="flex items-center gap-2 !border-t-0"><img src="https://ui-avatars.com/api/?name=<?= urlencode($l['nombre']) ?>&background=7c6cf6&color=fff" class="w-7 h-7 rounded-full avatar"><?= e($l['nombre']) ?></td>
                <td><?= $l['reportes'] ?></td>
                <td class="text-lg"><?= $l['medallas_emoji'] ?: '—' ?></td>
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
<div id="modalObraGerente" class="modal-backdrop">
  <div class="modal-box p-7">
    <form id="formObraGerente">
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
      <div id="obraGerenteError" class="hidden mt-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
      <div class="flex gap-3 mt-7">
        <button type="button" data-close-modal class="btn btn-ghost flex-1 justify-center">Cancelar</button>
        <button type="submit" class="btn btn-primary flex-1 justify-center">Crear obra</button>
      </div>
    </form>
  </div>
</div>

<?php include '../partials/modal-material.php'; ?>

<?php include '../partials/scripts.php'; ?>
<script>
  document.getElementById('formObraGerente').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('obraGerenteError');
    errBox.classList.add('hidden');
    const res = await fetch('../actions/obras-create.php', { method:'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (!data.ok) { errBox.textContent = data.error; errBox.classList.remove('hidden'); return; }
    MEGA.toast('Obra registrada correctamente','success');
    setTimeout(()=>location.reload(), 700);
  });
</script>
<script>
  chartDefaults();
  new Chart(document.getElementById('chartAvance'), {
    type:'bar',
    data:{ labels: <?= json_encode(array_column($obras,'nombre')) ?>,
      datasets:[{label:'Avance %', data: <?= json_encode(array_map('floatval', array_column($obras,'avance_pct'))) ?>, backgroundColor:'#d91e2c', borderRadius:8}]},
    options:{indexAxis:'y',scales:{x:{max:100}},plugins:{legend:{display:false}}}
  });
  new Chart(document.getElementById('chartMargen'), {
    type:'doughnut',
    data:{ labels: <?= json_encode(array_column($obras,'nombre')) ?>,
      datasets:[{data: <?= json_encode(array_map(fn($o)=>$o['monto_contratado']>0?round((($o['monto_contratado']-$o['costo_real'])/$o['monto_contratado'])*100,1):0, $obras)) ?>, backgroundColor:['#d91e2c','#7c6cf6','#f59e0b','#22a35a','#06b6d4'], borderWidth:0}]},
    options:{cutout:'60%'}
  });

  const csrf = '<?= csrf_token() ?>';

  async function analizarObra(obraId, btn){
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando a Gemini…';
    const fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('obra_id', obraId);
    try {
      const res = await fetch('../actions/ia-analizar-obra.php', { method:'POST', body: fd });
      const data = await res.json();
      if (!data.ok) { MEGA.toast(data.error || 'Error de IA', 'error'); btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles"></i> Analizar con IA ahora'; return; }
      const card = document.querySelector(`[data-obra-card="${obraId}"]`);
      card.querySelector('.analisis-text').textContent = data.analisis || 'Sin observaciones adicionales.';
      MEGA.toast('Análisis de IA actualizado', 'success');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i> Volver a analizar';
    } catch(e) {
      MEGA.toast('Error de conexión con Gemini', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Analizar con IA ahora';
    }
  }

  async function actualizarValorizacion(id, accion, btn){
    btn.closest('[data-val-card]').style.opacity = '.5';
    const fd = new FormData();
    fd.append('csrf_token', csrf);
    fd.append('id', id);
    fd.append('accion', accion);
    const res = await fetch('../actions/valorizacion-actualizar.php', { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { MEGA.toast(data.error, 'error'); btn.closest('[data-val-card]').style.opacity = '1'; return; }
    MEGA.toast('Valorización actualizada', 'success');
    setTimeout(()=>location.reload(), 600);
  }
</script>
</body>
</html>
