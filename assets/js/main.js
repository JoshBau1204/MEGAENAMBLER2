/* ==========================================================================
   MEGAENSAMBLER — main.js
   Comportamiento compartido: reveals, contadores, sidebar, toasts, modales,
   partículas, spotlight cards, gráficos base.
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  initRevealOnScroll();
  initCounters();
  initSidebarToggle();
  initSpotlightCards();
  initCursorGlow();
  initParticles();
  initSwitches();
  initDropdowns();
  initTabs();
  initModals();
  initRingProgress();
  initNavbarScroll();
  initMobileMenu();
  initImageFallbacks();
  window.MEGA = { toast, openModal, closeModal };
});

/* Reveal on scroll ---------------------------------------------------- */
function initRevealOnScroll(){
  const els = document.querySelectorAll('[data-reveal]');
  if(!els.length) return;
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        const delay = e.target.getAttribute('data-delay') || 0;
        setTimeout(()=>e.target.classList.add('in'), delay);
        io.unobserve(e.target);
      }
    });
  },{threshold:.15});
  els.forEach(el=>io.observe(el));
}

/* Animated counters ----------------------------------------------------- */
function initCounters(){
  const els = document.querySelectorAll('[data-counter]');
  if(!els.length) return;
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        animateCounter(e.target);
        io.unobserve(e.target);
      }
    });
  },{threshold:.4});
  els.forEach(el=>io.observe(el));
}
function animateCounter(el){
  const target = parseFloat(el.getAttribute('data-counter'));
  const decimals = el.getAttribute('data-decimals') ? parseInt(el.getAttribute('data-decimals')) : 0;
  const suffix = el.getAttribute('data-suffix') || '';
  const dur = 1600;
  const start = performance.now();
  function tick(now){
    const p = Math.min((now-start)/dur,1);
    const eased = 1-Math.pow(1-p,3);
    const val = target*eased;
    el.textContent = val.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g,',') + suffix;
    if(p<1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

/* Sidebar (dashboards) --------------------------------------------------- */
function initSidebarToggle(){
  const btn = document.getElementById('sidebarToggle');
  const shell = document.querySelector('.app-shell');
  if(!btn || !shell) return;
  btn.addEventListener('click', ()=>{
    if(window.innerWidth <= 1100){
      shell.classList.toggle('sidebar-open');
    } else {
      shell.classList.toggle('sidebar-collapsed');
    }
  });
  document.addEventListener('click',(e)=>{
    if(window.innerWidth<=1100 && shell.classList.contains('sidebar-open')){
      if(!e.target.closest('.sidebar') && !e.target.closest('#sidebarToggle')){
        shell.classList.remove('sidebar-open');
      }
    }
  });
}

/* Mobile nav (landing/login) --------------------------------------------- */
function initMobileMenu(){
  const btn = document.getElementById('mobileMenuBtn');
  const menu = document.getElementById('mobileMenu');
  if(!btn || !menu) return;
  btn.addEventListener('click', ()=>{
    menu.classList.toggle('hidden');
    btn.classList.toggle('open');
  });
}

/* Fallback visual si una imagen externa (picsum/ui-avatars/placehold) no carga --- */
function initImageFallbacks(){
  document.querySelectorAll('img').forEach(img=>{
    const applyFallback = () => {
      if(img.dataset.fallbackApplied) return;
      img.dataset.fallbackApplied = '1';
      img.style.background = 'linear-gradient(135deg,#d91e2c,#7c6cf6)';
      img.removeAttribute('srcset');
    };
    if(img.complete && img.naturalWidth === 0){ applyFallback(); return; }
    img.addEventListener('error', applyFallback, { once:true });
  });
}

function initNavbarScroll(){
  const nav = document.getElementById('siteNav');
  if(!nav) return;
  window.addEventListener('scroll', ()=>{
    if(window.scrollY > 24) nav.classList.add('nav-scrolled');
    else nav.classList.remove('nav-scrolled');
  });
}

/* Spotlight hover cards --------------------------------------------------- */
function initSpotlightCards(){
  document.querySelectorAll('.spotlight-card').forEach(card=>{
    card.addEventListener('mousemove', e=>{
      const rect = card.getBoundingClientRect();
      card.style.setProperty('--mx', `${e.clientX-rect.left}px`);
      card.style.setProperty('--my', `${e.clientY-rect.top}px`);
    });
  });
}

/* Cursor glow (hero sections) --------------------------------------------- */
function initCursorGlow(){
  const glow = document.querySelector('.cursor-glow');
  if(!glow) return;
  window.addEventListener('mousemove', e=>{
    glow.style.left = e.clientX+'px';
    glow.style.top = e.clientY+'px';
  });
}

/* Floating particles generator -------------------------------------------- */
function initParticles(){
  document.querySelectorAll('[data-particles]').forEach(container=>{
    const count = parseInt(container.getAttribute('data-particles')) || 24;
    for(let i=0;i<count;i++){
      const p = document.createElement('span');
      p.className = 'particle';
      const size = Math.random()*4+2;
      p.style.width = size+'px';
      p.style.height = size+'px';
      p.style.left = Math.random()*100+'%';
      p.style.bottom = (Math.random()*20)+'px';
      p.style.animationDuration = (Math.random()*10+8)+'s';
      p.style.animationDelay = (Math.random()*10)+'s';
      container.appendChild(p);
    }
  });
}

/* Toggle switches ----------------------------------------------------- */
function initSwitches(){
  document.querySelectorAll('.switch').forEach(sw=>{
    sw.addEventListener('click', ()=> sw.classList.toggle('on'));
  });
}

/* Dropdowns ----------------------------------------------------------- */
function initDropdowns(){
  document.querySelectorAll('[data-dropdown-trigger]').forEach(trigger=>{
    const id = trigger.getAttribute('data-dropdown-trigger');
    const menu = document.getElementById(id);
    if(!menu) return;
    trigger.addEventListener('click', (e)=>{
      e.stopPropagation();
      document.querySelectorAll('.dropdown-menu.show').forEach(m=>{ if(m!==menu) m.classList.remove('show'); });
      menu.classList.toggle('show');
    });
  });
  document.addEventListener('click', ()=>{
    document.querySelectorAll('.dropdown-menu.show').forEach(m=>m.classList.remove('show'));
  });
}

/* Tabs ------------------------------------------------------------------ */
function initTabs(){
  document.querySelectorAll('[data-tabs]').forEach(group=>{
    const buttons = group.querySelectorAll('[data-tab]');
    buttons.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const target = btn.getAttribute('data-tab');
        group.querySelectorAll('[data-tab]').forEach(b=>b.classList.remove('tab-active'));
        btn.classList.add('tab-active');
        const panelGroup = group.getAttribute('data-tabs');
        document.querySelectorAll(`[data-tab-panel][data-tabs-group="${panelGroup}"]`).forEach(p=>{
          p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== target);
        });
      });
    });
  });
}

