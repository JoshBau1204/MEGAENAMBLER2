<?php
/** Variables esperadas: $pageHeading, $pageSubheading, $userInitials, $roleColor */
require_once __DIR__ . '/../includes/notificaciones.php';
if(!isset($pageHeading)) $pageHeading = 'Dashboard';
if(!isset($userInitials)) $userInitials = 'UD';
if(!isset($roleColor)) $roleColor = '#d91e2c';

$__me = current_user();
$__notifs = obtener_notificaciones($__me['id']);
$__unread = contar_no_leidas($__me['id']);
?>
<header class="topbar">
  <button id="sidebarToggle" class="btn-icon btn-ghost !rounded-xl border border-slate-200">
    <i class="fa-solid fa-bars text-navy-700"></i>
  </button>

  <div class="hidden md:block">
    <h1 class="font-head font-bold text-[19px] text-navy-900 leading-tight"><?= $pageHeading ?></h1>
    <?php if(isset($pageSubheading)): ?><p class="text-[12.5px] text-slate-500"><?= $pageSubheading ?></p><?php endif; ?>
  </div>

  <div class="flex-1 max-w-md ml-2 hidden lg:block">
    <div class="relative">
      <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" placeholder="Buscar obra, usuario, documento…" class="w-full bg-slate-100 border border-transparent focus:border-brand-500 focus:bg-white rounded-full pl-11 pr-4 py-2.5 text-sm outline-none transition">
    </div>
  </div>

  <div class="ml-auto flex items-center gap-2.5">

    <div class="relative">
      <button data-dropdown-trigger="notifMenu" id="notifBellBtn" class="btn-icon btn-ghost !rounded-xl border border-slate-200 relative">
        <i class="fa-regular fa-bell text-navy-700"></i>
        <?php if ($__unread > 0): ?>
          <span id="notifDot" class="absolute top-1.5 right-2 min-w-[16px] h-4 px-1 rounded-full bg-brand-600 ring-2 ring-white text-white text-[9.5px] font-bold flex items-center justify-center"><?= $__unread > 9 ? '9+' : $__unread ?></span>
        <?php endif; ?>
      </button>
      <div id="notifMenu" class="dropdown-menu absolute right-0 mt-2 w-[340px] bg-white border border-slate-200 rounded-2xl shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
          <span class="font-head font-bold text-[13.5px] text-navy-900">Notificaciones</span>
          <?php if ($__unread > 0): ?>
            <button onclick="marcarNotifsLeidas()" class="text-[11.5px] text-brand-600 font-semibold hover:underline">Marcar todo leído</button>
          <?php endif; ?>
        </div>
        <div class="max-h-[360px] overflow-y-auto">
          <?php if (!$__notifs): ?>
            <div class="px-4 py-10 text-center text-slate-400 text-[13px]">
              <i class="fa-regular fa-bell-slash text-2xl mb-2 block"></i>
              Sin notificaciones por ahora.
            </div>
          <?php endif; ?>
          <?php foreach($__notifs as $n): ?>
            <a href="<?= $n['link'] ? '../' . ltrim(str_replace('/MEGAENAMBLER2/', '', $n['link']), '/') : '#' ?>" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition border-b border-slate-50 last:border-0 <?= $n['leida'] ? '' : 'bg-red-50/40' ?>">
              <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5" style="background:<?= $n['leida']?'#eef1f6':'#fff0f1' ?>;color:<?= $n['leida']?'#67758a':'#d91e2c' ?>">
                <i class="fa-solid <?= htmlspecialchars($n['icono']) ?> text-[13px]"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-[13px] font-semibold text-navy-900 leading-snug"><?= htmlspecialchars($n['titulo']) ?></div>
                <?php if ($n['mensaje']): ?><div class="text-[12px] text-slate-500 leading-snug mt-0.5"><?= htmlspecialchars($n['mensaje']) ?></div><?php endif; ?>
                <div class="text-[10.5px] text-slate-400 mt-1"><?= time_ago($n['created_at']) ?></div>
              </div>
              <?php if (!$n['leida']): ?><span class="w-2 h-2 rounded-full bg-brand-600 mt-1.5 flex-shrink-0"></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="relative">
      <button data-dropdown-trigger="userMenu" class="flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-full border border-slate-200 hover:border-brand-400 transition">
        <div class="w-8 h-8 rounded-full flex items-center justify-center font-head font-bold text-[12px] text-white" style="background:<?= $roleColor ?>"><?= $userInitials ?></div>
        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
      </button>
      <div id="userMenu" class="dropdown-menu absolute right-0 mt-2 w-52 bg-white border border-slate-200 rounded-2xl shadow-lg p-2 z-50">
        <a href="#" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-slate-100 text-sm"><i class="fa-regular fa-user w-4"></i> Mi perfil</a>
        <a href="#" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-slate-100 text-sm"><i class="fa-solid fa-gear w-4"></i> Preferencias</a>
        <div class="divider-fade my-1.5"></div>
        <button type="button" data-open-modal="logoutModal" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-red-50 text-sm text-brand-600"><i class="fa-solid fa-right-from-bracket w-4"></i> Cerrar sesión</button>
      </div>
    </div>
  </div>
</header>

<style>
  .dropdown-menu{display:none;opacity:0;transform:translateY(-6px);transition:all .2s var(--ease-out)}
  .dropdown-menu.show{display:block;opacity:1;transform:translateY(0)}
</style>
<script>
  const __csrfNotif = '<?= csrf_token() ?>';
  async function marcarNotifsLeidas(){
    const fd = new FormData();
    fd.append('csrf_token', __csrfNotif);
    await fetch('../actions/notificaciones-marcar-leidas.php', { method:'POST', body: fd });
    document.getElementById('notifDot')?.remove();
    document.querySelectorAll('#notifMenu .bg-red-50\\/40').forEach(el => el.classList.remove('bg-red-50/40'));
    document.querySelectorAll('#notifMenu .bg-brand-600.w-2.h-2').forEach(el => el.remove());
  }
</script>
