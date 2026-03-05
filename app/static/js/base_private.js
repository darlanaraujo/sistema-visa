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

function withBreakpointSwitching(flagOn) {
  const layout = document.getElementById('privateLayout');
  if (!layout) return;
  layout.classList.toggle('is-breakpoint-switching', Boolean(flagOn));
}

function storageGet(key, fallback = null) {
  try {
    if (window.BaseStore?.ui && typeof window.BaseStore.ui.getRaw === 'function') {
      const v = window.BaseStore.ui.getRaw(key, fallback);
      return v === null || typeof v === 'undefined' ? fallback : String(v);
    }
    return fallback;
  } catch (_) {
    return fallback;
  }
}

function storageSet(key, value) {
  try {
    if (window.BaseStore?.ui && typeof window.BaseStore.ui.setRaw === 'function') {
      window.BaseStore.ui.setRaw(key, String(value));
    }
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

  const userCardSidebar = document.getElementById('userCardSidebar');
  const topbarLogo = document.getElementById('topbarLogo');
  const userModal = document.getElementById('userModal');
  const userModalOverlay = document.getElementById('userModalOverlay');
  const userModalClose = document.getElementById('userModalClose');
  const userModalCloseFoot = document.getElementById('userModalCloseFoot');
  const userModalAvatar = document.getElementById('userModalAvatar');
  const userModalName = document.getElementById('userModalName');
  const userModalRole = document.getElementById('userModalRole');
  const userModalFieldName = document.getElementById('userModalFieldName');
  const userModalFieldRole = document.getElementById('userModalFieldRole');
  const userModalFieldEmail = document.getElementById('userModalFieldEmail');
  const userModalFieldPhone = document.getElementById('userModalFieldPhone');
  const userModalFieldTheme = document.getElementById('userModalFieldTheme');
  const userModalFieldAccent = document.getElementById('userModalFieldAccent');
  const userModalFieldUpdatedAt = document.getElementById('userModalFieldUpdatedAt');

  const btnLogout = document.getElementById('btnLogout');

  const STORAGE_KEY = 'sv_sidebar_collapsed';
  let _wasMobile = isMobile();

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
    // Topbar: no mobile usa favicon para liberar espaço do título/ações.
    if (topbarLogo) {
      const logo = topbarLogo.dataset?.logo || topbarLogo.getAttribute('data-logo') || topbarLogo.getAttribute('src');
      const fav  = topbarLogo.dataset?.favicon || topbarLogo.getAttribute('data-favicon') || '';
      if (isMobile() && fav) {
        topbarLogo.src = fav;
        document.documentElement.classList.add('sv-topbar--favicon');
      } else {
        if (logo) topbarLogo.src = logo;
        document.documentElement.classList.remove('sv-topbar--favicon');
      }
    }
  }

  function getUserForUi() {
    try {
      const u = window.BaseStore?.user?.get?.();
      if (u && typeof u === 'object') return u;
    } catch (_) {}
    return { name: 'Usuario', role: 'Administrador', avatar: { type: 'initials', initials: 'US', url: '' } };
  }

  // Resolve avatar por prioridade (contrato único da UI base):
  // 1) user.avatar.url (ou alias avatarUrl/imageDataUrl)
  // 2) arquivo por nome em /img/users
  // 3) iniciais
  // Este helper é exposto em window.BaseUserAvatar para reuso em outras telas.
  const avatarUrlCache = new Map();
  function slugifyName(name) {
    return String(name || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function findAvatarUrlByName(userName) {
    const raw = String(userName || '').trim();
    const slug = slugifyName(raw);
    const cacheKey = slug || raw.toLowerCase();
    if (!cacheKey) return Promise.resolve('');
    if (avatarUrlCache.has(cacheKey)) return Promise.resolve(String(avatarUrlCache.get(cacheKey) || ''));

    // Suporta múltiplos padrões de nome de arquivo no diretório /img/users
    // Ex.: "darlan-p-araujo.png", "darlan_p_araujo.png", "Darlan%20P.%20Araujo.png", "darlan-avatar.png"
    const rawEncoded = encodeURIComponent(raw);
    const rawUnderscore = slug.replace(/-/g, '_');
    const firstName = slug.split('-')[0] || '';
    const stems = [slug, rawUnderscore, rawEncoded, `${slug}-avatar`, `${firstName}-avatar`].filter(Boolean);
    const exts = ['png', 'jpg', 'jpeg', 'webp'];
    const candidates = [];
    stems.forEach((stem) => {
      exts.forEach((ext) => {
        candidates.push(`/sistema-visa/app/static/img/users/${stem}.${ext}`);
        candidates.push(`/sistema-visa/app/static/img/user/${stem}.${ext}`); // compat legado
      });
    });
    const uniqueCandidates = Array.from(new Set(candidates));

    return new Promise((resolve) => {
      let idx = 0;
      function tryNext() {
        if (idx >= uniqueCandidates.length) {
          avatarUrlCache.set(cacheKey, '');
          resolve('');
          return;
        }
        const url = uniqueCandidates[idx++];
        const img = new Image();
        img.onload = () => { avatarUrlCache.set(cacheKey, url); resolve(url); };
        img.onerror = () => tryNext();
        img.src = url;
      }
      tryNext();
    });
  }

  function userInitials(user) {
    const fromAvatar = String(user?.avatar?.initials || '').trim();
    if (fromAvatar) return fromAvatar.slice(0, 2).toUpperCase();
    const name = String(user?.name || '').trim();
    if (!name) return 'US';
    const parts = name.split(/\s+/).filter(Boolean);
    const a = (parts[0] || '').slice(0, 1);
    const b = (parts.length > 1 ? parts[parts.length - 1] : (parts[0] || '')).slice(0, 1);
    const out = `${a}${b}`.toUpperCase();
    return out || 'US';
  }

  function setAvatarContent(targetEl, user, resolvedUrl) {
    if (!targetEl) return;
    const url = String(resolvedUrl || '').trim();
    if (url) {
      targetEl.innerHTML = `<img src="${url}" alt="">`;
      return;
    }
    targetEl.textContent = userInitials(user);
  }

  function resolveUserAvatarContract(user) {
    const explicit = String(
      user?.avatar?.url ||
      user?.avatarUrl ||
      user?.avatar?.imageDataUrl ||
      ''
    ).trim();
    const initials = userInitials(user);

    if (explicit) {
      return Promise.resolve({ source: 'explicit', url: explicit, initials });
    }

    return findAvatarUrlByName(String(user?.name || '')).then((url) => {
      if (url) return { source: 'local-file', url, initials };
      return { source: 'initials', url: '', initials };
    });
  }

  window.BaseUserAvatar = {
    resolve: resolveUserAvatarContract,
    initials: userInitials,
  };

  let userCardRenderSeq = 0;
  function renderUserCards() {
    const seq = ++userCardRenderSeq;
    const user = getUserForUi();
    const name = String(user.name || 'Usuario');
    const role = String(user.role || 'Perfil');

    if (userCardSidebar) {
      const av = userCardSidebar.querySelector('.sidebar__user-avatar');
      const nm = userCardSidebar.querySelector('.sidebar__user-name');
      const rl = userCardSidebar.querySelector('.sidebar__user-role');
      if (av) {
        setAvatarContent(av, user, '');
        resolveUserAvatarContract(user).then((avatar) => {
          // Evita race condition: só aplica se este render ainda for o último.
          if (seq !== userCardRenderSeq) return;
          setAvatarContent(av, user, avatar?.url || '');
        });
      }
      if (nm) nm.textContent = name;
      if (rl) rl.textContent = role;
      userCardSidebar.setAttribute('data-tip', name);
      userCardSidebar.setAttribute('title', name);
    }
  }

  function displayOrDash(value) {
    const v = String(value ?? '').trim();
    return v || '—';
  }

  function mapThemeModeLabel(modeRaw) {
    const mode = String(modeRaw || '').toLowerCase().trim();
    if (mode === 'dark') return 'Escuro';
    if (mode === 'auto') return 'Automático';
    return 'Claro';
  }

  function mapAccentLabel(accentRaw) {
    const accent = String(accentRaw || '').toLowerCase().trim();
    const map = {
      visa: 'Visa',
      blue: 'Azul',
      green: 'Verde',
      purple: 'Roxo',
      orange: 'Laranja',
      slate: 'Slate',
      red: 'Vermelho',
      amber: 'Âmbar',
      teal: 'Teal',
      custom: 'Personalizado',
    };
    if (!accent) return 'Padrão';
    return map[accent] || accentRaw;
  }

  function formatUpdatedAt(value) {
    const n = Number(value);
    if (!Number.isFinite(n) || n <= 0) return '—';
    try {
      return new Date(n).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch (_) {
      return '—';
    }
  }

  function renderUserModalData() {
    if (!userModal) return;

    const user = getUserForUi();
    const name = String(user?.name || 'Usuario');
    const role = String(user?.role || 'Perfil');

    if (userModalName) userModalName.textContent = name;
    if (userModalRole) userModalRole.textContent = role;
    if (userModalFieldName) userModalFieldName.textContent = displayOrDash(name);
    if (userModalFieldRole) userModalFieldRole.textContent = displayOrDash(role);
    if (userModalFieldEmail) userModalFieldEmail.textContent = displayOrDash(user?.email || '');
    if (userModalFieldPhone) userModalFieldPhone.textContent = displayOrDash(user?.phone || '');

    const userThemeMode = user?.theme?.mode || user?.prefs?.themeMode || 'light';
    const userAccent = user?.theme?.accentPreset || user?.prefs?.accent || '';
    if (userModalFieldTheme) userModalFieldTheme.textContent = mapThemeModeLabel(userThemeMode);
    if (userModalFieldAccent) userModalFieldAccent.textContent = mapAccentLabel(userAccent);
    if (userModalFieldUpdatedAt) userModalFieldUpdatedAt.textContent = formatUpdatedAt(user?.updatedAt);

    if (userModalAvatar) {
      setAvatarContent(userModalAvatar, user, '');
      resolveUserAvatarContract(user).then((avatar) => {
        setAvatarContent(userModalAvatar, user, avatar?.url || '');
      });
    }
  }

  /* ---------------------------
     USER MODAL (Etapa 6 - Parte 2.3)
     - Abre pelo UserCard
     - Fecha por botão, overlay e ESC
     - Acessibilidade: foco inicial no close e retorno ao gatilho
  --------------------------- */
  const USER_MODAL_CLOSE_MS = 340;
  let userModalLastTrigger = null;

  function isUserModalOpen() {
    return Boolean(userModal && userModal.classList.contains('is-open'));
  }

  function openUserModal(triggerEl) {
    if (!userModal) return;
    userModalLastTrigger = triggerEl || document.activeElement || null;
    renderUserModalData();
    userModal.classList.remove('is-closing');
    userModal.classList.add('is-open');
    userModal.setAttribute('aria-hidden', 'false');

    // Duplo RAF evita "pular" animação em primeiro frame.
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        try { userModalClose?.focus(); } catch (_) {}
      });
    });
  }

  function closeUserModal() {
    if (!userModal || !isUserModalOpen()) return;
    userModal.classList.add('is-closing');

    window.setTimeout(() => {
      userModal.classList.remove('is-open', 'is-closing');
      userModal.setAttribute('aria-hidden', 'true');
      try { userModalLastTrigger?.focus?.(); } catch (_) {}
      userModalLastTrigger = null;
    }, USER_MODAL_CLOSE_MS);
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

  /* ---------------------------
     SIDEBAR AUTO-COLLAPSE (desktop)
     - Reinicia o contador quando há interação.
     - Pausa enquanto há hover/foco dentro do sidebar.
  --------------------------- */
  const SIDEBAR_AUTO_COLLAPSE_MS = 30 * 1000;
  let sidebarAutoCollapseTimer = null;
  let sidebarInteractionLock = false;

  function clearSidebarAutoCollapse() {
    if (sidebarAutoCollapseTimer) {
      window.clearTimeout(sidebarAutoCollapseTimer);
      sidebarAutoCollapseTimer = null;
    }
  }

  function collapseSidebarByTimer() {
    if (!sidebar || isMobile()) return;
    if (sidebarInteractionLock) return;
    if (sidebar.classList.contains('is-collapsed')) return;

    sidebar.classList.add('is-collapsed');
    setLayoutSidebarWidthVar();
    setEdgeIconByState();
    setLogosByState();
    try { window.BaseStore?.ui?.setSidebarCollapsed?.(true); } catch (_) {}
    try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
  }

  function restartSidebarAutoCollapse() {
    clearSidebarAutoCollapse();
    if (!sidebar || isMobile()) return;
    if (sidebarInteractionLock) return;
    if (sidebar.classList.contains('is-collapsed')) return;

    sidebarAutoCollapseTimer = window.setTimeout(() => {
      collapseSidebarByTimer();
    }, SIDEBAR_AUTO_COLLAPSE_MS);
  }

  function setSidebarInteractionLock(active) {
    sidebarInteractionLock = Boolean(active);
    if (sidebarInteractionLock) {
      clearSidebarAutoCollapse();
      return;
    }
    restartSidebarAutoCollapse();
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
    try {
      if (window.BaseStore?.prefs && typeof window.BaseStore.prefs.get === 'function') {
        const obj = window.BaseStore.prefs.get();
        return obj && typeof obj === 'object' ? obj : null;
      }
      return null;
    }
    catch (_) { return null; }
  }

  function setPrefs(nextPrefs) {
    try {
      if (window.BaseStore?.prefs && typeof window.BaseStore.prefs.set === 'function') {
        return Boolean(window.BaseStore.prefs.set(nextPrefs));
      }
      return false;
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
      // Salva preferência atual e reinicia contador quando expandir manualmente.
      try { window.BaseStore?.ui?.setSidebarCollapsed?.(sidebar.classList.contains('is-collapsed')); } catch (_) {}
      if (sidebar.classList.contains('is-collapsed')) clearSidebarAutoCollapse();
      else restartSidebarAutoCollapse();
    });
  }

  if (overlay) overlay.addEventListener('click', () => closeMobileSidebar());

  if (sidebar) {
    sidebar.addEventListener('click', (e) => {
      const link = e.target.closest('a.sidebar__item');
      if (link && isMobile()) closeMobileSidebar();
    });

    // Interação no sidebar pausa/reinicia auto-colapso.
    sidebar.addEventListener('mouseenter', () => setSidebarInteractionLock(true));
    sidebar.addEventListener('mouseleave', () => setSidebarInteractionLock(false));

    sidebar.addEventListener('focusin', () => setSidebarInteractionLock(true));
    sidebar.addEventListener('focusout', () => {
      window.setTimeout(() => {
        const hasFocusInside = Boolean(document.activeElement && sidebar.contains(document.activeElement));
        if (!hasFocusInside) setSidebarInteractionLock(false);
      }, 0);
    });

    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'wheel', 'scroll'].forEach((evt) => {
      sidebar.addEventListener(evt, () => restartSidebarAutoCollapse(), { passive: true });
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

  if (userCardSidebar) {
    userCardSidebar.addEventListener('click', () => {
      openUserModal(userCardSidebar);
    });
  }

  if (userModalOverlay) userModalOverlay.addEventListener('click', () => closeUserModal());
  if (userModalClose) userModalClose.addEventListener('click', () => closeUserModal());
  if (userModalCloseFoot) userModalCloseFoot.addEventListener('click', () => closeUserModal());

  window.addEventListener('resize', () => {
    const mobileNow = isMobile();
    const switched = mobileNow !== _wasMobile;
    _wasMobile = mobileNow;

    // Evita flash visual no momento exato da troca de breakpoint.
    if (switched) {
      withBreakpointSwitching(true);
      window.setTimeout(() => withBreakpointSwitching(false), 220);
    }

    if (isMobile()) {
      clearSidebarAutoCollapse();
      if (sidebar) sidebar.classList.remove('is-collapsed');
      layout?.classList?.remove('is-sidebar-open');
      setLayoutSidebarWidthVar();
      setEdgeIconByState();
      setLogosByState();
      try { window.dispatchEvent(new CustomEvent('sidebar:toggle')); } catch (_) {}
      return;
    }

    closeMobileSidebar();
    applyDesktopSidebarStateFromStorage();
    renderUserCards();
    restartSidebarAutoCollapse();
  });

  window.addEventListener('base:user:changed', () => {
    renderUserCards();
    renderUserModalData();
  });

  window.addEventListener('base:prefs:changed', () => {
    // Re-render para refletir tema/acento na ficha quando houver atualização de prefs.
    renderUserModalData();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (!isUserModalOpen()) return;
    e.preventDefault();
    closeUserModal();
  });

  /* ---------------------------
     INIT
  --------------------------- */
  setActiveNav();
  applyDesktopSidebarStateFromStorage();
  setLayoutSidebarWidthVar();
  setEdgeIconByState();
  setLogosByState();
  renderUserCards();
  renderUserModalData();
  restartSidebarAutoCollapse();

  document.documentElement.classList.remove('sv-sidebar-collapsed');

  // Inicializa tooltip unificado (sidebar + topbar)
  scheduleInitUIComponents(sidebar);
});
