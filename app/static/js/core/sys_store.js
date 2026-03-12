// app/static/js/core/sys_store.js
(function (global) {
  function resolveAppUrl(path) {
    try {
      if (typeof global.appUrl === "function") return global.appUrl(path);
    } catch (_) {}
    return String(path || "");
  }

  const DRIVER_LOCAL = "local";
  const DRIVER_API = "api";
  let CURRENT_DRIVER = DRIVER_API;

  const cache = new Map();
  const pendingGets = new Map();

  const KEYS = {
    SYS_PREFS: "tools_sys_prefs_v2",
    USER_DATA: "sys_user_v1",
  };

  function buildApiError(message, status) {
    const error = new Error(message || "Falha na persistência");
    error.apiStatus = Number(status || 0);
    return error;
  }

  async function decodeApiResponse(res) {
    const json = await res.json().catch(() => null);
    if (!res.ok || !json || json.success !== true) {
      const message = json && typeof json.error === "string" && json.error
        ? json.error
        : "Falha na persistência";
      throw buildApiError(message, res.status);
    }
    return json.data;
  }

  const ApiDriver = {
    async get(key) {
      const url = resolveAppUrl(`/public_php/api/store_get.php?store_key=${encodeURIComponent(key)}&company_id=1`);
      const res = await fetch(url, {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json" },
      });
      return decodeApiResponse(res);
    },
    async set(key, value) {
      const res = await fetch(resolveAppUrl("/public_php/api/store_set.php"), {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          store_key: key,
          company_id: 1,
          value_json: value,
        }),
      });
      await decodeApiResponse(res);
      return true;
    },
    async remove(key) {
      const res = await fetch(resolveAppUrl("/public_php/api/store_remove.php"), {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          store_key: key,
          company_id: 1,
        }),
      });
      await decodeApiResponse(res);
      return true;
    },
  };

  function emit(event, payload) {
    try {
      window.dispatchEvent(new CustomEvent(event, { detail: payload }));
    } catch (_) {}
  }

  async function fetchAndCache(key) {
    if (pendingGets.has(key)) {
      return pendingGets.get(key);
    }

    const task = ApiDriver.get(key)
      .then((value) => {
        cache.set(key, value);
        pendingGets.delete(key);
        return value;
      })
      .catch((error) => {
        pendingGets.delete(key);
        throw error;
      });

    pendingGets.set(key, task);
    return task;
  }

  const SysStore = {
    DRIVER_LOCAL,
    DRIVER_API,
    KEYS,

    configure(nextDriver) {
      if (nextDriver === DRIVER_API || nextDriver === DRIVER_LOCAL) {
        CURRENT_DRIVER = nextDriver;
      }
      return CURRENT_DRIVER;
    },

    getDriver() {
      return CURRENT_DRIVER;
    },

    async init(keys) {
      const list = Array.isArray(keys) ? keys.filter(Boolean) : [];
      await Promise.all(list.map((key) => this.get(key)));
      return true;
    },

    async get(key) {
      if (!key) return null;
      if (CURRENT_DRIVER !== DRIVER_API) {
        throw new Error("LocalDriver não é permitido nesta etapa");
      }
      if (cache.has(key)) return cache.get(key);
      return fetchAndCache(key);
    },

    getCached(key, fallback) {
      if (cache.has(key)) {
        const value = cache.get(key);
        return typeof value === "undefined" ? fallback : value;
      }
      return fallback;
    },

    async set(key, value) {
      if (CURRENT_DRIVER !== DRIVER_API) {
        throw new Error("LocalDriver não é permitido nesta etapa");
      }
      const ok = await ApiDriver.set(key, value);
      if (!ok) return false;
      cache.set(key, value);
      emit("sys:changed", { key, at: Date.now() });
      return true;
    },

    async remove(key) {
      if (CURRENT_DRIVER !== DRIVER_API) {
        throw new Error("LocalDriver não é permitido nesta etapa");
      }
      const ok = await ApiDriver.remove(key);
      if (!ok) return false;
      cache.delete(key);
      emit("sys:changed", { key, at: Date.now(), removed: true });
      return true;
    },

    async getSysPrefs() {
      return (await this.get(KEYS.SYS_PREFS)) || {};
    },

    async setSysPrefs(payload) {
      const current = await this.getSysPrefs();
      const next = { ...current, ...payload };
      await this.set(KEYS.SYS_PREFS, next);
      emit("sys:prefs:changed", next);
      return next;
    },

    async resetSysPrefs() {
      await this.remove(KEYS.SYS_PREFS);
      emit("sys:prefs:changed", {});
    },

    async getUser() {
      return (await this.get(KEYS.USER_DATA)) || null;
    },

    async setUser(userData) {
      await this.set(KEYS.USER_DATA, userData);
      emit("sys:user:changed", userData);
    },

    async clearUser() {
      await this.remove(KEYS.USER_DATA);
      emit("sys:user:changed", null);
    },
  };

  global.SysStore = SysStore;
})(window);
