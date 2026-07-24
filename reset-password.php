<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
$baseUrl = '.'; $pageTitle = 'Restablecer contraseña';
$token = $_GET['token'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
<?php include 'partials/head.php'; ?>
</head>
<body class="font-body text-navy-800 bg-white">

<div class="min-h-screen grid lg:grid-cols-2">
  <div class="flex flex-col justify-center px-8 sm:px-16 lg:px-20 py-12 relative">
    <a href="login.php" class="absolute top-8 left-8 sm:left-16 lg:left-20 flex items-center gap-2 text-slate-400 hover:text-navy-800 transition text-sm font-medium">
      <i class="fa-solid fa-arrow-left"></i> Volver al login
    </a>

    <div class="max-w-sm mx-auto w-full" data-reveal>
      <div class="flex items-center gap-3 mb-10">
        <img src="assets/img/logo.png" class="w-12 h-12 rounded-xl" alt="Logo">
        <div class="leading-tight">
          <div class="font-head font-bold text-navy-900">MegaEnsambler</div>
          <div class="text-[10.5px] tracking-widest text-brand-600 font-semibold">BIM COORDINATION</div>
        </div>
      </div>

      <?php if (!$token): ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13.5px]">
          Enlace inválido. Solicita uno nuevo desde <a href="forgot-password.php" class="underline font-semibold">aquí</a>.
        </div>
      <?php else: ?>
      <div id="stepForm">
        <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 text-xl mb-6"><i class="fa-solid fa-lock-open"></i></div>
        <h1 class="font-head font-bold text-[26px] text-navy-900 mb-2">Crea una nueva contraseña</h1>
        <p class="text-slate-500 text-[14.5px] mb-8">Elige una contraseña segura para tu cuenta.</p>

        <form id="resetForm">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
          <div class="mb-4">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Nueva contraseña</label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="password" name="password" id="pwd1" required minlength="8" class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-11 py-3 text-sm outline-none transition">
              <button type="button" onclick="togglePassword(this,'pwd1')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy-700"><i class="fa-regular fa-eye"></i></button>
            </div>
          </div>
          <div class="mb-3">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Confirmar contraseña</label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="password" id="pwd2" required minlength="8" class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-4 py-3 text-sm outline-none transition">
            </div>
          </div>
          <div id="resetError" class="hidden mb-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
          <button type="submit" id="resetBtn" class="btn btn-primary w-full justify-center mt-4">
            <i class="fa-solid fa-check"></i> Restablecer contraseña
          </button>
        </form>
      </div>

      <div id="stepDone" class="hidden text-center">
        <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center text-green-600 text-2xl mb-6 mx-auto"><i class="fa-solid fa-circle-check"></i></div>
        <h1 class="font-head font-bold text-[24px] text-navy-900 mb-2">¡Listo!</h1>
        <p class="text-slate-500 text-[14.5px] mb-6">Tu contraseña se actualizó correctamente.</p>
        <a href="login.php" class="btn btn-primary w-full justify-center">Iniciar sesión</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="hidden lg:flex relative overflow-hidden items-center justify-center" style="background:var(--gradient-dark)">
    <div class="tech-grid-bg dark-grid"></div>
    <div class="blob blob-cyan w-80 h-80 bottom-0 -left-10"></div>
    <div class="blob blob-red w-96 h-96 top-10 -right-10"></div>
    <div class="relative z-10 max-w-md px-10 text-center">
      <div class="text-6xl mb-6">✅</div>
      <h2 class="font-head font-bold text-white text-[26px] leading-snug mb-3">Casi listo</h2>
      <p class="text-slate-300 text-[14.5px]">Este enlace es de un solo uso y expira en 30 minutos por tu seguridad.</p>
    </div>
  </div>
</div>

<?php include 'partials/scripts.php'; ?>
<script>
  const form = document.getElementById('resetForm');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const errBox = document.getElementById('resetError');
      errBox.classList.add('hidden');

      if (document.getElementById('pwd1').value !== document.getElementById('pwd2').value) {
        errBox.textContent = 'Las contraseñas no coinciden.';
        errBox.classList.remove('hidden');
        return;
      }

      const btn = document.getElementById('resetBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

      try {
        const fd = new FormData(e.target);
        const res = await fetch('actions/reset-password.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.ok) {
          errBox.textContent = data.error || 'No se pudo restablecer la contraseña.';
          errBox.classList.remove('hidden');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-check"></i> Restablecer contraseña';
          return;
        }

        document.getElementById('stepForm').classList.add('hidden');
        document.getElementById('stepDone').classList.remove('hidden');
      } catch (err) {
        errBox.textContent = 'Error de conexión con el servidor.';
        errBox.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Restablecer contraseña';
      }
    });
  }
</script>
</body>
</html>
