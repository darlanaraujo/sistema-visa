// app/static/js/ferramentas/data/fer_store.js
// Camada de dados do módulo Ferramentas.
// UI (ferramentas.js) fala com FerStore; FerStore fala com SysStore.

(function () {
  if (!window.SysStore) {
    console.error("[FerStore] SysStore não carregado.");
    return;
  }

  const STORAGE_PREFIX = "tools_ns_";
  const VERSION = "v1";
  const SYS_PREFS_KEY = "tools_sys_prefs_v2";
  const EVT = "fin:change";

  function storageKey(ns) {
    return `${STORAGE_PREFIX}${ns}_${VERSION}`;
  }

  function list(ns) {
    const key = storageKey(ns);
    const v = window.SysStore.get(key);
    return Array.isArray(v) ? v : [];
  }

  function save(ns, items) {
    const key = storageKey(ns);
    window.SysStore.set(key, Array.isArray(items) ? items : []);
    emitToolsChange(ns);
  }

  function emitToolsChange(ns) {
    try {
      const evtName = (window.FinStore && window.FinStore.EVT) ? window.FinStore.EVT : EVT;
      window.dispatchEvent(new CustomEvent(evtName, { detail: { key: `tools:${ns}`, at: Date.now() } }));
    } catch (_) {}
  }

  function getSysPrefs() {
    const obj = window.SysStore.get(SYS_PREFS_KEY);
    return obj && typeof obj === "object" ? obj : null;
  }

  function setSysPrefs(prefs) {
    window.SysStore.set(SYS_PREFS_KEY, prefs || {});
    emitToolsChange("sistema.personalizacao");
  }

  function removeSysPrefs() {
    window.SysStore.remove(SYS_PREFS_KEY);
    emitToolsChange("sistema.personalizacao");
  }

  window.FerStore = {
    EVT,
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
