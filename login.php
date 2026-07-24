<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /MEGAENAMBLER2/dashboard/' . str_replace('_', '-', current_user()['role_slug']) . '.php');
    exit;
}

$baseUrl = '.'; $pageTitle = 'Iniciar sesión';
$oauthError = $_GET['error'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
<?php include 'partials/head.php'; ?>
</head>
<body class="font-body text-navy-800 bg-white">

<div class="min-h-screen grid lg:grid-cols-2">

  <!-- ===================== PANEL IZQUIERDO — FORMULARIO ===================== -->
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

      <?php if ($oauthError): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px] flex items-center gap-2.5">
          <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($oauthError) ?>
        </div>
      <?php endif; ?>

      <!-- Paso 1: credenciales -->
      <div id="stepCreds">
        <div class="eyebrow mb-4"><span class="dot"></span> ACCESO SEGURO</div>
        <h1 class="font-head font-bold text-[28px] text-navy-900 mb-2">Bienvenido de vuelta</h1>
        <p class="text-slate-500 text-[14.5px] mb-6">Ingresa tus credenciales para acceder a tu panel.</p>

        <details class="mb-6 group">
          <summary class="text-[12.5px] font-semibold text-brand-600 cursor-pointer select-none list-none flex items-center gap-1.5">
            <i class="fa-solid fa-flask"></i> Cuentas de demostración <i class="fa-solid fa-chevron-down text-[10px] group-open:rotate-180 transition"></i>
          </summary>
          <div class="grid grid-cols-3 gap-2 mt-3">
            <?php
              $demoRoles = [
                ['email'=>'admin@megaensambler.com','icon'=>'fa-crown','label'=>'Super Admin'],
                ['email'=>'gerente@megaensambler.com','icon'=>'fa-chart-line','label'=>'Gerente'],
                ['email'=>'jefeobra@megaensambler.com','icon'=>'fa-helmet-safety','label'=>'Jefe Obra'],
                ['email'=>'cliente@megaensambler.com','icon'=>'fa-house-user','label'=>'Cliente'],
                ['email'=>'proveedor@megaensambler.com','icon'=>'fa-truck-field','label'=>'Proveedor'],
                ['email'=>'contador@megaensambler.com','icon'=>'fa-file-invoice-dollar','label'=>'Contador'],
              ];
            ?>
            <?php foreach($demoRoles as $r): ?>
            <button type="button" class="chip-select text-center py-2.5" onclick="fillDemo('<?= $r['email'] ?>')">
              <i class="fa-solid <?= $r['icon'] ?> text-sm mb-1 block text-slate-500"></i>
              <span class="text-[10px] font-semibold block leading-tight"><?= $r['label'] ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <p class="text-[11.5px] text-slate-400 mt-2">Contraseña de todas: <code class="bg-slate-100 px-1.5 py-0.5 rounded">Mega2026!</code></p>
        </details>

        <form id="loginForm">
          <?= csrf_field() ?>
          <div class="mb-4">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Correo electrónico</label>
            <div class="relative">
              <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="email" name="email" id="email" required class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-4 py-3 text-sm outline-none transition">
            </div>
          </div>
          <div class="mb-3">
            <label class="text-[13px] font-semibold text-navy-700 mb-1.5 block">Contraseña</label>
            <div class="relative">
              <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
              <input type="password" name="password" id="pwd" required class="w-full bg-slate-50 border border-slate-200 focus:border-brand-500 focus:bg-white rounded-xl pl-11 pr-11 py-3 text-sm outline-none transition">
              <button type="button" onclick="togglePassword(this,'pwd')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy-700"><i class="fa-regular fa-eye"></i></button>
            </div>
          </div>
          <div id="loginError" class="hidden mb-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
          <div class="flex items-center justify-between mb-7">
            <label class="flex items-center gap-2 text-[13px] text-slate-500 cursor-pointer">
              <input type="checkbox" checked class="rounded accent-brand-600"> Recordarme
            </label>
            <a href="forgot-password.php" class="text-[13px] font-semibold text-brand-600 hover:underline">¿Olvidaste tu contraseña?</a>
          </div>
          <button type="submit" id="loginBtn" class="btn btn-primary w-full justify-center">
            <i class="fa-solid fa-shield-halved"></i> Continuar de forma segura
          </button>
        </form>

        <div class="flex items-center gap-3 my-7">
          <div class="h-px flex-1 bg-slate-200"></div>
          <span class="text-[11.5px] text-slate-400 font-medium">O CONTINÚA CON</span>
          <div class="h-px flex-1 bg-slate-200"></div>
        </div>
        <a href="auth/google-login.php" class="btn btn-outline w-full justify-center">
          <i class="fa-brands fa-google"></i> Continuar con Google
        </a>

        <p class="text-center text-[13px] text-slate-500 mt-8">¿Aún no tienes cuenta? <a href="register.php" class="text-brand-600 font-semibold hover:underline">Regístrate gratis</a></p>
      </div>

      <!-- Paso 2: 2FA -->
      <div id="step2FA" class="hidden">
        <div class="w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 text-2xl mb-6"><i class="fa-solid fa-envelope-open-text"></i></div>
        <h1 class="font-head font-bold text-[26px] text-navy-900 mb-2">Verificación en dos pasos</h1>
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

        <button onclick="verify2FA()" id="verifyBtn" class="btn btn-primary w-full justify-center mb-4">
          <i class="fa-solid fa-check-double"></i> Verificar e ingresar
        </button>
        <button type="button" onclick="backToCreds()" class="text-center text-[13px] text-slate-500 hover:text-navy-800 w-full">← Volver</button>
      </div>
    </div>
  </div>

  <!-- ===================== PANEL DERECHO — VISUAL ===================== -->
  <div class="hidden lg:flex relative overflow-hidden items-center justify-center" style="background:var(--gradient-dark)">
    <div class="tech-grid-bg dark-grid"></div>
    <div class="blob blob-red w-96 h-96 top-10 -right-10"></div>
    <div class="blob blob-cyan w-80 h-80 bottom-0 -left-10"></div>
    <div class="blob blob-violet w-72 h-72 top-1/2 right-1/3"></div>
    <div class="absolute inset-0" data-particles="30"></div>

    <div class="relative z-10 max-w-md px-10 text-center">
      <div class="glass-dark rounded-2xl p-6 mb-8 float-slow text-left">
        <div class="flex items-center justify-between mb-4">
          <span class="badge badge-red"><span class="badge-dot"></span> IA Predictiva activa</span>
          <i class="fa-solid fa-ellipsis text-white/40"></i>
        </div>
        <div class="flex items-center gap-4 mb-4">
          <div class="relative w-16 h-16">
            <svg class="progress-ring w-16 h-16" data-pct="82">
              <circle cx="32" cy="32" r="27" stroke="rgba(255,255,255,.12)" stroke-width="6"/>
              <circle class="ring-value" cx="32" cy="32" r="27" stroke="#22d3ee" stroke-width="6"/>
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-white font-head font-bold text-sm">82%</span>
          </div>
          <div>
            <div class="text-white font-semibold text-sm">Obra Torre Andes</div>
            <div class="text-white/50 text-[12px]">Sin riesgos detectados</div>
          </div>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
          <div class="bg-white/5 rounded-lg py-2"><div class="text-white font-head font-bold text-sm">120+</div><div class="text-white/40 text-[10px]">Obras</div></div>
          <div class="bg-white/5 rounded-lg py-2"><div class="text-white font-head font-bold text-sm">6</div><div class="text-white/40 text-[10px]">Roles</div></div>
          <div class="bg-white/5 rounded-lg py-2"><div class="text-white font-head font-bold text-sm">24/7</div><div class="text-white/40 text-[10px]">Monitoreo</div></div>
        </div>
      </div>

      <h2 class="font-head font-bold text-white text-[26px] leading-snug mb-3">Coordinación BIM con inteligencia de verdad</h2>
      <p class="text-slate-300 text-[14.5px]">Seguridad de banco, visibilidad por rol y automatización real — todo en un solo acceso.</p>

      <div class="flex justify-center gap-2 mt-8">
        <span class="w-8 h-1.5 rounded-full bg-brand-500"></span>
        <span class="w-1.5 h-1.5 rounded-full bg-white/25"></span>
        <span class="w-1.5 h-1.5 rounded-full bg-white/25"></span>
      </div>
    </div>
  </div>
