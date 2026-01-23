// app/static/js/dashboard.js

async function apiPost(url, data = {}) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify(data),
  });
  return res;
}

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

document.addEventListener('DOMContentLoaded', () => {
  const layout = document.getElementById('privateLayout');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  // Mobile
  const btnToggle = document.getElementById('btnToggleSidebar');

  // Desktop
  const btnEdgeToggle = document.getElementById('btnEdgeToggle');
  const edgeIcon = document.getElementById('edgeToggleIcon');

  // Logo swap
  const sidebarLogo = document.getElementById('sidebarLogo');

  const btnLogout = document.getElementById('btnLogout');

  /* ---------------------------
     AUTO LOGOUT POR INATIVIDADE (CLIENT-SIDE)
     - Complementa o server-side
     --------------------------- */
  const IDLE_LIMIT_MS = 30 * 60 * 1000; // 30 min
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

  // reseta timer em atividades comuns
  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(evt => {
    window.addEventListener(evt, scheduleIdleLogout, { passive: true });
  });

  // inicia
  scheduleIdleLogout();

  // Logout "best effort" ao fechar aba/janela (não é garantido)
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
    else if (path.includes('/financeiro.php')) key = 'financeiro';
    else if (path.includes('/relatorios.php')) key = 'relatorios';

    if (!key) return;

    document.querySelectorAll('.sidebar__item').forEach(a => a.classList.remove('active'));
    const target = document.querySelector(`.sidebar__item[data-nav="${key}"]`);
    if (target) target.classList.add('active');
  }

  /* ---------------------------
     ÍCONE/LOGO
     --------------------------- */
  function setEdgeIconByState() {
    if (!edgeIcon || !sidebar) return;
    const collapsed = sidebar.classList.contains('is-collapsed');

    edgeIcon.classList.remove('fa-chevron-left', 'fa-chevron-right');
    edgeIcon.classList.add(collapsed ? 'fa-chevron-right' : 'fa-chevron-left');
  }

  function setLogoByState() {
    if (!sidebarLogo || !sidebar) return;

    const logo = sidebarLogo.getAttribute('data-logo');
    const fav = sidebarLogo.getAttribute('data-favicon');
    const collapsed = sidebar.classList.contains('is-collapsed');

    // No mobile sempre logo normal
    if (isMobile()) {
      if (logo) sidebarLogo.src = logo;
      return;
    }

    if (collapsed && fav) sidebarLogo.src = fav;
    else if (logo) sidebarLogo.src = logo;
  }

  /* ---------------------------
     MOBILE OPEN/CLOSE
     --------------------------- */
  function openMobileSidebar() {
    // no mobile, sempre expandido
    if (sidebar) sidebar.classList.remove('is-collapsed');
    setEdgeIconByState();
    setLogoByState();
    layout?.classList.add('is-sidebar-open');
  }

  function closeMobileSidebar() {
    layout?.classList.remove('is-sidebar-open');
  }

  /* ---------------------------
     DESKTOP COLLAPSE (persistência)
     --------------------------- */
  const STORAGE_KEY = 'sv_sidebar_collapsed';

  function applyDesktopSidebarStateFromStorage() {
    if (!sidebar) return;
    if (isMobile()) return;

    const v = storageGet(STORAGE_KEY, '0');
    if (v === '1') sidebar.classList.add('is-collapsed');
    else sidebar.classList.remove('is-collapsed');

    setEdgeIconByState();
    setLogoByState();
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

  function toggleDesktopCollapse() {
    if (!sidebar) return;
    sidebar.classList.toggle('is-collapsed');

    storageSet(STORAGE_KEY, sidebar.classList.contains('is-collapsed') ? '1' : '0');

    setEdgeIconByState();
    setLogoByState();
    nudgeEdgeByState();
  }

  /* ---------------------------
     EVENTOS
     --------------------------- */

  // mobile: hambúrguer
  if (btnToggle) {
    btnToggle.addEventListener('click', () => {
      if (!isMobile()) return;
      if (layout?.classList.contains('is-sidebar-open')) closeMobileSidebar();
      else openMobileSidebar();
    });
  }

  // desktop: seta
  if (btnEdgeToggle) {
    btnEdgeToggle.addEventListener('click', () => {
      if (isMobile()) return;
      toggleDesktopCollapse();
    });
  }

  // overlay fecha no mobile
  if (overlay) {
    overlay.addEventListener('click', () => closeMobileSidebar());
  }

  // ao clicar em item no mobile, fecha sidebar
  if (sidebar) {
    sidebar.addEventListener('click', (e) => {
      const link = e.target.closest('a.sidebar__item');
      if (link && isMobile()) closeMobileSidebar();
    });
  }

  // ao navegar (clicar em link dentro do app), no mobile sempre fecha
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (!a) return;

    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('javascript:') || href.startsWith('#')) return;

    if (isMobile() && layout?.classList.contains('is-sidebar-open')) closeMobileSidebar();
  });

  // Logout
  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      try {
        await apiPost('/sistema-visa/public_php/api/logout.php', {});
      } catch (e) {}
      forceToLogin();
    });
  }

  // resize:
  // - se entrar em mobile, remove colapso (evita quebrar)
  // - se sair do mobile, aplica estado do desktop salvo
  window.addEventListener('resize', () => {
    if (isMobile()) {
      if (sidebar) sidebar.classList.remove('is-collapsed');
      setEdgeIconByState();
      setLogoByState();
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
  setEdgeIconByState();
  setLogoByState();
});