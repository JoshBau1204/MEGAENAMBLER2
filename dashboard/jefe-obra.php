<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role(['jefe_obra']);

$me = current_user();
$baseUrl = '..'; $pageTitle = 'Jefe de Obra';
$role = 'jefe-obra'; $activePage = 'dashboard';
$userName = $me['nombre']; $userInitials = initials($me['nombre']); $roleColor = $me['role_color'];

$pdo = db();
$misObras = $pdo->prepare('SELECT * FROM obras WHERE jefe_obra_user_id = ? ORDER BY created_at DESC');
$misObras->execute([$me['id']]);
$misObras = $misObras->fetchAll();

$obraId = isset($_GET['obra']) ? (int)$_GET['obra'] : ($misObras[0]['id'] ?? null);
$obra = null;
foreach ($misObras as $o) if ($o['id'] == $obraId) $obra = $o;
if (!$obra && $misObras) $obra = $misObras[0];

$pageHeading = 'Mis Obras';
$pageSubheading = $obra ? e($obra['nombre']) : 'Aún no tienes obras asignadas';

$partidas = [];
$materiales = [];
$medallas = [];
if ($obra) {
    $stmt = $pdo->prepare('SELECT * FROM partidas WHERE obra_id = ? ORDER BY orden');
    $stmt->execute([$obra['id']]);
    $partidas = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM materiales_pedidos WHERE obra_id = ? ORDER BY created_at DESC');
    $stmt->execute([$obra['id']]);
    $materiales = $stmt->fetchAll();
}

