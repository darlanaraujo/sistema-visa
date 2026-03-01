// app/static/js/base_private.js
// ---------------------------------------------------------
// Layout privado — Sidebar premium + Persistência + Topbar + Alerts + Theme
// Componentes reutilizáveis (Tooltip/Render alerts) ficam em ui_components.js
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
========================================================= */
(function earlyApplySidebarState() {
  const sidebar = document.getElementById('sidebar');
  const layout = document.getElementById('privateLayout');
  if (!sidebar || !layout) return;

  if (isMobile()) {
    sidebar.classList.remove('is-collapsed');
    layout.style.setProperty('--sv-sidebar-w', '260px');
    return;
  }

  const shouldCollapse = (storageGet('sv_sidebar_collapsed', '0') === '1');
  if (shouldCollapse) sidebar.classList.add('is-collapsed');
  else sidebar.classList.remove('is-collapsed');

  layout.style.setProperty('--sv-sidebar-w', sidebar.classList.contains('is-collapsed') ? '96px' : '260px');
})();

/* ---------------------------
   ASSETS: TOAST (mantém como está)
--------------------------- */
function ensureToastAssets() {
  try {
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

    if (!window.Notify) {
      window.Notify = {
        show(msg) { if (window.Toast?.show) window.Toast.show(msg); },
        success(msg) { if (window.Toast?.success) window.Toast.success(msg); else if (window.Toast?.show) window.Toast.show(msg); },
        danger(msg) { if (window.Toast?.danger) window.Toast.danger(msg); else if (window.Toast?.show) window.Toast.show(msg); },
        warning(msg) { if (window.Toast?.warning) window.Toast.warning(msg); else if (window.Toast?.show) window.Toast.show(msg); },
      };
    }
  } catch (_) {}
}

/* ---------------------------
   ASSETS: UI COMPONENTS (novo)
   - ui_components.css
   - ui_components.js  (window.UIComponents)
--------------------------- */
function ensureUIComponentsAssets() {
  try {
    const cssHref = '/sistema-visa/app/static/css/ui_components.css';
    const hasCss = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
      .some(l => (l.getAttribute('href') || '').includes(cssHref));

    if (!hasCss) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = cssHref;
      link.setAttribute('data-sys', 'ui_components');
      document.head.appendChild(link);
    }

    const jsSrc = '/sistema-visa/app/static/js/ui_components.js';
    const hasJs = Array.from(document.scripts)
      .some(s => (s.getAttribute('src') || '').includes(jsSrc));

    if (!hasJs) {
      const s = document.createElement('script');
      s.src = jsSrc;
      s.defer = true;
      s.setAttribute('data-sys', 'ui_components');
      document.head.appendChild(s);
    }
  } catch (_) {}
}

function scheduleInitUIComponents(sidebarEl) {
  // Aguarda o script ser carregado (foi injetado com defer)
  const tries = [0, 50, 140, 260, 420, 700];

  tries.forEach((ms) => {
    window.setTimeout(() => {
      try {
        if (!window.UIComponents?.initTooltips) return;
        window.UIComponents.initTooltips({ sidebarEl });
      } catch (_) {}
    }, ms);
  });
}

