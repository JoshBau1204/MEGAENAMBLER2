<?php
/**
 * Cabecera compartida. Variables esperadas antes del include:
 * $pageTitle (string), $baseUrl (string, ruta relativa a la raíz, ej: "." o "..")
 */
if(!isset($baseUrl)) $baseUrl = '.';
if(!isset($pageTitle)) $pageTitle = 'MegaEnsambler';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> · Grupo MegaEnsambler S.A.C.</title>
<meta name="description" content="Plataforma inteligente de gestión de obras y coordinación BIM — Grupo MegaEnsambler S.A.C.">
<link rel="icon" type="image/png" href="<?= $baseUrl ?>/assets/img/logo.png">

<!-- Fuentes -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Iconos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- Tailwind (utilidades) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { head: ['Sora','sans-serif'], body: ['Inter','sans-serif'] },
        colors: {
          brand: { 50:'#fff0f1', 400:'#ff5c62', 500:'#ef2d3b', 600:'#d91e2c', 700:'#af1522' },
          navy: { 600:'#2a3854', 700:'#1c2841', 800:'#121b30', 900:'#0b1220', 950:'#060a17' },
          tech: { 400:'#22d3ee', 500:'#06b6d4', 600:'#0891b2' }
        },
        boxShadow: { glow: '0 8px 30px rgba(217,30,44,.28)' }
      }
    }
  }
</script>

<!-- Animate on scroll -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

<!-- Estilos propios -->
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