$stmt = $pdo->prepare("
    SELECT m.* FROM user_medallas um JOIN medallas m ON m.id = um.medalla_id
    WHERE um.user_id = ? ORDER BY um.obtenida_at DESC
");
$stmt->execute([$me['id']]);
$medallasObtenidas = $stmt->fetchAll();
$todasMedallas = $pdo->query('SELECT * FROM medallas ORDER BY id')->fetchAll();
$obtenidasSlugs = array_column($medallasObtenidas, 'slug');

$obrasParaMaterial = $obra ? [['id' => $obra['id'], 'nombre' => $obra['nombre']]] : [];
$obraFijaId = $obra['id'] ?? null;
$proveedoresDisponibles = $pdo->query("SELECT u.id, u.nombre FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'proveedor'")->fetchAll();
$navBadges = ['logros' => count($medallasObtenidas)];
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

      <?php if (!$obra): ?>
        <div class="card p-10 text-center">
          <i class="fa-solid fa-helmet-safety text-4xl text-slate-300 mb-4"></i>
          <h2 class="font-head font-bold text-navy-900 mb-2">Aún no tienes obras asignadas</h2>
          <p class="text-slate-500 text-sm">Pídele al Super Admin o al Gerente que te asigne una obra desde su panel.</p>
        </div>
      <?php else: ?>

      <?php if (count($misObras) > 1): ?>
      <div class="flex gap-2 mb-6 flex-wrap">
        <?php foreach($misObras as $o): ?>
          <a href="?obra=<?= $o['id'] ?>" class="tab-pill <?= $o['id']==$obra['id']?'tab-active':'' ?>"><?= e($o['nombre']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Resumen obra actual -->
      <div class="card p-6 mb-8 flex flex-wrap items-center gap-6" data-reveal>
        <img src="https://picsum.photos/seed/obra<?= $obra['id'] ?>/140/140" class="w-24 h-24 rounded-2xl object-cover">
        <div class="flex-1 min-w-[220px]">
          <div class="flex items-center gap-2 mb-1">
            <h2 class="font-head font-bold text-xl text-navy-900"><?= e($obra['nombre']) ?></h2>
            <span class="badge" style="background:<?= estado_obra_color($obra['estado']) ?>18;color:<?= estado_obra_color($obra['estado']) ?>"><span class="badge-dot"></span> <?= estado_obra_label($obra['estado']) ?></span>
          </div>
          <p class="text-[13px] text-slate-500 mb-3"><i class="fa-solid fa-location-dot mr-1"></i> <?= e($obra['ubicacion'] ?? 'Sin ubicación') ?></p>
          <div class="flex items-center gap-3 max-w-md">
            <div class="progress flex-1"><div class="progress-bar" style="width:<?= $obra['avance_pct'] ?>%"></div></div>
            <span class="font-head font-bold text-navy-900 text-sm"><?= (int)$obra['avance_pct'] ?>%</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button data-open-modal="modalReportar" class="btn btn-primary"><i class="fa-solid fa-microphone"></i> Reportar avance</button>
        </div>
      </div>

      <!-- Acciones de campo (grandes, para uso con el celular en obra) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10" data-reveal>
        <button onclick="startVoice(); document.getElementById('reportar').scrollIntoView({behavior:'smooth'})" class="field-action-btn" style="background:var(--gradient-brand)">
          <span class="field-icon-lg"><i class="fa-solid fa-microphone"></i></span> Reportar por voz
        </button>
        <a href="#ar" class="field-action-btn" style="background:linear-gradient(135deg,#06b6d4,#7c6cf6)">
          <span class="field-icon-lg"><i class="fa-solid fa-camera"></i></span> Escanear con AR
        </a>
        <a href="#qr" class="field-action-btn" style="background:linear-gradient(135deg,#0b1220,#2a3854)">
          <span class="field-icon-lg"><i class="fa-solid fa-qrcode"></i></span> Escanear QR
        </a>
        <a href="#materiales" class="field-action-btn" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
          <span class="field-icon-lg"><i class="fa-solid fa-boxes-stacked"></i></span> Ver materiales
        </a>
      </div>

      <!-- Reportar avance por voz -->
      <section id="reportar" class="mb-10">
        <div class="grid lg:grid-cols-2 gap-6">
          <div class="card p-7 text-center">
            <div class="eyebrow mx-auto mb-4"><span class="dot"></span> COMANDOS DE VOZ</div>
            <h3 class="font-head font-bold text-navy-900 text-lg mb-2" id="voiceTranscript">"Oye sistema, reporta el avance de una partida"</h3>
            <p class="text-[13.5px] text-slate-500 mb-7">Web Speech API — totalmente gratis, sin apps adicionales</p>
            <button onclick="startVoice()" class="w-24 h-24 rounded-full mx-auto flex items-center justify-center text-white text-3xl relative" style="background:var(--gradient-brand)" id="micBtn">
              <i class="fa-solid fa-microphone"></i>
              <span class="absolute inset-0 rounded-full border-4 border-brand-400 animate-ping opacity-30"></span>
            </button>
            <p id="voiceStatus" class="text-[13px] text-slate-400 mt-5">Toca para hablar (usa Chrome)</p>
          </div>
          <div class="card p-7">
            <h4 class="font-head font-bold text-navy-900 mb-4">Registrar avance manual</h4>
            <form id="formAvanceManual">
              <?= csrf_field() ?>
              <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
              <input type="hidden" name="origen" value="manual">
              <div class="space-y-4">
                <select name="partida_id" id="partidaSelectManual" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
                  <?php foreach($partidas as $p): ?>
                    <option value="<?= $p['id'] ?>" data-avance="<?= $p['avance_pct'] ?>">Partida: <?= e($p['nombre']) ?> (<?= (int)$p['avance_pct'] ?>%)</option>
                  <?php endforeach; ?>
                </select>
                <div>
                  <label class="text-[13px] font-semibold text-navy-700 mb-2 block">% de avance reportado: <span id="rangeVal" class="text-brand-600 font-bold">40</span>%</label>
                  <input type="range" name="porcentaje" id="rangeManual" min="0" max="100" value="40" class="w-full accent-brand-600" oninput="document.getElementById('rangeVal').textContent=this.value">
                </div>
                <textarea name="comentario" placeholder="Comentario (opcional)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500" rows="2"></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-full justify-center mt-4">Enviar reporte</button>
            </form>
          </div>
        </div>
      </section>

      <!-- AR: Plano vs Real -->
      <section id="ar" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Comparar Plano vs Real (Realidad Aumentada)</h2><p class="text-[12.5px] text-slate-500">Apunta la cámara a la columna recién vaciada</p></div>
        <div class="grid lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 holo-frame">
            <img src="https://picsum.photos/seed/holo<?= $obra['id'] ?>/900/460" class="w-full h-[380px] object-cover">
            <div class="holo-corner top-4 left-4 border-t-2 border-l-2 w-8 h-8"></div>
            <div class="holo-corner top-4 right-4 border-t-2 border-r-2 w-8 h-8"></div>
            <div class="holo-corner bottom-4 left-4 border-b-2 border-l-2 w-8 h-8"></div>
            <div class="holo-corner bottom-4 right-4 border-b-2 border-r-2 w-8 h-8"></div>
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="border-2 border-cyan-400 rounded-lg w-44 h-60 relative bg-cyan-400/5">
                <span class="absolute -top-8 left-0 text-[11px] text-cyan-300 font-mono bg-navy-900/85 px-2.5 py-1 rounded">COLUMNA C-14</span>
                <span class="absolute -bottom-8 left-0 text-[11px] text-green-300 font-mono bg-navy-900/85 px-2.5 py-1 rounded"><i class="fa-solid fa-check"></i> Desviación: 0.8cm (OK)</span>
              </div>
            </div>
          </div>
          <div class="card p-6">
            <h4 class="font-head font-bold text-navy-900 mb-4">Resultado del escaneo</h4>
            <div class="space-y-3">
              <div class="flex justify-between text-[13.5px]"><span class="text-slate-500">Elemento</span><span class="font-semibold">Columna C-14</span></div>
              <div class="flex justify-between text-[13.5px]"><span class="text-slate-500">Desviación</span><span class="font-semibold text-green-600">0.8 cm</span></div>
              <div class="flex justify-between text-[13.5px]"><span class="text-slate-500">Tolerancia</span><span class="font-semibold">± 2.0 cm</span></div>
              <div class="flex justify-between text-[13.5px]"><span class="text-slate-500">Estado</span><span class="badge badge-green">Dentro de norma</span></div>
            </div>
            <button class="btn btn-outline w-full justify-center mt-6"><i class="fa-solid fa-camera-rotate"></i> Escanear otro elemento</button>
          </div>
        </div>
      </section>

      <!-- Cronograma -->
      <section id="cronograma" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Cronograma de Obra</h2></div>
        <div class="card p-6 space-y-5">
          <?php foreach($partidas as $p): ?>
          <div>
            <div class="flex justify-between text-[13.5px] mb-1.5"><span class="font-medium text-navy-700"><?= e($p['nombre']) ?></span><span class="font-semibold"><?= (int)$p['avance_pct'] ?>%</span></div>
            <div class="progress"><div class="progress-bar <?= $p['avance_pct']>=100?'success':'' ?>" style="width:<?= $p['avance_pct'] ?>%"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Materiales -->
      <section id="materiales" class="mb-10">
        <div class="flex items-center justify-between mb-5">
          <h2 class="font-head font-bold text-[20px] text-navy-900">Materiales & Pedidos</h2>
          <button data-open-modal="modalMaterial" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Solicitar material</button>
        </div>
        <div class="table-wrap card">
          <table class="data-table">
            <thead><tr><th>Material</th><th>Cantidad</th><th>Estado</th><th>ETA</th></tr></thead>
            <tbody>
              <?php if (!$materiales): ?><tr><td colspan="4" class="text-center text-slate-400 py-6">Sin pedidos registrados.</td></tr><?php endif; ?>
              <?php foreach($materiales as $m): ?>
              <tr><td class="font-semibold text-navy-900"><?= e($m['material']) ?></td><td><?= e($m['cantidad']) ?></td>
                <td><span class="badge <?= estado_badge_class($m['estado']) ?>"><?= ucfirst($m['estado']) ?></span></td>
                <td class="text-slate-500"><?= e($m['eta'] ?? '—') ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Logros -->
      <section id="logros" class="mb-10">
        <div class="mb-5"><h2 class="font-head font-bold text-[20px] text-navy-900">Mis Logros</h2><p class="text-[12.5px] text-slate-500">Gamificación — medallas digitales</p></div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
          <?php foreach($todasMedallas as $m): $obtenida = in_array($m['slug'], $obtenidasSlugs); ?>
          <div class="card p-6 text-center <?= $obtenida?'':'opacity-40 grayscale' ?>">
            <div class="text-4xl mb-3"><?= $m['icono_emoji'] ?></div>
            <div class="font-semibold text-navy-900 text-[13px] mb-1"><?= e($m['nombre']) ?></div>
            <span class="badge <?= $obtenida?'badge-green':'badge-slate' ?>"><?= $obtenida?'Obtenida':'Bloqueada' ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <?php endif; ?>
    </main>
  </div>
</div>

<?php if ($obra): ?>
<!-- Modal reportar -->
<div id="modalReportar" class="modal-backdrop">
  <div class="modal-box p-7">
    <form id="formReportarModal">
      <?= csrf_field() ?>
      <input type="hidden" name="obra_id" value="<?= $obra['id'] ?>">
      <input type="hidden" name="origen" value="manual">
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-head font-bold text-lg text-navy-900">Reportar avance rápido</h3>
        <button type="button" data-close-modal class="btn-icon btn-ghost !w-9 !h-9"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="space-y-4">
        <select name="partida_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
          <?php foreach($partidas as $p): ?><option value="<?= $p['id'] ?>">Partida: <?= e($p['nombre']) ?></option><?php endforeach; ?>
        </select>
        <input type="number" name="porcentaje" min="0" max="100" placeholder="% de avance" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <textarea name="comentario" placeholder="Comentario (opcional)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500" rows="3"></textarea>
      </div>
      <div id="modalReportarError" class="hidden mt-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
      <div class="flex gap-3 mt-7">
        <button type="button" data-close-modal class="btn btn-ghost flex-1 justify-center">Cancelar</button>
        <button type="submit" class="btn btn-primary flex-1 justify-center">Enviar</button>
      </div>
    </form>
  </div>
</div>

<?php include '../partials/modal-material.php'; ?>
<?php endif; ?>

<?php include '../partials/scripts.php'; ?>
<script>
  async function submitAvance(formEl, errBoxId){
    const errBox = document.getElementById(errBoxId);
    if (errBox) errBox.classList.add('hidden');
    const res = await fetch('../actions/reportar-avance.php', { method:'POST', body: new FormData(formEl) });
    const data = await res.json();
    if (!data.ok) {
      if (errBox) { errBox.textContent = data.error; errBox.classList.remove('hidden'); }
      else MEGA.toast(data.error, 'error');
      return false;
    }
    MEGA.toast('Avance reportado. Notificando a gerencia y cliente.', 'success');
    setTimeout(()=>location.reload(), 900);
    return true;
  }

  const formManual = document.getElementById('formAvanceManual');
  if (formManual) formManual.addEventListener('submit', (e)=>{ e.preventDefault(); submitAvance(e.target); });

  const formModal = document.getElementById('formReportarModal');
  if (formModal) formModal.addEventListener('submit', (e)=>{ e.preventDefault(); submitAvance(e.target, 'modalReportarError'); });

  // Comandos de voz (Web Speech API)
  function startVoice(){
    const statusEl = document.getElementById('voiceStatus');
    const transcriptEl = document.getElementById('voiceTranscript');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      MEGA.toast('Tu navegador no soporta comandos de voz. Prueba con Chrome.', 'warning');
      return;
    }
    const recognition = new SpeechRecognition();
    recognition.lang = 'es-PE';
    recognition.interimResults = false;
    statusEl.textContent = '🎙️ Escuchando…';
    recognition.start();

    recognition.onresult = (event) => {
      const text = event.results[0][0].transcript;
      transcriptEl.textContent = '"' + text + '"';
      const match = text.match(/(\d{1,3})\s*(por ciento|%)?/i);
      if (match) {
        const pct = Math.min(100, parseInt(match[1]));
        document.getElementById('rangeManual').value = pct;
        document.getElementById('rangeVal').textContent = pct;
        statusEl.textContent = `Detectado: ${pct}% — revisa la partida y envía el reporte`;
        MEGA.toast(`Reportado ${pct}% por voz. Confirma la partida y envía.`, 'success');
      } else {
        statusEl.textContent = 'No se detectó un porcentaje. Intenta de nuevo.';
      }
    };
    recognition.onerror = () => { statusEl.textContent = 'No se pudo escuchar. Intenta de nuevo.'; };
  }
</script>
</body>
</html>
