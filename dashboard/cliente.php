<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notificaciones.php';
require_role(['cliente']);

$me = current_user();
$baseUrl = '..'; $pageTitle = 'Mi Obra';

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM obras WHERE cliente_user_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$me['id']]);
$obra = $stmt->fetch();

$partidas = [];
$timeline = [];
$valorizaciones = [];
$comprobantes = [];
$chatHistorial = [];

if ($obra) {
    $stmt = $pdo->prepare('SELECT * FROM partidas WHERE obra_id = ? ORDER BY orden');
    $stmt->execute([$obra['id']]);
    $partidas = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT ra.*, p.nombre AS partida_nombre FROM reportes_avance ra
        LEFT JOIN partidas p ON p.id = ra.partida_id
        WHERE ra.obra_id = ? ORDER BY ra.created_at DESC LIMIT 12
    ");
    $stmt->execute([$obra['id']]);
    $timeline = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM valorizaciones WHERE obra_id = ? ORDER BY created_at DESC');
    $stmt->execute([$obra['id']]);
    $valorizaciones = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM comprobantes WHERE cliente_user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$me['id']]);
    $comprobantes = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM chat_mensajes WHERE obra_id = ? ORDER BY created_at ASC LIMIT 30');
    $stmt->execute([$obra['id']]);
    $chatHistorial = $stmt->fetchAll();
}

$notifs = obtener_notificaciones($me['id']);
$unread = contar_no_leidas($me['id']);

$partidaIcons = [
    'Cimentación' => 'fa-layer-group',
    'Estructura' => 'fa-building',
    'Instalaciones' => 'fa-bolt',
    'Muros y tabiquería' => 'fa-border-all',
    'Acabados' => 'fa-paint-roller',
];

// Determina el nodo "actual" del recorrido: la primera partida que no llegó a 100%.
$currentIdx = null;
foreach ($partidas as $i => $p) {
    if ((float)$p['avance_pct'] < 100) { $currentIdx = $i; break; }
}
?>
<!doctype html>
<html lang="es">
<head>
<?php include '../partials/head.php'; ?>
</head>
<body class="font-body text-navy-800 client-shell">

<?php if (!$obra): ?>
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="card p-10 text-center max-w-md">
      <i class="fa-solid fa-house-circle-exclamation text-4xl text-slate-300 mb-4"></i>
      <h2 class="font-head font-bold text-navy-900 mb-2">Aún no tienes una obra vinculada</h2>
      <p class="text-slate-500 text-sm mb-6">Cuando la constructora asocie tu cuenta a una obra, la verás aquí automáticamente.</p>
      <a href="../actions/logout.php" class="btn btn-outline">Cerrar sesión</a>
    </div>
  </div>
<?php else: ?>

