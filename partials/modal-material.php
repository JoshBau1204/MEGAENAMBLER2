<?php
/**
 * Modal "Solicitar material" reutilizable.
 * Variables esperadas antes del include:
 *   $obrasParaMaterial       -> array de obras [id, nombre] seleccionables
 *   $proveedoresDisponibles  -> array de usuarios proveedor [id, nombre]
 */
$obrasParaMaterial ??= [];
$proveedoresDisponibles ??= [];
$materialesComunes = [
    'Cemento tipo I', 'Fierro corrugado 1/2"', 'Fierro corrugado 3/8"', 'Ladrillo King Kong',
    'Arena gruesa', 'Arena fina', 'Piedra chancada', 'Madera tornillo', 'Alambre negro #16',
    'Clavos de 3"', 'Yeso en bolsa', 'Pintura látex', 'Tubería PVC 4"', 'Cable eléctrico THW 12',
    'Cerámico/Porcelanato', 'Concreto premezclado',
];
?>
<div id="modalMaterial" class="modal-backdrop">
  <div class="modal-box p-7">
    <form id="formMaterial">
      <?= csrf_field() ?>
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-head font-bold text-lg text-navy-900">Solicitar material</h3>
        <button type="button" data-close-modal class="btn-icon btn-ghost !w-9 !h-9"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="space-y-4">
        <?php if (count($obrasParaMaterial) > 1 || empty($obraFijaId)): ?>
        <select name="obra_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
          <option value="">Selecciona la obra…</option>
          <?php foreach($obrasParaMaterial as $o): ?><option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nombre']) ?></option><?php endforeach; ?>
        </select>
        <?php else: ?>
          <input type="hidden" name="obra_id" value="<?= (int)$obraFijaId ?>">
          <div class="text-[13px] text-slate-500 bg-slate-50 rounded-xl px-4 py-3"><i class="fa-solid fa-building mr-1.5"></i><?= htmlspecialchars($obrasParaMaterial[0]['nombre'] ?? '') ?></div>
        <?php endif; ?>

        <input list="materialesComunes" name="material" required placeholder="Material (ej. Cemento tipo I)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
        <datalist id="materialesComunes">
          <?php foreach($materialesComunes as $m): ?><option value="<?= htmlspecialchars($m) ?>"><?php endforeach; ?>
        </datalist>

        <input type="text" name="cantidad" required placeholder="Cantidad (ej. 120 bolsas, 2.4 ton)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">

        <select name="proveedor_user_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
          <option value="">Asignar proveedor (opcional)…</option>
          <?php foreach($proveedoresDisponibles as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option><?php endforeach; ?>
        </select>

        <input type="text" name="eta" placeholder="Fecha/hora estimada de llegada (opcional)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-brand-500">
      </div>
      <div id="materialError" class="hidden mt-4 p-3 rounded-xl bg-red-50 border border-red-100 text-brand-600 text-[13px]"></div>
      <div class="flex gap-3 mt-7">
        <button type="button" data-close-modal class="btn btn-ghost flex-1 justify-center">Cancelar</button>
        <button type="submit" class="btn btn-primary flex-1 justify-center"><i class="fa-solid fa-boxes-stacked"></i> Solicitar</button>
      </div>
    </form>
  </div>
</div>
<script>
  document.getElementById('formMaterial').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('materialError');
    errBox.classList.add('hidden');
    const res = await fetch('../actions/materiales-crear.php', { method:'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (!data.ok) { errBox.textContent = data.error; errBox.classList.remove('hidden'); return; }
    MEGA.toast('Material solicitado correctamente', 'success');
    setTimeout(()=>location.reload(), 700);
  });
</script>
