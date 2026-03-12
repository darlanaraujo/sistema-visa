// app/static/js/data/base_store.js
(function (global) {
  function resolveAppUrl(path) {
    try {
      if (typeof global.appUrl === "function") return global.appUrl(path);
    } catch (_) {}
    return String(path || "");
  }

  const KEYS = {
    USER: "sys_user_v1",
    SYS_PREFS: "tools_sys_prefs_v2",
    SIDEBAR_COLLAPSED: "sv_sidebar_collapsed",
  };

  const EVENTS = {
    EVT: "base:changed",
    DATA_CHANGED: "sys:data:changed",
    USER_CHANGED: "base:user:changed",
    PREFS_CHANGED: "base:prefs:changed",
    UI_CHANGED: "base:ui:changed",
  };

  const state = {
    ready: false,
    user: null,
    prefs: {},
    ui: {
      [KEYS.SIDEBAR_COLLAPSED]: "0",
    },
  };

  let initPromise = null;

  function emit(name, detail) {
    try { window.dispatchEvent(new CustomEvent(name, { detail })); } catch (_) {}
  }

  function emitChange(kind, payload) {
    const detail = { kind, at: Date.now(), payload: payload || null };
    emit(EVENTS.EVT, detail);
    emit(EVENTS.DATA_CHANGED, detail);
    if (kind === "user") emit(EVENTS.USER_CHANGED, detail);
    if (kind === "prefs") emit(EVENTS.PREFS_CHANGED, detail);
    if (kind === "ui") emit(EVENTS.UI_CHANGED, detail);
  }

  async function storeGet(key) {
    try {
      const s = global.SysStore;
      if (!s || typeof s.get !== "function") return null;
      return await s.get(key);
    } catch (_) {
      return null;
    }
  }

  async function storeSet(key, value) {
    try {
      const s = global.SysStore;
      if (!s || typeof s.set !== "function") return false;
      return Boolean(await s.set(key, value));
    } catch (_) {
      return false;
    }
  }

  async function storeRemove(key) {
    try {
      const s = global.SysStore;
      if (!s || typeof s.remove !== "function") return false;
      return Boolean(await s.remove(key));
    } catch (_) {
      return false;
    }
  }

  function deepMerge(base, patch) {
    const out = Object.assign({}, base || {});
    if (!patch || typeof patch !== "object") return out;
    Object.keys(patch).forEach((k) => {
      const b = out[k];
      const p = patch[k];
      if (b && typeof b === "object" && !Array.isArray(b) && p && typeof p === "object" && !Array.isArray(p)) {
        out[k] = deepMerge(b, p);
        return;
      }
      out[k] = p;
    });
    return out;
  }

  function buildInitials(name) {
    const clean = String(name || "").trim();
    if (!clean) return "US";
    const parts = clean.split(/\s+/).filter(Boolean);
    const first = (parts[0] || "").slice(0, 1);
    const second = (parts.length > 1 ? parts[parts.length - 1] : (parts[0] || "")).slice(0, 1);
    const initials = `${first}${second}`.toUpperCase();
    return initials || "US";
  }

  function defaultUser() {
    const name = "Darlan P. Araujo";
    return {
      id: "USR-LOCAL-0001",
      name,
      role: "Administrador",
      email: "darlan@visaremocoes.com.br",
      phone: "(62) 99176-5871",
      username: "Darlan P. Araujo",
      site: "visaremocoes.com.br",
      address: "Rua Panama, Qd7, Lt6, Setor Santo André, Aparecida de Goiânia GO",
      zipCode: "74.984-570",
      theme: {
        mode: "light",
        accentPreset: "blue",
        accent: "",
      },
      avatar: {
        type: "generated",
        url: resolveAppUrl("/app/static/img/users/darlan-avatar.png"),
      },
      prefs: {
        themeMode: "light",
        accent: "blue",
        compactSidebar: false,
      },
      updatedAt: Date.now(),
    };
  }

  function normalizeUser(raw) {
    const base = defaultUser();
    const merged = deepMerge(base, raw && typeof raw === "object" ? raw : {});

    merged.id = String(merged.id || base.id);
    merged.name = String(merged.name || base.name);
    merged.role = String(merged.role || base.role);
    merged.email = String(merged.email || "");
    merged.phone = String(merged.phone || "");
    merged.username = String(merged.username || merged.name || "");
    merged.site = String(merged.site || "");
    merged.address = String(merged.address || "");
    merged.zipCode = String(merged.zipCode || "");

    merged.avatar = merged.avatar && typeof merged.avatar === "object" ? merged.avatar : {};
    const legacyImage = String(merged.avatar.imageDataUrl || "");
    const url = String(merged.avatar.url || legacyImage || "");
    const initials = String(merged.avatar.initials || buildInitials(merged.name));
    const hasImage = Boolean(url);
    merged.avatar.type = hasImage ? "generated" : "initials";
    merged.avatar.url = url;
    merged.avatar.initials = initials;
    merged.avatar.imageDataUrl = url;

    merged.prefs = merged.prefs && typeof merged.prefs === "object" ? merged.prefs : {};
    const mode = String(merged.prefs.themeMode || "light").toLowerCase();
    merged.prefs.themeMode = (mode === "dark" || mode === "auto" || mode === "light") ? mode : "light";
    merged.prefs.accent = String(merged.prefs.accent || "blue");
    merged.prefs.compactSidebar = Boolean(merged.prefs.compactSidebar);

    merged.theme = merged.theme && typeof merged.theme === "object" ? merged.theme : {};
    const themeModeRaw = String(merged.theme.mode || merged.prefs.themeMode || "light").toLowerCase();
    merged.theme.mode = (themeModeRaw === "dark" || themeModeRaw === "auto" || themeModeRaw === "light") ? themeModeRaw : "light";
    merged.theme.accentPreset = String(merged.theme.accentPreset || merged.prefs.accent || "blue");
    merged.theme.accent = String(merged.theme.accent || "");

    merged.prefs.themeMode = merged.theme.mode;
    merged.prefs.accent = merged.theme.accentPreset || merged.prefs.accent;

    const ts = Number(merged.updatedAt || Date.now());
    merged.updatedAt = Number.isFinite(ts) ? ts : Date.now();
    return merged;
  }

  async function init() {
    if (state.ready) return true;
    if (initPromise) return initPromise;

    initPromise = (async () => {
      const [user, prefs, sidebarCollapsed] = await Promise.all([
        storeGet(KEYS.USER),
        storeGet(KEYS.SYS_PREFS),
        storeGet(KEYS.SIDEBAR_COLLAPSED),
      ]);

      state.user = user && typeof user === "object" ? normalizeUser(user) : defaultUser();
      state.prefs = prefs && typeof prefs === "object" ? prefs : {};
      state.ui[KEYS.SIDEBAR_COLLAPSED] = (sidebarCollapsed === null || typeof sidebarCollapsed === "undefined")
        ? "0"
        : String(sidebarCollapsed);
      state.ready = true;
      return true;
    })();

    return initPromise;
  }

  const BaseStore = {
    events: EVENTS,
    init,
    isReady() {
      return state.ready === true;
    },
    ready() {
      return init();
    },

    user: {
      get() {
        return state.user ? normalizeUser(state.user) : defaultUser();
      },
      async set(payload) {
        await init();
        const current = this.get();
        const next = normalizeUser(deepMerge(current, payload && typeof payload === "object" ? payload : {}));
        next.updatedAt = Date.now();
        const ok = await storeSet(KEYS.USER, next);
        if (!ok) return current;
        state.user = next;
        emitChange("user", next);
        return next;
      },
      async clear() {
        await init();
        const ok = await storeRemove(KEYS.USER);
        if (!ok) return false;
        state.user = defaultUser();
        emitChange("user", null);
        return true;
      },
      getThemePrefs() {
        const u = this.get();
        return Object.assign({}, u.prefs || {});
      },
    },

    prefs: {
      get() {
        return state.prefs && typeof state.prefs === "object" ? state.prefs : {};
      },
      async set(nextPrefs) {
        await init();
        const next = nextPrefs && typeof nextPrefs === "object" ? nextPrefs : {};
        const ok = await storeSet(KEYS.SYS_PREFS, next);
        if (!ok) return false;
        state.prefs = next;
        emitChange("prefs", next);
        return true;
      },
      async patch(patch) {
        await init();
        const current = this.get();
        const next = deepMerge(current, patch && typeof patch === "object" ? patch : {});
        return this.set(next);
      },
      async clear() {
        await init();
        const ok = await storeRemove(KEYS.SYS_PREFS);
        if (!ok) return false;
        state.prefs = {};
        emitChange("prefs", {});
        return true;
      },
    },

    ui: {
      getRaw(key, fallback) {
        if (Object.prototype.hasOwnProperty.call(state.ui, key)) {
          return state.ui[key];
        }
        return fallback;
      },
      async setRaw(key, value) {
        await init();
        const ok = await storeSet(key, value);
        if (!ok) return false;
        state.ui[key] = value;
        emitChange("ui", { key, value });
        return true;
      },
      getSidebarCollapsed() {
        const raw = this.getRaw(KEYS.SIDEBAR_COLLAPSED, "0");
        return String(raw) === "1";
      },
      async setSidebarCollapsed(collapsed) {
        return this.setRaw(KEYS.SIDEBAR_COLLAPSED, collapsed ? "1" : "0");
      },
    },
  };

  global.BaseStore = BaseStore;
})(window);