/* Modals ------------------------------------------------------------------ */
function initModals(){
  document.querySelectorAll('[data-open-modal]').forEach(btn=>{
    btn.addEventListener('click', ()=> openModal(btn.getAttribute('data-open-modal')));
  });
  document.querySelectorAll('[data-close-modal]').forEach(btn=>{
    btn.addEventListener('click', ()=> closeModal(btn.closest('.modal-backdrop').id));
  });
  document.querySelectorAll('.modal-backdrop').forEach(backdrop=>{
    backdrop.addEventListener('click', (e)=>{ if(e.target===backdrop) closeModal(backdrop.id); });
  });
}
function openModal(id){ document.getElementById(id)?.classList.add('show'); document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id)?.classList.remove('show'); document.body.style.overflow=''; }

/* Toast notifications -------------------------------------------------- */
function toast(message, type='info'){
  let stack = document.getElementById('toast-stack');
  if(!stack){
    stack = document.createElement('div');
    stack.id = 'toast-stack';
    document.body.appendChild(stack);
  }
  const icons = { info:'fa-circle-info', success:'fa-circle-check', warning:'fa-triangle-exclamation', error:'fa-circle-xmark' };
  const colors = { info:'#22d3ee', success:'#22a35a', warning:'#f59e0b', error:'#ef2d3b' };
  const el = document.createElement('div');
  el.className = 'toast';
  el.innerHTML = `<i class="fa-solid ${icons[type]||icons.info}" style="color:${colors[type]||colors.info}"></i><span style="font-size:14px">${message}</span>`;
  stack.appendChild(el);
  setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateX(40px)'; el.style.transition='all .4s'; setTimeout(()=>el.remove(),400); }, 3600);
}

/* Radial ring progress (SVG) --------------------------------------------- */
function initRingProgress(){
  document.querySelectorAll('.progress-ring').forEach(ring=>{
    const circle = ring.querySelector('circle.ring-value');
    if(!circle) return;
    const radius = circle.r.baseVal.value;
    const circumference = 2*Math.PI*radius;
    const pct = parseFloat(ring.getAttribute('data-pct'))||0;
    circle.style.strokeDasharray = `${circumference} ${circumference}`;
    circle.style.strokeDashoffset = circumference;
    const io = new IntersectionObserver(entries=>{
      entries.forEach(e=>{
        if(e.isIntersecting){
          circle.style.strokeDashoffset = circumference - (pct/100)*circumference;
          io.unobserve(ring);
        }
      });
    },{threshold:.5});
    io.observe(ring);
  });
}

/* Chart.js shared defaults ------------------------------------------------ */
function chartDefaults(){
  if(typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Inter',sans-serif";
  Chart.defaults.color = '#67758a';
  Chart.defaults.borderColor = '#eef1f6';
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 8;
  Chart.defaults.plugins.legend.labels.boxHeight = 8;
}
window.chartDefaults = chartDefaults;

/* Simple client-side table search/filter ---------------------------------- */
function tableSearch(inputId, tableId){
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if(!input || !table) return;
  input.addEventListener('input', ()=>{
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row=>{
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}
window.tableSearch = tableSearch;

/* Password visibility toggle ------------------------------------------- */
function togglePassword(btnEl, inputId){
  const input = document.getElementById(inputId);
  const icon = btnEl.querySelector('i');
  if(input.type === 'password'){ input.type='text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
  else { input.type='password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
window.togglePassword = togglePassword;