/* =========================================================
   DOM READY
========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  ensureToastAssets();
  ensureUIComponentsAssets();

  const layout = document.getElementById('privateLayout');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  const btnToggle = document.getElementById('btnToggleSidebar');
  const btnEdgeToggle = document.getElementById('btnEdgeToggle');
  const edgeIcon = document.getElementById('edgeToggleIcon');

  const sidebarLogo = document.getElementById('sidebarLogo');
  const topbarLogo = document.getElementById('topbarLogo');

  const btnLogout = document.getElementById('btnLogout');

  const STORAGE_KEY = 'sv_sidebar_collapsed';

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

  function setLogosByState() {
    if (sidebarLogo && sidebar) {
      const logo = sidebarLogo.dataset?.logo || sidebarLogo.getAttribute('data-logo');
      const fav  = sidebarLogo.dataset?.favicon || sidebarLogo.getAttribute('data-favicon');
      const collapsed = sidebar.classList.contains('is-collapsed');

      if (isMobile()) {
        if (logo) sidebarLogo.src = logo;
      } else {
        if (collapsed && fav) sidebarLogo.src = fav;
        else if (logo) sidebarLogo.src = logo;
      }
    }

    // topbar logo controlado por IIFE
    if (topbarLogo) {
      const logo = topbarLogo.dataset?.logo || topbarLogo.getAttribute('data-logo') || topbarLogo.getAttribute('src');
      if (logo && !topbarLogo.getAttribute('src')) topbarLogo.src = logo;
    }
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
    setLogosByState();
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  function toggleDesktopCollapse() {
    if (!sidebar) return;

    sidebar.classList.toggle('is-collapsed');
    storageSet(STORAGE_KEY, sidebar.classList.contains('is-collapsed') ? '1' : '0');

    setLayoutSidebarWidthVar();
    setEdgeIconByState();
    setLogosByState();
    nudgeEdgeByState();

    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  function openMobileSidebar() {
    if (sidebar) sidebar.classList.remove('is-collapsed');
    setLayoutSidebarWidthVar();
    setEdgeIconByState();
    setLogosByState();

    layout?.classList.add('is-sidebar-open');
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  function closeMobileSidebar() {
    layout?.classList?.remove('is-sidebar-open');
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  /* ---------------------------
     AUTO LOGOUT POR INATIVIDADE
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

  function setActiveNav() {
    const path = window.location.pathname;
    let key = '';

    if (path.includes('/dashboard.php')) key = 'dashboard';
    else if (path.includes('/lotes.php')) key = 'lotes';
    else if (path.includes('/relatorios.php')) key = 'relatorios';
    else if (path.includes('/financeiro')) key = 'financeiro';
    else if (path.includes('/ferramentas')) key = 'ferramentas';

    if (!key) return;

    document.querySelectorAll('.sidebar__item').forEach(a => a.classList.remove('active'));
    const target = document.querySelector(`.sidebar__item[data-nav="${key}"]`);
    if (target) target.classList.add('active');
  }

  /* =========================================================
     TOPBAR — THEME + ALERTS
  ========================================================= */
  const SYS_PREFS_KEY = 'tools_sys_prefs_v2';

  const btnThemeToggle = document.getElementById('btnThemeToggle');
  const themeToggleIcon = document.getElementById('themeToggleIcon');

  const btnAlerts = document.getElementById('btnAlerts');
  const btnAlertsClose = document.getElementById('btnAlertsClose');
  const alertsPopover = document.getElementById('alertsPopover');
  const alertsBadge = document.getElementById('alertsBadge');
  const alertsBody = document.getElementById('alertsBody');

  // ✅ Padroniza tooltip do topbar no mesmo modelo do sidebar
  // (usa UIComponents tooltip via [data-tip])
  try {
    if (btnThemeToggle && !btnThemeToggle.getAttribute('data-tip')) btnThemeToggle.setAttribute('data-tip', 'Alterar tema');
    if (btnAlerts && !btnAlerts.getAttribute('data-tip')) btnAlerts.setAttribute('data-tip', 'Alertas');
  } catch (_) {}

  function safeJsonParse(raw, fallback) {
    try {
      const v = JSON.parse(raw);
      return v && typeof v === 'object' ? v : fallback;
    } catch (_) {
      return fallback;
    }
  }

  function getPrefs() {
    try { return safeJsonParse(localStorage.getItem(SYS_PREFS_KEY), null) || null; }
    catch (_) { return null; }
  }

  function setPrefs(nextPrefs) {
    try {
      localStorage.setItem(SYS_PREFS_KEY, JSON.stringify(nextPrefs));
      return true;
    } catch (_) {
      return false;
    }
  }

  function resolveAutoMode() {
    try {
      return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
    } catch (_) {
      return 'light';
    }
  }

  function normalizeMode(v) {
    const s = String(v || '').toLowerCase().trim();
    if (s === 'dark') return 'dark';
    if (s === 'light') return 'light';
    return 'auto';
  }

  function applyThemeLocal(modeRaw) {
    const m = normalizeMode(modeRaw);
    const finalMode = (m === 'auto') ? resolveAutoMode() : m;

    document.documentElement.setAttribute('data-theme', finalMode);
    document.documentElement.classList.toggle('theme-dark', finalMode === 'dark');
    document.documentElement.classList.toggle('theme-light', finalMode !== 'dark');

    if (themeToggleIcon) {
      themeToggleIcon.classList.remove('fa-moon', 'fa-sun');
      themeToggleIcon.classList.add(finalMode === 'dark' ? 'fa-sun' : 'fa-moon');
    }
  }

  function getCurrentThemeModeFromDOM() {
    const dt = (document.documentElement.getAttribute('data-theme') || '').trim();
    return dt === 'dark' ? 'dark' : 'light';
  }

  function toggleTheme() {
    const prefs = getPrefs() || {};
    prefs.theme = prefs.theme && typeof prefs.theme === 'object' ? prefs.theme : {};

    const currentDom = getCurrentThemeModeFromDOM();
    const next = (currentDom === 'dark') ? 'light' : 'dark';

    prefs.theme.mode = next;
    setPrefs(prefs);

    applyThemeLocal(next);

    try { window.SysUIBootstrap?.refresh?.(); } catch (_) {}
    try { window.dispatchEvent(new CustomEvent('sys:prefs:applied', { detail: { key: SYS_PREFS_KEY } })); } catch (_) {}
  }

  function syncThemeButton() {
    const prefs = getPrefs();
    const fromPrefs = prefs?.theme?.mode;
    if (fromPrefs) applyThemeLocal(fromPrefs);
    else applyThemeLocal(getCurrentThemeModeFromDOM());
  }

  function currentModuleFromRoute() {
    const path = window.location.pathname;

    if (path.includes('/financeiro')) return 'financeiro';
    if (path.includes('/lotes.php')) return 'lotes';
    if (path.includes('/relatorios.php')) return 'relatorios';
    if (path.includes('/dashboard.php')) return 'dashboard';
    if (path.includes('/ferramentas')) return 'ferramentas';

    return 'default';
  }

  function setAlertsCount(n) {
    const count = Math.max(0, Number(n || 0));

    if (btnAlerts) btnAlerts.classList.toggle('has-alerts', count > 0);

    if (!alertsBadge) return;

    if (count <= 0) {
      alertsBadge.style.display = 'none';
      alertsBadge.textContent = '0';
      alertsBadge.setAttribute('aria-hidden', 'true');
      return;
    }

    alertsBadge.style.display = 'inline-flex';
    alertsBadge.textContent = String(count);
    alertsBadge.setAttribute('aria-hidden', 'false');
  }

  function setAlertsEmpty(msg = 'Sem alertas no momento.') {
    if (!alertsBody) return;
    const safe = window.UIComponents?.escapeHtml ? window.UIComponents.escapeHtml(msg) : String(msg);
    alertsBody.innerHTML = `<div class="topbar-popover__empty">${safe}</div>`;
  }

  function renderAlertsPayload(payload) {
    if (!alertsBody) return;

    if (!window.UIComponents?.renderAlerts) {
      // fallback: vazio, se UIComponents ainda não carregou
      setAlertsEmpty('Carregando...');
      return;
    }

    const emptyHtml = `<div class="topbar-popover__empty">${window.UIComponents.escapeHtml('Sem alertas no momento.')}</div>`;
    window.UIComponents.renderAlerts(alertsBody, payload || {}, { emptyHtml });
  }

  // Cache simples (45s)
  const ALERTS_CACHE_TTL = 45 * 1000;
  const alertsCache = new Map(); // key -> { ts, data }
  let alertsInFlight = null;

  async function fetchAlerts(moduleKey) {
    const url = `/sistema-visa/public_php/api/alerts.php?module=${encodeURIComponent(moduleKey)}`;

    const res = await fetch(url, {
      method: 'GET',
      credentials: 'include',
      headers: { 'Accept': 'application/json' },
    });

    if (res.status === 401) {
      return { ok: false, error: 'unauthorized' };
    }

    const json = await res.json().catch(() => null);
    return json || { ok: false, error: 'bad_json' };
  }

  async function loadAlerts() {
    const moduleKey = currentModuleFromRoute();
    const cacheKey = `m:${moduleKey}`;

    const cached = alertsCache.get(cacheKey);
    const now = Date.now();
    if (cached && (now - cached.ts) < ALERTS_CACHE_TTL) {
      const payload = cached.data || {};
      const count = Array.isArray(payload?.alerts) ? payload.alerts.length : 0;
      setAlertsCount(count);
      renderAlertsPayload(payload);
      return;
    }

    if (alertsInFlight) return alertsInFlight;

    alertsInFlight = (async () => {
      const json = await fetchAlerts(moduleKey);

      if (!json || json.ok !== true) {
        if (json?.error === 'unauthorized') {
          setAlertsCount(0);
          setAlertsEmpty('Sessão expirada. Recarregue a página.');
          return;
        }

        setAlertsCount(0);
        setAlertsEmpty('Não foi possível carregar os alertas.');
        return;
      }

      const alerts = Array.isArray(json.alerts) ? json.alerts : [];
      alertsCache.set(cacheKey, { ts: Date.now(), data: json });

      setAlertsCount(alerts.length);
      renderAlertsPayload(json);
    })().finally(() => {
      alertsInFlight = null;
    });

    return alertsInFlight;
  }

  function isAlertsOpen() {
    return Boolean(alertsPopover && alertsPopover.classList.contains('is-open'));
  }

  function openAlerts() {
    if (!alertsPopover) return;

    alertsPopover.classList.add('is-open');
    alertsPopover.setAttribute('aria-hidden', 'false');

    loadAlerts().catch(() => {});
  }

  function closeAlerts() {
    if (!alertsPopover) return;

    alertsPopover.classList.remove('is-open');
    alertsPopover.setAttribute('aria-hidden', 'true');
  }

  function toggleAlerts() {
    if (isAlertsOpen()) closeAlerts();
    else openAlerts();
  }

  // Eventos do theme
  if (btnThemeToggle) btnThemeToggle.addEventListener('click', () => toggleTheme());
  syncThemeButton();

  // Eventos do popover de alertas
  if (btnAlerts) btnAlerts.addEventListener('click', (e) => { e.preventDefault(); toggleAlerts(); });
  if (btnAlertsClose) btnAlertsClose.addEventListener('click', (e) => { e.preventDefault(); closeAlerts(); });

  // click fora fecha
  document.addEventListener('click', (e) => {
    if (!alertsPopover || !btnAlerts) return;
    if (!isAlertsOpen()) return;

    const insidePopover = e.target.closest('#alertsPopover');
    const insideButton = e.target.closest('#btnAlerts');
    if (insidePopover || insideButton) return;

    closeAlerts();
  });

  // ESC fecha
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isAlertsOpen()) closeAlerts();
  });

  // Preload leve
  loadAlerts().catch(() => {});

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

  if (overlay) overlay.addEventListener('click', () => closeMobileSidebar());

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
      try { await apiPost('/sistema-visa/public_php/api/logout.php', {}); } catch (_) {}
      forceToLogin();
    });
  }

  window.addEventListener('resize', () => {
    if (isMobile()) {
      if (sidebar) sidebar.classList.remove('is-collapsed');
      setLayoutSidebarWidthVar();
      setEdgeIconByState();
      setLogosByState();
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
  setLogosByState();

  document.documentElement.classList.remove('sv-sidebar-collapsed');

  // Inicializa tooltip unificado (sidebar + topbar)
  scheduleInitUIComponents(sidebar);
});

// ---------------------------------------------------------
// Topbar: mobile usa FAVICON (abre espaço pro título)
// Desktop usa LOGO
// ---------------------------------------------------------
(function () {
  'use strict';

  function isMobile() {
    return window.matchMedia('(max-width: 980px)').matches;
  }

  function getAttrOrDataset(el, key) {
    const ds = el?.dataset?.[key];
    if (ds) return ds;
    const attr = el?.getAttribute?.(`data-${key}`);
    return attr || '';
  }

  function setTopbarLogoMode() {
    const img = document.getElementById('topbarLogo');
    if (!img) return;

    const logo = getAttrOrDataset(img, 'logo') || img.getAttribute('src') || '';
    const fav  = getAttrOrDataset(img, 'favicon') || '';

    const mobile = isMobile();

    if (mobile && fav) {
      img.src = fav;
      document.documentElement.classList.add('sv-topbar--favicon');
    } else {
      if (logo) img.src = logo;
      document.documentElement.classList.remove('sv-topbar--favicon');
    }
  }

  function scheduleReapply() {
    window.setTimeout(setTopbarLogoMode, 0);
    window.setTimeout(setTopbarLogoMode, 80);
    window.setTimeout(setTopbarLogoMode, 180);
  }

  document.addEventListener('DOMContentLoaded', () => {
    setTopbarLogoMode();
    window.addEventListener('resize', setTopbarLogoMode);
    window.addEventListener('sidebar:toggle', scheduleReapply);
    scheduleReapply();
  });
})();