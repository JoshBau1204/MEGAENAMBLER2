<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
$baseUrl = '.'; $pageTitle = 'Recuperar contraseña';
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

      <div id="stepForm">
        <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 text-xl mb-6"><i class="fa-solid fa-key"></i></div>
        <h1 class="font-head font-bold text-[26px] text-navy-900 mb-2">¿Olvidaste tu contraseña?</h1>
        <p class="text-slate-500 text-[14.5px] mb-8">Ingresa tu correo y te enviamos un enlace para restablecerla — gracias a la conexión con Gmail.</p>

        <form id="forgotForm">
          <?= csrf_field() ?>
          <div class="mb-5">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Correo electrónico</label>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-4 py-3 text-sm outline-none transition">
            </div>
          </div>
          <button type="submit" id="sendBtn" class="btn btn-primary w-full justify-center">
            <i class="fa-solid fa-paper-plane"></i> Enviar enlace de recuperación
          </button>
        </form>
      </div>

      <div id="stepSent" class="hidden text-center">
        <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center text-green-600 text-2xl mb-6 mx-auto"><i class="fa-solid fa-envelope-circle-check"></i></div>
        <h1 class="font-head font-bold text-[24px] text-navy-900 mb-2">Revisa tu correo</h1>
        <p class="text-slate-500 text-[14.5px] mb-6" id="sentMessage">Si el correo existe en nuestro sistema, te enviamos un enlace para restablecer tu contraseña.</p>
        <div id="devLinkBanner" class="hidden mb-6 p-4 rounded-xl bg-amber-50 border border-amber-100 text-left text-[13px]">
          <p class="text-amber-600 font-semibold mb-2"><i class="fa-solid fa-flask"></i> Gmail SMTP no está configurado — enlace de desarrollo:</p>
          <a id="devLink" href="#" class="text-brand-600 underline break-all"></a>
        </div>
        <a href="login.php" class="btn btn-outline w-full justify-center">Volver al login</a>
      </div>
    </div>
  </div>

  <div class="hidden lg:flex relative overflow-hidden items-center justify-center" style="background:var(--gradient-dark)">
    <div class="tech-grid-bg dark-grid"></div>
    <div class="blob blob-red w-96 h-96 top-10 -right-10"></div>
    <div class="blob blob-cyan w-80 h-80 bottom-0 -left-10"></div>
    <div class="relative z-10 max-w-md px-10 text-center">
      <div class="text-6xl mb-6">🔐</div>
      <h2 class="font-head font-bold text-white text-[26px] leading-snug mb-3">Recuperación segura por correo</h2>
      <p class="text-slate-300 text-[14.5px]">El enlace expira en 30 minutos y solo puede usarse una vez.</p>
    </div>
  </div>
</div>

<?php include 'partials/scripts.php'; ?>
<script>
  document.getElementById('forgotForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando…';

    try {
      const fd = new FormData(e.target);
      const res = await fetch('actions/forgot-password.php', { method: 'POST', body: fd });
      const data = await res.json();

      document.getElementById('stepForm').classList.add('hidden');
      document.getElementById('stepSent').classList.remove('hidden');
      if (data.message) document.getElementById('sentMessage').textContent = data.message;
      if (data.dev_reset_url) {
        document.getElementById('devLinkBanner').classList.remove('hidden');
        const a = document.getElementById('devLink');
        a.href = data.dev_reset_url;
        a.textContent = data.dev_reset_url;
      }
    } catch (err) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar enlace de recuperación';
      MEGA.toast('Error de conexión con el servidor', 'error');
    }
  });
</script>
</body>
</html>
