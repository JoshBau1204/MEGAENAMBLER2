<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /MEGAENAMBLER2/dashboard/' . str_replace('_', '-', current_user()['role_slug']) . '.php');
    exit;
}

$baseUrl = '.'; $pageTitle = 'Crear cuenta';
?>
<!doctype html>
<html lang="es">
<head>
<?php include 'partials/head.php'; ?>
</head>
<body class="font-body text-navy-800 bg-white">

<div class="min-h-screen grid lg:grid-cols-2">

  <div class="flex flex-col justify-center px-8 sm:px-16 lg:px-20 py-12 relative">
    <a href="index.php" class="absolute top-8 left-8 sm:left-16 lg:left-20 flex items-center gap-2 text-slate-400 hover:text-navy-800 transition text-sm font-medium">
      <i class="fa-solid fa-arrow-left"></i> Volver al sitio
    </a>

    <div class="max-w-sm mx-auto w-full" data-reveal>
      <div class="flex items-center gap-3 mb-10">
        <img src="assets/img/logo.png" class="w-12 h-12 rounded-xl" alt="Logo">
        <div class="leading-tight">
          <div class="font-head font-bold text-navy-900">MegaEnsambler</div>
          <div class="text-[10.5px] tracking-widest text-brand-600 font-semibold">BIM COORDINATION</div>
        </div>
      </div>

      <div id="stepCreds">
        <div class="eyebrow mb-4"><span class="dot"></span> ES GRATIS</div>
        <h1 class="font-head font-bold text-[28px] text-navy-900 mb-2">Crea tu cuenta</h1>
        <p class="text-slate-500 text-[14.5px] mb-8">Accede como cliente y sigue el avance de tu obra en tiempo real.</p>

        <form id="registerForm">
          <?= csrf_field() ?>
          <div class="mb-4">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Nombre completo</label>
            <div class="relative">
              <i class="fa-regular fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="text" name="nombre" required class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-4 py-3 text-sm outline-none transition">
            </div>
          </div>
          <div class="mb-4">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Correo electrónico</label>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="email" name="email" required class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-4 py-3 text-sm outline-none transition">
            </div>
          </div>
          <div class="mb-3">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Contraseña</label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="password" name="password" id="pwd" required minlength="8" class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-11 py-3 text-sm outline-none transition">
              <button type="button" onclick="togglePassword(this,'pwd')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy-700"><i class="fa-regular fa-eye"></i></button>
            </div>
            <p class="text-[11.5px] text-slate-400 mt-1.5">Mínimo 8 caracteres.</p>
          </div>
          <div id="regError" class="hidden mb-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
          <button type="submit" id="regBtn" class="btn btn-primary w-full justify-center mt-4">
            <i class="fa-solid fa-user-plus"></i> Crear cuenta gratis
          </button>
        </form>

        <div class="flex items-center gap-3 my-7">
          <div class="h-px flex-1 bg-slate-200"></div>
          <span class="text-[11.5px] text-slate-400 font-medium">O CONTINÚA CON</span>
          <div class="h-px flex-1 bg-slate-200"></div>
        </div>
        <a href="auth/google-login.php" class="btn btn-outline w-full justify-center">
          <i class="fa-brands fa-google"></i> Registrarme con Google
        </a>

        <p class="text-center text-[13px] text-slate-500 mt-8">¿Ya tienes cuenta? <a href="login.php" class="text-brand-600 font-semibold hover:underline">Inicia sesión</a></p>
      </div>

      <div id="step2FA" class="hidden">
        <div class="w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 text-2xl mb-6"><i class="fa-solid fa-envelope-open-text"></i></div>
        <h1 class="font-head font-bold text-[26px] text-navy-900 mb-2">Confirma tu correo</h1>
        <p class="text-slate-500 text-[14.5px] mb-4" id="otpHint">Enviamos un código de 6 dígitos a tu correo.</p>
        <div id="devCodeBanner" class="hidden mb-5 p-3.5 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 text-[13px]">
          <i class="fa-solid fa-flask"></i> Gmail SMTP no está configurado aún — código de desarrollo: <b id="devCodeValue" class="font-mono"></b>
        </div>
        <div class="flex gap-2.5 mb-4" id="otpGroup">
          <?php for($i=0;$i<6;$i++): ?>
            <input maxlength="1" inputmode="numeric" class="otp-input w-full h-14 text-center text-xl font-bold border border-slate-200 rounded-xl bg-slate-50 focus:border-brand-500 focus:bg-white outline-none transition">
          <?php endfor; ?>
        </div>
        <div id="otpError" class="hidden mb-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
        <button onclick="verify2FA()" id="verifyBtn" class="btn btn-primary w-full justify-center">
          <i class="fa-solid fa-check-double"></i> Confirmar y entrar
        </button>
      </div>
    </div>
  </div>

  <div class="hidden lg:flex relative overflow-hidden items-center justify-center" style="background:var(--gradient-dark)">
    <div class="tech-grid-bg dark-grid"></div>
    <div class="blob blob-red w-96 h-96 top-10 -right-10"></div>
    <div class="blob blob-cyan w-80 h-80 bottom-0 -left-10"></div>
    <div class="absolute inset-0" data-particles="30"></div>
    <div class="relative z-10 max-w-md px-10 text-center">
      <div class="text-6xl mb-6">🏗️</div>
      <h2 class="font-head font-bold text-white text-[26px] leading-snug mb-3">Sigue tu obra como nunca antes</h2>
      <p class="text-slate-300 text-[14.5px]">Fotos diarias, avance en tiempo real, valorizaciones y comprobantes — todo desde un solo lugar.</p>
    </div>
  </div>
