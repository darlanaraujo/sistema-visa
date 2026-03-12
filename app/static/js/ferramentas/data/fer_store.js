// app/static/js/ferramentas/data/fer_store.js
(function () {
  if (!window.SysStore) {
    console.error("[FerStore] SysStore não carregado.");
    return;
  }

  const STORAGE_PREFIX = "tools_ns_";
  const VERSION = "v1";
  const SYS_PREFS_KEY = "tools_sys_prefs_v2";
  const EVT = "fin:change";

  const state = {
    ready: false,
    prefs: null,
    tools: new Map(),
  };
  let initPromise = null;

  function storageKey(ns) {
    return `${STORAGE_PREFIX}${ns}_${VERSION}`;
  }

  function emitToolsChange(ns) {
    try {
      const evtName = (window.FinStore && window.FinStore.EVT) ? window.FinStore.EVT : EVT;
      window.dispatchEvent(new CustomEvent(evtName, { detail: { key: `tools:${ns}`, at: Date.now() } }));
    } catch (_) {}
  }

  async function init() {
    if (state.ready) return true;
    if (initPromise) return initPromise;

    initPromise = (async () => {
      const prefs = await window.SysStore.get(SYS_PREFS_KEY).catch(() => null);
      state.prefs = prefs && typeof prefs === "object" ? prefs : {};
      state.ready = true;
      return true;
    })();

    return initPromise;
  }

  async function list(ns) {
    const key = storageKey(ns);
    if (state.tools.has(key)) {
      return state.tools.get(key);
    }
    const value = await window.SysStore.get(key).catch(() => []);
    const out = Array.isArray(value) ? value : [];
    state.tools.set(key, out);
    return out;
  }

  async function save(ns, items) {
    const key = storageKey(ns);
    const next = Array.isArray(items) ? items : [];
    const ok = await window.SysStore.set(key, next);
    if (!ok) return false;
    state.tools.set(key, next);
    emitToolsChange(ns);
    return true;
  }

  async function getSysPrefs() {
    await init();
    return state.prefs && typeof state.prefs === "object" ? state.prefs : {};
  }

  async function setSysPrefs(prefs) {
    const next = prefs && typeof prefs === "object" ? prefs : {};
    const ok = await window.SysStore.set(SYS_PREFS_KEY, next);
    if (!ok) return false;
    state.prefs = next;
    emitToolsChange("sistema.personalizacao");
    return true;
  }

  async function removeSysPrefs() {
    const ok = await window.SysStore.remove(SYS_PREFS_KEY);
    if (!ok) return false;
    state.prefs = {};
    emitToolsChange("sistema.personalizacao");
    return true;
  }

  window.FerStore = {
    EVT,
    init,
    isReady() {
      return state.ready === true;
    },
    ready() {
      return init();
    },
    storageKey,
    tools: {
      list,
      save,
      emitChange: emitToolsChange,
    },
    prefs: {
      key: SYS_PREFS_KEY,
      get: getSysPrefs,
      set: setSysPrefs,
      remove: removeSysPrefs,
    },
  };
})();
