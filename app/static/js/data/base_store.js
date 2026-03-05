// app/static/js/data/base_store.js
// ---------------------------------------------------------
// BaseStore (escopo global do ambiente privado)
// Responsabilidade:
// - Centralizar acesso de dados globais (user/prefs/ui)
// - Falar com SysStore (camada de persistencia)
// - Expor API estavel para a UI (base_private.js)
// ---------------------------------------------------------
(function (global) {
  const KEYS = {
    USER: 'sys_user_v1',
    SYS_PREFS: 'tools_sys_prefs_v2',
    SIDEBAR_COLLAPSED: 'sv_sidebar_collapsed',
  };

  const EVENTS = {
    EVT: 'base:changed',
    DATA_CHANGED: 'sys:data:changed',
    USER_CHANGED: 'base:user:changed',
    PREFS_CHANGED: 'base:prefs:changed',
    UI_CHANGED: 'base:ui:changed',
  };

  function emit(name, detail) {
    try { window.dispatchEvent(new CustomEvent(name, { detail })); } catch (_) {}
  }

  function emitChange(kind, payload) {
    const detail = { kind, at: Date.now(), payload: payload || null };
    emit(EVENTS.EVT, detail);
    emit(EVENTS.DATA_CHANGED, detail);
    if (kind === 'user') emit(EVENTS.USER_CHANGED, detail);
    if (kind === 'prefs') emit(EVENTS.PREFS_CHANGED, detail);
    if (kind === 'ui') emit(EVENTS.UI_CHANGED, detail);
  }

  function storeGet(key) {
    try {
      const s = global.SysStore;
      if (!s || typeof s.get !== 'function') return null;
      return s.get(key);
    } catch (_) {
      return null;
    }
  }

  function storeSet(key, value) {
    try {
      const s = global.SysStore;
      if (!s || typeof s.set !== 'function') return false;
      return Boolean(s.set(key, value));
    } catch (_) {
      return false;
    }
  }

  function storeRemove(key) {
    try {
      const s = global.SysStore;
      if (!s || typeof s.remove !== 'function') return;
      s.remove(key);
    } catch (_) {}
  }

  function deepMerge(base, patch) {
    const out = Object.assign({}, base || {});
    if (!patch || typeof patch !== 'object') return out;
    Object.keys(patch).forEach((k) => {
      const b = out[k];
      const p = patch[k];
      if (b && typeof b === 'object' && !Array.isArray(b) && p && typeof p === 'object' && !Array.isArray(p)) {
        out[k] = deepMerge(b, p);
        return;
      }
      out[k] = p;
    });
    return out;
  }

  function buildInitials(name) {
    const clean = String(name || '').trim();
    if (!clean) return 'US';
    const parts = clean.split(/\s+/).filter(Boolean);
    const first = (parts[0] || '').slice(0, 1);
    const second = (parts.length > 1 ? parts[parts.length - 1] : (parts[0] || '')).slice(0, 1);
    const initials = `${first}${second}`.toUpperCase();
    return initials || 'US';
  }

  function defaultUser() {
    const name = 'Darlan P. Araujo';
    return {
      id: 'USR-LOCAL-0001',
      name,
      role: 'Administrador',
      email: 'darlan@visaremocoes.com.br',
      phone: '(62) 99176-5871',
      username: 'Darlan P. Araujo',
      site: 'visaremocoes.com.br',
      address: 'Rua Panama, Qd7, Lt6, Setor Santo André, Aparecida de Goiânia GO',
      zipCode: '74.984-570',
      theme: {
        mode: 'light',
        accentPreset: 'blue',
        accent: '',
      },
      avatar: {
        // Seed local (temporário da etapa 6): usa arquivo local do avatar.
        // No futuro, o cadastro real poderá sobrescrever esse campo via API/BD.
        type: 'generated',
        url: '/sistema-visa/app/static/img/users/darlan-avatar.png',
      },
      prefs: {
        themeMode: 'light',
        accent: 'blue',
        compactSidebar: false,
      },
      updatedAt: Date.now(),
    };
  }

  function normalizeUser(raw) {
    const base = defaultUser();
    const merged = deepMerge(base, raw && typeof raw === 'object' ? raw : {});

    merged.id = String(merged.id || base.id);
    merged.name = String(merged.name || base.name);
    merged.role = String(merged.role || base.role);
    merged.email = String(merged.email || '');
    merged.phone = String(merged.phone || '');
    merged.username = String(merged.username || merged.name || '');
    merged.site = String(merged.site || '');
    merged.address = String(merged.address || '');
    merged.zipCode = String(merged.zipCode || '');

    merged.avatar = merged.avatar && typeof merged.avatar === 'object' ? merged.avatar : {};
    const legacyImage = String(merged.avatar.imageDataUrl || '');
    const url = String(merged.avatar.url || legacyImage || '');
    const initials = String(merged.avatar.initials || buildInitials(merged.name));
    const hasImage = Boolean(url);
    merged.avatar.type = hasImage ? 'generated' : 'initials';
    merged.avatar.url = url;
    merged.avatar.initials = initials;
    merged.avatar.imageDataUrl = url;

    merged.prefs = merged.prefs && typeof merged.prefs === 'object' ? merged.prefs : {};
    const mode = String(merged.prefs.themeMode || 'light').toLowerCase();
    merged.prefs.themeMode = (mode === 'dark' || mode === 'auto' || mode === 'light') ? mode : 'light';
    merged.prefs.accent = String(merged.prefs.accent || 'blue');
    merged.prefs.compactSidebar = Boolean(merged.prefs.compactSidebar);

    merged.theme = merged.theme && typeof merged.theme === 'object' ? merged.theme : {};
    const themeModeRaw = String(merged.theme.mode || merged.prefs.themeMode || 'light').toLowerCase();
    merged.theme.mode = (themeModeRaw === 'dark' || themeModeRaw === 'auto' || themeModeRaw === 'light') ? themeModeRaw : 'light';
    merged.theme.accentPreset = String(merged.theme.accentPreset || merged.prefs.accent || 'blue');
    merged.theme.accent = String(merged.theme.accent || '');

    // Compatibilidade: prefs espelha o contrato novo
    merged.prefs.themeMode = merged.theme.mode;
    merged.prefs.accent = merged.theme.accentPreset || merged.prefs.accent;

    const ts = Number(merged.updatedAt || Date.now());
    merged.updatedAt = Number.isFinite(ts) ? ts : Date.now();
    return merged;
  }

  const BaseStore = {
    events: EVENTS,

    user: {
      get() {
        const raw = storeGet(KEYS.USER);
        if (!raw || typeof raw !== 'object') return defaultUser();
        return normalizeUser(raw);
      },
      set(payload) {
        const current = this.get();
        const next = normalizeUser(deepMerge(current, payload && typeof payload === 'object' ? payload : {}));
        next.updatedAt = Date.now();
        storeSet(KEYS.USER, next);
        emitChange('user', next);
        return next;
      },
      clear() {
        storeRemove(KEYS.USER);
        emitChange('user', null);
      },
      getThemePrefs() {
        const u = this.get();
        return Object.assign({}, u.prefs || {});
      },
    },

    prefs: {
      get() {
        const raw = storeGet(KEYS.SYS_PREFS);
        return raw && typeof raw === 'object' ? raw : {};
      },
      set(nextPrefs) {
        const next = nextPrefs && typeof nextPrefs === 'object' ? nextPrefs : {};
        const ok = storeSet(KEYS.SYS_PREFS, next);
        if (ok) emitChange('prefs', next);
        return ok;
      },
      patch(patch) {
        const current = this.get();
        const next = deepMerge(current, patch && typeof patch === 'object' ? patch : {});
        return this.set(next);
      },
      clear() {
        storeRemove(KEYS.SYS_PREFS);
        emitChange('prefs', {});
      },
    },

    ui: {
      getRaw(key, fallback) {
        const v = storeGet(key);
        if (v === null || typeof v === 'undefined') return fallback;
        return v;
      },
      setRaw(key, value) {
        const ok = storeSet(key, value);
        if (ok) emitChange('ui', { key, value });
        return ok;
      },
      getSidebarCollapsed() {
        const raw = this.getRaw(KEYS.SIDEBAR_COLLAPSED, '0');
        return String(raw) === '1';
      },
      setSidebarCollapsed(collapsed) {
        return this.setRaw(KEYS.SIDEBAR_COLLAPSED, collapsed ? '1' : '0');
      },
    },
  };

  global.BaseStore = BaseStore;
})(window);
