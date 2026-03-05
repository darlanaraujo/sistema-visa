// app/static/js/core/sys_store.js
(function (global) {
  const DRIVER_LOCAL = "local";
  const DRIVER_API = "api";

  // 🔧 ALTERAR AQUI no futuro para migrar para BD
  let CURRENT_DRIVER = DRIVER_LOCAL;

  const KEYS = {
    SYS_PREFS: "tools_sys_prefs_v2",
    USER_DATA: "sys_user_v1",
  };

  const LocalDriver = {
    get(key) {
      try {
        const raw = localStorage.getItem(key);
        if (!raw) return null;
        return JSON.parse(raw);
      } catch (_) {
        return null;
      }
    },
    set(key, value) {
      try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
      } catch (_) {
        return false;
      }
    },
    remove(key) {
      try { localStorage.removeItem(key); } catch (_) {}
    },
  };

  const ApiDriver = {
    async get(key) {
      const res = await fetch(`/public_php/api/store_get.php?key=${encodeURIComponent(key)}`);
      const json = await res.json();
      return json.ok ? json.data : null;
    },
    async set(key, value) {
      await fetch(`/public_php/api/store_set.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ key, value }),
      });
      return true;
    },
    async remove(key) {
      await fetch(`/public_php/api/store_remove.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ key }),
      });
    },
  };

  function driver() {
    return CURRENT_DRIVER === DRIVER_API ? ApiDriver : LocalDriver;
  }

  function emit(event, payload) {
    window.dispatchEvent(new CustomEvent(event, { detail: payload }));
  }

  const SysStore = {
    // =========================
    // API GENÉRICA (OBRIGATÓRIA)
    // =========================
    // Atenção: enquanto CURRENT_DRIVER = "local", isso é síncrono.
    // Se mudar para "api", vai virar Promise (aí os módulos terão que ser adaptados).
    get(key) {
      return driver().get(key);
    },
    set(key, value) {
      const ok = driver().set(key, value);
      emit("sys:changed", { key, at: Date.now() });
      return ok;
    },
    remove(key) {
      driver().remove(key);
      emit("sys:changed", { key, at: Date.now(), removed: true });
    },

    // =========================
    // PREFS DO SISTEMA
    // =========================
    getSysPrefs() {
      return this.get(KEYS.SYS_PREFS) || {};
    },
    setSysPrefs(payload) {
      const current = this.getSysPrefs();
      const next = { ...current, ...payload };
      this.set(KEYS.SYS_PREFS, next);
      emit("sys:prefs:changed", next);
      return next;
    },
    resetSysPrefs() {
      this.remove(KEYS.SYS_PREFS);
      emit("sys:prefs:changed", {});
    },

    // =========================
    // USUÁRIO
    // =========================
    getUser() {
      return this.get(KEYS.USER_DATA) || null;
    },
    setUser(userData) {
      this.set(KEYS.USER_DATA, userData);
      emit("sys:user:changed", userData);
    },
    clearUser() {
      this.remove(KEYS.USER_DATA);
      emit("sys:user:changed", null);
    },
  };

  global.SysStore = SysStore;
})(window);