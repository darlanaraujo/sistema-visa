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

  const btnToggle = document.getElementById('btnToggleSidebar');
  const btnEdgeToggle = document.getElementById('btnEdgeToggle');
  const edgeIcon = document.getElementById('edgeToggleIcon');

  const sidebarLogo = document.getElementById('sidebarLogo');
  const btnLogout = document.getElementById('btnLogout');

  /* ---------------------------
     AUTO LOGOUT POR INATIVIDADE (CLIENT-SIDE)
     --------------------------- */
  const IDLE_LIMIT_MS = 30 * 60 * 1000;
  let idleTimer = null;

  function forceToLogin() {
    window.location.href = '/sistema-visa/app/templates/login.php';
  }

  function scheduleIdleLogout() {
    if (idleTimer) clearTimeout(idleTimer);
    idleTimer = setTimeout(async () => {
      try { await apiPost('/sistema-visa/public_php/api/logout.php', {}); } catch (_) {}
      forceToLogin();
    }, IDLE_LIMIT_MS);
  }

  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(evt => {
    window.addEventListener(evt, scheduleIdleLogout, { passive: true });
  });
  scheduleIdleLogout();

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
    else if (path.includes('/financeiro')) key = 'financeiro'; // <- cobre todas as telas do módulo

    if (!key) return;

    document.querySelectorAll('.sidebar__item').forEach(a => a.classList.remove('active'));
    const target = document.querySelector(`.sidebar__item[data-nav="${key}"]`);
    if (target) target.classList.add('active');
  }

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

    if (isMobile()) {
      if (logo) sidebarLogo.src = logo;
      return;
    }

    if (collapsed && fav) sidebarLogo.src = fav;
    else if (logo) sidebarLogo.src = logo;
  }

  function openMobileSidebar() {
    if (sidebar) sidebar.classList.remove('is-collapsed');
    setEdgeIconByState();
    setLogoByState();
    layout?.classList.add('is-sidebar-open');
  }

  function closeMobileSidebar() {
    layout?.classList.remove('is-sidebar-open');
  }

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

  if (sidebar) {
    sidebar.addEventListener('click', (e) => {
      const link = e.target.closest('a.sidebar__item');
      if (link && isMobile()) closeMobileSidebar();
    });
  }

  document.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (!a) return;

    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('javascript:') || href.startsWith('#')) return;

    if (isMobile() && layout?.classList.contains('is-sidebar-open')) closeMobileSidebar();
  });

  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      try { await apiPost('/sistema-visa/public_php/api/logout.php', {}); } catch (e) {}
      forceToLogin();
    });
  }

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

  setActiveNav();
  applyDesktopSidebarStateFromStorage();
  setEdgeIconByState();
  setLogoByState();
});