</div>

<?php include 'partials/scripts.php'; ?>
<script>
  document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('regBtn');
    const errBox = document.getElementById('regError');
    errBox.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creando cuenta…';

    try {
      const fd = new FormData(e.target);
      const res = await fetch('actions/register.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (!data.ok) {
        errBox.textContent = data.error || 'No se pudo crear la cuenta.';
        errBox.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Crear cuenta gratis';
        return;
      }

      document.getElementById('stepCreds').classList.add('hidden');
      document.getElementById('step2FA').classList.remove('hidden');
      if (data.channel === 'dev') {
        document.getElementById('devCodeBanner').classList.remove('hidden');
        document.getElementById('devCodeValue').textContent = data.dev_code;
        document.getElementById('otpHint').textContent = 'Gmail aún no está conectado, así que te mostramos el código aquí mismo.';
      } else {
        MEGA.toast('Código de verificación enviado a tu correo', 'success');
      }
      document.querySelector('.otp-input').focus();
    } catch (err) {
      errBox.textContent = 'Error de conexión con el servidor.';
      errBox.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Crear cuenta gratis';
    }
  });

  document.querySelectorAll('.otp-input').forEach((input,i,arr)=>{
    input.addEventListener('input',()=>{
      if(input.value.length===1 && i<arr.length-1) arr[i+1].focus();
      if(arr[arr.length-1].value) verify2FA();
    });
    input.addEventListener('keydown',(e)=>{
      if(e.key==='Backspace' && input.value==='' && i>0) arr[i-1].focus();
    });
  });

  async function verify2FA(){
    const code = Array.from(document.querySelectorAll('.otp-input')).map(i=>i.value).join('');
    const errBox = document.getElementById('otpError');
    errBox.classList.add('hidden');
    if(code.length < 6) return;

    const btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando…';

    try {
      const fd = new FormData();
      fd.append('code', code);
      fd.append('csrf_token', document.querySelector('input[name=csrf_token]').value);
      const res = await fetch('actions/verify-2fa.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (!data.ok) {
        errBox.textContent = data.error || 'Código incorrecto.';
        errBox.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Confirmar y entrar';
        return;
      }

      MEGA.toast('Cuenta verificada. ¡Bienvenido!','success');
      setTimeout(()=>{ window.location.href = data.redirect; }, 700);
    } catch (err) {
      errBox.textContent = 'Error de conexión con el servidor.';
      errBox.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Confirmar y entrar';
    }
  }
</script>
</body>
</html>