<!-- ===================== NAV PROPIA DEL CLIENTE ===================== -->
<nav class="client-nav">
  <div class="max-w-5xl mx-auto px-5 py-3.5 flex items-center gap-4">
    <a href="cliente.php" class="flex items-center gap-2.5 flex-shrink-0">
      <img src="../assets/img/logo.png" class="w-9 h-9 rounded-lg">
      <span class="font-head font-bold text-navy-900 text-[15px] hidden sm:block">Mi Obra</span>
    </a>

    <div class="hidden md:flex items-center gap-1 mx-auto">
      <a href="#inicio" class="client-nav-pill active"><i class="fa-solid fa-house"></i> Inicio</a>
      <a href="#timeline" class="client-nav-pill"><i class="fa-solid fa-images"></i> Fotos</a>
      <a href="#pagos" class="client-nav-pill"><i class="fa-solid fa-money-check-dollar"></i> Pagos</a>
      <a href="#documentos" class="client-nav-pill"><i class="fa-solid fa-file-lines"></i> Documentos</a>
    </div>

    <div class="ml-auto flex items-center gap-2">
      <div class="relative">
        <button data-dropdown-trigger="notifMenu" class="btn-icon btn-ghost !rounded-full border border-slate-200 relative">
          <i class="fa-regular fa-bell text-navy-700"></i>
          <?php if ($unread > 0): ?>
            <span class="absolute top-1 right-1.5 min-w-[15px] h-[15px] px-1 rounded-full bg-brand-600 ring-2 ring-white text-white text-[9px] font-bold flex items-center justify-center"><?= $unread > 9 ? '9+' : $unread ?></span>
          <?php endif; ?>
        </button>
        <div id="notifMenu" class="dropdown-menu absolute right-0 mt-2 w-[320px] bg-white border border-slate-200 rounded-2xl shadow-lg z-50 overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <span class="font-head font-bold text-[13px] text-navy-900">Notificaciones</span>
            <?php if ($unread > 0): ?><button onclick="marcarNotifsLeidas()" class="text-[11px] text-brand-600 font-semibold hover:underline">Marcar leído</button><?php endif; ?>
          </div>
          <div class="max-h-80 overflow-y-auto">
            <?php if (!$notifs): ?>
              <div class="px-4 py-8 text-center text-slate-400 text-[12.5px]">Sin notificaciones por ahora.</div>
            <?php endif; ?>
            <?php foreach($notifs as $n): ?>
              <div class="flex items-start gap-2.5 px-4 py-3 border-b border-slate-50 last:border-0 <?= $n['leida']?'':'bg-red-50/40' ?>">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:<?= $n['leida']?'#eef1f6':'#fff0f1' ?>;color:<?= $n['leida']?'#67758a':'#d91e2c' ?>"><i class="fa-solid <?= e($n['icono']) ?> text-[11px]"></i></div>
                <div class="min-w-0">
                  <div class="text-[12.5px] font-semibold text-navy-900 leading-snug"><?= e($n['titulo']) ?></div>
                  <div class="text-[11.5px] text-slate-400 mt-0.5"><?= time_ago($n['created_at']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="relative">
        <button data-dropdown-trigger="userMenuC" class="w-9 h-9 rounded-full flex items-center justify-center font-head font-bold text-[12px] text-white" style="background:<?= $me['role_color'] ?>"><?= initials($me['nombre']) ?></button>
        <div id="userMenuC" class="dropdown-menu absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-2xl shadow-lg p-2 z-50">
          <div class="px-3 py-2 text-[12.5px] font-semibold text-navy-900 truncate"><?= e($me['nombre']) ?></div>
          <div class="divider-fade my-1"></div>
          <button type="button" data-open-modal="logoutModal" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-red-50 text-sm text-brand-600"><i class="fa-solid fa-right-from-bracket w-4"></i> Cerrar sesión</button>
        </div>
      </div>
    </div>
  </div>
</nav>

<main class="max-w-5xl mx-auto px-5 py-8" id="inicio">

  <!-- ===================== HERO ===================== -->
  <div class="client-hero grid md:grid-cols-2 mb-8" data-reveal>
    <div class="client-hero-photo">
      <img src="https://picsum.photos/seed/obra<?= $obra['id'] ?>/700/500" alt="<?= e($obra['nombre']) ?>">
      <div class="absolute top-5 left-5">
        <span class="badge" style="background:rgba(255,255,255,.9);color:<?= estado_obra_color($obra['estado']) ?>"><span class="badge-dot"></span> <?= estado_obra_label($obra['estado']) ?></span>
      </div>
      <div class="absolute bottom-5 left-5 right-5 text-white">
        <div class="font-head font-bold text-2xl"><?= e($obra['nombre']) ?></div>
        <div class="text-white/75 text-[13px]"><i class="fa-solid fa-location-dot mr-1"></i><?= e($obra['ubicacion'] ?? 'Perú') ?></div>
      </div>
    </div>
    <div class="p-8 flex flex-col justify-center">
      <div class="flex items-center gap-6 mb-6">
        <div class="relative w-28 h-28 flex-shrink-0">
          <svg class="progress-ring w-28 h-28" data-pct="<?= $obra['avance_pct'] ?>">
            <circle cx="56" cy="56" r="48" stroke="#eef1f6" stroke-width="10"/>
            <circle class="ring-value" cx="56" cy="56" r="48" stroke="url(#gradRing)" stroke-width="10"/>
            <defs><linearGradient id="gradRing" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#d91e2c"/><stop offset="100%" stop-color="#7c6cf6"/></linearGradient></defs>
          </svg>
          <span class="absolute inset-0 flex items-center justify-center font-head font-bold text-2xl text-navy-900"><?= (int)$obra['avance_pct'] ?>%</span>
        </div>
        <div>
          <div class="text-[12.5px] text-slate-500 mb-1">Tu obra va muy bien 🏠</div>
          <div class="font-head font-bold text-navy-900 text-lg leading-snug">Entrega estimada</div>
          <div class="text-brand-600 font-semibold text-[15px]"><?= fecha_es($obra['fecha_fin_estimada']) ?></div>
        </div>
      </div>
      <p class="text-slate-500 text-[13.5px] mb-6">Actualizado <?= time_ago($obra['updated_at']) ?> por tu jefe de obra. Toca el asistente para preguntar lo que quieras, al instante.</p>
      <button onclick="toggleChat(true)" class="btn btn-primary justify-center w-full"><i class="fa-solid fa-wand-magic-sparkles"></i> Preguntarle al asistente</button>
    </div>
  </div>

  <!-- ===================== JOURNEY / RECORRIDO ===================== -->
  <section class="mb-10" data-reveal>
    <h2 class="font-head font-bold text-navy-900 text-[18px] mb-1">El recorrido de tu obra</h2>
    <p class="text-slate-500 text-[13px] mb-5">Cada etapa se actualiza en tiempo real desde el campo</p>
    <div class="card p-6">
      <div class="journey-track">
        <?php foreach($partidas as $i => $p):
          $done = (float)$p['avance_pct'] >= 100;
          $isCurrent = ($i === $currentIdx);
          $icon = $partidaIcons[$p['nombre']] ?? 'fa-circle-check';
        ?>
        <div class="journey-node">
          <div class="journey-line <?= $i <= $currentIdx || ($currentIdx === null) ? 'done' : '' ?>"></div>
          <div class="journey-dot <?= $done ? 'done' : ($isCurrent ? 'current' : '') ?>">
            <i class="fa-solid <?= $done ? 'fa-check' : $icon ?>"></i>
          </div>
          <div class="journey-label"><?= e($p['nombre']) ?></div>
          <div class="journey-pct"><?= (int)$p['avance_pct'] ?>%</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===================== ACCESOS RÁPIDOS ===================== -->
  <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10" data-reveal>
    <a href="#timeline" class="quick-tile block">
      <div class="quick-tile-icon" style="background:#eef4ff;color:#2563eb"><i class="fa-solid fa-images"></i></div>
      <div class="font-head font-bold text-navy-900 text-[13px]">Fotos</div>
      <div class="text-[11px] text-slate-400 mt-0.5"><?= count($timeline) ?> reportes</div>
    </a>
    <a href="#pagos" class="quick-tile block">
      <div class="quick-tile-icon" style="background:#eafbf1;color:#22a35a"><i class="fa-solid fa-money-check-dollar"></i></div>
      <div class="font-head font-bold text-navy-900 text-[13px]">Pagos</div>
      <div class="text-[11px] text-slate-400 mt-0.5"><?= count($valorizaciones) ?> valorizaciones</div>
    </a>
    <a href="#documentos" class="quick-tile block">
      <div class="quick-tile-icon" style="background:#fff0f1;color:#d91e2c"><i class="fa-solid fa-file-lines"></i></div>
      <div class="font-head font-bold text-navy-900 text-[13px]">Documentos</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Planos y contrato</div>
    </a>
    <a href="#comprobantes" class="quick-tile block">
      <div class="quick-tile-icon" style="background:#fff8ea;color:#b45309"><i class="fa-solid fa-receipt"></i></div>
      <div class="font-head font-bold text-navy-900 text-[13px]">Comprobantes</div>
      <div class="text-[11px] text-slate-400 mt-0.5"><?= count($comprobantes) ?> SUNAT</div>
    </a>
  </section>

  <!-- ===================== TIMELINE FOTOGRÁFICA (stories) ===================== -->
  <section id="timeline" class="mb-10" data-reveal>
    <h2 class="font-head font-bold text-navy-900 text-[18px] mb-1">Línea de tiempo</h2>
    <p class="text-slate-500 text-[13px] mb-5">Reportado en vivo por tu jefe de obra</p>
    <?php if (!$timeline): ?>
      <div class="card p-8 text-center text-slate-400">Aún no hay reportes de avance registrados.</div>
    <?php else: ?>
    <div class="story-rail">
      <?php foreach($timeline as $t): ?>
      <div class="story-card">
        <img src="https://picsum.photos/seed/report<?= $t['id'] ?>/300/400">
        <div class="story-card-overlay">
          <span class="badge badge-red mb-2 w-fit"><?= (int)$t['porcentaje'] ?>%</span>
          <div class="text-white font-semibold text-[13px] leading-snug"><?= e($t['partida_nombre'] ?? 'Avance general') ?></div>
          <div class="text-white/70 text-[11px] mt-0.5"><?= time_ago($t['created_at']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- ===================== PAGOS ===================== -->
  <section id="pagos" class="mb-10" data-reveal>
    <h2 class="font-head font-bold text-navy-900 text-[18px] mb-1">Valorizaciones</h2>
    <p class="text-slate-500 text-[13px] mb-5">El estado de los pagos de tu obra</p>
    <?php if (!$valorizaciones): ?>
      <div class="card p-8 text-center text-slate-400">Sin valorizaciones registradas.</div>
    <?php else: ?>
    <div class="grid md:grid-cols-2 gap-4">
      <?php foreach($valorizaciones as $v): ?>
      <div class="card p-5 flex items-center gap-4">
        <div class="quick-tile-icon !mx-0 !mb-0 flex-shrink-0" style="background:#eef4ff;color:#2563eb"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-navy-900 text-[13.5px] truncate"><?= e($v['numero']) ?> — <?= e($v['contratista']) ?></div>
          <div class="text-[12px] text-slate-400"><?= date('d/m/Y', strtotime($v['created_at'])) ?></div>
        </div>
        <div class="text-right flex-shrink-0">
          <div class="font-head font-bold text-navy-900"><?= money($v['monto']) ?></div>
          <span class="badge <?= estado_badge_class($v['estado']) ?>"><?= ucfirst($v['estado']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- ===================== DOCUMENTOS ===================== -->
  <section id="documentos" class="mb-10" data-reveal>
    <h2 class="font-head font-bold text-navy-900 text-[18px] mb-1">Planos & Documentos</h2>
    <p class="text-slate-500 text-[13px] mb-5">Todo lo legal y técnico de tu proyecto, en un solo lugar</p>
    <div class="grid md:grid-cols-3 gap-4">
      <?php $docs=[['fa-file-lines','Plano de arquitectura','PDF · 4.2 MB'],['fa-cube','Modelo BIM 3D','IFC · 18 MB'],['fa-file-contract','Contrato de obra','PDF · 1.1 MB']]; ?>
      <?php foreach($docs as $d): ?>
      <div class="quick-tile flex items-center gap-3 text-left !p-4">
        <div class="quick-tile-icon !mx-0 !mb-0 flex-shrink-0" style="background:#fff0f1;color:#d91e2c"><i class="fa-solid <?= $d[0] ?>"></i></div>
        <div class="flex-1 min-w-0"><div class="font-semibold text-navy-900 text-[13px] truncate"><?= $d[1] ?></div><div class="text-[11px] text-slate-400"><?= $d[2] ?></div></div>
        <i class="fa-solid fa-download text-slate-400"></i>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ===================== COMPROBANTES ===================== -->
  <section id="comprobantes" class="mb-16" data-reveal>
    <h2 class="font-head font-bold text-navy-900 text-[18px] mb-1">Comprobantes SUNAT</h2>
    <p class="text-slate-500 text-[13px] mb-5">Tus facturas electrónicas, listas para descargar</p>
    <?php if (!$comprobantes): ?>
      <div class="card p-8 text-center text-slate-400">Aún no tienes comprobantes emitidos.</div>
    <?php else: ?>
    <div class="grid md:grid-cols-2 gap-4">
      <?php foreach($comprobantes as $c): ?>
      <div class="card p-5 flex items-center gap-4">
        <div class="quick-tile-icon !mx-0 !mb-0 flex-shrink-0" style="background:#eafbf1;color:#22a35a"><i class="fa-solid fa-receipt"></i></div>
        <div class="flex-1 min-w-0"><div class="font-semibold text-navy-900 text-[13.5px]"><?= e($c['serie_numero']) ?></div><div class="text-[12px] text-slate-400"><?= date('d/m/Y', strtotime($c['created_at'])) ?></div></div>
        <div class="font-head font-bold text-navy-900 flex-shrink-0"><?= money($c['monto']) ?></div>
        <button class="btn-icon btn-ghost !w-9 !h-9 flex-shrink-0"><i class="fa-solid fa-download text-slate-400"></i></button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</main>

<!-- ===================== ASISTENTE FLOTANTE (Gemini) ===================== -->
<button class="chat-fab" onclick="toggleChat()">
  <span class="fab-pulse"></span>
  <i class="fa-solid fa-comment-dots" id="chatFabIcon"></i>
</button>

<div class="chat-panel" id="chatPanel">
  <div class="flex items-center gap-3 px-5 py-4 flex-shrink-0" style="background:var(--gradient-dark)">
    <div class="w-9 h-9 rounded-full bg-green-500 flex items-center justify-center text-white"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
    <div class="flex-1">
      <div class="text-white text-[13.5px] font-semibold">Asistente de Obra</div>
      <div class="text-[11px] text-green-400">● en línea · Gemini AI</div>
    </div>
    <button onclick="toggleChat(false)" class="text-white/60 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="flex-1 overflow-y-auto px-4 py-4 space-y-2.5" id="chatLog">
    <?php if (!$chatHistorial): ?>
      <div class="bg-slate-100 text-navy-800 text-[13px] rounded-2xl rounded-tl-sm px-4 py-2.5 max-w-[88%]">¡Hola! Pregúntame sobre el avance de tu obra 🏗️</div>
    <?php endif; ?>
    <?php foreach($chatHistorial as $m): ?>
      <?php if ($m['remitente'] === 'cliente'): ?>
        <div class="bg-brand-600 text-white text-[13px] rounded-2xl rounded-tr-sm px-4 py-2.5 max-w-[85%] ml-auto"><?= e($m['mensaje']) ?></div>
      <?php else: ?>
        <div class="bg-slate-100 text-navy-800 text-[13px] rounded-2xl rounded-tl-sm px-4 py-2.5 max-w-[88%]"><?= nl2br(e($m['mensaje'])) ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="flex gap-2 p-3 border-t border-slate-100 flex-shrink-0">
    <input id="chatInput" type="text" placeholder="Escribe tu pregunta…" class="flex-1 bg-slate-100 rounded-full px-4 py-2.5 text-navy-800 placeholder-slate-400 text-sm outline-none focus:ring-2 focus:ring-brand-200">
    <button onclick="sendChat()" id="chatSendBtn" class="btn-icon btn-primary !rounded-full flex-shrink-0"><i class="fa-solid fa-paper-plane"></i></button>
  </div>
</div>

<?php include '../partials/logout-modal.php'; ?>
<?php endif; ?>

<?php include '../partials/scripts.php'; ?>
<?php if ($obra): ?>
<script>
  const csrfChat = '<?= csrf_token() ?>';
  const obraIdChat = <?= $obra['id'] ?>;

  // Nav pills activas según scroll
  const sections = ['inicio','timeline','pagos','documentos'];
  window.addEventListener('scroll', () => {
    let current = 'inicio';
    sections.forEach(id => {
      const el = document.getElementById(id);
      if (el && window.scrollY >= el.offsetTop - 120) current = id;
    });
    document.querySelectorAll('.client-nav-pill').forEach(p => {
      p.classList.toggle('active', p.getAttribute('href') === '#' + current);
    });
  });

  function toggleChat(force){
    const panel = document.getElementById('chatPanel');
    const icon = document.getElementById('chatFabIcon');
    const open = force !== undefined ? force : !panel.classList.contains('open');
    panel.classList.toggle('open', open);
    icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-comment-dots';
    if (open) document.getElementById('chatLog').scrollTop = 999999;
  }

  function appendBubble(text, remitente){
    const log = document.getElementById('chatLog');
    const cls = remitente === 'cliente'
      ? 'bg-brand-600 text-white text-[13px] rounded-2xl rounded-tr-sm px-4 py-2.5 max-w-[85%] ml-auto'
      : 'bg-slate-100 text-navy-800 text-[13px] rounded-2xl rounded-tl-sm px-4 py-2.5 max-w-[88%]';
    const div = document.createElement('div');
    div.className = cls;
    div.textContent = text;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }

  async function sendChat(){
    const input = document.getElementById('chatInput');
    const btn = document.getElementById('chatSendBtn');
    const text = input.value.trim();
    if (!text) return;
    appendBubble(text, 'cliente');
    input.value = '';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('csrf_token', csrfChat);
    fd.append('obra_id', obraIdChat);
    fd.append('mensaje', text);

    try {
      const res = await fetch('../actions/chat-enviar.php', { method:'POST', body: fd });
      const data = await res.json();
      appendBubble(data.ok ? data.respuesta : (data.error || 'No se pudo responder.'), 'bot');
    } catch(e) {
      appendBubble('Error de conexión con el asistente.', 'bot');
    }
    btn.disabled = false;
  }

  document.getElementById('chatInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') sendChat(); });

  const __csrfNotif = '<?= csrf_token() ?>';
  async function marcarNotifsLeidas(){
    const fd = new FormData();
    fd.append('csrf_token', __csrfNotif);
    await fetch('../actions/notificaciones-marcar-leidas.php', { method:'POST', body: fd });
    document.querySelectorAll('.bg-red-50\\/40').forEach(el => el.classList.remove('bg-red-50/40'));
  }
</script>
<?php endif; ?>
</body>
</html>
