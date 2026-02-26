// app/static/js/dashboard.js
// ---------------------------------------------------------
// Layout privado — Sidebar premium + Tooltip + Persistência
// (Submenu/Subsidebar REMOVIDO COMPLETAMENTE)
// ---------------------------------------------------------

/* =========================
   FETCH helper
========================= */
async function apiPost(url, data = {}) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  return res;
}

/* =========================
   Utilitários
========================= */
function isMobile() {
  return window.matchMedia('(max-width: 980px)').matches;
}

function storageGet(key, fallback = null) {
  try {
    const v = localStorage.getItem(key);
    return v === null ? fallback : v;
  } catch (_) {
    return fallback;
  }
}

function storageSet(key, value) {
  try {
    localStorage.setItem(key, String(value));
  } catch (_) {}
}

/* =========================================================
   EARLY APPLY (evita flash em navegação)
   - Aplica estado do sidebar o mais cedo possível (defer)
   - Mantém variável CSS --sv-sidebar-w coerente
========================================================= */
(function earlyApplySidebarState() {
  const sidebar = document.getElementById('sidebar');
  const layout = document.getElementById('privateLayout');
  if (!sidebar || !layout) return;

  // Em mobile não usamos "collapsed"
  if (isMobile()) {
    sidebar.classList.remove('is-collapsed');
    layout.style.setProperty('--sv-sidebar-w', '260px');
    return;
  }

  // Sidebar collapsed persistido
  const shouldCollapse = (storageGet('sv_sidebar_collapsed', '0') === '1');
  if (shouldCollapse) sidebar.classList.add('is-collapsed');
  else sidebar.classList.remove('is-collapsed');

  // Variável lógica do sidebar (ajuda CSS a alinhar elementos)
  layout.style.setProperty('--sv-sidebar-w', sidebar.classList.contains('is-collapsed') ? '96px' : '260px');
})();

/* ---------------------------
   TOAST GLOBAL (PRIVATE)
   - Carrega toast.css / toast.js somente em páginas privadas
   - Idempotente (não duplica)
   - Disponibiliza helpers: window.Notify.*
--------------------------- */
function ensureToastAssets() {
  try {
    // CSS
    const cssHref = '/sistema-visa/app/static/css/toast.css';
    const hasCss = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
      .some(l => (l.getAttribute('href') || '').includes(cssHref));

    if (!hasCss) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = cssHref;
      link.setAttribute('data-sys', 'toast');
      document.head.appendChild(link);
    }

    // JS
    const jsSrc = '/sistema-visa/app/static/js/toast.js';
    const hasJs = Array.from(document.scripts)
      .some(s => (s.getAttribute('src') || '').includes(jsSrc));

    if (!hasJs) {
      const s = document.createElement('script');
      s.src = jsSrc;
      s.defer = true;
      s.setAttribute('data-sys', 'toast');
      document.head.appendChild(s);
    }

    // Helpers (não sobrescreve se já existir)
    if (!window.Notify) {
      window.Notify = {
        show(msg) {
          if (window.Toast?.show) window.Toast.show(msg);
        },
        success(msg) {
          if (window.Toast?.success) window.Toast.success(msg);
          else if (window.Toast?.show) window.Toast.show(msg);
        },
        danger(msg) {
          if (window.Toast?.danger) window.Toast.danger(msg);
          else if (window.Toast?.show) window.Toast.show(msg);
        },
        warning(msg) {
          if (window.Toast?.warning) window.Toast.warning(msg);
          else if (window.Toast?.show) window.Toast.show(msg);
        },
      };
    }
  } catch (_) {}
}

/* ---------------------------------------------------------
   TOOLTIP PREMIUM — Sidebar colapsado
   - Cria 1 tooltip e reposiciona conforme hover
   - Funciona para:
     • links do menu (a.sidebar__item)
     • botão sair (button.sidebar__logout)
--------------------------------------------------------- */
function createSidebarTooltip() {
  let tip = document.querySelector('.sv-sidebar-tip');
  if (tip) return tip;

  tip = document.createElement('div');
  tip.className = 'sv-sidebar-tip';
  tip.innerHTML = `
    <div class="sv-sidebar-tip__arrow"></div>
    <div class="sv-sidebar-tip__card" id="svSidebarTipCard"></div>
  `;
  document.body.appendChild(tip);
  return tip;
}