</div>

<?php include 'partials/scripts.php'; ?>
<script>
  if (new URLSearchParams(window.location.search).get('logged_out') === '1') {
    window.addEventListener('load', () => {
      MEGA.toast('Sesión cerrada correctamente. ¡Hasta pronto! 👋', 'success');
    });
    history.replaceState({}, '', window.location.pathname);
  }

  function fillDemo(email){
    document.getElementById('email').value = email;
    document.getElementById('pwd').value = 'Mega2026!';
  }

  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    const errBox = document.getElementById('loginError');
    errBox.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando…';

    try {
      const fd = new FormData(e.target);
      const res = await fetch('actions/login.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (!data.ok) {
        errBox.textContent = data.error || 'No se pudo iniciar sesión.';
        errBox.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Continuar de forma segura';
        return;
      }

      if (data.requires_2fa) {
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
      } else {
        MEGA.toast('Bienvenido de nuevo', 'success');
        window.location.href = data.redirect;
      }
    } catch (err) {
      errBox.textContent = 'Error de conexión con el servidor.';
      errBox.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Continuar de forma segura';
    }
  });

  // Auto-avance entre inputs OTP
  document.querySelectorAll('.otp-input').forEach((input,i,arr)=>{
    input.addEventListener('input',()=>{
      if(input.value.length===1 && i<arr.length-1) arr[i+1].focus();
      if(arr[arr.length-1].value) verify2FA();
    });
    input.addEventListener('keydown',(e)=>{
      if(e.key==='Backspace' && input.value==='' && i>0) arr[i-1].focus();
    });
  });

  function backToCreds(){
    document.getElementById('step2FA').classList.add('hidden');
    document.getElementById('stepCreds').classList.remove('hidden');
  }

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
        btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Verificar e ingresar';
        document.querySelectorAll('.otp-input').forEach(i=>i.value='');
        document.querySelector('.otp-input').focus();
        return;
      }

      MEGA.toast('Verificación exitosa. Redirigiendo…','success');
      setTimeout(()=>{ window.location.href = data.redirect; }, 700);
    } catch (err) {
      errBox.textContent = 'Error de conexión con el servidor.';
      errBox.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Verificar e ingresar';
    }
  }
</script>
</body>
</html>
