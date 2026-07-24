<?php
/** Modal de confirmación de cierre de sesión. Requiere que el llamador ya haya
 *  iniciado sesión de PHP (config.php) — solo usa variables ya disponibles en scope. */
$__logoutName = $userName ?? ($me['nombre'] ?? 'de vuelta');
?>
<div id="logoutModal" class="modal-backdrop">
  <div class="modal-box p-8 text-center relative overflow-hidden">
    <div class="absolute -top-16 -right-16 w-40 h-40 rounded-full opacity-10" style="background:var(--gradient-brand)"></div>
    <div class="w-16 h-16 rounded-2xl bg-red-50 flex items-center justify-center text-brand-600 text-2xl mb-5 mx-auto relative">
      <i class="fa-solid fa-right-from-bracket"></i>
    </div>
    <h3 class="font-head font-bold text-[19px] text-navy-900 mb-2 relative">¿Cerrar sesión, <?= htmlspecialchars(explode(' ', $__logoutName)[0]) ?>?</h3>
    <p class="text-slate-500 text-[13.5px] mb-7 relative">Tu sesión se cerrará de forma segura. Podrás volver a entrar cuando quieras, incluso con otra cuenta.</p>
    <div class="flex gap-3 relative">
      <button type="button" data-close-modal class="btn btn-ghost flex-1 justify-center">Cancelar</button>
      <button type="button" id="logoutConfirmBtn" onclick="confirmLogout()" class="btn btn-primary flex-1 justify-center">
        <i class="fa-solid fa-right-from-bracket"></i> Sí, salir
      </button>
    </div>
  </div>
</div>
<script>
  function confirmLogout(){
    const btn = document.getElementById('logoutConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cerrando sesión…';
    window.location.href = '../actions/logout.php';
  }
</script>