function setupCollapsedSidebarTooltips(sidebar) {
  if (!sidebar) return;

  const tip = createSidebarTooltip();
  const card = tip.querySelector('#svSidebarTipCard');

  let currentEl = null;
  let hideTimer = null;

  function isCollapsedDesktop() {
    return !isMobile() && sidebar.classList.contains('is-collapsed');
  }

  function clearHide() {
    if (hideTimer) window.clearTimeout(hideTimer);
    hideTimer = null;
  }

  function hideNow() {
    clearHide();
    tip.classList.remove('is-show');
    currentEl = null;
  }

  function scheduleHide() {
    clearHide();
    hideTimer = window.setTimeout(hideNow, 90);
  }

  function getLabel(el) {
    try {
      // prioridade: data-tip (ex: logout)
      const dt = (el.getAttribute('data-tip') || '').trim();
      if (dt) return dt;

      // fallback: texto do <span> dentro do link
      const sp = el.querySelector('span');
      const t = (sp?.textContent || '').trim();
      return t || '';
    } catch (_) {
      return '';
    }
  }

  function positionTipFor(el) {
    if (!el) return;

    const r = el.getBoundingClientRect();
    const gap = 12;

    // vertical centralizado no item
    const top = Math.round(r.top + (r.height / 2));
    // aparece à direita do item (com gap)
    const left = Math.round(r.right + gap);

    tip.style.left = `${left}px`;
    tip.style.top = `${top}px`;
    tip.style.transformOrigin = 'left center';
    tip.style.marginTop = `-${Math.round(tip.offsetHeight / 2)}px`;
  }

  function showFor(el) {
    if (!isCollapsedDesktop()) return;

    const label = getLabel(el);
    if (!label) return;

    clearHide();
    if (card) card.textContent = label;

    currentEl = el;
    positionTipFor(el);

    tip.classList.add('is-show');
  }

  // Delegação: captura tanto links quanto botão
  sidebar.addEventListener('mouseenter', (e) => {
    if (!isCollapsedDesktop()) return;

    const a = e.target.closest('a.sidebar__item');
    if (a) {
      showFor(a);
      return;
    }

    const b = e.target.closest('button.sidebar__logout');
    if (b) {
      showFor(b);
    }
  }, true);

  sidebar.addEventListener('mousemove', () => {
    if (!currentEl) return;
    if (!isCollapsedDesktop()) {
      hideNow();
      return;
    }
    positionTipFor(currentEl);
  }, true);

  sidebar.addEventListener('mouseleave', scheduleHide);
  sidebar.addEventListener('click', hideNow);

  window.addEventListener('scroll', () => {
    if (!currentEl) return;
    if (!isCollapsedDesktop()) {
      hideNow();
      return;
    }
    positionTipFor(currentEl);
  }, true);

  window.addEventListener('resize', () => {
    if (!currentEl) return;
    if (!isCollapsedDesktop()) {
      hideNow();
      return;
    }
    positionTipFor(currentEl);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') hideNow();
  });
}

/* =========================================================
   DOM READY
========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  // Toast global no layout privado
  ensureToastAssets();

  const layout = document.getElementById('privateLayout');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  const btnToggle = document.getElementById('btnToggleSidebar'); // hambúrguer (mobile)
  const btnEdgeToggle = document.getElementById('btnEdgeToggle'); // bolinha (desktop)
  const edgeIcon = document.getElementById('edgeToggleIcon');

  const sidebarLogo = document.getElementById('sidebarLogo');
  const btnLogout = document.getElementById('btnLogout');

  const STORAGE_KEY = 'sv_sidebar_collapsed';

  /* ---------------------------
     Helpers visuais do sidebar
  --------------------------- */
  function setLayoutSidebarWidthVar() {
    if (!layout || !sidebar) return;
    layout.style.setProperty('--sv-sidebar-w', sidebar.classList.contains('is-collapsed') ? '96px' : '260px');
  }

  function setEdgeIconByState() {
    if (!edgeIcon || !sidebar) return;
    const collapsed = sidebar.classList.contains('is-collapsed');

    edgeIcon.classList.remove('fa-chevron-left', 'fa-chevron-right');
    edgeIcon.classList.add(collapsed ? 'fa-chevron-right' : 'fa-chevron-left');
  }

  function setLogoByState() {
    if (!sidebarLogo || !sidebar) return;

    // prioridade: dataset (sys_personalizacao escreve aqui)
    const logo = sidebarLogo.dataset?.logo || sidebarLogo.getAttribute('data-logo');
    const fav  = sidebarLogo.dataset?.favicon || sidebarLogo.getAttribute('data-favicon');
    const collapsed = sidebar.classList.contains('is-collapsed');

    // mobile sempre logo normal
    if (isMobile()) {
      if (logo) sidebarLogo.src = logo;
      return;
    }

    // desktop: colapsado usa favicon (se existir)
    if (collapsed && fav) sidebarLogo.src = fav;
    else if (logo) sidebarLogo.src = logo;
  }

  function nudgeEdgeByState() {
    if (!btnEdgeToggle || !sidebar) return;

    const collapsed = sidebar.classList.contains('is-collapsed');
    btnEdgeToggle.classList.remove('is-nudge-left', 'is-nudge-right');
    btnEdgeToggle.classList.add(collapsed ? 'is-nudge-right' : 'is-nudge-left');

    window.setTimeout(() => {
      btnEdgeToggle.classList.remove('is-nudge-left', 'is-nudge-right');
    }, 220);
  }

  function applyDesktopSidebarStateFromStorage() {
    if (!sidebar) return;
    if (isMobile()) return;

    const v = storageGet(STORAGE_KEY, '0');
    if (v === '1') sidebar.classList.add('is-collapsed');
    else sidebar.classList.remove('is-collapsed');

    setLayoutSidebarWidthVar();
    setEdgeIconByState();
    setLogoByState();
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  function toggleDesktopCollapse() {
    if (!sidebar) return;

    sidebar.classList.toggle('is-collapsed');
    storageSet(STORAGE_KEY, sidebar.classList.contains('is-collapsed') ? '1' : '0');

    setLayoutSidebarWidthVar();
    setEdgeIconByState();
    setLogoByState();
    nudgeEdgeByState();

    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  /* ---------------------------
     Mobile open/close
  --------------------------- */
  function openMobileSidebar() {
    if (sidebar) sidebar.classList.remove('is-collapsed');
    setLayoutSidebarWidthVar();
    setEdgeIconByState();
    setLogoByState();

    layout?.classList.add('is-sidebar-open');
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  function closeMobileSidebar() {
    layout?.classList?.remove('is-sidebar-open');
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  /* ---------------------------
     AUTO LOGOUT POR INATIVIDADE (CLIENT-SIDE)
  --------------------------- */
  const IDLE_LIMIT_MS = 30 * 60 * 1000; // 30min
  let idleTimer = null;

  function forceToLogin() {
    window.location.href = '/sistema-visa/app/templates/login.php';
  }

  function scheduleIdleLogout() {
    if (idleTimer) clearTimeout(idleTimer);

    idleTimer = setTimeout(async () => {
      try {
        await apiPost('/sistema-visa/public_php/api/logout.php', {});
      } catch (_) {}
      forceToLogin();
    }, IDLE_LIMIT_MS);
  }

  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(evt => {
    window.addEventListener(evt, scheduleIdleLogout, { passive: true });
  });
  scheduleIdleLogout();

  // fecha sessão ao sair da página (melhor esforço)
  window.addEventListener('pagehide', () => {
    try {
      const url = '/sistema-visa/public_php/api/logout.php';
      const blob = new Blob([JSON.stringify({})], { type: 'application/json' });
      navigator.sendBeacon(url, blob);
    } catch (_) {}
  });

  /* ---------------------------
     NAV ATIVO
  --------------------------- */
  function setActiveNav() {
    const path = window.location.pathname;
    let key = '';

    if (path.includes('/dashboard.php')) key = 'dashboard';
    else if (path.includes('/lotes.php')) key = 'lotes';
    else if (path.includes('/relatorios.php')) key = 'relatorios';
    else if (path.includes('/financeiro')) key = 'financeiro'; // cobre o módulo todo
    else if (path.includes('/ferramentas')) key = 'ferramentas';

    if (!key) return;

    document.querySelectorAll('.sidebar__item').forEach(a => a.classList.remove('active'));
    const target = document.querySelector(`.sidebar__item[data-nav="${key}"]`);
    if (target) target.classList.add('active');
  }

  /* ---------------------------
     Eventos UI (sidebar)
  --------------------------- */
  if (btnToggle) {
    btnToggle.addEventListener('click', () => {
      if (!isMobile()) return;
      if (layout?.classList.contains('is-sidebar-open')) closeMobileSidebar();
      else openMobileSidebar();
    });
  }

  if (btnEdgeToggle) {
    btnEdgeToggle.addEventListener('click', () => {
      if (isMobile()) return;
      toggleDesktopCollapse();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => closeMobileSidebar());
  }

  // em mobile: clicar em link fecha drawer
  if (sidebar) {
    sidebar.addEventListener('click', (e) => {
      const link = e.target.closest('a.sidebar__item');
      if (link && isMobile()) closeMobileSidebar();
    });
  }

  // qualquer navegação (link real) fecha drawer no mobile
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (!a) return;

    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('javascript:') || href.startsWith('#')) return;

    if (isMobile() && layout?.classList.contains('is-sidebar-open')) closeMobileSidebar();
  });

  // logout
  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      try {
        await apiPost('/sistema-visa/public_php/api/logout.php', {});
      } catch (_) {}
      forceToLogin();
    });
  }

  // resize: normaliza estado
  window.addEventListener('resize', () => {
    if (isMobile()) {
      if (sidebar) sidebar.classList.remove('is-collapsed');
      setLayoutSidebarWidthVar();
      setEdgeIconByState();
      setLogoByState();
      try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
      return;
    }

    closeMobileSidebar();
    applyDesktopSidebarStateFromStorage();
  });

  /* ---------------------------
     INIT
  --------------------------- */
  setActiveNav();
  applyDesktopSidebarStateFromStorage();
  setLayoutSidebarWidthVar();
  setEdgeIconByState();
  setLogoByState();

  // remove classe de bootstrap visual (se existir)
  document.documentElement.classList.remove('sv-sidebar-collapsed');

  // tooltip premium do sidebar colapsado (inclui logout)
  setupCollapsedSidebarTooltips(sidebar);
});